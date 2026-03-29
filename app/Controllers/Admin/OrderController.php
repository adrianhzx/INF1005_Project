<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderController extends BaseController {

    public function index(Request $request, Response $response): Response {
        global $pdo;

        $order_detail = null;
        $order_items  = [];
        $view_id = (int)($request->getQueryParams()['view'] ?? 0);

        if ($view_id > 0) {
            $stmt = $pdo->prepare('
                SELECT o.*, up.first_name, up.last_name, u.email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN user_profiles up ON o.user_id = up.user_id
                WHERE o.id = :id
            ');
            $stmt->execute([':id' => $view_id]);
            $order_detail = $stmt->fetch() ?: null;

            if ($order_detail) {
                $stmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :oid');
                $stmt->execute([':oid' => $view_id]);
                $order_items = $stmt->fetchAll();
            }
        }

        $stmt = $pdo->query('
            SELECT o.*, up.first_name, up.last_name
            FROM orders o
            LEFT JOIN user_profiles up ON o.user_id = up.user_id
            ORDER BY o.created_at DESC
        ');
        $orders = $stmt->fetchAll();

        $csrf_token   = generate_csrf_token();
        $page_title   = 'Manage Orders';
        $current_page = 'admin';

        return $this->render($response, 'admin/orders', compact(
            'orders', 'order_detail', 'order_items', 'page_title', 'current_page', 'csrf_token'
        ));
    }

    public function update(Request $request, Response $response): Response {
        global $pdo;

        $data = $request->getParsedBody();

        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid request.', 'danger');
            return $this->redirect($response, '/admin/orders');
        }

        $order_id   = (int)($data['order_id'] ?? 0);
        $new_status = $data['new_status'] ?? '';
        $valid      = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (in_array($new_status, $valid)) {
            // Check what the current status is before updating
            $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = :id');
            $stmt->execute([':id' => $order_id]);
            $current_status = $stmt->fetchColumn();

            // Only run the update if the status is actually changing
            if ($current_status && $current_status !== $new_status) {
                
                // RESTOCK LOGIC: If changing to 'cancelled', put items back in stock
                if ($new_status === 'cancelled' && $current_status !== 'cancelled') {
                    $items_stmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :oid');
                    $items_stmt->execute([':oid' => $order_id]);
                    $items = $items_stmt->fetchAll();

                    $restock_stmt = $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :pid');
                    foreach ($items as $item) {
                        $restock_stmt->execute([':qty' => $item['quantity'], ':pid' => $item['product_id']]);
                    }
                    ekea_log('Inventory restocked due to cancellation', 'INFO', ['order_id' => $order_id]);
                }

                // DEDUCT STOCK LOGIC: If un-cancelling an order, take the stock back out
                if ($current_status === 'cancelled' && $new_status !== 'cancelled') {
                    $items_stmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :oid');
                    $items_stmt->execute([':oid' => $order_id]);
                    $items = $items_stmt->fetchAll();

                    $deduct_stmt = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :pid');
                    foreach ($items as $item) {
                        $deduct_stmt->execute([':qty' => $item['quantity'], ':pid' => $item['product_id']]);
                    }
                    ekea_log('Inventory deducted due to un-cancellation', 'INFO', ['order_id' => $order_id]);
                }

                // Finally, update the actual order status
                $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id')
                    ->execute([':status' => $new_status, ':id' => $order_id]);
                
                ekea_log('Order status updated', 'INFO', ['order_id' => $order_id, 'status' => $new_status]);
                $this->flash('Order #' . str_pad($order_id, 5, '0', STR_PAD_LEFT) . ' updated to ' . ucfirst($new_status) . '.', 'success');
            } else {
                $this->flash('Status is already set to ' . ucfirst($new_status) . '.', 'info');
            }
        } else {
            $this->flash('Invalid status selected.', 'danger');
        }

        return $this->redirect($response, '/admin/orders');
    }
}