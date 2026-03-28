<?php
$page_title = 'Manage Users';
$current_page = 'admin';
require_once '../includes/db_connect.php';
require_once '../includes/auth_guard.php';
require_admin();

$errors = [];

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    }
    else {
        $uid = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'] === 'admin' ? 'admin' : 'user';

        // Prevent demoting yourself
        if ($uid === (int)$auth->getUserId() && $new_role !== 'admin') {
            $errors[] = 'You cannot remove your own admin privileges.';
        }
        else {
            try {
                if ($new_role === 'admin') {
                    $auth->admin()->addRoleForUserById($uid, \Delight\Auth\Role::ADMIN);
                } else {
                    $auth->admin()->removeRoleForUserById($uid, \Delight\Auth\Role::ADMIN);
                }
                ekea_log('User role updated', 'INFO', ['user_id' => $uid, 'new_role' => $new_role]);
                $_SESSION['flash_message'] = 'User role updated successfully.';
                $_SESSION['flash_type'] = 'success';
            } catch (\Delight\Auth\UnknownIdException $e) {
                $_SESSION['flash_message'] = 'Unknown user ID.';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: users.php');
            exit;
        }
    }
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    }
    else {
        $uid = (int)$_POST['user_id'];

        // Prevent deleting yourself
        if ($uid === (int)$auth->getUserId()) {
            $errors[] = 'You cannot delete your own account.';
        }
        else {
            try {
                $auth->admin()->deleteUserById($uid);
                ekea_log('User deleted', 'WARNING', ['user_id' => $uid]);
                $_SESSION['flash_message'] = 'User account deleted.';
                $_SESSION['flash_type'] = 'success';
            } catch (\Delight\Auth\UnknownIdException $e) {
                $_SESSION['flash_message'] = 'Unknown user ID.';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: users.php');
            exit;
        }
    }
}

// Handle force-logout (single-session management)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_logout'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    }
    else {
        $uid = (int)$_POST['user_id'];
        $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :uid')->execute([':uid' => $uid]);
        ekea_log('Admin force-logged out user', 'WARNING', ['target_user_id' => $uid, 'admin_id' => $auth->getUserId()]);
        $_SESSION['flash_message'] = 'User session terminated. They will be logged out on their next page load.';
        $_SESSION['flash_type'] = 'success';
        header('Location: users.php');
        exit;
    }
}

// Handle delete review (admin moderation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    }
    else {
        $review_id = (int)$_POST['review_id'];
        $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = :id');
        $stmt->execute([':id' => $review_id]);
        ekea_log('Review deleted by admin', 'INFO', ['review_id' => $review_id]);
        $_SESSION['flash_message'] = 'Review deleted successfully.';
        $_SESSION['flash_type'] = 'success';
        header('Location: users.php');
        exit;
    }
}

// Fetch all users with order count
$stmt = $pdo->query('
    SELECT u.*, up.first_name, up.last_name, up.phone,
           COUNT(DISTINCT o.id) AS order_count,
           COALESCE(SUM(o.total), 0) AS total_spent,
           COUNT(DISTINCT r.id) AS review_count
    FROM users u 
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN orders o ON u.id = o.user_id 
    LEFT JOIN reviews r ON u.id = r.user_id
    GROUP BY u.id 
    ORDER BY u.registered DESC
');
$users = $stmt->fetchAll();

// Fetch active sessions for each user
$sess_stmt = $pdo->query('SELECT user_id, ip_address, last_active, user_agent FROM user_sessions');
$sessions_raw = $sess_stmt->fetchAll();
$active_sessions = [];
foreach ($sessions_raw as $s) {
    $active_sessions[$s['user_id']] = $s;
}

// Fetch all reviews for moderation tab
$stmt = $pdo->query('
    SELECT r.*, up.first_name, up.last_name, u.email, p.name AS product_name
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN user_profiles up ON u.id = up.user_id
    JOIN products p ON r.product_id = p.id
    ORDER BY r.created_at DESC
');
$all_reviews = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-people me-2"></i>Manage Users</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="admin.php">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Users</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php
    endforeach; ?>
                </ul>
            </div>
        <?php
endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-panel" type="button" role="tab" aria-controls="users-panel" aria-selected="true">
                    <i class="bi bi-people me-1"></i>All Users (<?php echo count($users); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-panel" type="button" role="tab" aria-controls="reviews-panel" aria-selected="false">
                    <i class="bi bi-chat-square-text me-1"></i>Review Moderation (<?php echo count($all_reviews); ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            <!-- Users Tab -->
            <div class="tab-pane fade show active" id="users-panel" role="tabpanel" aria-labelledby="users-tab">
                <div class="d-flex justify-content-end mb-3">
                    <a href="users.php" class="btn btn-sm btn-dark-ekea" title="Refresh session data">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr style="background: var(--color-primary-dark); color: #fff;">
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Role</th>
                                <th scope="col">Session</th>
                                <th scope="col">Orders</th>
                                <th scope="col">Spent</th>
                                <th scope="col">Joined</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u):
                                $isAdmin = ((int)$u['roles_mask'] & \Delight\Auth\Role::ADMIN) === \Delight\Auth\Role::ADMIN;
                            ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$u['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars(($u['first_name'] ?? 'Unknown') . ' ' . ($u['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><code><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td><?php echo htmlspecialchars($u['phone'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($isAdmin): ?>
                                            <span class="status-badge status-delivered">Admin</span>
                                        <?php
    else: ?>
                                            <span class="status-badge status-processing">User</span>
                                        <?php
    endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($active_sessions[$u['id']])): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="session-dot session-online" title="Active session"></span>
                                                <div>
                                                    <span class="fw-semibold text-success" style="font-size: 0.85rem;">Online</span>
                                                    <br><small class="text-muted-ekea"><?php $ip = $active_sessions[$u['id']]['ip_address']; echo htmlspecialchars($ip === '::1' ? '127.0.0.1' : $ip, ENT_QUOTES, 'UTF-8'); ?></small>
                                                    <br><small class="text-muted-ekea"><i class="bi bi-clock me-1"></i><?php echo date('H:i', strtotime($active_sessions[$u['id']]['last_active'])); ?></small>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="session-dot session-offline" title="No active session"></span>
                                                <span class="text-muted-ekea" style="font-size: 0.85rem;">Offline</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo (int)$u['order_count']; ?></td>
                                    <td>$<?php echo number_format($u['total_spent'], 2); ?></td>
                                    <td><?php echo date('d M Y', $u['registered']); ?></td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <!-- Toggle Role -->
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                                <input type="hidden" name="new_role" value="<?php echo $isAdmin ? 'user' : 'admin'; ?>">
                                                <input type="hidden" name="update_role" value="1">
                                                <button type="submit" class="btn btn-sm btn-dark-ekea" title="Toggle role to <?php echo $isAdmin ? 'User' : 'Admin'; ?>">
                                                    <i class="bi bi-<?php echo $isAdmin ? 'person' : 'shield-lock'; ?> me-1"></i><?php echo $isAdmin ? 'Demote' : 'Promote'; ?>
                                                </button>
                                            </form>
                                            <?php if ($u['id'] !== $auth->getUserId()): ?>
                                                <!-- Force Logout -->
                                                <?php if (isset($active_sessions[$u['id']])): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                                        <input type="hidden" name="force_logout" value="1">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Force logout this user">
                                                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <!-- Delete User -->
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                                    <input type="hidden" name="delete_user" value="1">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete user">
                                                        <i class="bi bi-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reviews Moderation Tab -->
            <div class="tab-pane fade" id="reviews-panel" role="tabpanel" aria-labelledby="reviews-tab">
                <?php if (empty($all_reviews)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-chat-square-text"></i></div>
                        <h3>No Reviews</h3>
                        <p class="text-muted-ekea">Reviews will appear here for moderation.</p>
                    </div>
                <?php
else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr style="background: var(--color-primary-dark); color: #fff;">
                                    <th scope="col">ID</th>
                                    <th scope="col">User</th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Rating</th>
                                    <th scope="col">Comment</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_reviews as $rev): ?>
                                    <tr>
                                        <td>#<?php echo (int)$rev['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            <br><small class="text-muted-ekea"><?php echo htmlspecialchars($rev['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($rev['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <div class="star-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi <?php echo $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                                <?php
        endfor; ?>
                                            </div>
                                        </td>
                                        <td style="max-width: 250px;"><?php echo htmlspecialchars(substr($rev['comment'], 0, 80), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen($rev['comment']) > 80 ? '...' : ''; ?></td>
                                        <td><?php echo date('d M Y', strtotime($rev['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="review_id" value="<?php echo (int)$rev['id']; ?>">
                                                <input type="hidden" name="delete_review" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete review">
                                                    <i class="bi bi-trash me-1"></i>Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
    endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php
endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
