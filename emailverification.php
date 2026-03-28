<?php
$page_title = 'Email Verification';
require_once 'includes/db_connect.php';
if (isset($_GET['selector']) && isset($_GET['token'])) {
    try {
        $auth->confirmEmail($_GET['selector'], $_GET['token']);
        ekea_log('Email verified successfully', 'INFO');
        $_SESSION['flash_message'] = 'Your email has been verified! You can now log in.';
        $_SESSION['flash_type'] = 'success';
        
        header('Location: login.php');
        exit;

    } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
        $error = 'Invalid verification link. Please check your email and try again.';
    } catch (\Delight\Auth\TokenExpiredException $e) {
        $error = 'This verification link has expired. Please request a new one.';
    } catch (\Delight\Auth\UserAlreadyExistsException $e) {
        $error = 'This email address is already verified.';
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $error = 'Too many requests. Please try again later.';
    } catch (Exception $e) {
        ekea_log_exception($e, 'Unexpected error during email verification');
        $error = 'An unexpected error occurred. Please try again later.';
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <a href="login.php" class="btn btn-primary">Back to Login</a>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>