<?php
$page_title = 'My Profile';
$current_page = 'profile';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';
require_login();

$errors = [];
$user_id = $_SESSION['user']['id'];

// Fetch current user data
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    }

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($first_name))
        $errors[] = 'First name is required.';
    if (empty($last_name))
        $errors[] = 'Last name is required.';
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!empty($phone) && !preg_match('/^\d{8,15}$/', $phone)) {
        $errors[] = 'Phone number must be 8-15 digits.';
    }

    // Check email uniqueness (excluding current user) — admin only
    if (empty($errors) && $email !== $user['email'] && $_SESSION['user']['role'] === 'admin') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $stmt->execute([':email' => $email, ':id' => $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already in use by another account.';
        }
    }

    // Handle password change (optional)
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
    }

    if (empty($errors)) {
        // Non-admin users cannot change email
        $update_email = ($_SESSION['user']['role'] === 'admin') ? $email : $user['email'];

        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET first_name = :fn, last_name = :ln, email = :email, phone = :phone, address = :addr, password = :pw WHERE id = :id');
            $stmt->execute([
                ':fn' => $first_name, ':ln' => $last_name, ':email' => $update_email,
                ':phone' => $phone, ':addr' => $address, ':pw' => $hashed, ':id' => $user_id,
            ]);
        }
        else {
            $stmt = $pdo->prepare('UPDATE users SET first_name = :fn, last_name = :ln, email = :email, phone = :phone, address = :addr WHERE id = :id');
            $stmt->execute([
                ':fn' => $first_name, ':ln' => $last_name, ':email' => $update_email,
                ':phone' => $phone, ':addr' => $address, ':id' => $user_id,
            ]);
        }

        // Update session
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['last_name'] = $last_name;
        $_SESSION['user']['email'] = $email;

        $_SESSION['flash_message'] = 'Profile updated successfully!';
        $_SESSION['flash_type'] = 'success';
        header('Location: profile.php');
        exit;
    }
}

$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-person me-2"></i>My Profile</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="summary-card">
                    <h3 class="mb-4"><i class="bi bi-pencil-square text-accent me-2"></i>Edit Profile</h3>

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

                    <form id="profileForm" method="POST" class="ekea-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                       value="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                       required aria-required="true">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                       required aria-required="true">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                   <?php echo($_SESSION['user']['role'] !== 'admin') ? 'readonly' : 'required aria-required="true"'; ?>>
                            <?php if ($_SESSION['user']['role'] !== 'admin'): ?>
                                <div class="form-text">Email cannot be changed. Contact an admin if you need to update it.</div>
                            <?php
endif; ?>
                        </div>

                        <div class="mt-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="mt-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <hr class="my-4">
                        <h5>Change Password <small class="text-muted-ekea">(optional)</small></h5>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                                <div class="form-text">Leave blank to keep current password</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-ekea mt-4">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
