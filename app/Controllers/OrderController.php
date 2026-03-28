<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderController extends BaseController {

    public function history(Request $request, Response $response): Response {
        global $pdo, $auth;

        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute([':uid' => $auth->getUserId()]);
        $orders = $stmt->fetchAll();

        $spending_by_month = [];
        foreach ($orders as $o) {
            if ($o['status'] !== 'cancelled') {
                $key = date('M Y', strtotime($o['created_at']));
                $spending_by_month[$key] = ($spending_by_month[$key] ?? 0) + (float)$o['total'];
            }
        }
        $spending_by_month = array_reverse($spending_by_month, true);

        $order_detail = null;
        $order_items  = [];
        $params = $request->getQueryParams();
        if (isset($params['id'])) {
            $view_id = (int)$params['id'];
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :uid');
            $stmt->execute([':id' => $view_id, ':uid' => $auth->getUserId()]);
            $order_detail = $stmt->fetch() ?: null;
            if ($order_detail) {
                $stmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :oid');
                $stmt->execute([':oid' => $view_id]);
                $order_items = $stmt->fetchAll();
            }
        }

        $page_title   = 'Order History';
        $current_page = 'history';
        $use_chartjs  = true;

        return $this->render($response, 'shop/history', compact(
            'orders', 'order_detail', 'order_items',
            'spending_by_month', 'page_title', 'current_page', 'use_chartjs'
        ));
    }
}