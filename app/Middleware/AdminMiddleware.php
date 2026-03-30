<?php
namespace App\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AdminMiddleware implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        global $auth;
        if (!$auth->isLoggedIn() || !$auth->hasRole(\Delight\Auth\Role::ADMIN)) {
            $_SESSION['flash_message'] = 'Access denied.';
            $_SESSION['flash_type'] = 'danger';
            $response = new Response();
            return $response->withHeader('Location', BASE_URL . '/')->withStatus(302);
        }
        return $handler->handle($request);
    }
}
