<?php
namespace App\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;

class AdminMiddleware implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        global $auth;
        if (!$auth->isLoggedIn() || !$auth->hasRole(\Delight\Auth\Role::ADMIN)) {
            throw new HttpForbiddenException($request, 'Access denied.');
        }
        return $handler->handle($request);
    }
}