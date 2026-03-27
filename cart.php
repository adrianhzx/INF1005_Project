<?php
$page_title = 'Shopping Cart';
$current_page = 'cart';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';

// 1. Replaced require_login() with the Auth library check
if (!$auth->isLoggedIn()) {
    $_SESSION['flash_message'] = 'Please log in to view your cart.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php');
    exit;
}

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
        <?php else: ?>
            <div class="row g-4">
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
                                            <form method="POST" class="d-inline cart-update-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                                <input type="hidden" name="update_qty" value="1">
                                                <div class="quantity-control" data-auto-submit="true">
                                                    <button type="button" class="qty-minus" aria-label="Decrease quantity">&minus;</button>
                                                    <input type="number" class="qty-input" name="quantity"
                                                           value="<?php echo (int)$item['quantity']; ?>" min="1" readonly
                                                           aria-label="Quantity for <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button type="button" class="qty-plus" aria-label="Increase quantity">+</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                                        <td>
                                            <form method="POST" class="d-inline cart-remove-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                                <input type="hidden" name="remove_item" value="1">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-cart-remove"
                                                        data-item-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        aria-label="Remove <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-trash me-1"></i>Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="product.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                        </a>
                        <form method="POST" class="d-inline" id="clearCartForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="clear_cart" value="1">
                            <button type="button" class="btn btn-outline-danger" id="btnClearCart">
                                <i class="bi bi-trash me-1"></i>Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

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
                        <?php endif; ?>
                        <a href="checkout.php" class="btn btn-primary-ekea w-100 mt-3">
                            <i class="bi bi-lock me-2"></i>Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--color-primary-dark); color: #fff;">
                <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                Are you sure?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmModalAction">Yes, Remove</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var confirmModalEl = document.getElementById('confirmModal');
    if (!confirmModalEl) return;

    var pendingForm = null;
    var modal = new bootstrap.Modal(confirmModalEl);
    var modalBody = document.getElementById('confirmModalBody');
    var modalAction = document.getElementById('confirmModalAction');
    var modalLabel = document.getElementById('confirmModalLabel');

    // Remove item confirmation
    document.querySelectorAll('.btn-cart-remove').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var name = this.getAttribute('data-item-name');
            pendingForm = this.closest('form');
            modalLabel.textContent = 'Remove Item';
            modalBody.textContent = 'Remove "' + name + '" from your cart?';
            modalAction.textContent = 'Yes, Remove';
            modalAction.className = 'btn btn-danger';
            modal.show();
        });
    });

    // Clear cart confirmation
    var clearBtn = document.getElementById('btnClearCart');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            pendingForm = document.getElementById('clearCartForm');
            modalLabel.textContent = 'Clear Cart';
            modalBody.textContent = 'This will remove all items from your cart. Continue?';
            modalAction.textContent = 'Yes, Clear All';
            modalAction.className = 'btn btn-danger';
            modal.show();
        });
    }

    // Confirm action
    modalAction.addEventListener('click', function() {
        if (pendingForm) {
            pendingForm.submit();
        }
    });
});
</script>