<?php
/**
 * Common Header
 * Included at the top of every page. Contains HTML head, Bootstrap 5, and responsive navigation.
 * Set $current_page before including this file to highlight the active nav link.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Content-Security-Policy header (XSS protection)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://js.stripe.com https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https://storage.googleapis.com https://*.tile.openstreetmap.org; connect-src 'self' https://cdn.jsdelivr.net https://www.onemap.gov.sg https://api.stripe.com; frame-src https://js.stripe.com;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EKEA — Premium Scandinavian-inspired furniture for modern living. Shop sofas, beds, dining sets and more.">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . ' | EKEA' : 'EKEA — Modern Furniture'; ?></title>

    <!-- Fonts: Visby CF (self-hosted via @font-face in style.css) -->
     <link rel="preload" href="<?= BASE_URL ?>/uploads/logo.png" as="image">
    <link rel="preload" href="<?= BASE_URL ?>/fonts/VisbyRegular.otf" as="font" type="font/otf" crossorigin>
    <link rel="preload" href="<?= BASE_URL ?>/fonts/VisbyMedium.otf" as="font" type="font/otf" crossorigin>
    <link rel="preload" href="<?= BASE_URL ?>/fonts/VisbyBold.otf" as="font" type="font/otf" crossorigin>
   
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?php echo time(); ?>">
    <!-- <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.4/dist/turbo.es2017-umd.js"></script> -->
</head>
<body>

    <!-- Skip to Content (Accessibility) -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top ekea-navbar" aria-label="Main navigation">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand ekea-logo-box" href="<?= BASE_URL ?>/">
                <img src="<?= BASE_URL ?>/uploads/logo.png" alt="EKEA">
                <span class="brand-text" style="display: none;">EKEA</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Nav Links -->
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo(isset($current_page) && $current_page === 'home') ? 'active' : ''; ?>"
                           href="<?= BASE_URL ?>/">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo(isset($current_page) && $current_page === 'products') ? 'active' : ''; ?>"
                           href="<?= BASE_URL ?>/products">
                            <i class="bi bi-grid me-1"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo(isset($current_page) && $current_page === 'about') ? 'active' : ''; ?>"
                           href="<?= BASE_URL ?>/about">
                            <i class="bi bi-info-circle me-1"></i>About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo(isset($current_page) && $current_page === 'news') ? 'active' : ''; ?>"
                           href="<?= BASE_URL ?>/news">
                            <i class="bi bi-chat-quote me-1"></i>Reviews
                        </a>
                    </li>
                </ul>

                <!-- Right Side Nav -->
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <!--  check user is logged in. -->
                    <?php if ($auth->isLoggedIn()): ?>

                    <?php
                    // select first name from db
                    $stmt = $pdo->prepare('SELECT first_name FROM user_profiles WHERE user_id = :uid');
                        $stmt->execute([':uid' => $auth->getUserId()]);
                        $profile = $stmt->fetch();
                        $displayName = $profile ? $profile['first_name'] : 'User';
                        ?>
                        <!-- use php-auth to get ADMIN -->
                        <?php if ($auth->hasRole(\Delight\Auth\Role::ADMIN)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo(isset($current_page) && $current_page === 'admin') ? 'active' : ''; ?>"
                                href="<?= BASE_URL ?>/admin">
                                    <i class="bi bi-speedometer2 me-1"></i>Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>
                    
                        <li class="nav-item">
                            <a class="nav-link <?php echo(isset($current_page) && $current_page === 'cart') ? 'active' : ''; ?>"
                            href="<?= BASE_URL ?>/cart">
                                <i class="bi bi-cart3 me-1"></i>Cart
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <span class="badge bg-accent rounded-pill"><?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile"><i class="bi bi-person me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/history"><i class="bi bi-clock-history me-2"></i>Order History</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo(isset($current_page) && $current_page === 'login') ? 'active' : ''; ?>"
                            href="<?= BASE_URL ?>/login">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-accent btn-sm ms-2 px-3 <?php echo(isset($current_page) && $current_page === 'register') ? 'active' : ''; ?>"
                            href="<?= BASE_URL ?>/register">
                                <i class="bi bi-person-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Spacer for fixed navbar -->
    <div class="navbar-spacer"></div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container mt-3" aria-live="assertive" aria-atomic="true">
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info', ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php
    $icon = 'info-circle';
        if (($_SESSION['flash_type'] ?? '') === 'success') {
            $icon = 'check-circle';
        } elseif (($_SESSION['flash_type'] ?? '') === 'danger') {
            $icon = 'exclamation-triangle';
        } elseif (($_SESSION['flash_type'] ?? '') === 'warning') {
            $icon = 'exclamation-circle';
        }
echo $icon;
?> me-2"></i>
                <?php echo htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php
endif; ?>

    <!-- Main Content -->
    <main id="main-content">
