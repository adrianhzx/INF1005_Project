<?php
$page_title = 'My Profile';
$current_page = 'profile';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';

// Redirect guests to login
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$errors = [];
$user_id = $auth->getUserId();

// Fetch current user data
$stmt = $pdo->prepare('SELECT * FROM user_profiles WHERE user_id = :id');
$stmt->execute([':id' => $user_id]);
$user_profile = $stmt->fetch();

// Also fetch their email from the auth system
$current_email = $auth->getEmail();

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
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!empty($phone) && !preg_match('/^\d{8,15}$/', $phone)) {
        $errors[] = 'Phone number must be 8-15 digits.';
    }

    // Passwords
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($new_password)) {
        if (empty($old_password)) {
            $errors[] = 'You must enter your current password to change it.';
        }
        if (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (empty($errors)) {
        $profile_updated = false;

        // 1. Handle Profile Data Update (Names, Phone, Address)
        try {
            // Check if profile exists, if not, insert it
            if ($user_profile) {
                $stmt = $pdo->prepare('UPDATE user_profiles SET first_name = :fn, last_name = :ln, phone = :phone, address = :addr WHERE user_id = :uid');
            } else {
                $stmt = $pdo->prepare('INSERT INTO user_profiles (user_id, first_name, last_name, phone, address) VALUES (:uid, :fn, :ln, :phone, :addr)');
            }
            $stmt->execute([
                ':fn' => $first_name,
                ':ln' => $last_name,
                ':phone' => $phone,
                ':addr' => $address,
                ':uid' => $user_id
            ]);

            // Update the cached name for the header
            $_SESSION['cached_first_name'] = $first_name;
            $profile_updated = true;

        } catch (PDOException $e) {
            ekea_log_exception($e, 'Failed to update user profile');
            $errors[] = 'Failed to update profile details.';
        }

        // 2. Handle Password Change via Library
        if (!empty($new_password) && empty($errors)) {
            try {
                $auth->changePassword($old_password, $new_password);
                $profile_updated = true;
            } catch (\Delight\Auth\NotLoggedInException $e) {
                $errors[] = 'You must be logged in to change your password.';
            } catch (\Delight\Auth\InvalidPasswordException $e) {
                $errors[] = 'Incorrect current password.';
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                $errors[] = 'Too many requests. Try again later.';
            }
        }

        // 3. Handle Admin Email Change (Optional/Advanced)
        // If they are an admin and changed the email field...
        if ($email !== $current_email && $auth->hasRole(\Delight\Auth\Role::ADMIN) && empty($errors)) {
            try {
                // Note: The library does not let users change emails without re-verification natively.
                // For an admin override, you use the admin functions:
                // 1. We must verify the email exists before attempting to change it
                $auth->admin()->changeEmailForUserById($user_id, $email);
                $profile_updated = true;
            } catch (\Delight\Auth\UnknownIdException $e) {
                $errors[] = 'Unknown user ID.';
            } catch (\Delight\Auth\UserAlreadyExistsException $e) {
                $errors[] = 'This email is already in use.';
            }
        }

        if (empty($errors) && $profile_updated) {
            $_SESSION['flash_message'] = 'Profile updated successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: profile.php');
            exit;
        }
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
                                value="<?php echo htmlspecialchars($user_profile['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                required aria-required="true">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                value="<?php echo htmlspecialchars($user_profile['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                required aria-required="true">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?php echo htmlspecialchars($current_email, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo (!$auth->hasRole(\Delight\Auth\Role::ADMIN)) ? 'readonly' : 'required aria-required="true"'; ?>>
                        <?php if (!$auth->hasRole(\Delight\Auth\Role::ADMIN)): ?>
                            <div class="form-text">Email cannot be changed. Contact an admin if you need to update it.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                            value="<?php echo htmlspecialchars($user_profile['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="mt-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user_profile['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <hr class="my-4">
                    <h5>Change Password <small class="text-muted-ekea">(optional)</small></h5>
                    
                    <div class="row g-3 mt-1 mb-2">
                        <div class="col-md-12">
                            <label for="old_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="old_password" name="old_password">
                            <div class="form-text">Required only if you are setting a new password below.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
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
