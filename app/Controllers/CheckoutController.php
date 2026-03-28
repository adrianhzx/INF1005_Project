<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CheckoutController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        global $pdo, $auth;
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            $this->flash('Your cart is empty. Add items before checking out.', 'warning');
            return $this->redirect($response, '/products');
        }
        $stmt = $pdo->prepare('SELECT address FROM user_profiles WHERE user_id = :id');
        $stmt->execute([':id' => $auth->getUserId()]);
        $user_data = $stmt->fetch();
        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $cart));
        $shipping = $subtotal >= 200 ? 0 : 15;
        $page_title = 'Checkout';
        $current_page = 'checkout';
        $errors = [];
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'shop/checkout', compact('cart', 'subtotal', 'shipping', 'user_data', 'page_title', 'current_page', 'csrf_token', 'errors'));
    }

    public function coupon(Request $request, Response $response): Response
    {
        global $pdo;
        $data = $request->getParsedBody();
        $code = strtoupper(trim($data['coupon_code'] ?? ''));
        if (empty($code)) {
            return $this->json($response, ['valid' => false,'message' => 'Please enter a coupon code.']);
        }
        $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = :code AND active = 1');
        $stmt->execute([':code' => $code]);
        $coupon = $stmt->fetch();
        $cart = $_SESSION['cart'] ?? [];
        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $cart));
        if ($coupon) {
            $discount = ($subtotal * $coupon['discount_percent']) / 100;
            return $this->json($response, ['valid' => true,'message' => $coupon['discount_percent'].'% discount applied! You save $'.number_format($discount, 2),'discount' => $discount,'percent' => $coupon['discount_percent']]);
        }
        return $this->json($response, ['valid' => false,'message' => 'Invalid or expired coupon code.']);
    }

    public function place(Request $request, Response $response): Response
    {
        global $pdo, $auth;
        $data = $request->getParsedBody();
        $cart = $_SESSION['cart'] ?? [];
        $errors = [];
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission.';
        }
        $postal_code = trim($data['postal_code'] ?? '');
        $unit_number = trim($data['unit_number'] ?? '');
        $street_address = trim($data['street_address'] ?? '');
        $payment_method = trim($data['payment_method'] ?? '');
        $coupon_code = strtoupper(trim($data['coupon_code'] ?? ''));
        if (!preg_match('/^\d{6}$/', $postal_code)) {
            $errors[] = 'Please enter a valid 6-digit Singapore postal code.';
        }
        if (empty($unit_number)) {
            $errors[] = 'Unit number is required.';
        }
        if (empty($street_address)) {
            $errors[] = 'Street address is required.';
        }
        if (empty($payment_method)) {
            $errors[] = 'Please select a payment method.';
        }
        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $cart));
        $shipping = $subtotal >= 200 ? 0 : 15;
        $discount = 0;
        if (!empty($coupon_code)) {
            $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = :code AND active = 1');
            $stmt->execute([':code' => $coupon_code]);
            $coupon = $stmt->fetch();
            if ($coupon) {
                $discount = ($subtotal * $coupon['discount_percent']) / 100;
            } else {
                $errors[] = 'Invalid or expired coupon code.';
            }
        }
        if (empty($errors)) {
            $total = ($subtotal + $shipping) - $discount;
            $shipping_address = $unit_number.', '.$street_address.', Singapore '.$postal_code;
            foreach ($cart as $item) {
                $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id');
                $stmt->execute([':id' => $item['product_id']]);
                $prod = $stmt->fetch();
                if (!$prod || $prod['stock'] < $item['quantity']) {
                    $errors[] = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8').' does not have enough stock.';
                }
            }
            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('INSERT INTO orders (user_id,total,shipping_address,payment_method,coupon_code,discount) VALUES (:uid,:total,:addr,:pay,:coupon,:disc)');
                    $stmt->execute([':uid' => $auth->getUserId(),':total' => $total,':addr' => $shipping_address,':pay' => $payment_method,':coupon' => !empty($coupon_code) ? $coupon_code : null,':disc' => $discount]);
                    $order_id = $pdo->lastInsertId();
                    foreach ($cart as $item) {
                        $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (:oid,:pid,:qty,:price)')
                            ->execute([':oid' => $order_id,':pid' => $item['product_id'],':qty' => $item['quantity'],':price' => $item['price']]);
                        $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id')
                            ->execute([':qty' => $item['quantity'],':id' => $item['product_id']]);
                    }
                    $pdo->commit();
                    ekea_log('Order placed', 'INFO', ['order_id' => $order_id,'total' => $total]);
                    $_SESSION['cart'] = [];
                    $_SESSION['last_order_id'] = $order_id;
                    return $this->redirect($response, '/summary');
                } catch (\Exception $e) {
                    $pdo->rollBack();
                    ekea_log_exception($e, 'Checkout failed');
                    $errors[] = 'An error occurred. Please try again.';
                }
            }
        }
        $stmt = $pdo->prepare('SELECT address FROM user_profiles WHERE user_id = :id');
        $stmt->execute([':id' => $auth->getUserId()]);
        $user_data = $stmt->fetch();
        $page_title = 'Checkout';
        $current_page = 'checkout';
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'shop/checkout', compact('cart', 'subtotal', 'shipping', 'user_data', 'page_title', 'current_page', 'csrf_token', 'errors'));
    }

    public function summary(Request $request, Response $response): Response
    {
        global $pdo, $auth;
        $order_id = $_SESSION['last_order_id'] ?? null;
        if (!$order_id) {
            return $this->redirect($response, '/');
        }

        $stmt = $pdo->prepare('SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = :id AND o.user_id = :uid');
        $stmt->execute([':id' => $order_id, ':uid' => $auth->getUserId()]);
        $order = $stmt->fetch();
        if (!$order) {
            return $this->redirect($response, '/history');
        }

        $stmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :oid');
        $stmt->execute([':oid' => $order_id]);
        $items = $stmt->fetchAll();

        $page_title = 'Order Confirmed';
        $current_page = '';
        return $this->render($response, 'shop/summary', compact('order', 'items', 'page_title', 'current_page'));
    }
}
