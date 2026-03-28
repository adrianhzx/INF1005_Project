    </main>
    <!-- END Main Content -->

    <!-- Footer -->
    <footer class="ekea-footer mt-5">
        <div class="container">
            <div class="row g-4 py-5">
                <!-- Brand & About -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand-wrapper mb-3">
                        <img src="<?= BASE_URL ?>/uploads/logo.png" alt="EKEA" class="brand-logo"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                             style="height: 45px; object-fit: contain;">
                        <span class="brand-text h5 mb-0" style="display: none; color: var(--color-accent);">EKEA</span>
                    </div>
                    <p class="footer-text">Premium Scandinavian-inspired furniture designed for modern living. Quality craftsmanship meets timeless design.</p>
                    <div class="social-links mt-3">
                        <a href="https://www.facebook.com/SingaporeTech/" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="EKEA on Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/singaporetech/" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="EKEA on Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.linkedin.com/company/singapore-institute-of-technology" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="EKEA on LinkedIn"><i class="bi bi-linkedin"></i></a>
                        <a href="https://www.youtube.com/user/SingaporeTech" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="EKEA on YouTube"><i class="bi bi-youtube"></i></a>
                        <a href="https://www.tiktok.com/@singaporetech" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="EKEA on TikTok"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Shop</h6>
                    <ul class="footer-links list-unstyled">
                        <li><a href="<?= BASE_URL ?>/products">All Products</a></li>
                        <li><a href="<?= BASE_URL ?>/products?category=1">Living Room</a></li>
                        <li><a href="<?= BASE_URL ?>/products?category=2">Bedroom</a></li>
                        <li><a href="<?= BASE_URL ?>/products?category=3">Dining</a></li>
                        <li><a href="<?= BASE_URL ?>/products?category=4">Office</a></li>
                    </ul>
                </div>

                <!-- Company Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Company</h6>
                    <ul class="footer-links list-unstyled">
                        <li><a href="<?= BASE_URL ?>/about">About Us</a></li>
                        <li><a href="<?= BASE_URL ?>/news">Reviews</a></li>
                        <li><a href="<?= BASE_URL ?>/about#values">Our Values</a></li>
                        <li><a href="<?= BASE_URL ?>/about#team">Our Team</a></li>
                    </ul>
                </div>

                <!-- Support Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Account</h6>
                    <ul class="footer-links list-unstyled">
                        <?php if ($auth->isLoggedIn()): ?>
                            <li><a href="<?= BASE_URL ?>/profile">My Profile</a></li>
                            <li><a href="<?= BASE_URL ?>/history">Order History</a></li>
                            <li><a href="<?= BASE_URL ?>/cart">Shopping Cart</a></li>
                        <?php else: ?>
                            <li><a href="<?= BASE_URL ?>/login">Sign In</a></li>
                            <li><a href="<?= BASE_URL ?>/register">Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Contact</h6>
                    <ul class="footer-links list-unstyled">
                        <li><i class="bi bi-geo-alt me-1"></i> 1 Punggol Coast Road, Singapore 828608</li>
                        <li><i class="bi bi-telephone me-1"></i> +65 6123 4567</li>
                        <li><i class="bi bi-envelope me-1"></i> hello@ekea.com</li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> EKEA Furniture. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0 small">Built by P4-Team 12 in Singapore</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Chart.js (loaded conditionally) -->
    <?php if (!empty($use_chartjs)): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/js/main.js"></script>

    <!-- Chatbot Widget -->
    <div id="chatbot-widget">
        <button id="chatbot-toggle" class="chatbot-toggle-btn" aria-label="Open help chat">
            <i class="bi bi-chat-dots"></i>
        </button>
        <div id="chatbot-panel" class="chatbot-panel" style="display: none;" role="dialog" aria-label="EKEA Help Chat">
            <div class="chatbot-header">
                <strong><i class="bi bi-robot me-1"></i>EKEA Assistant</strong>
                <button id="chatbot-close" class="chatbot-close-btn" aria-label="Close chat"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="chatbot-messages" class="chatbot-messages">
                <div class="chat-msg bot-msg">
                    Hi! I can help you find products and pages. Try typing a keyword like <strong>"sofa"</strong>, <strong>"office"</strong>, or <strong>"checkout"</strong>.
                </div>
            </div>
            <div class="chatbot-input-area">
                <input type="text" id="chatbot-input" class="form-control" placeholder="Type a keyword..." maxlength="100" autocomplete="off">
                <button id="chatbot-send" class="btn btn-primary-ekea btn-sm" aria-label="Send"><i class="bi bi-send"></i></button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var basePath = '<?= BASE_URL ?>';
        var toggle = document.getElementById('chatbot-toggle');
        var panel = document.getElementById('chatbot-panel');
        var closeBtn = document.getElementById('chatbot-close');
        var input = document.getElementById('chatbot-input');
        var sendBtn = document.getElementById('chatbot-send');
        var messages = document.getElementById('chatbot-messages');

        toggle.addEventListener('click', function() {
            panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
            toggle.style.display = panel.style.display === 'flex' ? 'none' : 'flex';
            if (panel.style.display === 'flex') input.focus();
        });

        closeBtn.addEventListener('click', function() {
            panel.style.display = 'none';
            toggle.style.display = 'flex';
        });

        function sendMessage() {
            var query = input.value.trim();
            if (!query || query.length < 2) return;

            addMsg(query, 'user');
            input.value = '';

            var formData = new FormData();
            formData.append('query', query);

            fetch(basePath + '/chatbot', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.error) {
                        addMsg(data.error, 'bot');
                        return;
                    }
                    if (data.results && data.results.length > 0) {
                        var html = '';
                        data.results.forEach(function(r) {
                            html += '<a href="' + basePath + r.url + '" class="chatbot-result">';
                            html += '<i class="bi ' + r.icon + ' me-2"></i>';
                            html += '<span><strong>' + r.title + '</strong><br><small>' + r.subtitle + '</small></span>';
                            html += '</a>';
                        });
                        addMsg(html, 'bot', true);
                    } else {
                        addMsg(data.message || 'No results found.', 'bot');
                    }
                })
                .catch(function() {
                    addMsg('Sorry, something went wrong. Please try again.', 'bot');
                });
        }

        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') sendMessage();
        });

        function addMsg(content, type, isHtml) {
            var div = document.createElement('div');
            div.className = 'chat-msg ' + type + '-msg';
            if (isHtml) {
                div.innerHTML = content;
            } else {
                div.textContent = content;
            }
            messages.appendChild(div);
            messages.scrollTop = messages.scrollHeight;
        }
    })();
    </script>
</body>
</html>
