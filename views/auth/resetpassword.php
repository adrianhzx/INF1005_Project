<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Reset Password</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reset Password</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="auth-card">
            <h1><i class="bi bi-shield-lock text-accent"></i></h1>
            <p class="text-center text-muted-ekea mb-4">Choose a new password for your account</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/resetpassword" class="ekea-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token"    value="<?php echo htmlspecialchars($token,    ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label for="password" class="form-label">New Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="password" class="form-control" id="password" name="password"
                           required aria-required="true" autocomplete="new-password" minlength="8">
                    <div class="form-text">At least 8 characters.</div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           required aria-required="true" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary-ekea w-100 mt-2">
                    <i class="bi bi-check-circle me-2"></i>Reset Password
                </button>
            </form>
        </div>
    </div>
</section>
