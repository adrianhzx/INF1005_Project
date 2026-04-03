<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/mailer.php';

use App\Controllers\BaseController;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

define('BASE_URL', '');
//define('IMAGE_CDN_URL', 'https://res.cloudinary.com/YOUR_CLOUD_NAME/image/upload/f_auto,q_auto/ekea/');

$app = AppFactory::create();
$app->setBasePath('');
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setDefaultErrorHandler(
    function (
        \Psr\Http\Message\ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app) {
        ekea_log('UNCAUGHT EXCEPTION: ' . $exception->getMessage(), 'CRITICAL', [
            'file' => $exception->getFile() . ':' . $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $response = $app->getResponseFactory()->createResponse(500);

        $renderer = new class extends BaseController {
            public function page(\Psr\Http\Message\ResponseInterface $response): \Psr\Http\Message\ResponseInterface
            {
                $page_title = 'Server Error';
                $current_page = '';
                return $this->render($response, 'pages/500', compact('page_title', 'current_page'));
            }
        };

        return $renderer->page($response);
    }
);

$errorMiddleware->setErrorHandler(
    HttpBadRequestException::class,
    function (
        \Psr\Http\Message\ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app) {
        $response = $app->getResponseFactory()->createResponse(400);

        $renderer = new class extends BaseController {
            public function page(\Psr\Http\Message\ResponseInterface $response): \Psr\Http\Message\ResponseInterface
            {
                $page_title = 'Bad Request';
                $current_page = '';
                return $this->render($response, 'pages/400', compact('page_title', 'current_page'));
            }
        };

        return $renderer->page($response);
    }
);

$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function (
        \Psr\Http\Message\ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app) {
        $response = $app->getResponseFactory()->createResponse(404);

        $renderer = new class extends BaseController {
            public function page(\Psr\Http\Message\ResponseInterface $response): \Psr\Http\Message\ResponseInterface
            {
                $page_title = 'Page Not Found';
                $current_page = '';
                return $this->render($response, 'pages/404', compact('page_title', 'current_page'));
            }
        };

        return $renderer->page($response);
    }
);
$errorMiddleware->setErrorHandler(
    HttpForbiddenException::class,
    function (
        \Psr\Http\Message\ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app) {
        $response = $app->getResponseFactory()->createResponse(403);

        $renderer = new class extends BaseController {
            public function page(\Psr\Http\Message\ResponseInterface $response): \Psr\Http\Message\ResponseInterface
            {
                $page_title = 'Access Forbidden';
                $current_page = '';
                return $this->render($response, 'pages/403', compact('page_title', 'current_page'));
            }
        };

        return $renderer->page($response);
    }
);

$errorMiddleware->setErrorHandler(
    HttpMethodNotAllowedException::class,
    function (
        \Psr\Http\Message\ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app) {
        $response = $app->getResponseFactory()->createResponse(405);

        $renderer = new class extends BaseController {
            public function page(\Psr\Http\Message\ResponseInterface $response): \Psr\Http\Message\ResponseInterface
            {
                $page_title = 'Method Not Allowed';
                $current_page = '';
                return $this->render($response, 'pages/405', compact('page_title', 'current_page'));
            }
        };

        return $renderer->page($response);
    }
);

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
$app->get('/payment/stripe/success', [\App\Controllers\CheckoutController::class, 'stripeSuccess']);
$app->get('/payment/stripe/cancel', [\App\Controllers\CheckoutController::class, 'stripeCancel']);

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
