<?php
/**
 * Chart Data API Endpoint
 * Returns JSON data for Chart.js charts on the admin dashboard.
 * Protected — only accessible by admin users.
 */
header('Content-Type: application/json; charset=utf-8');

require_once '../../includes/db_connect.php';
require_once '../../includes/auth_guard.php';

// Block non-admin users
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? '';

try {
    switch ($type) {

        // ── Revenue by Category ──────────────────────────────────
        case 'revenue_by_category':
            $stmt = $pdo->query('
                SELECT c.name AS category, COALESCE(SUM(oi.price * oi.quantity), 0) AS revenue
                FROM categories c
                LEFT JOIN products p ON p.category_id = c.id
                LEFT JOIN order_items oi ON oi.product_id = p.id
                LEFT JOIN orders o ON o.id = oi.order_id AND o.status != "cancelled"
                GROUP BY c.id, c.name
                ORDER BY c.name
            ');
            $rows = $stmt->fetchAll();
            echo json_encode([
                'labels'  => array_column($rows, 'category'),
                'data'    => array_map('floatval', array_column($rows, 'revenue')),
            ]);
            break;

        // ── Monthly Sales Trend (last 12 months) ─────────────────
        case 'monthly_sales':
            $stmt = $pdo->query('
                SELECT DATE_FORMAT(created_at, "%Y-%m") AS month,
                       COUNT(*) AS order_count,
                       COALESCE(SUM(total), 0) AS revenue
                FROM orders
                WHERE status != "cancelled"
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, "%Y-%m")
                ORDER BY month
            ');
            $rows = $stmt->fetchAll();

            // Fill in missing months with zero
            $filled = [];
            $start  = new DateTime('first day of -11 months');
            $end    = new DateTime('first day of this month');
            $end->modify('+1 month');
            $interval = new DateInterval('P1M');
            $period   = new DatePeriod($start, $interval, $end);
            $lookup   = [];
            foreach ($rows as $r) {
                $lookup[$r['month']] = $r;
            }
            foreach ($period as $dt) {
                $key = $dt->format('Y-m');
                $filled[] = [
                    'month'       => $dt->format('M Y'),
                    'order_count' => isset($lookup[$key]) ? (int)$lookup[$key]['order_count'] : 0,
                    'revenue'     => isset($lookup[$key]) ? (float)$lookup[$key]['revenue'] : 0,
                ];
            }

            echo json_encode([
                'labels'      => array_column($filled, 'month'),
                'revenue'     => array_column($filled, 'revenue'),
                'order_count' => array_column($filled, 'order_count'),
            ]);
            break;

        // ── Order Status Distribution ────────────────────────────
        case 'order_status':
            $stmt = $pdo->query('
                SELECT status, COUNT(*) AS count
                FROM orders
                GROUP BY status
                ORDER BY FIELD(status, "pending","processing","shipped","delivered","cancelled")
            ');
            $rows = $stmt->fetchAll();
            echo json_encode([
                'labels' => array_map(function ($r) { return ucfirst($r['status']); }, $rows),
                'data'   => array_map('intval', array_column($rows, 'count')),
            ]);
            break;

        // ── Stock Levels by Category ─────────────────────────────
        case 'stock_by_category':
            $stmt = $pdo->query('
                SELECT c.name AS category, COALESCE(SUM(p.stock), 0) AS total_stock
                FROM categories c
                LEFT JOIN products p ON p.category_id = c.id
                GROUP BY c.id, c.name
                ORDER BY c.name
            ');
            $rows = $stmt->fetchAll();
            echo json_encode([
                'labels' => array_column($rows, 'category'),
                'data'   => array_map('intval', array_column($rows, 'total_stock')),
            ]);
            break;

        // ── Top 5 Selling Products ───────────────────────────────
        case 'top_products':
            $stmt = $pdo->query('
                SELECT p.name AS product, COALESCE(SUM(oi.quantity), 0) AS units_sold
                FROM products p
                LEFT JOIN order_items oi ON oi.product_id = p.id
                LEFT JOIN orders o ON o.id = oi.order_id AND o.status != "cancelled"
                GROUP BY p.id, p.name
                ORDER BY units_sold DESC
                LIMIT 5
            ');
            $rows = $stmt->fetchAll();
            echo json_encode([
                'labels' => array_column($rows, 'product'),
                'data'   => array_map('intval', array_column($rows, 'units_sold')),
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid chart type. Use: revenue_by_category, monthly_sales, order_status, stock_by_category, top_products']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    ekea_log('Chart data API error: ' . $e->getMessage(), 'ERROR');
}
