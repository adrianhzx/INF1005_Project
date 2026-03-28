<?php
$page_title = 'Login';
$current_page = 'login';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';

// if user loggin, go to index page
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Error log. for multiple errors
$errors = [];
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        ekea_log('Login CSRF validation failed', 'WARNING');
        $errors[] = 'Invalid form submission. Please try again.';
    }

    // instantiate variables
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $old_email = $email;

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {

        // 2. Safely check if the remember box was checked using isset()
        $rememberDuration = null;
        if (isset($_POST['remember']) && $_POST['remember'] == 1) {
            $rememberDuration = (int) (60 * 60 * 24 * 365.25);
        }

        try {
            $auth->login($_POST['email'], $_POST['password'], $rememberDuration);
            ekea_log('Login successful', 'DEBUG');
            header('Location: index.php');
            exit;

        } catch (\Delight\Auth\InvalidEmailException $e) {
            $errors[] = 'Wrong email address';
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $errors[] = 'Wrong password';
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            $errors[] = 'Email not verified';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $errors[] = 'Too many requests. Please try again later.';
        } catch (Exception $e) {
            ekea_log_exception($e, 'Unexpected error during login');
            $errors[] = 'An unexpected error occurred. Please try again later.';
        }
    }

}
$csrf_token = generate_csrf_token();

// ekea_log('Login page accessed', 'DEBUG');

// // Redirect if already logged in
// if (isset($_SESSION['user'])) {
//     ekea_log('Already logged in, redirecting', 'DEBUG');
//     header('Location: index.php');
//     exit;
// }

// $errors = [];
// $old_email = '';

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     // CSRF check
//     if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
//         ekea_log('CSRF token validation failed', 'WARNING', [
//             'submitted_token' => substr($_POST['csrf_token'] ?? '', 0, 10) . '...',
//             'session_token_exists' => isset($_SESSION['csrf_token']),
//         ]);
//         $errors[] = 'Invalid form submission. Please try again.';
//     }

//     $email = trim($_POST['email'] ?? '');
//     $password = $_POST['password'] ?? '';
//     $old_email = $email;

//     // Backend validation
//     if (empty($email)) {
//         $errors[] = 'Email is required.';
//     }
//     elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//         $errors[] = 'Please enter a valid email address.';
//     }

//     if (empty($password)) {
//         $errors[] = 'Password is required.';
//     }

//     // Authenticate
//     if (empty($errors)) {
//         ekea_log('Attempting login', 'INFO', ['email' => $email]);
//         $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
//         $stmt->execute([':email' => $email]);
//         $user = $stmt->fetch();

//         if ($user && password_verify($password, $user['password'])) {
//             ekea_log('Login successful', 'INFO', ['user_id' => $user['id'], 'role' => $user['role']]);
//             // Regenerate session ID to prevent fixation
//             session_regenerate_id(true);

//             // ── Single-Session Enforcement ──
//             // Delete any existing sessions for this user (only 1 active session allowed)
//             $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :uid')->execute([':uid' => $user['id']]);

//             // Create new session token
//             $session_token = bin2hex(random_bytes(64));
//             $stmt = $pdo->prepare('INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) VALUES (:uid, :token, :ip, :ua)');
//             $stmt->execute([
//                 ':uid'   => $user['id'],
//                 ':token' => $session_token,
//                 ':ip'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
//                 ':ua'    => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500),
//             ]);
//             ekea_log('Session token created', 'INFO', ['user_id' => $user['id']]);

//             $_SESSION['user'] = [
//                 'id' => $user['id'],
//                 'first_name' => $user['first_name'],
//                 'last_name' => $user['last_name'],
//                 'email' => $user['email'],
//                 'role' => $user['role'],
//             ];
//             $_SESSION['session_token'] = $session_token;

//             $_SESSION['flash_message'] = 'Welcome back, ' . htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') . '!';
//             $_SESSION['flash_type'] = 'success';

//             // Redirect based on role
//             if ($user['role'] === 'admin') {
//                 header('Location: admin/admin.php');
//             }
//             else {
//                 header('Location: index.php');
//             }
//             exit;
//         }
//         else {
//             ekea_log('Login failed', 'WARNING', [
//                 'email' => $email,
//                 'user_found' => $user ? true : false,
//                 'password_hash_in_db' => $user ? substr($user['password'], 0, 20) . '...' : 'N/A',
//             ]);
//             $errors[] = 'Invalid email or password.';
//         }
//     }
// }

// $csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Welcome Back</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Login</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="auth-card">
            <h1><i class="bi bi-box-arrow-in-right text-accent"></i></h1>
            <p class="text-center text-muted-ekea mb-4">Log in to your EKEA account</p>

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

            <form id="loginForm" method="POST" action="login.php" class="ekea-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($old_email, ENT_QUOTES, 'UTF-8'); ?>"
                           required aria-required="true" autocomplete="email">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="password" class="form-control" id="password" name="password"
                           required aria-required="true" autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary-ekea w-100 mt-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                </button>
            </form>

            <p class="text-center mt-3 mb-0">
                Don't have an account? <a href="register.php" class="fw-semibold">Register here</a>
            </p>

            <p class="text-center mt-3 mb-0">
                Forgot your password? <a href="#" class="fw-semibold text-muted" onclick="alert('Password reset feature coming soon!'); return false;">Reset here</a>
            </p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
