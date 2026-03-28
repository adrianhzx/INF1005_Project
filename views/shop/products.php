<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Our Collection</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Products</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="summary-card mb-4">
                    <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>

                    <!-- Search & Sort Forms -->
                    <form method="GET" action="<?= BASE_URL ?>/products" class="mb-4">
                        <?php if ($category_filter): ?>
                            <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                        <?php
                        endif; ?>
                        
                        <div class="mb-4">
                            <label for="search" class="form-label fw-semibold">Search</label>
                            <input type="text" class="form-control mb-2" id="search" name="search"
                                   value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="Search products...">
                            <button class="btn btn-dark-ekea w-100" type="submit" aria-label="Search products">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                        </div>

                        <div class="mb-2">
                            <label for="sort" class="form-label fw-semibold">Sort by</label>
                            <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                                <option value="newest"     <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest"     <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="price_asc"  <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>Price: Low &rarr; High</option>
                                <option value="price_desc" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>Price: High &rarr; Low</option>
                                <option value="name"       <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                            </select>
                        </div>
                    </form>

                    <!-- Categories -->
                    <h6 class="fw-semibold mb-2">Categories</h6>
                    <ul class="list-unstyled">
                        <li class="mb-1">
                            <a href="<?= BASE_URL ?>/products<?php echo $search_query ? '?search=' . urlencode($search_query) : ''; ?>"
                               class="d-flex justify-content-between align-items-center py-1 <?php echo $category_filter === 0 ? 'fw-bold text-accent' : ''; ?>">
                                <span>All Products</span>
                                <span class="badge bg-secondary rounded-pill"><?php echo $total_all; ?></span>
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li class="mb-1">
                                <a href="<?= BASE_URL ?>/products?category=<?php echo (int)$cat['id']; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                                   class="d-flex justify-content-between align-items-center py-1 <?php echo $category_filter === (int)$cat['id'] ? 'fw-bold text-accent' : ''; ?>">
                                    <span><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="badge bg-secondary rounded-pill"><?php echo (int)$cat['product_count']; ?></span>
                                </a>
                            </li>
                        <?php
                        endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="col-lg-9">
                <?php
                $pagination_base = BASE_URL . '/products?';
                if ($category_filter) {
                    $pagination_base .= 'category=' . $category_filter . '&';
                }
                if ($search_query) {
                    $pagination_base .= 'search=' . urlencode($search_query) . '&';
                }
                ?>
                <!-- Count Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <p class="mb-0 text-muted-ekea">
                        Showing <strong><?php echo count($products); ?></strong> of <strong><?php echo $total; ?></strong> products
                    </p>
                </div>

                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-search"></i></div>
                        <h3>No Products Found</h3>
                        <p class="text-muted-ekea">Try adjusting your filters or search terms.</p>
                        <a href="<?= BASE_URL ?>/products" class="btn btn-primary-ekea">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-4 col-md-6 fade-in-up">
                                <div class="product-card">
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
                                        <p class="small text-muted-ekea mb-2">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 80), ENT_QUOTES, 'UTF-8'); ?>...
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                                            <?php if ($product['stock'] > 0): ?>
                                                <span class="stock-badge stock-in">In Stock</span>
                                            <?php else: ?>
                                                <span class="stock-badge stock-out">Sold Out</span>
                                            <?php
                                            endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Product pagination" class="mt-5">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $pagination_base; ?>sort=<?php echo $sort_by; ?>&page=<?php echo $page - 1; ?>"
                                       aria-label="Previous page">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $pagination_base; ?>sort=<?php echo $sort_by; ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php
                                endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $pagination_base; ?>sort=<?php echo $sort_by; ?>&page=<?php echo $page + 1; ?>"
                                       aria-label="Next page">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php
                    endif; ?>
                <?php
endif; ?>
            </div>
        </div>
    </div>
</section>