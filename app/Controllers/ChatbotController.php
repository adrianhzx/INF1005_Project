<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChatbotController extends BaseController {
    public function handle(Request $request, Response $response): Response {
        global $pdo;
        if (!isset($_SESSION['chatbot_requests'])) $_SESSION['chatbot_requests'] = [];
        $now = time();
        $_SESSION['chatbot_requests'] = array_filter($_SESSION['chatbot_requests'], fn($t) => ($now - $t) < 60);
        if (count($_SESSION['chatbot_requests']) >= 20) return $this->json($response, ['error'=>'Too many requests. Please wait a moment.'], 429);
        $_SESSION['chatbot_requests'][] = $now;

        $data = $request->getParsedBody();
        $raw_query = substr($data['query'] ?? '', 0, 100);
        $query = trim(htmlspecialchars(strip_tags($raw_query), ENT_QUOTES, 'UTF-8'));
        if (empty($query) || strlen($query) < 2) return $this->json($response, ['results'=>[],'message'=>'Please type at least 2 characters.']);

        $blocked = ['/(union|select|drop|delete|insert|update|--|\/*)/i','/<script/i','/javascript:/i','/on\w+\s*=/i','/exec|shell|system|passthru/i'];
        foreach ($blocked as $pattern) {
            if (preg_match($pattern, $query)) { ekea_log('Chatbot blocked suspicious input','WARNING',['input'=>substr($query,0,50)]); return $this->json($response, ['results'=>[],'message'=>'Invalid query.']); }
        }

        $results = []; $search = '%' . $query . '%';
        try {
            $stmt = $pdo->prepare('SELECT p.id, p.name, p.price, p.image_url, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.name LIKE :q OR p.description LIKE :q2 OR c.name LIKE :q3 LIMIT 5');
            $stmt->execute([':q'=>$search,':q2'=>$search,':q3'=>$search]);
            foreach ($stmt->fetchAll() as $p) {
                $results[] = ['type'=>'product','title'=>htmlspecialchars($p['name'],ENT_QUOTES,'UTF-8'),'subtitle'=>htmlspecialchars($p['category_name'],ENT_QUOTES,'UTF-8').' - $'.number_format($p['price'],2),'url'=>'/products/'.(int)$p['id'],'icon'=>'bi-box-seam'];
            }
        } catch (\Exception $e) { ekea_log_exception($e,'Chatbot product search failed'); }

        try {
            $stmt = $pdo->prepare('SELECT id, name FROM categories WHERE name LIKE :q OR description LIKE :q2 LIMIT 3');
            $stmt->execute([':q'=>$search,':q2'=>$search]);
            foreach ($stmt->fetchAll() as $cat) {
                $results[] = ['type'=>'category','title'=>htmlspecialchars($cat['name'],ENT_QUOTES,'UTF-8'),'subtitle'=>'Browse all '.htmlspecialchars($cat['name'],ENT_QUOTES,'UTF-8').' products','url'=>'/products?category='.(int)$cat['id'],'icon'=>'bi-grid'];
            }
        } catch (\Exception $e) { ekea_log_exception($e,'Chatbot category search failed'); }

        $pages = [
            ['keywords'=>['home','main','front'],'title'=>'Home Page','url'=>'/','icon'=>'bi-house-door','subtitle'=>'Return to the homepage'],
            ['keywords'=>['about','company','team','story'],'title'=>'About Us','url'=>'/about','icon'=>'bi-info-circle','subtitle'=>'Learn about EKEA'],
            ['keywords'=>['product','catalog','shop','browse','buy'],'title'=>'Product Catalog','url'=>'/products','icon'=>'bi-grid','subtitle'=>'Browse all products'],
            ['keywords'=>['cart','basket','shopping'],'title'=>'Shopping Cart','url'=>'/cart','icon'=>'bi-cart3','subtitle'=>'View your cart'],
            ['keywords'=>['checkout','pay','order','purchase'],'title'=>'Checkout','url'=>'/checkout','icon'=>'bi-credit-card','subtitle'=>'Complete your purchase'],
            ['keywords'=>['login','sign in'],'title'=>'Login','url'=>'/login','icon'=>'bi-box-arrow-in-right','subtitle'=>'Sign in to your account'],
            ['keywords'=>['register','sign up','create account'],'title'=>'Register','url'=>'/register','icon'=>'bi-person-plus','subtitle'=>'Create a new account'],
            ['keywords'=>['profile','my account','settings'],'title'=>'My Profile','url'=>'/profile','icon'=>'bi-person','subtitle'=>'Manage your profile'],
            ['keywords'=>['history','my orders','past orders'],'title'=>'Order History','url'=>'/history','icon'=>'bi-clock-history','subtitle'=>'View past orders'],
            ['keywords'=>['review','feedback','news','community'],'title'=>'Community Reviews','url'=>'/news','icon'=>'bi-chat-quote','subtitle'=>'Read customer reviews'],
            ['keywords'=>['sofa','couch','living room'],'title'=>'Living Room','url'=>'/products?category=1','icon'=>'bi-house','subtitle'=>'Sofas, coffee tables and more'],
            ['keywords'=>['bed','bedroom','wardrobe'],'title'=>'Bedroom','url'=>'/products?category=2','icon'=>'bi-moon','subtitle'=>'Beds, wardrobes and more'],
            ['keywords'=>['dining','table','chair'],'title'=>'Dining','url'=>'/products?category=3','icon'=>'bi-cup-hot','subtitle'=>'Tables, chairs and sets'],
            ['keywords'=>['office','desk','work'],'title'=>'Office','url'=>'/products?category=4','icon'=>'bi-laptop','subtitle'=>'Desks, chairs and accessories'],
            ['keywords'=>['storage','shelf','cabinet'],'title'=>'Storage','url'=>'/products?category=5','icon'=>'bi-archive','subtitle'=>'Shelves, cabinets and more'],
            ['keywords'=>['coupon','discount','promo','sale'],'title'=>'Coupon: SAVE10','url'=>'/checkout?coupon=SAVE10','icon'=>'bi-tag','subtitle'=>'10% discount'],
            ['keywords'=>['cheap','budget','affordable'],'title'=>'Budget Friendly','url'=>'/products?sort=price_asc','icon'=>'bi-sort-up','subtitle'=>'Sort by lowest price'],
            ['keywords'=>['new','latest','arrival'],'title'=>'New Arrivals','url'=>'/products?sort=newest','icon'=>'bi-stars','subtitle'=>'View our newest products'],
        ];

        $lower = strtolower($query);
        foreach ($pages as $page) {
            foreach ($page['keywords'] as $kw) {
                if (str_contains($lower, $kw)) {
                    $already = array_filter($results, fn($r) => $r['url'] === $page['url']);
                    if (empty($already)) $results[] = ['type'=>'page','title'=>$page['title'],'subtitle'=>$page['subtitle'],'url'=>$page['url'],'icon'=>$page['icon']];
                    break;
                }
            }
        }

        $count = count($results);
        $message = $count === 0 ? 'No results found for "'.htmlspecialchars($query,ENT_QUOTES,'UTF-8').'". Try "sofa", "office", or "checkout".' : $count.' result'.($count!==1?'s':'').' found.';
        return $this->json($response, ['results'=>array_slice($results,0,8),'message'=>$message]);
    }
}
