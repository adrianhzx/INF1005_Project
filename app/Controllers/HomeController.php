<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends BaseController 
{
    public function index(Request $request, Response $response): Response 
    {
        global $pdo;
        
        // Fetch 8 newest products for the homepage grid
        $stmt = $pdo->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 8');
        $featured_products = $stmt->fetchAll();
        
        // Fetch categories and how many products are in each
        $stmt = $pdo->query('SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id');
        $categories = $stmt->fetchAll();
        
        $page_title = 'Home';
        $current_page = 'home';
        $csrf_token = generate_csrf_token();
        
        return $this->render($response, 'home', compact('featured_products', 'categories', 'page_title', 'current_page', 'csrf_token'));
    }

    public function newsletter(Request $request, Response $response): Response 
    {
        global $pdo;
        $data = $request->getParsedBody();
        
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid form submission.', 'danger');
            return $this->redirect($response, '/#newsletter');
        }
        
        $email = filter_var(trim($data['newsletter_email'] ?? ''), FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            $this->flash('Please enter a valid email address.', 'danger');
            return $this->redirect($response, '/#newsletter');
        }
        
        // Check for existing subscription
        $stmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = :email');
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            $this->flash("You're already subscribed! We'll keep you inspired.", 'info');
        } else {
            // Insert new subscriber
            $pdo->prepare('INSERT INTO newsletter_subscribers (email) VALUES (:email)')->execute([':email' => $email]);
            ekea_log('Newsletter subscription', 'INFO', ['email' => $email]);
            $this->flash('Thank you for subscribing! Stay inspired with EKEA.', 'success');
        }
        
        return $this->redirect($response, '/#newsletter');
    }

    public function about(Request $request, Response $response): Response 
    {
        $page_title = 'About Us';
        $current_page = 'about';
        return $this->render($response, 'pages/about', compact('page_title', 'current_page'));
    }

    public function news(Request $request, Response $response): Response 
    {
        global $pdo;
        
        // Added LIMIT 50 to protect server memory
        $stmt = $pdo->query('
            SELECT r.*, up.first_name, up.last_name, p.name AS product_name, p.id AS product_id, p.image_url 
            FROM reviews r 
            JOIN user_profiles up ON r.user_id = up.user_id 
            JOIN products p ON r.product_id = p.id 
            ORDER BY r.created_at DESC 
            LIMIT 50
        ');
        $reviews = $stmt->fetchAll();
        
        // Get overall stats for the Chart
        $stmt = $pdo->query('SELECT COUNT(*) AS total, AVG(rating) AS avg_rating FROM reviews');
        $stats = $stmt->fetch();
        
        // Calculate the 1-5 star distribution
        $rating_dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($reviews as $rev) { 
            $r = (int)$rev['rating']; 
            if (isset($rating_dist[$r])) {
                $rating_dist[$r]++; 
            }
        }
        
        $page_title = 'Community Reviews';
        $current_page = 'news';
        $use_chartjs = true;
        
        return $this->render($response, 'pages/news', compact('reviews', 'stats', 'rating_dist', 'page_title', 'current_page', 'use_chartjs'));
    }
}