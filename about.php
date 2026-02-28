<?php
$page_title = 'About Us';
$current_page = 'about';
require_once 'includes/db_connect.php';
require_once 'includes/auth_guard.php';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>About EKEA</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About Us</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Our Story -->
<section class="section-padding">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 fade-in-up">
                <span class="category-badge mb-3">Our Story</span>
                <h2 class="mt-3">Redefining Modern Living Since 2020</h2>
                <p class="text-muted-ekea">
                    EKEA was born from a simple belief: everyone deserves a beautiful home. 
                    Founded in Singapore, we blend Scandinavian design principles with Asian craftsmanship 
                    to create furniture that is both functional and stunning.
                </p>
                <p class="text-muted-ekea">
                    Our team of passionate designers works tirelessly to ensure every piece we create 
                    tells a story — one of quality, sustainability, and timeless elegance. From our workshop 
                    to your home, we pour care into every detail.
                </p>
                <div class="row g-3 mt-3">
                    <div class="col-4 text-center">
                        <h3 class="text-accent mb-0">500+</h3>
                        <small class="text-muted-ekea">Products</small>
                    </div>
                    <div class="col-4 text-center">
                        <h3 class="text-accent mb-0">50K+</h3>
                        <small class="text-muted-ekea">Happy Customers</small>
                    </div>
                    <div class="col-4 text-center">
                        <h3 class="text-accent mb-0">15+</h3>
                        <small class="text-muted-ekea">Countries</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 fade-in-up">
                <div class="row g-4">
                    <!-- Contact Info -->
                    <div class="col-12">
                        <div style="background: var(--color-bg-alt); border-radius: var(--border-radius-lg); padding: 2rem;">
                            <h4 class="mb-3"><i class="bi bi-building me-2 text-accent"></i>Our Headquarters</h4>
                            <p class="mb-1"><strong>EKEA Furniture Pte Ltd</strong></p>
                            <p class="text-muted-ekea mb-1"><i class="bi bi-geo-alt me-2"></i>1 Punggol Coast Road</p>
                            <p class="text-muted-ekea mb-1" style="padding-left: 1.5rem;">Singapore 828608</p>
                            <p class="text-muted-ekea mb-1"><i class="bi bi-telephone me-2"></i>+65 6123 4567</p>
                            <p class="text-muted-ekea mb-0"><i class="bi bi-envelope me-2"></i>hello@ekea.com</p>
                        </div>
                    </div>
                    <!-- Interactive Map -->
                    <div class="col-12">
                        <div id="ekea-map" style="height: 280px; border-radius: var(--border-radius-lg); overflow: hidden; border: 2px solid var(--color-border);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Leaflet CSS & JS (free, no API key) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('ekea-map').setView([1.4104, 103.9068], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    L.marker([1.4104, 103.9068]).addTo(map)
        .bindPopup('<strong>EKEA Furniture</strong><br>1 Punggol Coast Road<br>Singapore 828608')
        .openPopup();
});
</script>

<!-- Mission & Values -->
<section class="section-padding bg-light-ekea">
    <div class="container">
        <div class="section-header fade-in-up">
            <h2>Our Values</h2>
            <p>The principles that guide everything we do</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 fade-in-up">
                <div class="value-card h-100">
                    <div class="value-icon"><i class="bi bi-tree"></i></div>
                    <h5>Sustainability</h5>
                    <p class="text-muted-ekea mb-0">We use responsibly sourced materials and eco-friendly manufacturing processes to minimise our environmental impact.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 fade-in-up">
                <div class="value-card h-100">
                    <div class="value-icon"><i class="bi bi-palette"></i></div>
                    <h5>Design Excellence</h5>
                    <p class="text-muted-ekea mb-0">Our in-house design team draws inspiration from Scandinavian minimalism, creating pieces that are both beautiful and practical.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 fade-in-up">
                <div class="value-card h-100">
                    <div class="value-icon"><i class="bi bi-people"></i></div>
                    <h5>Community First</h5>
                    <p class="text-muted-ekea mb-0">We believe in building lasting relationships with our customers and the communities we serve.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="section-padding">
    <div class="container">
        <div class="section-header fade-in-up">
            <h2>Meet Our Team</h2>
            <p>The passionate people behind EKEA</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php
$team = [
    ['name' => 'Member A', 'role' => 'Project Lead & Backend Developer', 'icon' => 'bi-person-badge'],
    ['name' => 'Member B', 'role' => 'Frontend Developer & UI Designer', 'icon' => 'bi-brush'],
    ['name' => 'Member C', 'role' => 'Database Architect & Backend', 'icon' => 'bi-database'],
    ['name' => 'Member D', 'role' => 'E-Commerce & Payment Integration', 'icon' => 'bi-cart-check'],
    ['name' => 'Member E', 'role' => 'QA Testing & Security Specialist', 'icon' => 'bi-shield-lock'],
];
foreach ($team as $member):
?>
                <div class="col-lg col-md-4 col-6 fade-in-up">
                    <div class="team-card">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--color-accent-light); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="bi <?php echo $member['icon']; ?>" style="font-size: 2.5rem; color: var(--color-primary);"></i>
                        </div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                        <p class="text-muted-ekea small mb-0"><?php echo htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php
endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
