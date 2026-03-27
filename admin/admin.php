<?php
$page_title = 'Admin Dashboard';
$current_page = 'admin';
$use_chartjs = true;
require_once '../includes/db_connect.php';
require_once '../includes/auth_guard.php';

// 1. Global Admin Guard
if (!$auth->isLoggedIn() || !$auth->hasRole(\Delight\Auth\Role::ADMIN)) {
    $_SESSION['flash_message'] = 'Access denied. Administrator privileges required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../login.php');
    exit;
}

// Dashboard stats
$total_products = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$total_orders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$total_customers = $pdo->query('SELECT COUNT(DISTINCT user_id) FROM orders')->fetchColumn();
$total_revenue = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != "cancelled"')->fetchColumn();
$total_reviews = $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();

// 2. Recent orders (Updated to JOIN with user_profiles)
$stmt = $pdo->query('
    SELECT o.*, up.first_name, up.last_name 
    FROM orders o 
    LEFT JOIN user_profiles up ON o.user_id = up.user_id 
    ORDER BY o.created_at DESC 
    LIMIT 10
');
$recent_orders = $stmt->fetchAll();

require_once '../includes/header.php';
?>

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

        <div class="mb-5">
            <h3 class="chart-section-title mb-4"><i class="bi bi-graph-up"></i> Analytics Overview</h3>

            <div class="row g-4 mb-4">
                <div class="col-lg-7 fade-in-up">
                    <div class="chart-container" id="chart-revenue-category">
                        <h5><i class="bi bi-bar-chart-fill"></i> Revenue by Category</h5>
                        <div class="chart-wrapper">
                            <canvas id="revenueByCategory" role="img" aria-label="Bar chart showing revenue earned per product category">
                                <p>Bar chart displaying revenue by product category. Data loaded from database.</p>
                            </canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 fade-in-up">
                    <div class="chart-container" id="chart-order-status">
                        <h5><i class="bi bi-pie-chart-fill"></i> Order Status Distribution</h5>
                        <div class="chart-wrapper">
                            <canvas id="orderStatus" role="img" aria-label="Doughnut chart showing distribution of order statuses">
                                <p>Doughnut chart displaying order status distribution. Data loaded from database.</p>
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 fade-in-up">
                    <div class="chart-container chart-container-wide" id="chart-monthly-sales">
                        <h5><i class="bi bi-graph-up-arrow"></i> Monthly Sales Trend (Last 12 Months)</h5>
                        <div class="chart-wrapper">
                            <canvas id="monthlySales" role="img" aria-label="Line chart showing monthly sales revenue and order count over the last 12 months">
                                <p>Line chart displaying monthly sales trend. Data loaded from database.</p>
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-5 fade-in-up">
                    <div class="chart-container" id="chart-stock-levels">
                        <h5><i class="bi bi-bullseye"></i> Stock Levels by Category</h5>
                        <div class="chart-wrapper">
                            <canvas id="stockByCategory" role="img" aria-label="Polar area chart showing total stock levels per product category">
                                <p>Polar area chart displaying stock levels by category. Data loaded from database.</p>
                            </canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 fade-in-up">
                    <div class="chart-container" id="chart-top-products">
                        <h5><i class="bi bi-trophy-fill"></i> Top 5 Selling Products</h5>
                        <div class="chart-wrapper">
                            <canvas id="topProducts" role="img" aria-label="Horizontal bar chart showing the top 5 best selling products by units sold">
                                <p>Horizontal bar chart displaying top selling products. Data loaded from database.</p>
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                <a href="admin.php" class="text-decoration-none">
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

        <div class="fade-in-up">
            <h3 class="mb-3"><i class="bi bi-clock-history me-2"></i>Recent Orders</h3>
            <?php if (empty($recent_orders)): ?>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="orders.php?view=<?php echo (int)$order['id']; ?>" class="text-decoration-none text-dark-ekea">
                                            <strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars(($order['first_name'] ?? 'Unknown') . ' ' . ($order['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8')); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── EKEA colour palette ──────────────────────────────────
    var COLORS = {
        primary:      '#003366',
        primaryLight: '#004d99',
        primaryDark:  '#002244',
        accent:       '#C4932B',
        accentHover:  '#A87B1F',
        accentLight:  '#F5E6C8',
        success:      '#2E7D32',
        warning:      '#E65100',
        danger:       '#C62828',
        info:         '#01579B'
    };

    var CHART_COLORS = [
        COLORS.primary,
        COLORS.accent,
        COLORS.success,
        COLORS.info,
        COLORS.warning,
        COLORS.danger,
        COLORS.primaryLight,
        COLORS.accentHover
    ];

    var CHART_BG_COLORS = [
        'rgba(0, 51, 102, 0.8)',
        'rgba(196, 147, 43, 0.8)',
        'rgba(46, 125, 50, 0.8)',
        'rgba(1, 87, 155, 0.8)',
        'rgba(230, 81, 0, 0.8)',
        'rgba(198, 40, 40, 0.8)',
        'rgba(0, 77, 153, 0.8)',
        'rgba(168, 123, 31, 0.8)'
    ];

    // ── Default Chart.js settings ────────────────────────────
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    Chart.defaults.font.size = 13;
    Chart.defaults.color = '#555555';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 34, 68, 0.9)';
    Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.padding = 12;

    // ── Helper: fetch chart data ─────────────────────────────
    function fetchChartData(type, callback) {
        fetch('api/chart_data.php?type=' + type)
            .then(function(response) {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(callback)
            .catch(function(err) {
                console.error('Chart data error (' + type + '):', err);
            });
    }

    // ── 1. Revenue by Category — Bar Chart ───────────────────
    fetchChartData('revenue_by_category', function(data) {
        if (!data || !data.labels) return; // Prevent crash if API isn't ready
        new Chart(document.getElementById('revenueByCategory'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: data.data,
                    backgroundColor: CHART_BG_COLORS.slice(0, data.labels.length),
                    borderColor: CHART_COLORS.slice(0, data.labels.length),
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
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
                        ticks: {
                            callback: function(value) { return '$' + value.toLocaleString(); }
                        },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });

    // ── 2. Order Status Distribution — Doughnut Chart ────────
    fetchChartData('order_status', function(data) {
        if (!data || !data.labels) return;
        var statusColors = {
            'Pending':    COLORS.warning,
            'Processing': COLORS.info,
            'Shipped':    COLORS.primaryLight,
            'Delivered':  COLORS.success,
            'Cancelled':  COLORS.danger
        };

        var bgColors = data.labels.map(function(label) {
            return statusColors[label] || COLORS.primary;
        });

        new Chart(document.getElementById('orderStatus'), {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: bgColors,
                    borderColor: '#FFFFFF',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                animation: {
                    animateRotate: true,
                    duration: 1400,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var total = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    });

    // ── 3. Monthly Sales Trend — Line Chart ──────────────────
    fetchChartData('monthly_sales', function(data) {
        if (!data || !data.labels) return;
        new Chart(document.getElementById('monthlySales'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: data.revenue,
                        borderColor: COLORS.accent,
                        backgroundColor: 'rgba(196, 147, 43, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: COLORS.accent,
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: data.order_count,
                        borderColor: COLORS.primary,
                        backgroundColor: 'rgba(0, 51, 102, 0.08)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: COLORS.primary,
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                animation: {
                    duration: 1600,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { padding: 24 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.dataset.yAxisID === 'y') {
                                    return ' Revenue: $' + ctx.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                                }
                                return ' Orders: ' + ctx.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue ($)',
                            color: COLORS.accent,
                            font: { weight: '600' }
                        },
                        ticks: {
                            callback: function(value) { return '$' + value.toLocaleString(); },
                            color: COLORS.accent
                        },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Order Count',
                            color: COLORS.primary,
                            font: { weight: '600' }
                        },
                        ticks: {
                            color: COLORS.primary,
                            stepSize: 1
                        },
                        grid: { drawOnChartArea: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });

    // ── 4. Stock Levels by Category — Polar Area Chart ────────
    fetchChartData('stock_by_category', function(data) {
        if (!data || !data.labels) return;
        new Chart(document.getElementById('stockByCategory'), {
            type: 'polarArea',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: [
                        'rgba(0, 51, 102, 0.7)',
                        'rgba(196, 147, 43, 0.7)',
                        'rgba(46, 125, 50, 0.7)',
                        'rgba(1, 87, 155, 0.7)',
                        'rgba(230, 81, 0, 0.7)'
                    ],
                    borderColor: [
                        COLORS.primary,
                        COLORS.accent,
                        COLORS.success,
                        COLORS.info,
                        COLORS.warning
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateRotate: true,
                    duration: 1400,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.label + ': ' + ctx.parsed.r + ' units';
                            }
                        }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 20,
                            backdropColor: 'transparent'
                        },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    }
                }
            }
        });
    });

    // ── 5. Top 5 Selling Products — Horizontal Bar Chart ─────
    fetchChartData('top_products', function(data) {
        if (!data || !data.labels) return;
        new Chart(document.getElementById('topProducts'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Units Sold',
                    data: data.data,
                    backgroundColor: [
                        'rgba(196, 147, 43, 0.85)',
                        'rgba(0, 51, 102, 0.85)',
                        'rgba(46, 125, 50, 0.85)',
                        'rgba(1, 87, 155, 0.85)',
                        'rgba(230, 81, 0, 0.85)'
                    ],
                    borderColor: [
                        COLORS.accent,
                        COLORS.primary,
                        COLORS.success,
                        COLORS.info,
                        COLORS.warning
                    ],
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.parsed.x + ' units sold';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Units Sold',
                            font: { weight: '600' }
                        },
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { weight: '500' }
                        }
                    }
                }
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>