<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-receipt me-2"></i>Manage Orders</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Orders</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php if ($order_detail): ?>
            <div class="mb-4">
                <a href="<?= BASE_URL ?>/admin/orders" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Orders
                </a>
            </div>

            <div class="summary-card mb-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h4>Order #<?php echo str_pad($order_detail['id'], 5, '0', STR_PAD_LEFT); ?></h4>
                        <p class="mb-1"><strong>Customer:</strong> <?php echo htmlspecialchars(($order_detail['first_name'] ?? 'Unknown') . ' ' . ($order_detail['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order_detail['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order_detail['created_at'])); ?></p>
                        <p class="mb-1"><strong>Payment:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order_detail['payment_method'])), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Shipping Address:</strong></p>
                        <p class="mb-2"><?php echo htmlspecialchars($order_detail['shipping_address'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mb-1"><strong>Status:</strong>
                            <span class="status-badge status-<?php echo htmlspecialchars($order_detail['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo ucfirst(htmlspecialchars($order_detail['status'], ENT_QUOTES, 'UTF-8')); ?>
                            </span>
                        </p>
                        <?php if ($order_detail['coupon_code']): ?>
                            <p class="mb-1"><strong>Coupon:</strong> <?php echo htmlspecialchars($order_detail['coupon_code'], ENT_QUOTES, 'UTF-8'); ?> (-$<?php echo number_format($order_detail['discount'], 2); ?>)</p>
                        <?php endif; ?>
                        <p class="mb-0"><strong>Total: $<?php echo number_format($order_detail['total'], 2); ?></strong></p>
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
                                    <img src="<?= BASE_URL ?>/uploads/<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="<?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                         class="cart-item-img">
                                </td>
                                <td><?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo (int)$item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="summary-card mt-4">
                <h5><i class="bi bi-arrow-clockwise me-2"></i>Update Status</h5>
                <form method="POST" class="d-flex gap-3 align-items-end mt-3">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="order_id" value="<?php echo (int)$order_detail['id']; ?>">
                    <input type="hidden" name="update_status" value="1">
                    <div>
                        <label for="new_status" class="form-label fw-semibold">New Status</label>
                        <select class="form-select" id="new_status" name="new_status">
                            <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $order_detail['status'] === $s ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary-ekea">
                        <i class="bi bi-check-lg me-1"></i>Update
                    </button>
                </form>
            </div>

        <?php else: ?>
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-receipt"></i></div>
                    <h3>No Orders Yet</h3>
                    <p class="text-muted-ekea">Orders will appear here once customers start purchasing.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr style="background: var(--color-primary-dark); color: #fff;">
                                <th scope="col">Order #</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo htmlspecialchars(($order['first_name'] ?? 'Unknown') . ' ' . ($order['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8')); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/orders?view=<?php echo (int)$order['id']; ?>" class="btn btn-sm btn-dark-ekea"
                                           aria-label="View order <?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>