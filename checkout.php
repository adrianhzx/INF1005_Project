<?php
$page_title = 'Checkout';
$current_page = 'checkout';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';
require_login();

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['flash_message'] = 'Your cart is empty. Add items before checking out.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: product.php');
    exit;
}

$cart = $_SESSION['cart'];
$errors = [];

// Fetch user address
$stmt = $pdo->prepare('SELECT address FROM users WHERE id = :id');
$stmt->execute([':id' => $_SESSION['user']['id']]);
$user_data = $stmt->fetch();

// Calculate subtotal
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal >= 200 ? 0 : 15;

// Handle AJAX coupon validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_coupon'])) {
    header('Content-Type: application/json');
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

    if (empty($coupon_code)) {
        echo json_encode(['valid' => false, 'message' => 'Please enter a coupon code.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = :code AND active = 1');
    $stmt->execute([':code' => $coupon_code]);
    $coupon = $stmt->fetch();

    if ($coupon) {
        $disc = ($subtotal * $coupon['discount_percent']) / 100;
        echo json_encode([
            'valid' => true,
            'message' => $coupon['discount_percent'] . '% discount applied! You save $' . number_format($disc, 2),
            'discount' => $disc,
            'percent' => $coupon['discount_percent'],
        ]);
    }
    else {
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired coupon code.']);
    }
    exit;
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    }

    $postal_code = trim($_POST['postal_code'] ?? '');
    $unit_number = trim($_POST['unit_number'] ?? '');
    $street_address = trim($_POST['street_address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

    // Validation
    if (empty($postal_code) || !preg_match('/^\d{6}$/', $postal_code))
        $errors[] = 'Please enter a valid 6-digit Singapore postal code.';
    if (empty($unit_number))
        $errors[] = 'Unit number is required.';
    if (empty($street_address))
        $errors[] = 'Street address is required.';
    if (empty($payment_method))
        $errors[] = 'Please select a payment method.';

    // Build full shipping address
    $shipping_address = $unit_number . ', ' . $street_address . ', Singapore ' . $postal_code;

    // Validate and apply coupon
    $discount = 0;
    $discount_percent = 0;
    if (!empty($coupon_code)) {
        $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = :code AND active = 1');
        $stmt->execute([':code' => $coupon_code]);
        $coupon = $stmt->fetch();

        if ($coupon) {
            $discount_percent = $coupon['discount_percent'];
            $discount = ($subtotal * $discount_percent) / 100;
        }
        else {
            $errors[] = 'Invalid or expired coupon code.';
        }
    }

    if (empty($errors)) {
        $total = ($subtotal + $shipping) - $discount;

        // Check stock
        foreach ($cart as $item) {
            $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id');
            $stmt->execute([':id' => $item['product_id']]);
            $prod = $stmt->fetch();
            if (!$prod || $prod['stock'] < $item['quantity']) {
                $errors[] = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . ' does not have enough stock.';
                ekea_log('Stock check failed during checkout', 'WARNING', ['product_id' => $item['product_id'], 'requested' => $item['quantity'], 'available' => $prod ? $prod['stock'] : 0]);
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                ekea_log('Starting checkout transaction', 'INFO', ['user_id' => $_SESSION['user']['id'], 'total' => $total]);

                $stmt = $pdo->prepare('INSERT INTO orders (user_id, total, shipping_address, payment_method, coupon_code, discount) VALUES (:uid, :total, :addr, :pay, :coupon, :disc)');
                $stmt->execute([
                    ':uid' => $_SESSION['user']['id'],
                    ':total' => $total,
                    ':addr' => $shipping_address,
                    ':pay' => $payment_method,
                    ':coupon' => !empty($coupon_code) ? $coupon_code : null,
                    ':disc' => $discount,
                ]);
                $order_id = $pdo->lastInsertId();

                foreach ($cart as $item) {
                    $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:oid, :pid, :qty, :price)');
                    $stmt->execute([
                        ':oid' => $order_id,
                        ':pid' => $item['product_id'],
                        ':qty' => $item['quantity'],
                        ':price' => $item['price'],
                    ]);
                    $stmt = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');
                    $stmt->execute([':qty' => $item['quantity'], ':id' => $item['product_id']]);
                }

                $pdo->commit();
                ekea_log('Order placed successfully', 'INFO', ['order_id' => $order_id, 'total' => $total, 'items' => count($cart)]);

                $_SESSION['cart'] = [];
                $_SESSION['last_order_id'] = $order_id;
                header('Location: summary.php');
                exit;

            }
            catch (Exception $e) {
                $pdo->rollBack();
                ekea_log_exception($e, 'Checkout transaction failed');
                $errors[] = 'An error occurred while processing your order. Please try again.';
            }
        }
    }
}

$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-credit-card me-2"></i>Checkout</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
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
                    <?php
    endforeach; ?>
                </ul>
            </div>
        <?php
endif; ?>

        <form id="checkoutForm" method="POST" class="ekea-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="place_order" value="1">

            <div class="row g-4">
                <!-- Main Column -->
                <div class="col-lg-8">

                    <!-- 1. Order Items Preview -->
                    <div class="summary-card mb-4">
                        <h4 class="mb-3"><i class="bi bi-bag text-accent me-2"></i>Order Items (<?php echo count($cart); ?>)</h4>
                        <?php foreach ($cart as $item): ?>
                            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                                <img src="uploads/<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                     class="cart-item-img">
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <p class="mb-0 text-muted-ekea small">Qty: <?php echo (int)$item['quantity']; ?> x $<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                            </div>
                        <?php
endforeach; ?>
                        <a href="cart.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit Cart</a>
                    </div>

                    <!-- 2. Shipping Address (Postal Code + Unit Number + OneMap) -->
                    <div class="summary-card mb-4">
                        <h4 class="mb-3"><i class="bi bi-geo-alt text-accent me-2"></i>Delivery Address</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="postal_code" class="form-label">Postal Code <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="postal_code" name="postal_code"
                                           maxlength="6" pattern="\d{6}" placeholder="e.g. 828608" required aria-required="true">
                                    <button type="button" class="btn btn-dark-ekea" id="lookupPostalBtn" aria-label="Look up address">
                                        <i class="bi bi-search me-1"></i>Lookup
                                    </button>
                                </div>
                                <div class="form-text">Enter 6-digit postal code, then click search</div>
                            </div>
                            <div class="col-md-4">
                                <label for="unit_number" class="form-label">Unit Number <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control" id="unit_number" name="unit_number"
                                       placeholder="e.g. #12-345" required aria-required="true">
                            </div>
                            <div class="col-md-4">
                                <label for="building_name" class="form-label">Building Name</label>
                                <input type="text" class="form-control" id="building_name" name="building_name"
                                       placeholder="Auto-filled" readonly>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="street_address" class="form-label">Street Address <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control" id="street_address" name="street_address"
                                   placeholder="Auto-filled from postal code lookup" required aria-required="true">
                        </div>
                        <div id="postalLookupStatus" class="mt-2" style="display: none;"></div>
                    </div>

                    <!-- 3. Payment Method (Stripe Test) -->
                    <div class="summary-card mb-4">
                        <h4 class="mb-3"><i class="bi bi-credit-card text-accent me-2"></i>Payment Method</h4>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Select Payment <span class="text-danger" aria-hidden="true">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required aria-required="true">
                                <option value="">Choose payment method...</option>
                                <option value="credit_card">Credit / Debit Card (Stripe Test)</option>
                                <option value="paypal">PayPal</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <!-- Stripe Card Element (shown when credit_card is selected) -->
                        <div id="stripeCardSection" style="display: none;">
                            <label class="form-label">Card Details</label>
                            <div id="card-element" class="form-control" style="padding: 12px; min-height: 44px;"></div>
                            <div id="card-errors" class="text-danger small mt-1" role="alert"></div>
                            <div class="form-text mt-1">
                                <i class="bi bi-shield-lock me-1"></i>Test mode -- use card number <code>4242 4242 4242 4242</code>, any future expiry, any CVC.
                            </div>
                        </div>
                    </div>

                    <!-- 4. Coupon Code with Apply Button -->
                    <div class="summary-card mb-4">
                        <h4 class="mb-3"><i class="bi bi-tag text-accent me-2"></i>Discount Coupon</h4>
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <label for="coupon_code" class="form-label">Coupon Code</label>
                                <input type="text" class="form-control" id="coupon_code" name="coupon_code"
                                       placeholder="Enter coupon code (e.g. SAVE10)" style="text-transform: uppercase;">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-dark-ekea" id="applyCouponBtn">
                                    <i class="bi bi-check2 me-1"></i>Apply
                                </button>
                            </div>
                        </div>
                        <div id="couponFeedback" class="mt-2" style="display: none;"></div>
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="col-lg-4">
                    <div class="summary-card" style="position: sticky; top: 90px;">
                        <h4 class="mb-3"><i class="bi bi-receipt me-2"></i>Order Summary</h4>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span class="<?php echo $shipping === 0 ? 'text-success fw-semibold' : ''; ?>">
                                <?php echo $shipping === 0 ? 'FREE' : '$' . number_format($shipping, 2); ?>
                            </span>
                        </div>
                        <div class="summary-row" id="discountRow" style="display: none;">
                            <span>Discount</span>
                            <span class="text-success fw-semibold" id="discountAmount">-$0.00</span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span id="orderTotal">$<?php echo number_format($subtotal + $shipping, 2); ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary-ekea w-100 mt-3 btn-lg">
                            <i class="bi bi-lock me-2"></i>Place Order
                        </button>
                        <p class="text-center small text-muted-ekea mt-2 mb-0">
                            <i class="bi bi-shield-check me-1"></i>Secure checkout -- your data is encrypted
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Stripe.js (Test Mode) -->
<script src="https://js.stripe.com/v3/"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Stripe Init (Test Mode) ---
    var stripe = Stripe('pk_test_TYooMQauvdEDq54NiTphI7jx');
    var elements = stripe.elements();
    var cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#1b2a4a',
                fontFamily: 'Inter, sans-serif',
                '::placeholder': { color: '#6c7a89' },
            },
            invalid: { color: '#e74c3c' },
        }
    });

    var cardMounted = false;
    var paymentSelect = document.getElementById('payment_method');
    var stripeSection = document.getElementById('stripeCardSection');

    paymentSelect.addEventListener('change', function() {
        if (this.value === 'credit_card') {
            stripeSection.style.display = 'block';
            if (!cardMounted) {
                cardElement.mount('#card-element');
                cardMounted = true;
            }
        } else {
            stripeSection.style.display = 'none';
        }
    });

    cardElement.on('change', function(event) {
        var errEl = document.getElementById('card-errors');
        errEl.textContent = event.error ? event.error.message : '';
    });

    // --- OneMap Postal Code Lookup ---
    var lookupBtn = document.getElementById('lookupPostalBtn');
    var postalInput = document.getElementById('postal_code');
    var streetInput = document.getElementById('street_address');
    var buildingInput = document.getElementById('building_name');
    var statusEl = document.getElementById('postalLookupStatus');

    lookupBtn.addEventListener('click', function() {
        var code = postalInput.value.trim();
        if (!/^\d{6}$/.test(code)) {
            showLookupStatus('Please enter a valid 6-digit postal code.', false);
            return;
        }

        showLookupStatus('Looking up address...', null);

        fetch('https://www.onemap.gov.sg/api/common/elastic/search?searchVal=' + code + '&returnGeom=Y&getAddrDetails=Y&pageNum=1')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.found && data.found > 0) {
                    var result = data.results[0];
                    streetInput.value = result.ADDRESS || '';
                    buildingInput.value = result.BUILDING || 'N/A';
                    showLookupStatus('Address found: ' + (result.ADDRESS || ''), true);
                } else {
                    showLookupStatus('No address found for this postal code. Enter manually.', false);
                }
            })
            .catch(function() {
                showLookupStatus('Lookup service unavailable. Please enter address manually.', false);
            });
    });

    // Also trigger lookup on Enter key in postal code field
    postalInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            lookupBtn.click();
        }
    });

    function showLookupStatus(msg, success) {
        statusEl.style.display = 'block';
        if (success === null) {
            statusEl.className = 'mt-2 text-muted-ekea small';
            statusEl.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>' + msg;
        } else if (success) {
            statusEl.className = 'mt-2 text-success small';
            statusEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + msg;
        } else {
            statusEl.className = 'mt-2 text-danger small';
            statusEl.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + msg;
        }
    }

    // --- Coupon Apply via AJAX ---
    var applyCouponBtn = document.getElementById('applyCouponBtn');
    var couponInput = document.getElementById('coupon_code');
    var couponFeedback = document.getElementById('couponFeedback');
    var discountRow = document.getElementById('discountRow');
    var discountAmount = document.getElementById('discountAmount');
    var orderTotal = document.getElementById('orderTotal');
    var subtotal = <?php echo $subtotal; ?>;
    var shipping = <?php echo $shipping; ?>;

    applyCouponBtn.addEventListener('click', function() {
        var code = couponInput.value.trim();
        if (!code) {
            showCouponFeedback('Please enter a coupon code.', false);
            return;
        }

        var formData = new FormData();
        formData.append('validate_coupon', '1');
        formData.append('coupon_code', code);

        fetch('checkout.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showCouponFeedback(data.message, data.valid);
                if (data.valid) {
                    discountRow.style.display = 'flex';
                    discountAmount.textContent = '-$' + data.discount.toFixed(2);
                    var total = (subtotal + shipping) - data.discount;
                    orderTotal.textContent = '$' + total.toFixed(2);
                    couponInput.readOnly = true;
                    applyCouponBtn.disabled = true;
                    applyCouponBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Applied';
                } else {
                    discountRow.style.display = 'none';
                    orderTotal.textContent = '$' + (subtotal + shipping).toFixed(2);
                }
            })
            .catch(function() {
                showCouponFeedback('Could not validate coupon. Try again.', false);
            });
    });

    function showCouponFeedback(msg, success) {
        couponFeedback.style.display = 'block';
        couponFeedback.className = 'mt-2 small ' + (success ? 'text-success' : 'text-danger');
        couponFeedback.innerHTML = '<i class="bi bi-' + (success ? 'check-circle' : 'x-circle') + ' me-1"></i>' + msg;
    }

    // Auto-fill coupon from URL param (e.g. chatbot redirect)
    var urlParams = new URLSearchParams(window.location.search);
    var couponParam = urlParams.get('coupon');
    if (couponParam && couponInput) {
        couponInput.value = couponParam.toUpperCase();
        showCouponFeedback('Coupon code pre-filled. Click "Apply" to validate.', true);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
