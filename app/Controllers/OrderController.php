<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderController extends BaseController 
{
    public function history(Request $request, Response $response): Response 
    {
        global $pdo, $auth;

        // Fetch all orders for the logged-in user
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute([':uid' => $auth->getUserId()]);
        $orders = $stmt->fetchAll();

        // Calculate spending per month for the Chart.js graph
        $spending_by_month = [];
        foreach ($orders as $o) {
            if ($o['status'] !== 'cancelled') {
                $key = date('M Y', strtotime($o['created_at']));
                $spending_by_month[$key] = ($spending_by_month[$key] ?? 0) + (float)$o['total'];
            }
        }
        
        // Reverse array so the chart displays chronologically (left-to-right)
        $spending_by_month = array_reverse($spending_by_month, true);

        // Handle specific order detail view
        $order_detail = null;
        $order_items  = [];
        $params = $request->getQueryParams();
        
        if (isset($params['id'])) {
            $view_id = (int)$params['id'];
            
            // Secure Query: Prevents users from viewing other people's orders
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :uid');
            $stmt->execute([
                ':id'  => $view_id, 
                ':uid' => $auth->getUserId()
            ]);
            $order_detail = $stmt->fetch() ?: null;
            
            // If the order belongs to them, fetch the individual items
            if ($order_detail) {
                $stmt = $pdo->prepare('
                    SELECT oi.*, p.name AS product_name, p.image_url 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = :oid
                ');
                $stmt->execute([':oid' => $view_id]);
                $order_items = $stmt->fetchAll();
            }
        }

        $stripe_checkout_url = $this->stripeCheckoutUrlForOrder($request, $order_detail);
        $page_title   = 'Order History';
        $current_page = 'history';
        $use_chartjs  = true;

        return $this->render($response, 'shop/history', compact(
            'orders', 'order_detail', 'order_items', 'stripe_checkout_url',
            'spending_by_month', 'page_title', 'current_page', 'use_chartjs'
        ));
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

    private function stripeCheckoutUrlForOrder(Request $request, ?array $order): ?string
    {
        if (empty($order) || ($order['payment_method'] ?? '') !== 'stripe_qr' || ($order['status'] ?? '') !== 'pending') {
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