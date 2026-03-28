<?php
$page_title = 'Register';
$current_page = 'register';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';
require_once 'includes/mailer.php';

// user logged in, go to index page
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

ekea_log('Register page accessed', 'DEBUG');

// Log errors. can have many errors to show
$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        ekea_log('Registration CSRF validation failed', 'WARNING');
        $errors[] = 'Invalid form submission. Please try again.';
    }

    // instantiate variables
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $old = ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'phone' => $phone];

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    // Confirm password validation
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Name validation
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }

    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }

    // Phone Validationn
    if (!empty($phone) && !preg_match('/^\d{8,15}$/', $phone)) {
        $errors[] = 'Phone number must be 8-15 digits.';
    }

    if (empty($errors)) {
        try {
            $userId = $auth->register($email, $password, null, function ($selector, $token) use ($email) {
                ekea_log('Registration working', 'DEBUG');
                $verify_url = 'http://localhost/ekea/emailverification.php?selector=' . urlencode($selector) . '&token=' . urlencode($token);

                // log the verification URL for debugging (remove in production)
                ekea_log("Generated verification URL: {$verify_url}", 'DEBUG');

                // using phpmail to send our send verificaiton
                $emailSender = sendVerificationEmail($email, $verify_url);
                // logging the result
                if ($emailSent) {
                    ekea_log("Verification email successfully sent to {$email}", 'INFO');
                } else {
                    ekea_log("Failed to send verification email to {$email}", 'ERROR');
                }
            });

            $stmt = $pdo->prepare('INSERT INTO user_profiles (user_id, first_name, last_name, phone) VALUES (:uid, :fn, :ln, :phone)');
            $stmt->execute([
                ':uid' => $userId,
                ':fn' => $first_name,
                ':ln' => $last_name,
                ':phone' => $phone,
            ]);

            ekea_log('User account created successfully', 'INFO', ['email' => $email, 'user_id' => $userId]);

            $_SESSION['flash_message'] = 'Account created successfully! Please log in.';
            $_SESSION['flash_type'] = 'success';
            header('Location: login.php');
            exit;

        } catch (\Delight\Auth\InvalidEmailException $e) {
            $errors[] = 'Invalid email address';
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $errors[] = 'Invalid password';
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            $errors[] = 'An account with that email already exists';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $errors[] = 'Too many requests. Please try again later.';
        } catch (PDOException $e) {
            ekea_log_exception($e, 'Failed to insert user profile data');
            $errors[] = 'An error occurred while setting up your profile. Please contact support.';
        }
    }
}
$csrf_token = generate_csrf_token();


// // Redirect if already logged in
// if (isset($_SESSION['user'])) {
//     header('Location: index.php');
//     exit;
// }

// $errors = [];
// $old = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     // CSRF check
//     if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
//         ekea_log('Registration CSRF validation failed', 'WARNING');
//         $errors[] = 'Invalid form submission. Please try again.';
//     }

//     // Sanitise inputs
//     $first_name = trim($_POST['first_name'] ?? '');
//     $last_name = trim($_POST['last_name'] ?? '');
//     $email = trim($_POST['email'] ?? '');
//     $phone = trim($_POST['phone'] ?? '');
//     $password = $_POST['password'] ?? '';
//     $confirm = $_POST['confirm_password'] ?? '';

//     $old = ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'phone' => $phone];

//     // Backend validation
//     if (empty($first_name))
//         $errors[] = 'First name is required.';
//     if (empty($last_name))
//         $errors[] = 'Last name is required.';

//     if (empty($email)) {
//         $errors[] = 'Email is required.';
//     }
//     elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//         $errors[] = 'Please enter a valid email address.';
//     }

//     if (!empty($phone) && !preg_match('/^\d{8,15}$/', $phone)) {
//         $errors[] = 'Phone number must be 8-15 digits.';
//     }

// if (empty($password)) {
//     $errors[] = 'Password is required.';
// }
// elseif (strlen($password) < 8) {
//     $errors[] = 'Password must be at least 8 characters.';
// }

// if ($password !== $confirm) {
//     $errors[] = 'Passwords do not match.';
// }

//     // Check if email exists
//     if (empty($errors)) {
//         $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
//         $stmt->execute([':email' => $email]);
//         if ($stmt->fetch()) {
//             $errors[] = 'An account with this email already exists.';
//         }
//     }

//     // Create account
//     if (empty($errors)) {
//         $hashed = password_hash($password, PASSWORD_DEFAULT);
//         ekea_log('Creating new user account', 'INFO', ['email' => $email]);

// try {
//     $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, phone) VALUES (:fn, :ln, :email, :pw, :phone)');
//     $stmt->execute([
//         ':fn' => $first_name,
//         ':ln' => $last_name,
//         ':email' => $email,
//         ':pw' => $hashed,
//         ':phone' => $phone,
//     ]);

//     ekea_log('User account created successfully', 'INFO', ['email' => $email, 'user_id' => $pdo->lastInsertId()]);

//     $_SESSION['flash_message'] = 'Account created successfully! Please log in.';
//     $_SESSION['flash_type'] = 'success';
//     header('Location: login.php');
//     exit;
// }
// catch (PDOException $e) {
//     ekea_log_exception($e, 'Failed to create user account');
//     $errors[] = 'An error occurred while creating your account. Please try again.';
// }
//     }
// }

//$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Create Account</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Register</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="auth-card">
            <h1><i class="bi bi-person-plus text-accent"></i></h1>
            <p class="text-center text-muted-ekea mb-4">Join EKEA and start shopping for premium furniture</p>

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

            <form id="registerForm" method="POST" action="register.php" class="ekea-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($old['first_name'], ENT_QUOTES, 'UTF-8'); ?>"
                               required aria-required="true">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($old['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                               required aria-required="true">
                    </div>
                </div>

                <div class="mt-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8'); ?>"
                           required aria-required="true">
                </div>

                <div class="mt-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="e.g. 91234567">
                </div>

                <div class="mt-3">
                    <label for="password" class="form-label">Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="password" class="form-control" id="password" name="password"
                           required aria-required="true" minlength="8">
                    <div class="form-text">Minimum 8 characters</div>
                </div>

                <div class="mt-3">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           required aria-required="true">
                </div>

                <button type="submit" class="btn btn-primary-ekea w-100 mt-4">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>

            <p class="text-center mt-3 mb-0">
                Already have an account? <a href="login.php" class="fw-semibold">Log in here</a>
            </p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
