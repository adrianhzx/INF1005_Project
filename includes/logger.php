<?php
/**
 * EKEA Backend Logger
 * Writes timestamped log entries to /logs/ekea.log
 * 
 * Usage:
 *   ekea_log('User logged in', 'INFO');
 *   ekea_log('Database connection failed', 'ERROR', ['host' => 'localhost']);
 * 
 * Log levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
 * Log file: ekea/logs/ekea.log
 */

// Create logs directory if it doesn't exist
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Define log file path
define('EKEA_LOG_FILE', $log_dir . '/ekea.log');

/**
 * Write a log entry.
 *
 * @param string $message  The log message
 * @param string $level    Log level: DEBUG|INFO|WARNING|ERROR|CRITICAL
 * @param array  $context  Optional associative array of extra data
 */
function ekea_log($message, $level = 'INFO', $context = [])
{
    $timestamp = date('Y-m-d H:i:s');
    $level = strtoupper($level);

    // Get the calling file and line
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller_file = isset($backtrace[0]['file']) ? basename($backtrace[0]['file']) : 'unknown';
    $caller_line = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 0;

    // Get user info if available
    $user_info = '';
    if (isset($_SESSION['user'])) {
        $user_info = ' [User: ' . $_SESSION['user']['email'] . ' (ID:' . $_SESSION['user']['id'] . ')]';
    }

    // Build log line
    $log_line = "[{$timestamp}] [{$level}] [{$caller_file}:{$caller_line}]{$user_info} {$message}";

    // Append context data if provided
    if (!empty($context)) {
        $log_line .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    }

    $log_line .= PHP_EOL;

    // Write to log file
    file_put_contents(EKEA_LOG_FILE, $log_line, FILE_APPEND | LOCK_EX);
}

/**
 * Log an exception with stack trace.
 *
 * @param Exception $e       The exception object
 * @param string    $context Optional context message
 */
function ekea_log_exception($e, $context = '')
{
    $message = ($context ? "{$context}: " : '') . $e->getMessage();
    ekea_log($message, 'ERROR', [
        'exception' => get_class($e),
        'file' => $e->getFile() . ':' . $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
}
