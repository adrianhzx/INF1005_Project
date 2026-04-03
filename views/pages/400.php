<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-exclamation-circle me-2"></i>Bad Request</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">400</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="empty-state fade-in-up">
            <div class="empty-icon"><i class="bi bi-exclamation-circle"></i></div>
            <h2>The request could not be processed</h2>
            <p class="text-muted-ekea mb-4">
                The request sent to the server was invalid or incomplete.
                Please return to a valid page and try the action again.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?= BASE_URL ?>/" class="btn btn-primary-ekea">
                    <i class="bi bi-house-door me-2"></i>Go Home
                </a>
                <a href="<?= BASE_URL ?>/products" class="btn btn-dark-ekea">
                    <i class="bi bi-grid me-2"></i>Browse Products
                </a>
            </div>
        </div>
    </div>
</section>