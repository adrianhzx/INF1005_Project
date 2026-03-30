<?php
define('BASE_URL', '/ekea');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/mailer.php';

use Slim\Factory\AppFactory;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

$app = AppFactory::create();
$app->setBasePath(BASE_URL);
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/',               [\App\Controllers\HomeController::class,    'index']);
$app->post('/',              [\App\Controllers\HomeController::class,    'newsletter']);
$app->get('/about',          [\App\Controllers\HomeController::class,    'about']);
$app->get('/news',           [\App\Controllers\HomeController::class,    'news']);
$app->get('/products',       [\App\Controllers\ProductController::class, 'index']);
$app->get('/products/{id}',  [\App\Controllers\ProductController::class, 'detail']);
$app->post('/products/{id}', [\App\Controllers\ProductController::class, 'action']);

$app->get('/login',              [\App\Controllers\AuthController::class, 'loginPage']);
$app->post('/login',             [\App\Controllers\AuthController::class, 'login']);
$app->get('/register',           [\App\Controllers\AuthController::class, 'registerPage']);
$app->post('/register',          [\App\Controllers\AuthController::class, 'register']);
$app->get('/logout',             [\App\Controllers\AuthController::class, 'logout']);
$app->get('/email-verification', [\App\Controllers\AuthController::class, 'verifyEmail']);
$app->get('/forgetpassword',     [\App\Controllers\AuthController::class, 'forgotPasswordPage']);
$app->post('/forgetpassword',    [\App\Controllers\AuthController::class, 'forgotPassword']);
$app->get('/resetpassword',      [\App\Controllers\AuthController::class, 'resetPasswordPage']);
$app->post('/resetpassword',     [\App\Controllers\AuthController::class, 'resetPassword']);
$app->get('/auth/google',        [\App\Controllers\AuthController::class, 'googleLogin']);
$app->get('/auth/google/callback', [\App\Controllers\AuthController::class, 'googleCallback']);

$app->get('/cart',         [\App\Controllers\CartController::class, 'index']);
$app->post('/cart/add',    [\App\Controllers\CartController::class, 'add']);
$app->post('/cart/update', [\App\Controllers\CartController::class, 'update']);
$app->post('/cart/remove', [\App\Controllers\CartController::class, 'remove']);
$app->post('/cart/clear',  [\App\Controllers\CartController::class, 'clear']);

$app->post('/chatbot', [\App\Controllers\ChatbotController::class, 'handle']);

$app->group('', function ($group) {
    $group->get('/checkout',         [\App\Controllers\CheckoutController::class, 'index']);
    $group->post('/checkout',        [\App\Controllers\CheckoutController::class, 'place']);
    $group->post('/checkout/coupon', [\App\Controllers\CheckoutController::class, 'coupon']);
    $group->get('/summary',          [\App\Controllers\CheckoutController::class, 'summary']);
    $group->get('/history',          [\App\Controllers\OrderController::class,    'history']);
    $group->get('/profile',          [\App\Controllers\ProfileController::class,  'index']);
    $group->post('/profile',         [\App\Controllers\ProfileController::class,  'update']);
})->add(new AuthMiddleware());

$app->group('/admin', function ($group) {
    $group->get('',                [\App\Controllers\Admin\DashboardController::class, 'index']);
    $group->get('/chart-data',     [\App\Controllers\Admin\DashboardController::class, 'chartData']);
    $group->get('/inventory',      [\App\Controllers\Admin\InventoryController::class, 'index']);
    $group->post('/inventory',     [\App\Controllers\Admin\InventoryController::class, 'update']);
    $group->get('/inventory/add',  [\App\Controllers\Admin\InventoryController::class, 'addPage']);
    $group->post('/inventory/add', [\App\Controllers\Admin\InventoryController::class, 'add']);
    $group->get('/orders',         [\App\Controllers\Admin\OrderController::class,     'index']);
    $group->post('/orders',        [\App\Controllers\Admin\OrderController::class,     'update']);
    $group->get('/users',          [\App\Controllers\Admin\UserController::class,      'index']);
    $group->post('/users',         [\App\Controllers\Admin\UserController::class,      'update']);
})->add(new AdminMiddleware());

$app->run();
