<?php
$page_title = 'Inventory';
$current_page = 'admin';
require_once '../includes/db_connect.php';
require_once '../includes/auth_guard.php';
require_admin();

$errors = [];
$edit_product = null;

// --- Handle DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $del_id = (int)$_POST['product_id'];
        $stmt = $pdo->prepare('SELECT image_url FROM products WHERE id = :id');
        $stmt->execute([':id' => $del_id]);
        $del_prod = $stmt->fetch();
        if ($del_prod && $del_prod['image_url'] !== 'default.jpg') {
            $img_path = __DIR__ . '/../uploads/' . $del_prod['image_url'];
            if (file_exists($img_path)) unlink($img_path);
        }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $del_id]);
        ekea_log('Product deleted', 'INFO', ['product_id' => $del_id]);
        $_SESSION['flash_message'] = 'Product deleted successfully.';
        $_SESSION['flash_type']    = 'success';
        header('Location: inventory.php');
        exit;
    }
}

// --- Handle EDIT SAVE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $product_id  = (int)($_POST['product_id'] ?? 0);
        $name        = trim($_POST['product_name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $stock       = (int)($_POST['stock'] ?? 0);

        if (empty($name))       $errors[] = 'Product name is required.';
        if ($category_id <= 0)  $errors[] = 'Please select a category.';
        if ($price <= 0)        $errors[] = 'Price must be greater than $0.';
        if ($stock < 0)         $errors[] = 'Stock cannot be negative.';

        $image_url = $_POST['existing_image'] ?? 'default.jpg';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['product_image']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowed)) {
                $errors[] = 'Invalid image type. Only JPG, PNG, WebP, and GIF are allowed.';
            } elseif ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Image must be under 5MB.';
            } else {
                $ext       = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $safe_name = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '_', $name)));
                $image_url = $safe_name . '_' . time() . '.' . strtolower($ext);
                $dest      = __DIR__ . '/../uploads/' . $image_url;
                if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $dest)) {
                    $errors[] = 'Failed to upload image.';
                    $image_url = $_POST['existing_image'] ?? 'default.jpg';
                }
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('UPDATE products SET name = :name, category_id = :cat, description = :desc, price = :price, stock = :stock, image_url = :img WHERE id = :id');
            $stmt->execute([
                ':name'  => $name, ':cat' => $category_id, ':desc' => $description,
                ':price' => $price, ':stock' => $stock, ':img' => $image_url, ':id' => $product_id,
            ]);
            ekea_log('Product updated', 'INFO', ['product_id' => $product_id]);
            $_SESSION['flash_message'] = 'Product updated successfully.';
            $_SESSION['flash_type']    = 'success';
            header('Location: inventory.php');
            exit;
        } else {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
            $stmt->execute([':id' => $product_id]);
            $edit_product = $stmt->fetch();
        }
    }
}

// --- Load product for editing ---
if (isset($_GET['edit']) && empty($edit_product)) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $edit_id]);
    $edit_product = $stmt->fetch();
}

// --- Fetch all products ---
$stmt     = $pdo->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC');
$products = $stmt->fetchAll();

// --- Fetch categories (needed for edit form) ---
$stmt       = $pdo->query('SELECT * FROM categories ORDER BY name');
$categories = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-box-seam me-2"></i>Inventory</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="admin.php">Admin</a></li>
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

        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>All Products
                <span class="badge bg-secondary ms-1"><?php echo count($products); ?></span>
            </h4>
            <a href="inventory_add.php" class="btn btn-primary-ekea">
                <i class="bi bi-plus-lg me-1"></i>Add New Product
            </a>
        </div>

        <?php if ($edit_product): ?>
        <!-- Inline Edit Form -->
        <div class="summary-card mb-4">
            <h5 class="mb-3">
                <i class="bi bi-pencil-square text-accent me-2"></i>Edit Product
            </h5>
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
                        <?php if ($edit_product['image_url'] !== 'default.jpg'): ?>
                            <div class="mb-1">
                                <img src="../uploads/<?php echo htmlspecialchars($edit_product['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
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
                    <a href="inventory.php" class="btn btn-outline-ekea">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Product Table -->
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
                <h3>No Products Yet</h3>
                <p class="text-muted-ekea">Get started by adding your first product.</p>
                <a href="inventory_add.php" class="btn btn-primary-ekea mt-2">
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
                                    <img src="../uploads/<?php echo htmlspecialchars($prod['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="<?php echo htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                         class="cart-item-img">
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
                                        <a href="inventory.php?edit=<?php echo (int)$prod['id']; ?>"
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

<?php require_once '../includes/footer.php'; ?>
