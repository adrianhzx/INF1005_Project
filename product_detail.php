<?php
$current_page = 'products';
$use_chartjs = true;
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $_SESSION['flash_message'] = 'Invalid product.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: product.php');
    exit;
}

// Fetch product
$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = :id');
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['flash_message'] = 'Product not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: product.php');
    exit;
}

$page_title = $product['name'];

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    
    // Updated Auth Check
    if (!$auth->isLoggedIn()) {
        $_SESSION['flash_message'] = 'Please log in to add items to your cart.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: login.php');
        exit;
    }

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Invalid form submission.';
        $_SESSION['flash_type'] = 'danger';
        header("Location: product_detail.php?id={$product_id}");
        exit;
    }

    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] === $product_id) {
            $item['quantity'] += $qty;
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'image_url' => $product['image_url'],
            'quantity' => $qty,
        ];
    }

    $_SESSION['flash_message'] = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . ' added to your cart!';
    $_SESSION['flash_type'] = 'success';
    header("Location: product_detail.php?id={$product_id}");
    exit;
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    
    // Updated Auth Check
    if (!$auth->isLoggedIn()) {
        $_SESSION['flash_message'] = 'Please log in to leave a review.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: login.php');
        exit;
    }

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Invalid form submission.';
        $_SESSION['flash_type'] = 'danger';
        header("Location: product_detail.php?id={$product_id}");
        exit;
    }

    // Verify the user has purchased this product (using the library's User ID)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = :uid AND oi.product_id = :pid AND o.status = "delivered"');
    $stmt->execute([':uid' => $auth->getUserId(), ':pid' => $product_id]);
    
    if ($stmt->fetchColumn() == 0) {
        $_SESSION['flash_message'] = 'You must purchase and receive this product before leaving a review.';
        $_SESSION['flash_type'] = 'danger';
        header("Location: product_detail.php?id={$product_id}");
        exit;
    }

    $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
    $comment = trim($_POST['comment'] ?? '');

    $errors = [];
    if ($rating < 1 || $rating > 5)
        $errors[] = 'Please select a valid rating.';
    if (strlen($comment) < 10)
        $errors[] = 'Review must be at least 10 characters.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (:uid, :pid, :rating, :comment)');
        $stmt->execute([
            ':uid' => $auth->getUserId(),
            ':pid' => $product_id,
            ':rating' => $rating,
            ':comment' => $comment,
        ]);

        $_SESSION['flash_message'] = 'Thank you for your review!';
        $_SESSION['flash_type'] = 'success';
        header("Location: product_detail.php?id={$product_id}");
        exit;
    }
    else {
        $_SESSION['flash_message'] = implode(' ', $errors);
        $_SESSION['flash_type'] = 'danger';
    }
}

// Fetch reviews (Updated to JOIN with your new user_profiles table)
$stmt = $pdo->prepare('SELECT r.*, up.first_name, up.last_name FROM reviews r JOIN user_profiles up ON r.user_id = up.user_id WHERE r.product_id = :pid ORDER BY r.created_at DESC');
$stmt->execute([':pid' => $product_id]);
$reviews = $stmt->fetchAll();

// Average rating
$avg_rating = 0;
if (count($reviews) > 0) {
    $avg_rating = array_sum(array_column($reviews, 'rating')) / count($reviews);
}

// Rating distribution for chart.js
$rating_dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($reviews as $rev) {
    $r = (int)$rev['rating'];
    if (isset($rating_dist[$r])) $rating_dist[$r]++;
}

// Related products (same category, exclude current)
$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = :cat AND p.id != :id LIMIT 4');
$stmt->execute([':cat' => $product['category_id'], ':id' => $product_id]);
$related = $stmt->fetchAll();

// Check if user has purchased this product (for review gating)
$has_purchased = false;
if ($auth->isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = :uid AND oi.product_id = :pid AND o.status = "delivered"');
    $stmt->execute([':uid' => $auth->getUserId(), ':pid' => $product_id]);
    $has_purchased = $stmt->fetchColumn() > 0;
}

$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="product.php">Products</a></li>
                <li class="breadcrumb-item"><a href="product.php?category=<?php echo (int)$product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6 fade-in-up">
                <img src="uploads/<?php echo htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                     class="product-detail-img">
            </div>

            <div class="col-lg-6 product-detail-info fade-in-up">
                <span class="category-badge mb-3"><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <h1 class="mt-2"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi <?php echo $i <= round($avg_rating) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-muted-ekea">(<?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?>)</span>
                </div>

                <p class="product-detail-price mb-3">$<?php echo number_format($product['price'], 2); ?></p>

                <p class="text-muted-ekea mb-4"><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p>

                <?php if ($product['stock'] > 10): ?>
                    <p><span class="stock-badge stock-in"><i class="bi bi-check-circle me-1"></i>In Stock</span></p>
                <?php elseif ($product['stock'] > 0): ?>
                    <p><span class="stock-badge stock-low"><i class="bi bi-exclamation-circle me-1"></i>Only <?php echo (int)$product['stock']; ?> left</span></p>
                <?php else: ?>
                    <p><span class="stock-badge stock-out"><i class="bi bi-x-circle me-1"></i>Out of Stock</span></p>
                <?php endif; ?>

                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="add_to_cart" value="1">
                        <div class="d-flex align-items-end gap-3">
                            <div>
                                <label for="quantity" class="form-label fw-semibold">Quantity</label>
                                <div class="quantity-control">
                                    <button type="button" class="qty-minus" aria-label="Decrease quantity">&minus;</button>
                                    <input type="number" class="qty-input" id="quantity" name="quantity"
                                           value="1" min="1" max="<?php echo (int)$product['stock']; ?>" readonly
                                           aria-label="Quantity">
                                    <button type="button" class="qty-plus" aria-label="Increase quantity">+</button>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary-ekea btn-lg">
                                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="mt-4 pt-4 border-top">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted-ekea d-block">Category</small>
                            <strong><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted-ekea d-block">SKU</small>
                            <strong>EKEA-<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-light-ekea">
    <div class="container">
        <div class="section-header">
            <h2>Customer Reviews</h2>
            <p><?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?> for this product</p>
        </div>

        <?php if ($auth->isLoggedIn() && $has_purchased): ?>
            <div class="summary-card mb-4">
                <h5><i class="bi bi-pen me-2"></i>Write a Review</h5>
                <form id="reviewForm" method="POST" class="ekea-form mt-3" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="submit_review" value="1">
                    <input type="hidden" name="rating" id="rating" value="">

                    <div class="mb-3">
                        <label class="form-label">Your Rating <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="star-selector" style="font-size: 1.5rem; cursor: pointer;">
                            <i class="bi bi-star star" data-value="1" role="button" tabindex="0" aria-label="1 star"></i>
                            <i class="bi bi-star star" data-value="2" role="button" tabindex="0" aria-label="2 stars"></i>
                            <i class="bi bi-star star" data-value="3" role="button" tabindex="0" aria-label="3 stars"></i>
                            <i class="bi bi-star star" data-value="4" role="button" tabindex="0" aria-label="4 stars"></i>
                            <i class="bi bi-star star" data-value="5" role="button" tabindex="0" aria-label="5 stars"></i>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="comment" class="form-label">Your Review <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea class="form-control" id="comment" name="comment" rows="4"
                                  placeholder="Share your experience with this product..." required aria-required="true"></textarea>
                    </div>

                    <button type="submit" class="btn btn-dark-ekea">
                        <i class="bi bi-send me-2"></i>Submit Review
                    </button>
                </form>
            </div>
        <?php elseif ($auth->isLoggedIn() && !$has_purchased): ?>
            <div class="alert alert-warning mb-4">
                <i class="bi bi-bag-check me-2"></i>
                You must purchase and receive this product before leaving a review.
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle me-2"></i>
                <a href="login.php" class="fw-semibold">Log in</a> to leave a review.
            </div>
        <?php endif; ?>

        <?php if (!empty($reviews)): ?>
            <div class="row mb-4">
                <div class="col-lg-6 mx-auto">
                    <div class="rating-chart-container">
                        <h5 class="mb-3"><i class="bi bi-bar-chart me-2 text-accent"></i>Rating Distribution</h5>
                        <div class="chart-wrapper">
                            <canvas id="ratingDistChart" role="img" aria-label="Horizontal bar chart showing the distribution of review ratings from 1 to 5 stars">
                                <p>Rating distribution chart. Data loaded from reviews.</p>
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                new Chart(document.getElementById('ratingDistChart'), {
                    type: 'bar',
                    data: {
                        labels: ['5 ★', '4 ★', '3 ★', '2 ★', '1 ★'],
                        datasets: [{
                            label: 'Reviews',
                            data: [<?php echo $rating_dist[5]; ?>, <?php echo $rating_dist[4]; ?>, <?php echo $rating_dist[3]; ?>, <?php echo $rating_dist[2]; ?>, <?php echo $rating_dist[1]; ?>],
                            backgroundColor: [
                                'rgba(46, 125, 50, 0.8)',
                                'rgba(0, 77, 153, 0.8)',
                                'rgba(196, 147, 43, 0.8)',
                                'rgba(230, 81, 0, 0.8)',
                                'rgba(198, 40, 40, 0.8)'
                            ],
                            borderColor: ['#2E7D32','#004d99','#C4932B','#E65100','#C62828'],
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 1000, easing: 'easeOutQuart' },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(0, 34, 68, 0.9)',
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label: function(ctx) {
                                        return ' ' + ctx.parsed.x + ' review' + (ctx.parsed.x !== 1 ? 's' : '');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.06)' } },
                            y: { grid: { display: false }, ticks: { font: { weight: '600', size: 14 } } }
                        }
                    }
                });
            });
            </script>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-chat-square-text"></i></div>
                <h3>No Reviews Yet</h3>
                <p class="text-muted-ekea">Be the first to review this product!</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-12">
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start flex-wrap">
                                <div>
                                    <span class="reviewer-name">
                                        <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <div class="star-rating mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?php echo $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span class="review-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                                </span>
                            </div>
                            <p class="mt-3 mb-0"><?php echo htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($related)): ?>
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2>You May Also Like</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($related as $rp): ?>
                <div class="col-lg-3 col-md-6 fade-in-up">
                    <div class="product-card">
                        <a href="product_detail.php?id=<?php echo (int)$rp['id']; ?>">
                            <div class="card-img-wrapper">
                                <img src="uploads/<?php echo htmlspecialchars($rp['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($rp['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                     loading="lazy">
                            </div>
                        </a>
                        <div class="card-body">
                            <span class="product-category"><?php echo htmlspecialchars($rp['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <a href="product_detail.php?id=<?php echo (int)$rp['id']; ?>" class="text-decoration-none">
                                <h5 class="product-name"><?php echo htmlspecialchars($rp['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                            </a>
                            <p class="product-price">$<?php echo number_format($rp['price'], 2); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>