<?php
/**
 * Logout — Cleans up session record, destroys session, and redirects to homepage.
 */
require_once 'includes/db_connect.php';

// Delete session record from DB (single-session cleanup)
if (isset($_SESSION['user'], $_SESSION['session_token'])) {
    try {
        $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :uid AND session_token = :token')
            ->execute([':uid' => $_SESSION['user']['id'], ':token' => $_SESSION['session_token']]);
        ekea_log('User logged out', 'INFO', ['user_id' => $_SESSION['user']['id']]);
    } catch (Exception $e) {
        ekea_log('Failed to delete session record on logout: ' . $e->getMessage(), 'WARNING');
    }
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Redirect with flash message via a new session
session_start();
$_SESSION['flash_message'] = 'You have been logged out successfully.';
$_SESSION['flash_type'] = 'info';
header('Location: index.php');
exit;
