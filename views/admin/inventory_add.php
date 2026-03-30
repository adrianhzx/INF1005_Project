<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-plus-circle me-2"></i>Add New Product</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin">Admin</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/inventory">Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Product</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="summary-card">
                    <h4 class="mb-4">
                        <i class="bi bi-box-seam text-accent me-2"></i>Product Details
                    </h4>

                    <form id="inventoryForm" method="POST" enctype="multipart/form-data" class="ekea-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="save_product" value="1">

                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product Name <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control" id="product_name" name="product_name"
                                   value="<?php echo htmlspecialchars($_POST['product_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   required aria-required="true">
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger" aria-hidden="true">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required aria-required="true">
                                <option value="">Select category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>"
                                        <?php echo (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="price" class="form-label">Price ($) <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01"
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       required aria-required="true">
                            </div>
                            <div class="col-6">
                                <label for="stock" class="form-label">Stock <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0"
                                       value="<?php echo htmlspecialchars($_POST['stock'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"
                                       required aria-required="true">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                            <div class="form-text">Max 5MB. JPG, PNG, WebP, or GIF.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-ekea">
                                <i class="bi bi-plus-lg me-1"></i>Add Product
                            </button>
                            <a href="<?= BASE_URL ?>/admin/inventory" class="btn btn-outline-ekea">
                                <i class="bi bi-arrow-left me-1"></i>Back to Inventory
                            </a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>