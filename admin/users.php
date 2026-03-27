<?php
$page_title = 'Manage Users';
$current_page = 'admin';
require_once '../includes/db_connect.php';
require_once '../includes/auth_guard.php';

// 1. Global Admin Guard
if (!$auth->isLoggedIn() || !$auth->hasRole(\Delight\Auth\Role::ADMIN)) {
    $_SESSION['flash_message'] = 'Access denied. Administrator privileges required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../login.php');
    exit;
}

$errors = [];

// Handle role change via Auth Library
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $uid = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'] === 'admin' ? 'admin' : 'user';

        // Prevent demoting yourself
        if ($uid === $auth->getUserId() && $new_role !== 'admin') {
            $errors[] = 'You cannot remove your own admin privileges.';
        } else {
            try {
                // Use the library's built-in role management
                if ($new_role === 'admin') {
                    $auth->admin()->addRoleForUserById($uid, \Delight\Auth\Role::ADMIN);
                } else {
                    $auth->admin()->removeRoleForUserById($uid, \Delight\Auth\Role::ADMIN);
                }

                ekea_log('User role updated', 'INFO', ['user_id' => $uid, 'new_role' => $new_role]);
                $_SESSION['flash_message'] = 'User role updated successfully.';
                $_SESSION['flash_type'] = 'success';
                header('Location: admin.php');
                exit;
            } catch (\Delight\Auth\UnknownIdException $e) {
                $errors[] = 'User not found in the authentication system.';
            }
        }
    }
}

// Handle delete user via Auth Library
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $uid = (int)$_POST['user_id'];

        // Prevent deleting yourself
        if ($uid === $auth->getUserId()) {
            $errors[] = 'You cannot delete your own account.';
        } else {
            try {
                // Safely destroy the user and all their auth tokens
                $auth->admin()->deleteUserById($uid);

                // Clean up their custom profile data
                $pdo->prepare('DELETE FROM user_profiles WHERE user_id = :id')->execute([':id' => $uid]);

                ekea_log('User deleted', 'WARNING', ['user_id' => $uid]);
                $_SESSION['flash_message'] = 'User account deleted successfully.';
                $_SESSION['flash_type'] = 'success';
                header('Location: admin.php');
                exit;
            } catch (\Delight\Auth\UnknownIdException $e) {
                $errors[] = 'User not found.';
            }
        }
    }
}

// Handle delete review (admin moderation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $review_id = (int)$_POST['review_id'];
        $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = :id');
        $stmt->execute([':id' => $review_id]);
        ekea_log('Review deleted by admin', 'INFO', ['review_id' => $review_id]);
        $_SESSION['flash_message'] = 'Review deleted successfully.';
        $_SESSION['flash_type'] = 'success';
        header('Location: admin.php');
        exit;
    }
}

// 2. Fetch all users (Joined with user_profiles)
$stmt = $pdo->query('
    SELECT u.*, up.first_name, up.last_name, up.phone,
           COUNT(DISTINCT o.id) AS order_count,
           COALESCE(SUM(o.total), 0) AS total_spent
    FROM users u 
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN orders o ON u.id = o.user_id 
    GROUP BY u.id 
    ORDER BY u.id DESC
');
$users = $stmt->fetchAll();


// 3. Fetch all reviews for moderation tab (Joined with user_profiles)
$stmt = $pdo->query('
    SELECT r.*, up.first_name, up.last_name, u.email, p.name AS product_name
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN user_profiles up ON u.id = up.user_id
    JOIN products p ON r.product_id = p.id
    ORDER BY r.created_at DESC
');
$all_reviews = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-people me-2"></i>Manage Users</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Admin Dashboard</li>
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
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

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
            <div class="tab-pane fade show active" id="users-panel" role="tabpanel" aria-labelledby="users-tab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr style="background: var(--color-primary-dark); color: #fff;">
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Role</th>
                                <th scope="col">Orders</th>
                                <th scope="col">Spent</th>
                                <th scope="col">Joined</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <?php
                                    // Securely check if the user is an admin via the library's roles_mask, or fallback to the old role column just in case
                                    $is_user_admin = (isset($u['roles_mask']) && ($u['roles_mask'] & \Delight\Auth\Role::ADMIN)) || (isset($u['role']) && $u['role'] === 'admin');
                                // Handle library's UNIX timestamp format vs standard datetime
                                $join_date = isset($u['registered']) ? date('d M Y', $u['registered']) : date('d M Y', strtotime($u['created_at']));
                                ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$u['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars(($u['first_name'] ?? 'Unknown') . ' ' . ($u['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><code><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td><?php echo htmlspecialchars($u['phone'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($is_user_admin): ?>
                                            <span class="status-badge status-delivered">Admin</span>
                                        <?php else: ?>
                                            <span class="status-badge status-processing">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo (int)$u['order_count']; ?></td>
                                    <td>$<?php echo number_format($u['total_spent'], 2); ?></td>
                                    <td><?php echo $join_date; ?></td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                                <input type="hidden" name="new_role" value="<?php echo $is_user_admin ? 'user' : 'admin'; ?>">
                                                <input type="hidden" name="update_role" value="1">
                                                <button type="submit" class="btn btn-sm btn-dark-ekea" title="Toggle role to <?php echo $is_user_admin ? 'User' : 'Admin'; ?>">
                                                    <i class="bi bi-<?php echo $is_user_admin ? 'person' : 'shield-lock'; ?> me-1"></i><?php echo $is_user_admin ? 'Demote' : 'Promote'; ?>
                                                </button>
                                            </form>
                                            
                                            <?php if ($u['id'] != $auth->getUserId()): ?>
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
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="reviews-panel" role="tabpanel" aria-labelledby="reviews-tab">
                <?php if (empty($all_reviews)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-chat-square-text"></i></div>
                        <h3>No Reviews</h3>
                        <p class="text-muted-ekea">Reviews will appear here for moderation.</p>
                    </div>
                <?php else: ?>
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
                                                <?php endfor; ?>
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
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>