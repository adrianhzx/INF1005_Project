<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Welcome Back</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Login</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="auth-card">
            <h2><i class="bi bi-box-arrow-in-right text-accent" aria-hidden="true"></i><span class="visually-hidden">Login</span></h2>
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

            <form id="loginForm" method="POST" action="<?= BASE_URL ?>/login" class="ekea-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($old_email, ENT_QUOTES, 'UTF-8'); ?>"
                           required autocomplete="email">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="password" class="form-control" id="password" name="password"
                           required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary-ekea w-100 mt-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                </button>

                <div class="auth-separator">
                <span style="color: #767676;">OR</span>
            </div>

            <div class="google-login-container" style="text-align: center; margin-top: 15px;">
                <a href="<?= BASE_URL ?>/auth/google" class="btn btn-google">
                    <img src="<?= BASE_URL ?>/uploads/g-logo.png" alt="Google Logo" class="google-logo">
                    Sign in with Google
                </a>
            </div>
            </form>


            <p class="text-center mt-3 mb-0">
                Don't have an account? <a href="<?= BASE_URL ?>/register" class="fw-semibold">Register here</a>
            </p>

            <p class="text-center mt-3 mb-0">
                Forgot your password? <a href="<?= BASE_URL ?>/forgetpassword" class="fw-semibold">Reset here</a>
            </p>
        </div>
    </div>
</section>