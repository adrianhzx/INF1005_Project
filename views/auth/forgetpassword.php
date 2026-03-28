<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Forgot Password</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Forgot Password</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="auth-card">
            <h1><i class="bi bi-envelope-lock text-accent"></i></h1>
            <p class="text-center text-muted-ekea mb-4">Enter your email and we'll send you a reset link</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="forgetPasswordForm" method="POST" action="<?= BASE_URL ?>/forgetpassword" class="ekea-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($old_email, ENT_QUOTES, 'UTF-8'); ?>"
                           required aria-required="true" autocomplete="email">
                </div>

                <button type="submit" class="btn btn-primary-ekea w-100 mt-2">
                    <i class="bi bi-send me-2"></i>Send Reset Link
                </button>
            </form>

            <p class="text-center mt-3 mb-0">
                Remembered your password? <a href="<?= BASE_URL ?>/login" class="fw-semibold">Log in here</a>
            </p>
        </div>
    </div>
</section>
