<?php
/**
 * Database Connection (PDO) + delight-im/auth
 * Parses db_config.ini and establishes a secure MySQL connection.
 * Also starts the session, initialises the logger, and sets up $auth.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Harden session cookies (INF1005 Security Best Practices)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Include the logger
require_once __DIR__ . '/logger.php';

$config = parse_ini_file('/var/www/private/db-config.ini');

if ($config === false) {
    ekea_log('Failed to read db_config.ini', 'CRITICAL');
    die('Error: Unable to read database configuration file.');
}

$db_host = $config['host'];
$db_name = $config['dbname'];
$db_user = $config['username'];
$db_pass = $config['password'];

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
        );
    ekea_log('Database connection established', 'DEBUG');

    // ── delight-im/auth ──
    $auth = new \Delight\Auth\Auth($pdo, null, null, false);

    // ── Single-Session Enforcement ──
    // Verify session token on every page load for logged-in users
    if (isset($_SESSION['user'], $_SESSION['session_token'])) {
        $sess_stmt = $pdo->prepare('SELECT id FROM user_sessions WHERE user_id = :uid AND session_token = :token');
        $sess_stmt->execute([':uid' => $_SESSION['user']['id'], ':token' => $_SESSION['session_token']]);
        if (!$sess_stmt->fetch()) {
            // Session token not found — user was logged out (another login or admin force-logout)
            ekea_log('Session invalidated — token mismatch', 'INFO', ['user_id' => $_SESSION['user']['id']]);
            $old_user = $_SESSION['user']['first_name'] ?? 'User';
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['flash_message'] = 'You were logged out because your account was accessed from another location.';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false ? '../' : '') . 'login.php');
            exit;
        }
        // Update last_active timestamp
        $pdo->prepare('UPDATE user_sessions SET last_active = NOW() WHERE user_id = :uid AND session_token = :token')
            ->execute([':uid' => $_SESSION['user']['id'], ':token' => $_SESSION['session_token']]);
    }
}
catch (PDOException $e) {
    ekea_log('Database connection failed: ' . $e->getMessage(), 'CRITICAL', [
        'host' => $db_host,
        'dbname' => $db_name,
        'user' => $db_user,
    ]);
    die('Database connection failed. Please check your configuration. Check logs/ekea.log for details.');
}
