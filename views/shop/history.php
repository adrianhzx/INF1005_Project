<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-clock-history me-2"></i>Order History</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order History</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php if (!empty($order_detail)): ?>
            <div class="mb-4">
                <a href="<?= BASE_URL ?>/history" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Orders
                </a>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="summary-card mb-4">
                        <h2>Order #<?php echo str_pad($order_detail['id'], 5, '0', STR_PAD_LEFT); ?></h2>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order_detail['created_at'])); ?></p>
                                <p class="mb-1"><strong>Status:</strong>
                                    <span class="status-badge status-<?php echo htmlspecialchars($order_detail['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($order_detail['status'], ENT_QUOTES, 'UTF-8')); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Payment:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order_detail['payment_method'])), ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($order_detail['shipping_address'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($order_detail['payment_method'] === 'stripe_qr' && $order_detail['status'] === 'pending' && !empty($stripe_checkout_url)): ?>
                        <div class="summary-card mb-4" style="border-left: 4px solid var(--color-primary);">
                            <h3 class="mb-3"><i class="bi bi-qr-code-scan text-accent me-2"></i>Stripe PayNow QR Payment (Test Mode)</h3>
                            <p class="text-muted-ekea mb-3">This order is awaiting payment. Open Stripe PayNow Checkout and select PayNow to generate Stripe's test QR code for this order.</p>
                            <div class="text-center">
                                <canvas id="stripeHistoryQrCanvas" width="180" height="180" aria-label="Stripe payment QR code"></canvas>
                                <p class="text-muted-ekea small mt-2 mb-3">Test card: <code>4242 4242 4242 4242</code>, any future expiry, any CVC</p>
                                <a href="<?php echo htmlspecialchars($stripe_checkout_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-dark-ekea">
                                    <i class="bi bi-box-arrow-up-right me-2"></i>Open Stripe PayNow Checkout
                                </a>
                            </div>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>This test order will remain pending until the Stripe PayNow payment succeeds.
                            </div>
                        </div>
                    <?php elseif ($order_detail['payment_method'] === 'bank_transfer' && $order_detail['status'] === 'pending'): ?>
                        <div class="summary-card mb-4" style="border-left: 4px solid var(--color-primary);">
                            <h3 class="mb-3"><i class="bi bi-bank text-accent me-2"></i>Payment Pending</h3>
                            <p class="text-muted-ekea mb-3">This order is still awaiting payment. Please transfer the exact amount below and use the order number as the reference.</p>
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
                                    <p class="fw-bold text-accent">$<?php echo number_format($order_detail['total'], 2); ?></p>
                                </div>
                                <div class="col-12">
                                    <p class="mb-1"><strong>Reference:</strong></p>
                                    <p class="fw-bold text-accent fs-5">#<?php echo str_pad($order_detail['id'], 5, '0', STR_PAD_LEFT); ?></p>
                                </div>
                                <div class="col-12 text-center mt-2">
                                    <p class="mb-2"><strong>Or PayNow via QR Code:</strong></p>
                                    <img src="<?= BASE_URL ?>/uploads/paynow_qr.jpg" alt="PayNow QR Code"
                                        style="width: 180px; height: 180px; object-fit: contain; border: 1px solid #ddd; border-radius: 8px; padding: 8px;">
                                    <p class="text-muted-ekea small mt-2">Scan with your banking app</p>
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>This order will remain pending until payment is received.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr style="background: var(--color-primary-dark); color: #fff;">
                                    <th scope="col">Product</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <?php $filename = basename($item['image_url']); ?>
                                            <img src="<?= IMAGE_CDN_URL ?>f_auto,q_auto,w_200/ekea/<?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>"
                                                alt="<?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                class="cart-item-img">
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/products/<?php echo (int)$item['product_id']; ?>" class="fw-semibold text-decoration-none">
                                                <?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo (int)$item['quantity']; ?></td>
                                        <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        <?php
                            $items_total = 0;
                            foreach ($order_items as $it) {
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
                        <?php if ($order_detail['discount'] > 0): ?>
                            <div class="summary-row text-success">
                                <span>Discount (<?php echo htmlspecialchars($order_detail['coupon_code'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                <span>-$<?php echo number_format($order_detail['discount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>$<?php echo number_format($order_detail['total'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-bag-x"></i></div>
                    <h2>No Orders Yet</h2>
                    <p class="text-muted-ekea">You haven't placed any orders. Start shopping now!</p>
                    <a href="<?= BASE_URL ?>/products" class="btn btn-primary-ekea">
                        <i class="bi bi-grid me-2"></i>Browse Products
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($spending_by_month)): ?>
                    <div class="spending-chart-container fade-in-up">
                        <h2 class="mb-3"><i class="bi bi-graph-up-arrow me-2" style="color: var(--color-accent);"></i>Your Spending Trend</h2>
                        <div class="chart-wrapper">
                            <canvas id="spendingTrend" role="img" aria-label="Line chart showing your monthly spending over time">
                                <p>Spending trend chart. Data loaded from your order history.</p>
                            </canvas>
                        </div>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        new Chart(document.getElementById('spendingTrend'), {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode(array_keys($spending_by_month)); ?>,
                                datasets: [{
                                    label: 'Spending ($)',
                                    data: <?php echo json_encode(array_values($spending_by_month)); ?>,
                                    borderColor: '#C4932B',
                                    backgroundColor: 'rgba(196, 147, 43, 0.12)',
                                    fill: true,
                                    tension: 0.4,
                                    borderWidth: 3,
                                    pointBackgroundColor: '#C4932B',
                                    pointBorderColor: '#FFFFFF',
                                    pointBorderWidth: 2,
                                    pointRadius: 6,
                                    pointHoverRadius: 9
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                animation: { duration: 1200, easing: 'easeOutQuart' },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 34, 68, 0.9)',
                                        cornerRadius: 8,
                                        padding: 12,
                                        callbacks: {
                                            label: function(ctx) {
                                                return ' $' + ctx.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { callback: function(v) { return '$' + v.toLocaleString(); } },
                                        grid: { color: 'rgba(0,0,0,0.06)' }
                                    },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    });
                    </script>
                <?php endif; ?>

                <div class="row g-4">
                    <?php foreach ($orders as $order): ?>
                        <div class="col-lg-6 fade-in-up">
                            <div class="summary-card h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h2 class="mb-1">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h2>
                                        <small class="text-muted-ekea">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="status-badge status-<?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8')); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-5 fw-bold">$<?php echo number_format($order['total'], 2); ?></span>
                                    <a href="<?= BASE_URL ?>/history?id=<?php echo (int)$order['id']; ?>" class="btn btn-sm btn-dark-ekea">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php if (!empty($stripe_checkout_url)): ?>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var canvas = document.getElementById('stripeHistoryQrCanvas');
    if (canvas && window.QRCode) {
        QRCode.toCanvas(canvas, <?php echo json_encode($stripe_checkout_url); ?>, { width: 180, margin: 1 });
    }
});
</script>
<?php endif; ?>
