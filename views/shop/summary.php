<!-- Page Header -->
<div class="page-header" style="background: linear-gradient(135deg, var(--color-success), #1B5E20);">
    <div class="container">
        <h1><i class="bi bi-check-circle me-2"></i>Order Confirmed!</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order Summary</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Success Banner -->
                <div class="text-center mb-5 fade-in-up">
                    <div
                        style="width: 100px; height: 100px; background: #E8F5E9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                        <i class="bi bi-check-lg" style="font-size: 3rem; color: var(--color-success);"></i>
                    </div>
                    <h2 class="mt-3">Thank You for Your Order!</h2>
                    <p class="text-muted-ekea">Your order has been placed successfully. A confirmation email will be
                        sent to <strong><?php echo htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8'); ?></strong>.
                    </p>
                </div>

                <!-- Order Details -->
                <div class="summary-card mb-4 fade-in-up">
                    <h4 class="mb-3"><i class="bi bi-receipt text-accent me-2"></i>Order Details</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Order Number:</strong></p>
                            <p class="text-accent fw-bold fs-5">
                                #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Date:</strong></p>
                            <p><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Payment Method:</strong></p>
                            <p><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_method'])), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Status:</strong></p>
                            <p><span
                                    class="status-badge status-<?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8')); ?>
                                </span></p>
                        </div>
                        <div class="col-12">
                            <p class="mb-1"><strong>Shipping Address:</strong></p>
                            <p><?php echo htmlspecialchars($order['shipping_address'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                </div>
                <?php if ($order['payment_method'] === 'stripe_qr' && $order['status'] === 'pending' && !empty($stripe_checkout_url)): ?>
                    <div class="summary-card mb-4 fade-in-up" style="border-left: 4px solid var(--color-primary);">
                        <h4 class="mb-3"><i class="bi bi-qr-code-scan text-accent me-2"></i>Stripe PayNow QR Payment (Test Mode)</h4>
                        <p class="text-muted-ekea mb-3">Scan this QR code to open a real Stripe PayNow Checkout flow in test mode and complete payment for this order.</p>
                        <div class="text-center">
                            <canvas id="stripeSummaryQrCanvas" width="180" height="180" aria-label="Stripe payment QR code"></canvas>
                            <p class="text-muted-ekea small mt-2 mb-3">Test card: <code>4242 4242 4242 4242</code>, any future expiry, any CVC</p>
                            <a href="<?php echo htmlspecialchars($stripe_checkout_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-dark-ekea">
                                <i class="bi bi-box-arrow-up-right me-2"></i>Open Stripe PayNow Checkout
                            </a>
                        </div>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>This test order will remain pending until the Stripe PayNow payment succeeds.
                        </div>
                    </div>
                <?php elseif ($order['payment_method'] === 'bank_transfer'): ?>
                    <!-- Bank Transfer Instructions -->
                    <div class="summary-card mb-4 fade-in-up" style="border-left: 4px solid var(--color-primary);">
                        <h4 class="mb-3"><i class="bi bi-bank text-accent me-2"></i>Bank Transfer Instructions</h4>
                        <p class="text-muted-ekea mb-3">Please transfer the exact amount to the bank account below. Use your
                            order number as the reference so we can verify your payment.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Bank Name:</strong></p>
                                <p>DBS Bank</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Account Name:</strong></p>
                                <p>Ekea Pte Ltd</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Bank Account Number:</strong></p>
                                <p class="fw-bold">003-916221-9</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Amount to Transfer:</strong></p>
                                <p class="fw-bold text-accent">$
                                    <?php echo number_format($order['total'], 2); ?>
                                </p>
                            </div>
                            <div class="col-12">
                                <p class="mb-1"><strong>Reference (important!):</strong></p>
                                <p class="fw-bold text-accent fs-5">#
                                    <?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-12 text-center mt-2">
                            <p class="mb-2"><strong>Or PayNow via QR Code:</strong></p>
                            <img src="<?= BASE_URL ?>/uploads/paynow_qr.jpg" alt="PayNow QR Code"
                                style="width: 180px; height: 180px; object-fit: contain; border: 1px solid #ddd; border-radius: 8px; padding: 8px;">
                            <p class="text-muted-ekea small mt-2">Scan with your banking app</p>
                        </div>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>Your order will only be processed after payment
                            is received. Please transfer within <strong>24 hours</strong> to avoid cancellation.
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Items -->
                <div class="summary-card mb-4 fade-in-up">
                    <h4 class="mb-3"><i class="bi bi-bag text-accent me-2"></i>Items Ordered</h4>
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                            <img src="<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                class="cart-item-img">
                            <div class="flex-grow-1">
                                <strong><?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <p class="mb-0 text-muted-ekea small">Qty: <?php echo (int) $item['quantity']; ?> ×
                                    $<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                        </div>
                        <?php
                    endforeach; ?>

                    <!-- Totals -->
                    <?php
                    $items_total = 0;
                    foreach ($items as $it) {
                        $items_total += $it['price'] * $it['quantity'];
                    }
                    $ship = $items_total >= 200 ? 0 : 15;
                    ?>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($items_total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo $ship === 0 ? 'FREE' : '$' . number_format($ship, 2); ?></span>
                    </div>
                    <?php if ($order['discount'] > 0): ?>
                        <div class="summary-row text-success">
                            <span>Coupon
                                (<?php echo htmlspecialchars($order['coupon_code'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                            <span>-$<?php echo number_format($order['discount'], 2); ?></span>
                        </div>
                        <?php
                    endif; ?>
                    <div class="summary-row summary-total">
                        <span>Total Paid</span>
                        <span>$<?php echo number_format($order['total'], 2); ?></span>
                    </div>
                </div>

                <!-- Estimated Delivery -->
                <div class="summary-card mb-4 fade-in-up">
                    <div class="d-flex align-items-center gap-3">
                        <div
                            style="width: 50px; height: 50px; background: var(--color-accent-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-truck" style="font-size: 1.25rem; color: var(--color-primary);"></i>
                        </div>
                        <div>
                            <strong>Estimated Delivery</strong>
                            <p class="mb-0 text-muted-ekea"><?php echo date('d M Y', strtotime('+5 days')); ?> —
                                <?php echo date('d M Y', strtotime('+7 days')); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="text-center fade-in-up">
                    <a href="<?= BASE_URL ?>/history" class="btn btn-dark-ekea me-2">
                        <i class="bi bi-clock-history me-1"></i>View Order History
                    </a>
                    <a href="<?= BASE_URL ?>/products" class="btn btn-outline-ekea">
                        <i class="bi bi-grid me-1"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if (!empty($stripe_checkout_url)): ?>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var canvas = document.getElementById('stripeSummaryQrCanvas');
    if (canvas && window.QRCode) {
        QRCode.toCanvas(canvas, <?php echo json_encode($stripe_checkout_url); ?>, { width: 180, margin: 1 });
    }
});
</script>
<?php endif; ?>
