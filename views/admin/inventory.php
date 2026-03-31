<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-box-seam me-2"></i>Inventory</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Inventory</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h2 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>All Products
                <span class="badge bg-secondary ms-1"><?php echo count($products); ?></span>
            </h2>
            <a href="<?= BASE_URL ?>/admin/inventory/add" class="btn btn-primary-ekea">
                <i class="bi bi-plus-lg me-1"></i>Add New Product
            </a>
        </div>

        <?php if ($edit_product): ?>
        <div class="summary-card mb-4">
            <h3 class="mb-3">
                <i class="bi bi-pencil-square text-accent me-2"></i>Edit Product
            </h3>
            <form id="inventoryForm" method="POST" enctype="multipart/form-data" class="ekea-form" novalidate>
                <input type="hidden" name="csrf_token"     value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="save_product"   value="1">
                <input type="hidden" name="product_id"     value="<?php echo (int)$edit_product['id']; ?>">
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_product['image_url'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="product_name" name="product_name"
                               value="<?php echo htmlspecialchars($edit_product['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>"
                                    <?php echo ($edit_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_product['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01"
                               value="<?php echo htmlspecialchars($edit_product['price'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0"
                               value="<?php echo htmlspecialchars($edit_product['stock'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="product_image" class="form-label">Replace Image</label>
                        <?php if ($edit_product['image_url'] !== 'logo.png'): ?>
                            <div class="mb-1">
                                <?php $edit_filename = basename($edit_product['image_url']); ?>
                                <img src="<?= IMAGE_CDN_URL ?>f_auto,q_auto,w_200/ekea/<?= htmlspecialchars($edit_filename, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Current image" style="height:60px;border-radius:var(--border-radius);">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                        <div class="form-text">Leave blank to keep current image. Max 5MB.</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary-ekea">
                        <i class="bi bi-check-lg me-1"></i>Update Product
                    </button>
                    <a href="<?= BASE_URL ?>/admin/inventory" class="btn btn-outline-ekea">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
                <h2>No Products Yet</h2>
                <p class="text-muted-ekea">Get started by adding your first product.</p>
                <a href="<?= BASE_URL ?>/admin/inventory/add" class="btn btn-primary-ekea mt-2">
                    <i class="bi bi-plus-lg me-1"></i>Add New Product
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr style="background: var(--color-primary-dark); color: #fff;">
                            <th scope="col">Image</th>
                            <th scope="col">Product</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price</th>
                            <th scope="col">Stock</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr <?php echo ($edit_product && $edit_product['id'] == $prod['id']) ? 'class="table-active"' : ''; ?>>
                                <td>
                                    <?php $filename = basename($prod['image_url']); ?>
                                    <img src="<?= IMAGE_CDN_URL ?>f_auto,q_auto,w_100/ekea/<?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') ?>"
                                        class="cart-item-img" loading="lazy">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <br><small class="text-muted-ekea"><?php echo htmlspecialchars(substr($prod['description'] ?? '', 0, 50), ENT_QUOTES, 'UTF-8'); ?>...</small>
                                </td>
                                <td><span class="category-badge"><?php echo htmlspecialchars($prod['category_name'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>$<?php echo number_format($prod['price'], 2); ?></td>
                                <td>
                                    <?php if ($prod['stock'] > 10): ?>
                                        <span class="stock-badge stock-in"><?php echo (int)$prod['stock']; ?></span>
                                    <?php elseif ($prod['stock'] > 0): ?>
                                        <span class="stock-badge stock-low"><?php echo (int)$prod['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="stock-badge stock-out">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?= BASE_URL ?>/admin/inventory?edit=<?php echo (int)$prod['id']; ?>"
                                           class="btn btn-sm btn-dark-ekea"
                                           aria-label="Edit <?php echo htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token"     value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="product_id"    value="<?php echo (int)$prod['id']; ?>">
                                            <input type="hidden" name="delete_product" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                                    aria-label="Delete <?php echo htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</section>