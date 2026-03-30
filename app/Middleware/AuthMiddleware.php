<?php
namespace App\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddleware implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        global $auth;
        if (!$auth->isLoggedIn()) {
            $_SESSION['flash_message'] = 'Please log in to access this page.';
            $_SESSION['flash_type'] = 'warning';
            $response = new Response();
            return $response->withHeader('Location', BASE_URL . '/login')->withStatus(302);
        }
        return $handler->handle($request);
    }
}
