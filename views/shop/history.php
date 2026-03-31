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