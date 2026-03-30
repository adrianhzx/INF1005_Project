<!-- Hero Section (Slideshow) -->
<section class="hero-section hero-fixed">
    <!-- Slideshow images -->
        <div class="hero-slide active" style="background-image: url('<?= BASE_URL ?>/uploads/spacejoy-unsplash1.jpg'); background-position: center center;"></div>
        <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>/uploads/spacejoy-unsplash2.jpg'); background-position: center center;"></div>
        <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>/uploads/spacejoy-unsplash-row1.jpg'); background-position: center center;"></div>

        <div class="hero-overlay">
        <div class="hero-content fade-in-up visible">
            <h1 class="hero-title">Elevate Your Living Space</h1>
            <p class="hero-subtitle">Discover thoughtfully designed furniture that blends Scandinavian simplicity with modern comfort.</p>
            <div class="hero-buttons">
                <a href="<?= BASE_URL ?>/products" class="btn btn-primary-ekea btn-lg">Shop Collection</a>
            </div>
        </div>
    </div>
        <!-- 3 dot indicators -->
        <div class="hero-dots">
            <button class="hero-dot active" data-index="0"></button>
            <button class="hero-dot" data-index="1"></button>
            <button class="hero-dot" data-index="2"></button>
        </div>
</section>

<div class="content-layer">

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
                    <a href="<?= BASE_URL ?>/products?category=<?php echo (int)$cat['id']; ?>" class="text-decoration-none">
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
                        <a href="<?= BASE_URL ?>/products/<?php echo (int)$product['id']; ?>">
                            <div class="card-img-wrapper">
                                <img src="<?= BASE_URL ?>/uploads/<?php echo htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                     loading="lazy">
                            </div>
                        </a>
                        <div class="card-body">
                            <span class="product-category"><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <a href="<?= BASE_URL ?>/products/<?php echo (int)$product['id']; ?>" class="text-decoration-none">
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
            <a href="<?= BASE_URL ?>/products" class="btn btn-dark-ekea btn-lg">
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

</div>