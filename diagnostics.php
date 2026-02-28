<?php
/**
 * EKEA Diagnostics Page
 * Quick health check for database, sessions, and password hashes.
 * DELETE this file in production!
 */
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';

ekea_log('Diagnostics page accessed', 'INFO');

echo '<html><head><title>EKEA Diagnostics</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">';
echo '</head><body class="p-4 bg-light">';
echo '<div class="container"><h1 class="mb-4"><i class="bi bi-tools me-2"></i>EKEA Diagnostics</h1>';

// 1. PHP Version
echo '<div class="card mb-3"><div class="card-body">';
echo '<h5><i class="bi bi-check-circle text-success me-2"></i>PHP Version</h5>';
echo '<p>PHP ' . phpversion() . '</p>';
echo '</div></div>';

// 2. Session Status
echo '<div class="card mb-3"><div class="card-body">';
echo '<h5><i class="bi bi-check-circle text-success me-2"></i>Session</h5>';
echo '<p>Session status: ' . (session_status() === PHP_SESSION_ACTIVE ? '<span class="text-success">Active</span>' : '<span class="text-danger">Inactive</span>') . '</p>';
echo '<p>Session ID: <code>' . session_id() . '</code></p>';
echo '<p>CSRF token in session: ' . (isset($_SESSION['csrf_token']) ? '<code>' . substr($_SESSION['csrf_token'], 0, 16) . '...</code>' : '<span class="text-danger">Not set</span>') . '</p>';
echo '<p>Logged in user: ' . (isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['email']) . ' (' . $_SESSION['user']['role'] . ')' : '<span class="text-danger">Not logged in</span>') . '</p>';
echo '</div></div>';

// 3. Database Connection
echo '<div class="card mb-3"><div class="card-body">';
echo '<h5>';
try {
    $pdo->query('SELECT 1');
    echo '<i class="bi bi-check-circle text-success me-2"></i>Database Connection</h5>';
    echo '<p class="text-success">Connected to <strong>' . htmlspecialchars($db_name) . '</strong> on ' . htmlspecialchars($db_host) . '</p>';
}
catch (Exception $e) {
    echo '<i class="bi bi-x-circle text-danger me-2"></i>Database Connection</h5>';
    echo '<p class="text-danger">' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</div></div>';

// 4. Tables Check
echo '<div class="card mb-3"><div class="card-body">';
echo '<h5><i class="bi bi-list-check me-2"></i>Database Tables</h5>';
$tables = ['users', 'categories', 'products', 'reviews', 'orders', 'order_items', 'coupons'];
echo '<table class="table table-sm table-bordered">';
echo '<tr><th>Table</th><th>Status</th><th>Row Count</th></tr>';
foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        echo "<tr><td><code>{$table}</code></td><td class='text-success'>OK</td><td>{$count}</td></tr>";
    }
    catch (Exception $e) {
        echo "<tr class='table-danger'><td><code>{$table}</code></td><td class='text-danger'>Missing</td><td>-</td></tr>";
    }
}
echo '</table>';
echo '</div></div>';

// 5. Password Hash Verification
echo '<div class="card mb-3"><div class="card-body">';
echo '<h5><i class="bi bi-lock me-2"></i>Password Hash Verification</h5>';
$test_users = [
    ['email' => 'admin@ekea.com', 'password' => 'Admin@123'],
    ['email' => 'manager@ekea.com', 'password' => 'Manager@123'],
    ['email' => 'john@example.com', 'password' => 'User@123'],
    ['email' => 'jane@example.com', 'password' => 'User@123'],
];
echo '<table class="table table-sm table-bordered">';
echo '<tr><th>Email</th><th>Exists?</th><th>Hash Starts With</th><th>Password Verifies?</th></tr>';
foreach ($test_users as $tu) {
    $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = :email');
    $stmt->execute([':email' => $tu['email']]);
    $row = $stmt->fetch();
    if ($row) {
        $verified = password_verify($tu['password'], $row['password']);
        $icon = $verified ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>';
        echo "<tr class='" . ($verified ? '' : 'table-danger') . "'>";
        echo "<td>{$tu['email']}</td>";
        echo "<td class='text-success'>Yes (ID: {$row['id']})</td>";
        echo "<td><code>" . substr($row['password'], 0, 25) . "...</code></td>";
        echo "<td>{$icon} " . ($verified ? 'PASS' : 'FAIL -- hash mismatch!') . "</td>";
        echo "</tr>";
    }
    else {
        echo "<tr class='table-warning'><td>{$tu['email']}</td><td class='text-danger'>Not found</td><td>-</td><td>-</td></tr>";
    }
}
echo '</table>';

// Generate fresh hashes
echo '<h6 class="mt-3">Fresh Password Hashes (for manual SQL update if needed):</h6>';
echo '<pre class="bg-dark text-light p-3 rounded">';
foreach (['Admin@123', 'Manager@123', 'User@123'] as $pass) {
    echo "{$pass}: " . password_hash($pass, PASSWORD_DEFAULT) . "\n";
}
echo '</pre>';
echo '</div></div>';

// 6. Recent Log Entries
echo '<div class="card mb-3"><div class="card-body">';
echo '<h5><i class="bi bi-journal-text me-2"></i>Recent Log Entries (last 30 lines)</h5>';
$log_file = __DIR__ . '/logs/ekea.log';
if (file_exists($log_file)) {
    $lines = file($log_file);
    $recent = array_slice($lines, -30);
    echo '<pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto; font-size: 0.8rem;">';
    foreach ($recent as $line) {
        echo htmlspecialchars($line);
    }
    echo '</pre>';
}
else {
    echo '<p class="text-muted">No log file found yet. It will be created on next page load.</p>';
}
echo '</div></div>';

echo '<div class="alert alert-warning mt-4"><strong>Security Warning:</strong> Delete <code>diagnostics.php</code> before deploying to production!</div>';
echo '</div></body></html>';
