<?php
/**
 * EKEA Chatbot AJAX Endpoint
 * Keyword-based product/page finder with security hardening.
 *
 * Security measures:
 * - Session-based rate limiting (max 20 requests/minute)
 * - Input length capped at 100 characters
 * - All input sanitised with htmlspecialchars
 * - SQL injection prevented via PDO prepared statements
 * - Output encoded for XSS prevention
 * - CSRF not required (read-only endpoint)
 * - No shell/eval/exec calls
 */
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// Rate limiting: max 20 requests per minute per session
if (!isset($_SESSION['chatbot_requests'])) {
    $_SESSION['chatbot_requests'] = [];
}

// Clean old entries (older than 60 seconds)
$now = time();
$_SESSION['chatbot_requests'] = array_filter($_SESSION['chatbot_requests'], function ($t) use ($now) {
    return ($now - $t) < 60;
});

if (count($_SESSION['chatbot_requests']) >= 20) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please wait a moment.']);
    exit;
}
$_SESSION['chatbot_requests'][] = $now;

// Get and sanitise input
$raw_query = $_POST['query'] ?? '';

// Length cap
if (strlen($raw_query) > 100) {
    $raw_query = substr($raw_query, 0, 100);
}

$query = trim($raw_query);

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['results' => [], 'message' => 'Please type at least 2 characters.']);
    exit;
}

// Strip any HTML/script injection attempts
$query = strip_tags($query);
$query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

// Block suspicious patterns (SQL injection / code injection)
$blocked_patterns = [
    '/(\bunion\b|\bselect\b|\bdrop\b|\bdelete\b|\binsert\b|\bupdate\b|\b--\b|\/\*)/i',
    '/<script/i',
    '/javascript:/i',
    '/on\w+\s*=/i',
    '/\bexec\b|\bshell\b|\bsystem\b|\bpassthru\b/i',
];
foreach ($blocked_patterns as $pattern) {
    if (preg_match($pattern, $query)) {
        ekea_log('Chatbot blocked suspicious input', 'WARNING', ['input' => substr($query, 0, 50)]);
        echo json_encode(['results' => [], 'message' => 'Invalid query. Please try a different search.']);
        exit;
    }
}

$results = [];
$search = '%' . $query . '%';

// Search products
try {
    $stmt = $pdo->prepare('SELECT p.id, p.name, p.price, p.image_url, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.name LIKE :q OR p.description LIKE :q2 OR c.name LIKE :q3 LIMIT 5');
    $stmt->execute([':q' => $search, ':q2' => $search, ':q3' => $search]);
    $products = $stmt->fetchAll();

    foreach ($products as $p) {
        $results[] = [
            'type' => 'product',
            'title' => htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'),
            'subtitle' => htmlspecialchars($p['category_name'], ENT_QUOTES, 'UTF-8') . ' - $' . number_format($p['price'], 2),
            'url' => 'product_detail.php?id=' . (int)$p['id'],
            'icon' => 'bi-box-seam',
        ];
    }
}
catch (Exception $e) {
    ekea_log_exception($e, 'Chatbot product search failed');
}

// Search categories
try {
    $stmt = $pdo->prepare('SELECT id, name, description FROM categories WHERE name LIKE :q OR description LIKE :q2 LIMIT 3');
    $stmt->execute([':q' => $search, ':q2' => $search]);
    $categories = $stmt->fetchAll();

    foreach ($categories as $cat) {
        $results[] = [
            'type' => 'category',
            'title' => htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'),
            'subtitle' => 'Browse all ' . htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') . ' products',
            'url' => 'product.php?category=' . (int)$cat['id'],
            'icon' => 'bi-grid',
        ];
    }
}
catch (Exception $e) {
    ekea_log_exception($e, 'Chatbot category search failed');
}

// Match site pages by keyword
$pages = [
    ['keywords' => ['home', 'main', 'front', 'landing'], 'title' => 'Home Page', 'url' => 'index.php', 'icon' => 'bi-house-door', 'subtitle' => 'Return to the homepage'],
    ['keywords' => ['about', 'company', 'team', 'story', 'mission'], 'title' => 'About Us', 'url' => 'about.php', 'icon' => 'bi-info-circle', 'subtitle' => 'Learn about EKEA'],
    ['keywords' => ['product', 'catalog', 'shop', 'browse', 'furniture', 'buy'], 'title' => 'Product Catalog', 'url' => 'product.php', 'icon' => 'bi-grid', 'subtitle' => 'Browse all products'],
    ['keywords' => ['cart', 'basket', 'shopping'], 'title' => 'Shopping Cart', 'url' => 'cart.php', 'icon' => 'bi-cart3', 'subtitle' => 'View your cart'],
    ['keywords' => ['checkout', 'pay', 'order', 'purchase'], 'title' => 'Checkout', 'url' => 'checkout.php', 'icon' => 'bi-credit-card', 'subtitle' => 'Complete your purchase'],
    ['keywords' => ['login', 'sign in', 'account'], 'title' => 'Login', 'url' => 'login.php', 'icon' => 'bi-box-arrow-in-right', 'subtitle' => 'Sign in to your account'],
    ['keywords' => ['register', 'sign up', 'create account', 'join'], 'title' => 'Register', 'url' => 'register.php', 'icon' => 'bi-person-plus', 'subtitle' => 'Create a new account'],
    ['keywords' => ['profile', 'my account', 'settings'], 'title' => 'My Profile', 'url' => 'profile.php', 'icon' => 'bi-person', 'subtitle' => 'Manage your profile'],
    ['keywords' => ['history', 'my orders', 'past orders', 'tracking'], 'title' => 'Order History', 'url' => 'history.php', 'icon' => 'bi-clock-history', 'subtitle' => 'View past orders'],
    ['keywords' => ['review', 'feedback', 'news', 'community'], 'title' => 'Community Reviews', 'url' => 'news.php', 'icon' => 'bi-chat-quote', 'subtitle' => 'Read customer reviews'],
    ['keywords' => ['contact', 'support', 'help', 'email', 'phone'], 'title' => 'Contact EKEA', 'url' => 'about.php', 'icon' => 'bi-telephone', 'subtitle' => '1 Punggol Coast Road, Singapore 828608'],
    ['keywords' => ['sofa', 'couch', 'living room'], 'title' => 'Living Room Furniture', 'url' => 'product.php?category=1', 'icon' => 'bi-house', 'subtitle' => 'Sofas, coffee tables and more'],
    ['keywords' => ['bed', 'bedroom', 'mattress', 'wardrobe'], 'title' => 'Bedroom Furniture', 'url' => 'product.php?category=2', 'icon' => 'bi-moon', 'subtitle' => 'Beds, wardrobes and more'],
    ['keywords' => ['dining', 'table', 'chair'], 'title' => 'Dining Furniture', 'url' => 'product.php?category=3', 'icon' => 'bi-cup-hot', 'subtitle' => 'Tables, chairs and sets'],
    ['keywords' => ['office', 'desk', 'work'], 'title' => 'Office Furniture', 'url' => 'product.php?category=4', 'icon' => 'bi-laptop', 'subtitle' => 'Desks, chairs and accessories'],
    ['keywords' => ['storage', 'shelf', 'cabinet', 'organis'], 'title' => 'Storage Solutions', 'url' => 'product.php?category=5', 'icon' => 'bi-archive', 'subtitle' => 'Shelves, cabinets and more'],
    ['keywords' => ['coupon', 'discount', 'promo', 'deal', 'sale', 'voucher'], 'title' => 'Coupon: SAVE10 (10% Off)', 'url' => 'checkout.php?coupon=SAVE10', 'icon' => 'bi-tag', 'subtitle' => '10% discount — auto-filled at checkout'],
    ['keywords' => ['coupon', 'discount', 'promo', 'deal', 'sale', 'voucher'], 'title' => 'Coupon: SAVE20 (20% Off)', 'url' => 'checkout.php?coupon=SAVE20', 'icon' => 'bi-tag', 'subtitle' => '20% discount — auto-filled at checkout'],
    ['keywords' => ['coupon', 'discount', 'promo', 'deal', 'sale', 'voucher'], 'title' => 'Coupon: EKEA50 (50% Off)', 'url' => 'checkout.php?coupon=EKEA50', 'icon' => 'bi-tag', 'subtitle' => '50% discount — auto-filled at checkout'],
    ['keywords' => ['deliver', 'shipping', 'address'], 'title' => 'Delivery Info', 'url' => 'checkout.php', 'icon' => 'bi-truck', 'subtitle' => 'Free shipping on orders over $200'],
    ['keywords' => ['cheap', 'cheaper', 'cheapest', 'budget', 'affordable', 'lowest price'], 'title' => 'Budget Friendly', 'url' => 'product.php?sort=price_asc', 'icon' => 'bi-sort-up', 'subtitle' => 'Sort products by lowest price'],
    ['keywords' => ['new', 'latest', 'arrival', 'recent'], 'title' => 'New Arrivals', 'url' => 'product.php?sort=newest', 'icon' => 'bi-stars', 'subtitle' => 'View our newest products'],
];

$lower_query = strtolower($query);
foreach ($pages as $page) {
    foreach ($page['keywords'] as $kw) {
        if (strpos($lower_query, $kw) !== false) {
            // Avoid duplicate
            $already = false;
            foreach ($results as $r) {
                if ($r['url'] === $page['url']) {
                    $already = true;
                    break;
                }
            }
            if (!$already) {
                $results[] = [
                    'type' => 'page',
                    'title' => $page['title'],
                    'subtitle' => $page['subtitle'],
                    'url' => $page['url'],
                    'icon' => $page['icon'],
                ];
            }
            break;
        }
    }
}

// Default message
$message = '';
if (empty($results)) {
    $message = 'No results found for "' . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . '". Try searching for a product name, category, or page like "sofa", "office", or "checkout".';
}
else {
    $message = count($results) . ' result' . (count($results) !== 1 ? 's' : '') . ' found.';
}

echo json_encode(['results' => array_slice($results, 0, 8), 'message' => $message]);
