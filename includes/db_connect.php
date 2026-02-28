<?php
/**
 * Database Connection (PDO)
 * Parses db_config.ini and establishes a secure MySQL connection.
 * Also starts the session and initialises the logger.
 */

// Start session FIRST — before any page logic that uses $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the logger
require_once __DIR__ . '/logger.php';

$config = parse_ini_file(__DIR__ . '/db_config.ini', true);

if ($config === false) {
    ekea_log('Failed to read db_config.ini', 'CRITICAL');
    die('Error: Unable to read database configuration file.');
}

$db_host = $config['database']['host'];
$db_name = $config['database']['dbname'];
$db_user = $config['database']['username'];
$db_pass = $config['database']['password'];

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
}
catch (PDOException $e) {
    ekea_log('Database connection failed: ' . $e->getMessage(), 'CRITICAL', [
        'host' => $db_host,
        'dbname' => $db_name,
        'user' => $db_user,
    ]);
    die('Database connection failed. Please check your configuration. Check logs/ekea.log for details.');
}
