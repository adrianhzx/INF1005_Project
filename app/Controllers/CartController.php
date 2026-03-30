<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CartController extends BaseController {

    public function index(Request $request, Response $response): Response {
        global $pdo, $auth;
        if (!$auth->isLoggedIn()) {
            $this->flash('Please log in to view your cart.', 'warning');
            return $this->redirect($response, '/login');
        }
        $cart = $_SESSION['cart'] ?? [];
        $cart_stock = [];
        foreach ($cart as $item) {
            $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id');
            $stmt->execute([':id' => $item['product_id']]);
            $row = $stmt->fetch();
            $cart_stock[$item['product_id']] = $row ? (int)$row['stock'] : 0;
        }
        $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
        $csrf_token = generate_csrf_token();
        $page_title = 'Shopping Cart'; $current_page = 'cart';
        return $this->render($response, 'shop/cart', compact('cart','cart_stock','subtotal','page_title','current_page','csrf_token'));
    }

    public function add(Request $request, Response $response): Response {
        global $pdo;
        $data = $request->getParsedBody();
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid request.', 'danger');
            return $this->redirect($response, '/cart');
        }
        $product_id = (int)($data['product_id'] ?? 0);
        $qty = max(1, (int)($data['quantity'] ?? 1));
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch();
        if (!$product) { $this->flash('Product not found.', 'danger'); return $this->redirect($response, '/products'); }
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $product_id) { $item['quantity'] += $qty; $found = true; break; }
        }
        unset($item);
        if (!$found) {
            $_SESSION['cart'][] = ['product_id'=>$product_id,'name'=>$product['name'],'price'=>$product['price'],'image_url'=>$product['image_url'],'quantity'=>$qty];
        }
        $this->flash(htmlspecialchars($product['name'],ENT_QUOTES,'UTF-8').' added to cart!','success');
        return $this->redirect($response, '/cart');
    }

    public function update(Request $request, Response $response): Response {
        global $pdo;
        $data = $request->getParsedBody();
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid request.', 'danger');
            return $this->redirect($response, '/cart');
        }
        $product_id = (int)($data['product_id'] ?? 0);
        $new_qty = max(1, (int)($data['quantity'] ?? 1));
        $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id');
        $stmt->execute([':id' => $product_id]);
        $stock_row = $stmt->fetch();
        $max_stock = $stock_row ? (int)$stock_row['stock'] : 1;
        if ($new_qty > $max_stock) {
            $new_qty = $max_stock;
            $this->flash("Quantity adjusted to maximum available stock ({$max_stock}).", 'warning');
        } else {
            $this->flash('Cart updated.', 'success');
        }
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $product_id) { $item['quantity'] = $new_qty; break; }
        }
        unset($item);
        return $this->redirect($response, '/cart');
    }

    public function remove(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid request.', 'danger');
            return $this->redirect($response, '/cart');
        }
        $product_id = (int)($data['product_id'] ?? 0);
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], fn($i) => $i['product_id'] !== $product_id));
        $this->flash('Item removed from cart.', 'success');
        return $this->redirect($response, '/cart');
    }

    public function clear(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid request.', 'danger');
            return $this->redirect($response, '/cart');
        }
        $_SESSION['cart'] = [];
        $this->flash('Cart cleared.', 'info');
        return $this->redirect($response, '/cart');
    }
}
