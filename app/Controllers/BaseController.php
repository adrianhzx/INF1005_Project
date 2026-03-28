<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;

abstract class BaseController {
    protected function render(Response $response, string $view, array $data = []): Response {
        global $auth, $pdo;
        extract($data);
        ob_start();
        require __DIR__ . '/../../views/layout/header.php';
        require __DIR__ . '/../../views/' . $view . '.php';
        require __DIR__ . '/../../views/layout/footer.php';
        $response->getBody()->write(ob_get_clean());
        return $response;
    }

    protected function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    protected function redirect(Response $response, string $url): Response {
        if (str_starts_with($url, '/') && !str_starts_with($url, BASE_URL)) {
            $url = BASE_URL . $url;
        }
        return $response->withHeader('Location', $url)->withStatus(302);
    }

    protected function flash(string $message, string $type = 'info'): void {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
}
