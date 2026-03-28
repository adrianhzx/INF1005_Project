<?php
$page_title = 'Add New Product';
$current_page = 'admin';
require_once '../includes/db_connect.php';
require_once '../includes/auth_guard.php';

// 1. Global Admin Guard
if (!$auth->isLoggedIn() || !$auth->hasRole(\Delight\Auth\Role::ADMIN)) {
    $_SESSION['flash_message'] = 'Access denied. Administrator privileges required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../login.php');
    exit;
}

$errors = [];

// --- Handle ADD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $name        = trim($_POST['product_name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $stock       = (int)($_POST['stock'] ?? 0);

        if (empty($name))       $errors[] = 'Product name is required.';
        if ($category_id <= 0)  $errors[] = 'Please select a category.';
        if ($price <= 0)        $errors[] = 'Price must be greater than $0.';
        if ($stock < 0)         $errors[] = 'Stock cannot be negative.';

        $image_url = 'logo.jpg';
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
                    $image_url = 'logo.jpg';
                }
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('INSERT INTO products (name, category_id, description, price, stock, image_url) VALUES (:name, :cat, :desc, :price, :stock, :img)');
            $stmt->execute([
                ':name'  => $name,
                ':cat'   => $category_id,
                ':desc'  => $description,
                ':price' => $price,
                ':stock' => $stock,
                ':img'   => $image_url,
            ]);
            ekea_log('Product created', 'INFO', ['product_id' => $pdo->lastInsertId()]);
            $_SESSION['flash_message'] = 'Product added successfully.';
            $_SESSION['flash_type']    = 'success';
            header('Location: inventory.php');
            exit;
        }
    }
}

// --- Fetch categories for dropdown ---
$stmt       = $pdo->query('SELECT * FROM categories ORDER BY name');
$categories = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-plus-circle me-2"></i>Add New Product</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="admin.php">Admin</a></li>
                <li class="breadcrumb-item"><a href="inventory.php">Inventory</a></li>
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
                            <a href="inventory.php" class="btn btn-outline-ekea">
                                <i class="bi bi-arrow-left me-1"></i>Back to Inventory
                            </a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>