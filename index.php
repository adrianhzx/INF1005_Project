<?php
$page_title = 'Home';
$current_page = 'home';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';

// Fetch featured products (latest 8)
$stmt = $pdo->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 8');
$featured_products = $stmt->fetchAll();

// Fetch categories
$stmt = $pdo->query('SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id');
$categories = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-pattern"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <p class="text-uppercase letter-spacing-1 mb-3" style="color: var(--color-accent); font-weight: 600;">
                    <i class="bi bi-stars me-1"></i> Scandinavian Design
                </p>
                <h1>Furniture That Feels Like Home</h1>
                <p class="mb-4">
                    Discover EKEA's curated collection of modern, sustainable furniture. 
                    Crafted with care, designed for life.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="product.php" class="btn btn-primary-ekea btn-lg">
                        <i class="bi bi-grid me-2"></i>Shop Now
                    </a>
                    <a href="about.php" class="btn btn-outline-ekea btn-lg">
                        Learn More
                    </a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block text-center">
                <div style="padding: 3rem;">
                    <i class="bi bi-house-heart" style="font-size: 15rem; color: rgba(255,255,255,0.1);"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section-padding">
    <div class="container">
        <div class="section-header fade-in-up">
            <h2>Shop by Room</h2>
            <p>Find the perfect pieces for every space in your home</p>
        </div>
        <div class="row g-4">
            <?php
$icons = ['bi-lamp', 'bi-moon-stars', 'bi-cup-hot', 'bi-laptop', 'bi-box-seam'];
foreach ($categories as $i => $cat):
?>
                <div class="col-lg col-md-4 col-6 fade-in-up">
                    <a href="product.php?category=<?php echo (int)$cat['id']; ?>" class="text-decoration-none">
                        <div class="value-card h-100">
                            <div class="value-icon">
                                <i class="bi <?php echo $icons[$i % count($icons)]; ?>"></i>
                            </div>
                            <h5><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                            <p class="text-muted-ekea small mb-0">
                                <?php echo (int)$cat['product_count']; ?> product<?php echo $cat['product_count'] != 1 ? 's' : ''; ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php
endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="section-padding bg-light-ekea">
    <div class="container">
        <div class="section-header fade-in-up">
            <h2>Featured Collection</h2>
            <p>Our most popular pieces, chosen by our community</p>
        </div>
        <div class="row g-4">
            <?php foreach ($featured_products as $fi => $product): ?>
                <div class="<?php echo $fi < 2 ? 'col-lg-6 col-md-6' : 'col-lg-3 col-md-6'; ?> fade-in-up">
                    <div class="product-card <?php echo $fi < 2 ? 'product-card-featured' : ''; ?>">
                        <a href="product_detail.php?id=<?php echo (int)$product['id']; ?>">
                            <div class="card-img-wrapper">
                                <img src="uploads/<?php echo htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                     loading="lazy">
                            </div>
                        </a>
                        <div class="card-body">
                            <span class="product-category"><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <a href="product_detail.php?id=<?php echo (int)$product['id']; ?>" class="text-decoration-none">
                                <h5 class="product-name"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                            </a>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                        </div>
                    </div>
                </div>
            <?php
endforeach; ?>
        </div>
        <div class="text-center mt-4 fade-in-up">
            <a href="product.php" class="btn btn-dark-ekea btn-lg">
                <i class="bi bi-arrow-right me-2"></i>View All Products
            </a>
        </div>
    </div>
</section>

<!-- Why EKEA Section -->
<section class="section-padding">
    <div class="container">
        <div class="section-header fade-in-up">
            <h2>Why Choose EKEA</h2>
            <p>We're redefining what it means to furnish your home</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="value-card h-100">
                    <div class="value-icon"><i class="bi bi-award"></i></div>
                    <h5>Premium Quality</h5>
                    <p class="text-muted-ekea mb-0">Every piece is crafted from sustainably sourced materials with meticulous attention to detail.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="value-card h-100">
                    <div class="value-icon"><i class="bi bi-truck"></i></div>
                    <h5>Free Delivery</h5>
                    <p class="text-muted-ekea mb-0">Enjoy complimentary delivery on all orders above $200. Your furniture, your doorstep.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="value-card h-100">
                    <div class="value-icon"><i class="bi bi-arrow-repeat"></i></div>
                    <h5>Easy Returns</h5>
                    <p class="text-muted-ekea mb-0">30-day hassle-free returns. If it doesn't fit your space, we'll take it back.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="value-card h-100">
                    <div class="value-icon"><i class="bi bi-shield-check"></i></div>
                    <h5>5-Year Warranty</h5>
                    <p class="text-muted-ekea mb-0">We stand behind our craftsmanship with an industry-leading warranty.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="section-padding bg-light-ekea">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center fade-in-up">
                <h2>Stay Inspired</h2>
                <p class="text-muted-ekea mb-4">Subscribe to our newsletter for exclusive deals, design tips, and new arrivals.</p>
                <form class="d-flex gap-2 justify-content-center flex-wrap" onsubmit="event.preventDefault(); alert('Thank you for subscribing!');">
                    <label for="newsletter-email" class="visually-hidden">Email address</label>
                    <input type="email" class="form-control" id="newsletter-email" placeholder="Enter your email" style="max-width: 350px;" required>
                    <button type="submit" class="btn btn-primary-ekea">
                        <i class="bi bi-envelope me-1"></i>Subscribe
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
