<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-shield-lock me-2"></i>Access Forbidden</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">403</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="empty-state fade-in-up">
            <div class="empty-icon"><i class="bi bi-shield-lock"></i></div>
            <h2>You do not have permission to view this page</h2>
            <p class="text-muted-ekea mb-4">
                Access to this resource is restricted. Please return to a permitted page
                or sign in with an account that has the required access level.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?= BASE_URL ?>/" class="btn btn-primary-ekea">
                    <i class="bi bi-house-door me-2"></i>Go Home
                </a>
                <a href="<?= BASE_URL ?>/login" class="btn btn-dark-ekea">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </a>
            </div>
        </div>
    </div>
</section>