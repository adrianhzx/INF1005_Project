<?php
require_once 'includes/db_connect.php';

try {
    // 1. The core secure logout function
    // This destroys the Auth session variables and deletes the "Remember Me" cookie from the browser
    $auth->logOut();
    
    // 2. Clear out our custom cached profile data
    unset($_SESSION['cached_first_name']);
    
    // Optional: If you want to completely wipe their shopping cart when they log out too, 
    // you would uncomment the line below:
    // unset($_SESSION['cart']);

    // 3. Set a friendly goodbye message
    $_SESSION['flash_message'] = 'You have been safely logged out. See you next time!';
    $_SESSION['flash_type'] = 'success';

} catch (\Delight\Auth\NotLoggedInException $e) {
    // If a guest accidentally navigates to logout.php, we just ignore it
} catch (Exception $e) {
    ekea_log_exception($e, 'Unexpected error during logout');
}

// 4. Send them back to the login page
header('Location: login.php');
exit;