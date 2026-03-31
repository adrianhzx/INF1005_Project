<?php
/**
 * Authentication Guard
 * Helper functions to protect pages that require login or admin access.
 */

function app_url(string $path): string
{
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    return $base . $path;
}

/**
 * Require user to be logged in. Redirects to login page if not.
 */
function require_login()
{
    global $auth;
    if (!$auth->isLoggedIn()) {
        $_SESSION['flash_message'] = 'Please log in to access this page.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: ' . app_url('/login'));
        exit;
    }
}

/**
 * Require user to be an admin. Redirects to index if not.
 */
function require_admin()
{
    global $auth;
    if (!$auth->isLoggedIn() || !$auth->hasRole(\Delight\Auth\Role::ADMIN)) {
        $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . app_url('/'));
        exit;
    }
}

/**
 * Generate a CSRF token and store it in the session.
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the submitted CSRF token.
 */
function validate_csrf_token($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function tracked_session_ip(): ?string
{
    return $_SERVER['REMOTE_ADDR'] ?? null;
}

function tracked_session_user_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 1000);
}

function start_user_session_record(int $userId): string
{
    global $pdo;

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $sessionToken = bin2hex(random_bytes(32));
    $_SESSION['session_token'] = $sessionToken;

    $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :uid')->execute([':uid' => $userId]);
    $pdo->prepare(
        'INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) VALUES (:uid, :token, :ip, :ua)'
    )->execute([
        ':uid' => $userId,
        ':token' => $sessionToken,
        ':ip' => tracked_session_ip(),
        ':ua' => tracked_session_user_agent(),
    ]);

    return $sessionToken;
}

function clear_user_session_record(?int $userId = null, ?string $sessionToken = null): void
{
    global $pdo;

    if (isset($pdo)) {
        if ($userId !== null && $sessionToken !== null) {
            $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :uid AND session_token = :token')
                ->execute([':uid' => $userId, ':token' => $sessionToken]);
        }
        elseif ($userId !== null) {
            $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :uid')->execute([':uid' => $userId]);
        }
        elseif ($sessionToken !== null) {
            $pdo->prepare('DELETE FROM user_sessions WHERE session_token = :token')->execute([':token' => $sessionToken]);
        }
    }

    unset($_SESSION['session_token']);
}

function invalidate_user_session(
    string $message = 'You were logged out because your account was accessed from another location.',
    string $type = 'warning',
    string $path = '/login'
): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;

    header('Location: ' . app_url($path));
    exit;
}