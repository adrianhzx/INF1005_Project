<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-slash-circle me-2"></i>Method Not Allowed</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">405</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="empty-state fade-in-up">
            <div class="empty-icon"><i class="bi bi-slash-circle"></i></div>
            <h2>This action is not allowed here</h2>
            <p class="text-muted-ekea mb-4">
                The requested URL exists, but it does not support this request method.
                Please return to a valid page and try the intended action again.
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