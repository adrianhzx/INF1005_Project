<div class="page-header">
    <div class="container">
        <h1>Community Reviews</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reviews</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-md-4 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-chat-quote"></i></div>
                    <div class="stat-number"><?php echo (int)$stats['total']; ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
            </div>
            <div class="col-md-4 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
                    <div class="stat-number"><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '0'; ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
            <div class="col-md-4 fade-in-up">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <div class="stat-number"><?php
                        $stmt2 = $pdo->query('SELECT COUNT(DISTINCT user_id) FROM reviews');
                        echo (int)$stmt2->fetchColumn();
                    ?></div>
                    <div class="stat-label">Happy Reviewers</div>
                </div>
            </div>
        </div>

        <?php if (!empty($reviews)): ?>
            <div class="row mb-5">
                <div class="col-md-6 col-lg-4 mx-auto fade-in-up">
                    <div class="rating-chart-container">
                        <h2 class="mb-3"><i class="bi bi-pie-chart-fill me-2" style="color: var(--color-accent);"></i>Ratings Breakdown</h2>
                        <div class="chart-wrapper" style="min-height: 260px;">
                            <canvas id="ratingsBreakdown" role="img" aria-label="Doughnut chart showing the breakdown of all review ratings from 1 to 5 stars">
                                <p>Ratings breakdown chart. Data loaded from reviews.</p>
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                new Chart(document.getElementById('ratingsBreakdown'), {
                    type: 'doughnut',
                    data: {
                        labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
                        datasets: [{
                            data: [<?php echo $rating_dist[5]; ?>, <?php echo $rating_dist[4]; ?>, <?php echo $rating_dist[3]; ?>, <?php echo $rating_dist[2]; ?>, <?php echo $rating_dist[1]; ?>],
                            backgroundColor: [
                                'rgba(46, 125, 50, 0.85)',
                                'rgba(0, 77, 153, 0.85)',
                                'rgba(196, 147, 43, 0.85)',
                                'rgba(230, 81, 0, 0.85)',
                                'rgba(198, 40, 40, 0.85)'
                            ],
                            borderColor: '#FFFFFF',
                            borderWidth: 3,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '55%',
                        animation: { animateRotate: true, duration: 1200, easing: 'easeOutQuart' },
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
                            tooltip: {
                                backgroundColor: 'rgba(0, 34, 68, 0.9)',
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label: function(ctx) {
                                        var total = ctx.dataset.data.reduce(function(a,b){return a+b;}, 0);
                                        var pct = total>0 ? ((ctx.parsed / total)*100).toFixed(1) : 0;
                                        return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            });
            </script>
        <?php endif; ?>

        <h2 class="mb-4">Latest Reviews</h2>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-chat-square-text"></i></div>
                <h3>No Reviews Yet</h3>
                <p class="text-muted-ekea">Be the first to share your experience with EKEA products!</p>
                <a href="<?= BASE_URL ?>/products" class="btn btn-primary-ekea">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-lg-6 fade-in-up">
                        <div class="review-card h-100">
                            <div class="d-flex gap-3">
                                <?php $filename = basename($review['image_url']); ?>
                                <img src="<?= IMAGE_CDN_URL ?>f_auto,q_auto,w_200/ekea/<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($review['product_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                     class="cart-item-img" loading="lazy">
                                <div class="flex-grow-1">
                                    <a href="<?= BASE_URL ?>/products/<?php echo (int)$review['product_id']; ?>" class="fw-semibold text-decoration-none">
                                        <?php echo htmlspecialchars($review['product_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    <div class="star-rating mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?php echo $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-2"><?php echo htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted-ekea">
                                    <i class="bi bi-person me-1"></i>
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                                <small class="review-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>