<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-person me-2"></i>My Profile</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
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