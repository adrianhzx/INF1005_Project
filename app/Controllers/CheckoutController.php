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

        $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
        $shipping = $subtotal >= 200 ? 0 : 15;

        $page_title = 'Checkout';
        $current_page = 'checkout';
        $errors = [];
        $csrf_token = generate_csrf_token();

        return $this->render($response, 'shop/checkout', compact(
            'cart',
            'subtotal',
            'shipping',
            'user_data',
            'page_title',
            'current_page',
            'csrf_token',
            'errors'
        ));
    }

    public function coupon(Request $request, Response $response): Response
    {
        global $pdo;
        $data = $request->getParsedBody();

        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            return $this->json($response, ['valid' => false, 'message' => 'Invalid form submission.'], 400);
        }

        $code = strtoupper(trim($data['coupon_code'] ?? ''));

        if (empty($code)) {
            return $this->json($response, ['valid' => false, 'message' => 'Please enter a coupon code.']);
        }

        $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = :code AND active = 1');
        $stmt->execute([':code' => $code]);
        $coupon = $stmt->fetch();

        $cart = $_SESSION['cart'] ?? [];
        $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));

        if ($coupon) {
            $discount = ($subtotal * $coupon['discount_percent']) / 100;
            return $this->json($response, [
                'valid' => true,
                'message' => $coupon['discount_percent'] . '% discount applied! You save $' . number_format($discount, 2),
                'discount' => $discount,
                'percent' => $coupon['discount_percent']
            ]);
        }

        return $this->json($response, ['valid' => false, 'message' => 'Invalid or expired coupon code.']);
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

        // Input Validation
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

        // Calculation Logic
        $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
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
            // Ensure total doesn't accidentally drop below 0
            $total = max(0, ($subtotal + $shipping) - $discount);
            $shipping_address = $unit_number . ', ' . $street_address . ', Singapore ' . $postal_code;

            // Final Stock Verification before charging
            foreach ($cart as $item) {
                $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id');
                $stmt->execute([':id' => $item['product_id']]);
                $prod = $stmt->fetch();

                if (!$prod || $prod['stock'] < $item['quantity']) {
                    $errors[] = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . ' does not have enough stock.';
                }
            }

            // Process the Transaction
            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    // 1. Create Order
                    $stmt = $pdo->prepare('INSERT INTO orders (user_id, total, shipping_address, payment_method, coupon_code, discount) VALUES (:uid, :total, :addr, :pay, :coupon, :disc)');
                    $stmt->execute([
                        ':uid' => $auth->getUserId(),
                        ':total' => $total,
                        ':addr' => $shipping_address,
                        ':pay' => $payment_method,
                        ':coupon' => !empty($coupon_code) ? $coupon_code : null,
                        ':disc' => $discount
                    ]);
                    $order_id = $pdo->lastInsertId();

                    // 2. Charge via Stripe if credit card was selected
                    if ($payment_method === 'credit_card') {
                        require_once __DIR__ . '/../../includes/stripe_helper.php';
                        $ini = parse_ini_file(__DIR__ . '/../../includes/db_config.ini', true);
                        $stripe_token = trim($data['stripeToken'] ?? '');
                        if (empty($stripe_token)) {
                            throw new \Exception('Payment token missing. Please try again.');
                        }
                        $charge = stripe_post('charges', [
                            'amount' => (int) round($total * 100),
                            'currency' => 'sgd',
                            'source' => $stripe_token,
                            'description' => 'Ekea Order #' . $order_id,
                            'receipt_email' => $auth->getEmail(),
                            'metadata' => [
                                'invoice' => 'INV-' . $order_id,
                                'customer_email' => $auth->getEmail(),
                                'customer_address' => $shipping_address,
                            ],
                        ], $ini['stripe']['secret_key'] ?? '');

                        if ($charge['status'] !== 200 || ($charge['data']['status'] ?? '') !== 'succeeded') {
                            throw new \Exception($charge['data']['error']['message'] ?? 'Payment failed. Please try again.');
                        }

                         $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id')
                        ->execute([':status' => 'processing', ':id' => $order_id]);
                    }

                    // 3. Insert Items & Deduct Stock
                    foreach ($cart as $item) {
                        $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:oid, :pid, :qty, :price)')
                            ->execute([
                                ':oid' => $order_id,
                                ':pid' => $item['product_id'],
                                ':qty' => $item['quantity'],
                                ':price' => $item['price']
                            ]);

                        $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id')
                            ->execute([
                                ':qty' => $item['quantity'],
                                ':id' => $item['product_id']
                            ]);
                    }

                    $pdo->commit();
                    ekea_log('Order placed', 'INFO', ['order_id' => $order_id, 'total' => $total]);

                    // Clear cart and go to success page
                    $_SESSION['cart'] = [];
                    $_SESSION['last_order_id'] = $order_id;

                    // *OPTIONAL*: Send Email Confirmation Here if you want to use your Mailer!
                    //send_order_email($auth->getEmail(), $order_id);

                    return $this->redirect($response, '/summary');

                } catch (\Exception $e) {
                    $pdo->rollBack();
                    ekea_log_exception($e, 'Checkout failed');
                    $errors[] = $e->getMessage();
                }
            }
        }

        // If errors exist, re-render checkout page
        $stmt = $pdo->prepare('SELECT address FROM user_profiles WHERE user_id = :id');
        $stmt->execute([':id' => $auth->getUserId()]);
        $user_data = $stmt->fetch();

        $page_title = 'Checkout';
        $current_page = 'checkout';
        $csrf_token = generate_csrf_token();

        return $this->render($response, 'shop/checkout', compact(
            'cart',
            'subtotal',
            'shipping',
            'user_data',
            'page_title',
            'current_page',
            'csrf_token',
            'errors'
        ));
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

        $stripe_checkout_url = $this->stripeCheckoutUrlForOrder($request, $order);
        $page_title = 'Order Confirmed';
        $current_page = '';

        return $this->render($response, 'shop/summary', compact('order', 'items', 'stripe_checkout_url', 'page_title', 'current_page'));
    }

    public function stripeSuccess(Request $request, Response $response): Response
    {
        global $pdo;

        require_once __DIR__ . '/../../includes/stripe_helper.php';
        $ini = parse_ini_file(__DIR__ . '/../../includes/db_config.ini', true);
        $secret_key = trim((string)($ini['stripe']['secret_key'] ?? ''));
        $session_id = trim((string)($request->getQueryParams()['session_id'] ?? ''));

        if ($session_id === '' || $secret_key === '') {
            $this->flash('Unable to verify the Stripe payment session.', 'danger');
            return $this->redirect($response, '/');
        }

        $result = stripe_get_checkout_session($session_id, $secret_key);
        $session = $result['data'] ?? [];
        $order_id = (int)($session['metadata']['order_id'] ?? 0);

        if ($result['status'] !== 200 || $order_id <= 0) {
            $this->flash('Stripe payment verification failed.', 'danger');
            return $this->redirect($response, '/');
        }

        $checkout_complete = (($session['status'] ?? '') === 'complete');
        $payment_paid = in_array(($session['payment_status'] ?? ''), ['paid', 'no_payment_required'], true);

        if ($checkout_complete || $payment_paid) {
            $pdo->prepare("UPDATE orders SET status = CASE WHEN status = 'pending' THEN 'processing' ELSE status END WHERE id = :id AND payment_method = :method")
                ->execute([':id' => $order_id, ':method' => 'stripe_qr']);
            $_SESSION['last_order_id'] = $order_id;
            $this->flash('Stripe payment received for order #' . str_pad((string)$order_id, 5, '0', STR_PAD_LEFT) . '.', 'success');
        } else {
            $this->flash('Payment has not been completed yet.', 'warning');
        }

        return $this->redirect($response, '/history?id=' . $order_id);
    }

    public function stripeCancel(Request $request, Response $response): Response
    {
        $order_id = (int)($request->getQueryParams()['order_id'] ?? 0);
        $this->flash('Stripe Checkout was cancelled. The order is still pending payment.', 'warning');

        if ($order_id > 0) {
            return $this->redirect($response, '/history?id=' . $order_id);
        }

        return $this->redirect($response, '/history');
    }

    private function buildAbsoluteUrl(Request $request, string $path): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme() ?: 'http';
        $host = $uri->getHost();
        $port = $uri->getPort();
        $origin = $scheme . '://' . $host;

        if ($port !== null && !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $origin .= ':' . $port;
        }

        return $origin . rtrim(BASE_URL, '/') . $path;
    }

    private function stripeCheckoutUrlForOrder(Request $request, array $order): ?string
    {
        if (($order['payment_method'] ?? '') !== 'stripe_qr' || ($order['status'] ?? '') !== 'pending') {
            return null;
        }

        require_once __DIR__ . '/../../includes/stripe_helper.php';
        $ini = parse_ini_file(__DIR__ . '/../../includes/db_config.ini', true);
        $secret_key = trim((string)($ini['stripe']['secret_key'] ?? ''));

        if ($secret_key === '') {
            return null;
        }

        $order_number = '#' . str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT);
        $result = stripe_create_checkout_session([
            'mode' => 'payment',
            'payment_method_types' => ['paynow'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'sgd',
                    'unit_amount' => (int)round(((float)$order['total']) * 100),
                    'product_data' => [
                        'name' => 'EKEA Order ' . $order_number,
                        'description' => 'Stripe QR test payment for order ' . $order_number,
                    ],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $this->buildAbsoluteUrl($request, '/payment/stripe/success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->buildAbsoluteUrl($request, '/payment/stripe/cancel?order_id=' . (int)$order['id']),
            'metadata' => [
                'order_id' => (string)$order['id'],
            ],
        ], $secret_key);

        if ($result['status'] !== 200) {
            return null;
        }

        return $result['data']['url'] ?? null;
    }
}