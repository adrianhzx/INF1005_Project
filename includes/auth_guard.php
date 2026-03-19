<?php
/**
 * Authentication Guard
 * Helper functions to protect pages that require login or admin access.
 */

/**
 * Require user to be logged in. Redirects to login page if not.
 */
function require_login()
{
    if (!isset($_SESSION['user'])) {
        $_SESSION['flash_message'] = 'Please log in to access this page.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: login.php');
        exit;
    }
}

/**
 * Require user to be an admin. Redirects to index if not.
 */
function require_admin()
{
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ../index.php');
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
