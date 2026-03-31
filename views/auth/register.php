<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Create Account</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Register</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="auth-card">
            <h2><i class="bi bi-person-plus text-accent" aria-hidden="true"></i><span class="visually-hidden">Register</span></h2>
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

            <form id="registerForm" method="POST" action="<?= BASE_URL ?>/register" class="ekea-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($old['first_name'], ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($old['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                    </div>
                </div>

                <div class="mt-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8'); ?>"
                           required>
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
                           required minlength="8">
                    <div class="form-text">Minimum 8 characters</div>
                </div>

                <div class="mt-3">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           required>
                </div>

                <button type="submit" class="btn btn-primary-ekea w-100 mt-4">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>

            <p class="text-center mt-3 mb-0">
                Already have an account? <a href="<?= BASE_URL ?>/login" class="fw-semibold">Log in here</a>
            </p>
        </div>
    </div>
</section>