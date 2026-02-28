<?php
$page_title = 'Shopping Cart';
$current_page = 'cart';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';
require_login();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Invalid request.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: cart.php');
        exit;
    }

    // Update quantity
    if (isset($_POST['update_qty'])) {
        $product_id = (int)$_POST['product_id'];
        $new_qty = max(1, (int)$_POST['quantity']);

        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $product_id) {
                $item['quantity'] = $new_qty;
                break;
            }
        }
        unset($item);

        $_SESSION['flash_message'] = 'Cart updated.';
        $_SESSION['flash_type'] = 'success';
        header('Location: cart.php');
        exit;
    }

    // Remove item
    if (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($item) use ($product_id) {
            return $item['product_id'] !== $product_id;
        }));

        $_SESSION['flash_message'] = 'Item removed from cart.';
        $_SESSION['flash_type'] = 'success';
        header('Location: cart.php');
        exit;
    }

    // Clear cart
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        $_SESSION['flash_message'] = 'Cart cleared.';
        $_SESSION['flash_type'] = 'info';
        header('Location: cart.php');
        exit;
    }
}

$cart = $_SESSION['cart'] ?? [];

// Calculate totals
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-cart3 me-2"></i>Shopping Cart</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cart</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php if (empty($cart)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-cart-x"></i></div>
                <h3>Your Cart is Empty</h3>
                <p class="text-muted-ekea">Looks like you haven't added any items yet.</p>
                <a href="product.php" class="btn btn-primary-ekea">
                    <i class="bi bi-grid me-2"></i>Browse Products
                </a>
            </div>
        <?php
else: ?>
            <div class="row g-4">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="table-responsive">
                        <table class="table cart-table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Subtotal</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="uploads/<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                                 alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                 class="cart-item-img">
                                        </td>
                                        <td>
                                            <a href="product_detail.php?id=<?php echo (int)$item['product_id']; ?>" class="fw-semibold text-decoration-none">
                                                <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                                <input type="hidden" name="update_qty" value="1">
                                                <div class="quantity-control">
                                                    <button type="button" class="qty-minus" aria-label="Decrease quantity">−</button>
                                                    <input type="number" class="qty-input" name="quantity"
                                                           value="<?php echo (int)$item['quantity']; ?>" min="1"
                                                           aria-label="Quantity for <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button type="button" class="qty-plus" aria-label="Increase quantity">+</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                                <input type="hidden" name="remove_item" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Remove <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
    endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="product.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                        </a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="clear_cart" value="1">
                            <button type="submit" class="btn btn-outline-danger btn-delete-confirm">
                                <i class="bi bi-trash me-1"></i>Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h4 class="mb-3"><i class="bi bi-receipt me-2"></i>Order Summary</h4>
                        <div class="summary-row">
                            <span>Subtotal (<?php echo array_sum(array_column($cart, 'quantity')); ?> items)</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span class="text-success fw-semibold"><?php echo $subtotal >= 200 ? 'FREE' : '$15.00'; ?></span>
                        </div>
                        <?php $shipping = $subtotal >= 200 ? 0 : 15; ?>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>$<?php echo number_format($subtotal + $shipping, 2); ?></span>
                        </div>
                        <?php if ($subtotal < 200): ?>
                            <p class="small text-muted-ekea mt-2">
                                <i class="bi bi-info-circle me-1"></i>Add $<?php echo number_format(200 - $subtotal, 2); ?> more for free shipping!
                            </p>
                        <?php
    endif; ?>
                        <a href="checkout.php" class="btn btn-primary-ekea w-100 mt-3">
                            <i class="bi bi-lock me-2"></i>Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        <?php
endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
