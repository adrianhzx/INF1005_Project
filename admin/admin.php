<?php
$page_title = 'Admin Dashboard';
$current_page = 'admin';
require_once '../includes/db_connect.php';
require_once '../includes/auth_guard.php';
require_admin();

// Dashboard stats
$total_products = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$total_orders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$total_customers = $pdo->query('SELECT COUNT(DISTINCT user_id) FROM orders')->fetchColumn();
$total_revenue = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != "cancelled"')->fetchColumn();
$total_reviews = $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();

// Recent orders
$stmt = $pdo->query('
    SELECT o.*, u.first_name, u.last_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
');
$recent_orders = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Admin Dashboard</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                    <div class="stat-number"><?php echo (int)$total_products; ?></div>
                    <div class="stat-label">Products</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                    <div class="stat-number"><?php echo (int)$total_orders; ?></div>
                    <div class="stat-label">Orders</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <div class="stat-number"><?php echo (int)$total_customers; ?></div>
                    <div class="stat-label">Unique Customers</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                    <div class="stat-number">$<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-4 mb-5">
            <div class="col-md-4 fade-in-up">
                <a href="inventory.php" class="text-decoration-none">
                    <div class="summary-card d-flex align-items-center gap-3">
                        <div style="width: 60px; height: 60px; background: var(--color-accent-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-box-seam" style="font-size: 1.5rem; color: var(--color-primary);"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Manage Inventory</h5>
                            <p class="text-muted-ekea mb-0">Add, edit, or delete products</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 fade-in-up">
                <a href="orders.php" class="text-decoration-none">
                    <div class="summary-card d-flex align-items-center gap-3">
                        <div style="width: 60px; height: 60px; background: var(--color-accent-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-receipt" style="font-size: 1.5rem; color: var(--color-primary);"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Manage Orders</h5>
                            <p class="text-muted-ekea mb-0">View and update order statuses</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 fade-in-up">
                <a href="users.php" class="text-decoration-none">
                    <div class="summary-card d-flex align-items-center gap-3">
                        <div style="width: 60px; height: 60px; background: var(--color-accent-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-people" style="font-size: 1.5rem; color: var(--color-primary);"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Manage Users</h5>
                            <p class="text-muted-ekea mb-0">View and manage user accounts</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="fade-in-up">
            <h3 class="mb-3"><i class="bi bi-clock-history me-2"></i>Recent Orders</h3>
            <?php if (empty($recent_orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-receipt"></i></div>
                    <h3>No Orders Yet</h3>
                    <p class="text-muted-ekea">Orders will appear here once customers start purchasing.</p>
                </div>
            <?php
else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr style="background: var(--color-primary-dark); color: #fff;">
                                <th scope="col">Order #</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8')); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php
    endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php
endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
