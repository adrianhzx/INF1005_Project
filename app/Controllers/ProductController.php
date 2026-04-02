<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController extends BaseController 
{
    public function index(Request $request, Response $response): Response 
    {
        global $pdo;
        $params = $request->getQueryParams();
        
        $category_filter = isset($params['category']) ? (int)$params['category'] : 0;
        $search_query = trim($params['search'] ?? '');
        $sort_by = $params['sort'] ?? 'newest';
        $page = max(1, (int)($params['page'] ?? 1));
        $per_page = 12;
        $offset = ($page - 1) * $per_page;

        $where = []; 
        $qparams = [];
        
        if ($category_filter > 0) { 
            $where[] = 'p.category_id = :cat'; 
            $qparams[':cat'] = $category_filter; 
        }
        
        if ($search_query !== '') { 
            $where[] = '(p.name LIKE :search OR p.description LIKE :search2)'; 
            $qparams[':search'] = "%{$search_query}%"; 
            $qparams[':search2'] = "%{$search_query}%"; 
        }
        
        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $order = match($sort_by) {
            'price_asc'  => 'p.price ASC', 
            'price_desc' => 'p.price DESC',
            'name'       => 'p.name ASC', 
            'oldest'     => 'p.created_at ASC',
            default      => 'p.created_at DESC',
        };
        
        $sort_by = in_array($sort_by, ['price_asc','price_desc','name','oldest','newest']) ? $sort_by : 'newest';

        // Get total count for pagination
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products p {$where_sql}");
        $stmt->execute($qparams);
        $total = $stmt->fetchColumn();
        $total_pages = max(1, ceil($total / $per_page));

        // Get filtered products
        $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id {$where_sql} ORDER BY {$order} LIMIT {$per_page} OFFSET {$offset}");
        $stmt->execute($qparams);
        $products = $stmt->fetchAll();

        // Get categories for the sidebar
        $stmt = $pdo->query('SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name');
        $categories = $stmt->fetchAll();
        $total_all = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

        $page_title = 'Products'; 
        $current_page = 'products';
        $csrf_token = generate_csrf_token();
        
        return $this->render($response, 'shop/products', compact(
            'products', 'categories', 'total', 'total_pages', 'total_all', 
            'page', 'per_page', 'category_filter', 'search_query', 'sort_by', 
            'page_title', 'current_page', 'csrf_token'
        ));
    }

    public function detail(Request $request, Response $response, array $args): Response 
    {
        global $pdo, $auth;
        $product_id = (int)($args['id'] ?? 0);
        
        if ($product_id <= 0) { 
            $this->flash('Invalid product.', 'danger'); 
            return $this->redirect($response, '/products'); 
        }

        // Fetch Product
        $stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = :id');
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch();
        
        if (!$product) { 
            $this->flash('Product not found.', 'danger'); 
            return $this->redirect($response, '/products'); 
        }

        // Fetch Reviews
        $stmt = $pdo->prepare('SELECT r.*, up.first_name, up.last_name FROM reviews r JOIN user_profiles up ON r.user_id = up.user_id WHERE r.product_id = :pid ORDER BY r.created_at DESC');
        $stmt->execute([':pid' => $product_id]);
        $reviews = $stmt->fetchAll();
        $avg_rating = count($reviews) ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

        // Check Auth Status for Reviews/Purchases
        $has_reviewed = false; 
        $has_purchased = false;
        
        if ($auth->isLoggedIn()) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = :uid AND product_id = :pid');
            $stmt->execute([':uid' => $auth->getUserId(), ':pid' => $product_id]);
            $has_reviewed = $stmt->fetchColumn() > 0;
            
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = :uid AND oi.product_id = :pid AND o.status = "delivered"');
            $stmt->execute([':uid' => $auth->getUserId(), ':pid' => $product_id]);
            $has_purchased = $stmt->fetchColumn() > 0;
        }

        // Related products
        $stmt = $pdo->prepare('SELECT * FROM products WHERE category_id = :cat AND id != :id LIMIT 4');
        $stmt->execute([':cat' => $product['category_id'], ':id' => $product_id]);
        $related_products = $stmt->fetchAll();

        $page_title = $product['name']; 
        $current_page = 'products'; 
        $use_chartjs = true;
        $csrf_token = generate_csrf_token();
        
        return $this->render($response, 'shop/product_detail', compact(
            'product', 'reviews', 'avg_rating', 'has_reviewed', 'has_purchased', 
            'related_products', 'product_id', 'page_title', 'current_page', 
            'use_chartjs', 'csrf_token'
        ));
    }

    public function action(Request $request, Response $response, array $args): Response 
    {
        global $pdo, $auth;
        $product_id = (int)($args['id'] ?? 0);
        $data = $request->getParsedBody();

        if (!$auth->isLoggedIn()) {
            $_SESSION['flash_message'] = 'You must be logged in to post a review.';
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $userId = $auth->getUserId();

        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid form submission.', 'danger');
            return $this->redirect($response, "/products/{$product_id}");
        }

        // --- ADD TO CART ---
        if (isset($data['add_to_cart'])) {
            if (!$auth->isLoggedIn()) { 
                $this->flash('Please log in to add items to your cart.', 'warning'); 
                return $this->redirect($response, '/login'); 
            }

            // Fix: Check if product actually exists
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
            $stmt->execute([':id' => $product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                $this->flash('Error: Product does not exist.', 'danger');
                return $this->redirect($response, '/products');
            }

            $qty = max(1, (int)($data['quantity'] ?? 1));
            
            // Optional but recommended: Check Stock levels!
            if ($qty > $product['stock']) {
                $this->flash("Sorry, we only have {$product['stock']} of those in stock.", 'warning');
                return $this->redirect($response, "/products/{$product_id}");
            }

            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] === $product_id) { 
                    // Prevent user from adding more than stock allows by clicking "Add to cart" multiple times
                    if (($item['quantity'] + $qty) > $product['stock']) {
                        $this->flash("You cannot add more of this item than we have in stock.", 'warning');
                        return $this->redirect($response, "/products/{$product_id}");
                    }
                    $item['quantity'] += $qty; 
                    $found = true; 
                    break; 
                }
            }
            unset($item);
            
            if (!$found) {
                $_SESSION['cart'][] = [
                    'product_id' => $product_id,
                    'name'       => $product['name'],
                    'price'      => $product['price'],
                    'image_url'  => $product['image_url'],
                    'quantity'   => $qty
                ];
            }
            
            $this->flash(htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . ' added to your cart!', 'success');
        }

        // --- SUBMIT REVIEW ---
        if (isset($data['submit_review'])) {
            if (!$auth->isLoggedIn()) { 
                $this->flash('Please log in to leave a review.', 'warning'); 
                return $this->redirect($response, '/login'); 
            }
            
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = :uid AND oi.product_id = :pid AND o.status = "delivered"');
            $stmt->execute([':uid' => $auth->getUserId(), ':pid' => $product_id]);
            
            if ($stmt->fetchColumn() == 0) { 
                $this->flash('You must purchase and receive this product before leaving a review.', 'danger'); 
                return $this->redirect($response, "/products/{$product_id}"); 
            }
            
            $rating = max(1, min(5, (int)($data['rating'] ?? 0)));
            $comment = trim($data['comment'] ?? '');
            
            if ($rating < 1 || $rating > 5 || strlen($comment) < 10) { 
                $this->flash('Please provide a valid rating and review of at least 10 characters.', 'danger'); 
                return $this->redirect($response, "/products/{$product_id}"); 
            }
            
            $pdo->prepare('INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (:uid, :pid, :rating, :comment)')
                ->execute([
                    ':uid'     => $auth->getUserId(),
                    ':pid'     => $product_id,
                    ':rating'  => $rating,
                    ':comment' => $comment
                ]);
                
            $this->flash('Thank you for your review!', 'success');
        }

        return $this->redirect($response, "/products/{$product_id}");
    }
}