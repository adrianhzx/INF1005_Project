<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        global $pdo;
        $total_products  = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $total_orders    = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $total_customers = $pdo->query('SELECT COUNT(DISTINCT user_id) FROM orders')->fetchColumn();
        $total_revenue   = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != "cancelled"')->fetchColumn();
        $total_reviews   = $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
        $stmt = $pdo->query('SELECT o.*, up.first_name, up.last_name FROM orders o LEFT JOIN user_profiles up ON o.user_id = up.user_id ORDER BY o.created_at DESC LIMIT 10');
        $recent_orders = $stmt->fetchAll();
        $page_title = 'Admin Dashboard';
        $current_page = 'admin';
        $use_chartjs = true;
        return $this->render($response, 'admin/dashboard', compact('total_products', 'total_orders', 'total_customers', 'total_revenue', 'total_reviews', 'recent_orders', 'page_title', 'current_page', 'use_chartjs'));
    }

    public function chartData(Request $request, Response $response): Response
    {
        global $pdo;
        $type = $request->getQueryParams()['type'] ?? '';
        try {
            switch ($type) {
                case 'revenue_by_category':
                    $stmt = $pdo->query('SELECT c.name AS category, COALESCE(SUM(oi.price * oi.quantity), 0) AS revenue FROM categories c LEFT JOIN products p ON p.category_id = c.id LEFT JOIN order_items oi ON oi.product_id = p.id LEFT JOIN orders o ON o.id = oi.order_id AND o.status != "cancelled" GROUP BY c.id, c.name ORDER BY c.name');
                    $rows = $stmt->fetchAll();
                    return $this->json($response, ['labels' => array_column($rows, 'category'),'data' => array_map('floatval', array_column($rows, 'revenue'))]);
                case 'monthly_sales':
                    $stmt = $pdo->query('SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE status != "cancelled" AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(created_at, "%Y-%m") ORDER BY month');
                    $rows = $stmt->fetchAll();
                    $filled = [];
                    $start = new \DateTime('first day of -11 months');
                    $end = new \DateTime('first day of next month');
                    $lookup = [];
                    foreach ($rows as $r) {
                        $lookup[$r['month']] = $r;
                    }
                    foreach (new \DatePeriod($start, new \DateInterval('P1M'), $end) as $dt) {
                        $key = $dt->format('Y-m');
                        $filled[] = ['month' => $dt->format('M Y'),'order_count' => isset($lookup[$key]) ? (int)$lookup[$key]['order_count'] : 0,'revenue' => isset($lookup[$key]) ? (float)$lookup[$key]['revenue'] : 0];
                    }
                    return $this->json($response, ['labels' => array_column($filled, 'month'),'revenue' => array_column($filled, 'revenue'),'order_count' => array_column($filled, 'order_count')]);
                case 'order_status':
                    $rows = $pdo->query('SELECT status, COUNT(*) AS count FROM orders GROUP BY status')->fetchAll();
                    return $this->json($response, ['labels' => array_column($rows, 'status'),'data' => array_map('intval', array_column($rows, 'count'))]);
                case 'top_products':
                    $rows = $pdo->query('SELECT p.name, SUM(oi.quantity) AS sold FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE o.status != "cancelled" GROUP BY p.id, p.name ORDER BY sold DESC LIMIT 5')->fetchAll();
                    return $this->json($response, ['labels' => array_column($rows, 'name'),'data' => array_map('intval', array_column($rows, 'sold'))]);
                case 'stock_by_category':  
                    $rows = $pdo->query('
                                SELECT c.name AS category, COALESCE(SUM(p.stock), 0) AS total_stock
                                FROM categories c
                                LEFT JOIN products p ON p.category_id = c.id
                                GROUP BY c.id, c.name
                                ORDER BY c.name
                            ')->fetchAll();
                    return $this->json($response, [
                        'labels' => array_column($rows, 'category'),
                        'data'   => array_map('intval', array_column($rows, 'total_stock')),
                    ]);
                default:
                    return $this->json($response, ['error' => 'Unknown chart type.'], 400);
            }
        } catch (\Exception $e) {
            ekea_log_exception($e, 'Chart data error');
            return $this->json($response, ['error' => 'Failed to load chart data.'], 500);
        }
    }
}
