<?php
$page_title = 'Community Reviews';
$current_page = 'news';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';

// Fetch all reviews sorted by newest first
$stmt = $pdo->query('
    SELECT r.*, u.first_name, u.last_name, p.name AS product_name, p.id AS product_id, p.image_url
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN products p ON r.product_id = p.id 
    ORDER BY r.created_at DESC
');
$reviews = $stmt->fetchAll();

// Overall stats
$stmt = $pdo->query('SELECT COUNT(*) AS total, AVG(rating) AS avg_rating FROM reviews');
$stats = $stmt->fetch();

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Community Reviews</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reviews</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <!-- Stats Bar -->
        <div class="row g-4 mb-5">
            <div class="col-md-4 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-chat-quote"></i></div>
                    <div class="stat-number"><?php echo (int)$stats['total']; ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
            </div>
            <div class="col-md-4 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
                    <div class="stat-number"><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '0'; ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
            <div class="col-md-4 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <div class="stat-number"><?php
$stmt2 = $pdo->query('SELECT COUNT(DISTINCT user_id) FROM reviews');
echo (int)$stmt2->fetchColumn();
?></div>
                    <div class="stat-label">Happy Reviewers</div>
                </div>
            </div>
        </div>

        <h2 class="mb-4">Latest Reviews</h2>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-chat-square-text"></i></div>
                <h3>No Reviews Yet</h3>
                <p class="text-muted-ekea">Be the first to share your experience with EKEA products!</p>
                <a href="product.php" class="btn btn-primary-ekea">Browse Products</a>
            </div>
        <?php
else: ?>
            <div class="row g-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-lg-6 fade-in-up">
                        <div class="review-card h-100">
                            <div class="d-flex gap-3">
                                <img src="uploads/<?php echo htmlspecialchars($review['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($review['product_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                     class="cart-item-img" loading="lazy">
                                <div class="flex-grow-1">
                                    <a href="product_detail.php?id=<?php echo (int)$review['product_id']; ?>" class="fw-semibold text-decoration-none">
                                        <?php echo htmlspecialchars($review['product_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    <div class="star-rating mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?php echo $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                        <?php
        endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-2"><?php echo htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted-ekea">
                                    <i class="bi bi-person me-1"></i>
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                                <small class="review-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php
    endforeach; ?>
            </div>
        <?php
endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
