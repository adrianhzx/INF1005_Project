<?php
/**
 * Database Connection (PDO) + delight-im/auth + Zebra Session
 * Parses db_config.ini, establishes a secure MySQL connection, starts the
 * session handler, initialises the logger, and sets up $auth.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/logger.php';

$config = parse_ini_file('/var/www/private/db-config.ini');

define('IMAGE_CDN_URL', 'https://res.cloudinary.com/dadvyxeah/image/upload/');

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

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `session_data` (
            `session_id` varchar(128) NOT NULL,
            `hash` varchar(32) NOT NULL DEFAULT '',
            `session_data` blob NOT NULL,
            `session_expire` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`session_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $sessionUserColumn = strtolower((string) $pdo->query(
        "SELECT COLUMN_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'user_sessions'
           AND COLUMN_NAME = 'user_id'"
    )->fetchColumn());

    $sessionForeignKey = $pdo->query(
        "SELECT CONSTRAINT_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'user_sessions'
           AND COLUMN_NAME = 'user_id'
           AND REFERENCED_TABLE_NAME IS NOT NULL
         LIMIT 1"
    )->fetchColumn();

    if ($sessionUserColumn !== 'int(10) unsigned' || $sessionForeignKey) {
        if ($sessionForeignKey) {
            $constraintName = str_replace('`', '``', $sessionForeignKey);
            $pdo->exec("ALTER TABLE `user_sessions` DROP FOREIGN KEY `{$constraintName}`");
        }

        $pdo->exec('ALTER TABLE `user_sessions` MODIFY `user_id` int(10) UNSIGNED NOT NULL');
        ekea_log('Aligned user_sessions tracking schema', 'DEBUG');
    }

    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/Zebra_Session.php';

        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);

        $session_security_code = trim((string) ($config['session']['security_code'] ?? ''));
        if ($session_security_code === '') {
            $session_security_code = hash('sha256', __DIR__ . '|' . $db_name . '|ekea-zebra-session');
        }

        $session_table = trim((string) ($config['session']['table'] ?? 'session_data'));
        if ($session_table === '') {
            $session_table = 'session_data';
        }

        $session = new \Zebra_Session(
            $pdo,
            $session_security_code,
            0,
            false, //rmb to enable for bug fixx
            false,
            60,
            $session_table
        );

        ekea_log('Zebra session handler initialised', 'DEBUG');
    }

    $auth = new \Delight\Auth\Auth($pdo, null, null, false);

    if ($auth->isLoggedIn()) {
        $currentUserId = (int) $auth->getUserId();
        $sessionToken = $_SESSION['session_token'] ?? '';

        if ($sessionToken === '') {
            start_user_session_record($currentUserId);
            ekea_log('Bootstrapped tracked session for logged-in user', 'DEBUG', ['user_id' => $currentUserId]);
        }
        else {
            $sessStmt = $pdo->prepare('SELECT id FROM user_sessions WHERE user_id = :uid AND session_token = :token');
            $sessStmt->execute([':uid' => $currentUserId, ':token' => $sessionToken]);

            if (!$sessStmt->fetch()) {
                ekea_log('Session invalidated - token mismatch', 'INFO', ['user_id' => $currentUserId]);
                clear_user_session_record($currentUserId, $sessionToken);
                try {
                    $auth->logOut();
                }
                catch (\Throwable $e) {
                    ekea_log_exception($e, 'Tracked session logout failed');
                }
                invalidate_user_session();
            }

            $pdo->prepare(
                'UPDATE user_sessions SET last_active = NOW(), ip_address = :ip, user_agent = :ua WHERE user_id = :uid AND session_token = :token'
            )->execute([
                ':ip' => tracked_session_ip(),
                ':ua' => tracked_session_user_agent(),
                ':uid' => $currentUserId,
                ':token' => $sessionToken,
            ]);
        }
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