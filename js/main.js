/**
 * EKEA — Client-side logic
 * Form validation, quantity controls, and UI interactions.
 */

document.addEventListener('DOMContentLoaded', function () {

    // Navbar scroll effect
    const navbar = document.querySelector('.ekea-navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // Scroll fade-in animations
    const fadeElements = document.querySelectorAll('.fade-in-up');
    if (fadeElements.length > 0) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        fadeElements.forEach(function (el) {
            observer.observe(el);
        });
    }

    // ============================================================
    // 3. Form Validation — Registration
    // ============================================================
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            let isValid = true;
            clearValidation(registerForm);

            const firstName = registerForm.querySelector('#first_name');
            const lastName = registerForm.querySelector('#last_name');
            const email = registerForm.querySelector('#email');
            const phone = registerForm.querySelector('#phone');
            const password = registerForm.querySelector('#password');
            const confirm = registerForm.querySelector('#confirm_password');

            if (!firstName.value.trim()) {
                showError(firstName, 'First name is required.');
                isValid = false;
            }

            if (!lastName.value.trim()) {
                showError(lastName, 'Last name is required.');
                isValid = false;
            }

            if (!email.value.trim()) {
                showError(email, 'Email is required.');
                isValid = false;
            } else if (!isValidEmail(email.value)) {
                showError(email, 'Please enter a valid email address.');
                isValid = false;
            }

            if (phone && phone.value.trim() && !/^\d{8,15}$/.test(phone.value.trim())) {
                showError(phone, 'Please enter a valid phone number (8-15 digits).');
                isValid = false;
            }

            if (!password.value) {
                showError(password, 'Password is required.');
                isValid = false;
            } else if (password.value.length < 8) {
                showError(password, 'Password must be at least 8 characters.');
                isValid = false;
            }

            if (!confirm.value) {
                showError(confirm, 'Please confirm your password.');
                isValid = false;
            } else if (password.value !== confirm.value) {
                showError(confirm, 'Passwords do not match.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ============================================================
    // 4. Form Validation — Login
    // ============================================================
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            let isValid = true;
            clearValidation(loginForm);

            const email = loginForm.querySelector('#email');
            const password = loginForm.querySelector('#password');

            if (!email.value.trim()) {
                showError(email, 'Email is required.');
                isValid = false;
            } else if (!isValidEmail(email.value)) {
                showError(email, 'Please enter a valid email address.');
                isValid = false;
            }

            if (!password.value) {
                showError(password, 'Password is required.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ============================================================
    // 5. Form Validation — Profile Edit
    // ============================================================
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            let isValid = true;
            clearValidation(profileForm);

            const firstName = profileForm.querySelector('#first_name');
            const lastName = profileForm.querySelector('#last_name');
            const email = profileForm.querySelector('#email');

            if (!firstName.value.trim()) {
                showError(firstName, 'First name is required.');
                isValid = false;
            }

            if (!lastName.value.trim()) {
                showError(lastName, 'Last name is required.');
                isValid = false;
            }

            if (!email.value.trim()) {
                showError(email, 'Email is required.');
                isValid = false;
            } else if (!isValidEmail(email.value)) {
                showError(email, 'Please enter a valid email address.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ============================================================
    // 6. Form Validation — Checkout
    // ============================================================
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            let isValid = true;
            clearValidation(checkoutForm);

            const postalCode = checkoutForm.querySelector('#postal_code');
            const unitNumber = checkoutForm.querySelector('#unit_number');
            const streetAddress = checkoutForm.querySelector('#street_address');
            const payment = checkoutForm.querySelector('#payment_method');

            if (!postalCode.value.trim() || !/^\d{6}$/.test(postalCode.value.trim())) {
                showError(postalCode, 'Please enter a valid 6-digit postal code.');
                isValid = false;
            }

            if (!unitNumber.value.trim()) {
                showError(unitNumber, 'Unit number is required.');
                isValid = false;
            }

            if (!streetAddress.value.trim()) {
                showError(streetAddress, 'Street address is required.');
                isValid = false;
            }

            if (!payment.value) {
                showError(payment, 'Please select a payment method.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ============================================================
    // 7. Form Validation — Review Submission
    // ============================================================
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function (e) {
            let isValid = true;
            clearValidation(reviewForm);

            const rating = reviewForm.querySelector('#rating');
            const comment = reviewForm.querySelector('#comment');

            if (!rating.value) {
                showError(rating, 'Please select a rating.');
                isValid = false;
            }

            if (!comment.value.trim()) {
                showError(comment, 'Please write a review comment.');
                isValid = false;
            } else if (comment.value.trim().length < 10) {
                showError(comment, 'Review must be at least 10 characters long.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ============================================================
    // 8. Form Validation — Admin Inventory (Add/Edit Product)
    // ============================================================
    const inventoryForm = document.getElementById('inventoryForm');
    if (inventoryForm) {
        inventoryForm.addEventListener('submit', function (e) {
            let isValid = true;
            clearValidation(inventoryForm);

            const name = inventoryForm.querySelector('#product_name');
            const category = inventoryForm.querySelector('#category_id');
            const price = inventoryForm.querySelector('#price');
            const stock = inventoryForm.querySelector('#stock');

            if (!name.value.trim()) {
                showError(name, 'Product name is required.');
                isValid = false;
            }

            if (!category.value) {
                showError(category, 'Please select a category.');
                isValid = false;
            }

            if (!price.value || parseFloat(price.value) <= 0) {
                showError(price, 'Please enter a valid price greater than $0.');
                isValid = false;
            }

            if (stock.value === '' || parseInt(stock.value) < 0) {
                showError(stock, 'Please enter a valid stock quantity.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Quantity controls (+/- buttons)
    document.querySelectorAll('.quantity-control').forEach(function (control) {
        var minusBtn = control.querySelector('.qty-minus');
        var plusBtn = control.querySelector('.qty-plus');
        var input = control.querySelector('.qty-input');
        var autoSubmit = control.hasAttribute('data-auto-submit');

        if (minusBtn && plusBtn && input) {
            minusBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var val = parseInt(input.value) || 1;
                if (val > 1) {
                    input.value = val - 1;
                    if (autoSubmit && input.form && input.form.className.includes('cart-update-form')) {
                        input.form.submit();
                    }
                } else if (val === 1 && autoSubmit) {
                    // At qty 1, ask if user wants to remove the item
                    var row = control.closest('tr');
                    if (row) {
                        var removeBtn = row.querySelector('.btn-cart-remove');
                        if (removeBtn) {
                            removeBtn.dispatchEvent(new Event('click'));
                        }
                    }
                }
            });

            plusBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var val = parseInt(input.value) || 1;
                var max = parseInt(input.getAttribute('max'));
                if (!max || val < max) {
                    input.value = val + 1;
                    if (autoSubmit && input.form && input.form.className.includes('cart-update-form')) {
                        input.form.submit();
                    }
                } else {
                    // Show stock limit modal if it exists, otherwise show alert
                    var stockModal = document.getElementById('stockLimitModal');
                    if (stockModal) {
                        var modalBody = document.getElementById('stockLimitModalBody');
                        if (modalBody) {
                            modalBody.textContent = 'Only ' + max + ' units available for this item.';
                        }
                        var bsModal = new bootstrap.Modal(stockModal);
                        bsModal.show();
                    }
                }
            });
        }
    });

    // Admin delete confirmation (non-cart pages)
    document.querySelectorAll('.btn-delete-confirm').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Cart navbar shake animation when item is added
    (function() {
        var flashEl = document.querySelector('.alert');
        if (flashEl && flashEl.textContent.indexOf('added to your cart') !== -1) {
            var cartLink = document.querySelector('a[href*="cart.php"]');
            if (cartLink) {
                cartLink.classList.add('cart-shake');
                setTimeout(function() {
                    cartLink.classList.remove('cart-shake');
                }, 800);
            }
        }
    })();

    // ============================================================
    // 11. Interactive Star Rating Selector
    // ============================================================
    const starSelector = document.querySelector('.star-selector');
    if (starSelector) {
        const stars = starSelector.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');

        stars.forEach(function (star) {
            star.addEventListener('click', function () {
                const val = this.dataset.value;
                ratingInput.value = val;
                stars.forEach(function (s) {
                    if (parseInt(s.dataset.value) <= parseInt(val)) {
                        s.classList.add('bi-star-fill');
                        s.classList.remove('bi-star');
                    } else {
                        s.classList.remove('bi-star-fill');
                        s.classList.add('bi-star');
                    }
                });
            });

            star.addEventListener('mouseenter', function () {
                const val = this.dataset.value;
                stars.forEach(function (s) {
                    s.classList.toggle('bi-star-fill', parseInt(s.dataset.value) <= parseInt(val));
                    s.classList.toggle('bi-star', parseInt(s.dataset.value) > parseInt(val));
                });
            });
        });

        starSelector.addEventListener('mouseleave', function () {
            const currentVal = ratingInput.value;
            stars.forEach(function (s) {
                if (currentVal && parseInt(s.dataset.value) <= parseInt(currentVal)) {
                    s.classList.add('bi-star-fill');
                    s.classList.remove('bi-star');
                } else {
                    s.classList.remove('bi-star-fill');
                    s.classList.add('bi-star');
                }
            });
        });
    }

    // ============================================================
    // Helper Functions
    // ============================================================

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /**
     * Show validation error on a field
     */
    function showError(input, message) {
        input.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        input.parentNode.appendChild(feedback);
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', input.id + '-error');
        feedback.id = input.id + '-error';
    }

    /**
     * Clear all validation states from a form
     */
    function clearValidation(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
            el.removeAttribute('aria-invalid');
            el.removeAttribute('aria-describedby');
        });
        form.querySelectorAll('.invalid-feedback').forEach(function (el) {
            el.remove();
        });
    }

    // ============================================================
    // Hero Slideshow
    // ============================================================
    const heroSlides = document.querySelectorAll('.hero-slide');
    const heroDots = document.querySelectorAll('.hero-dot');
    const heroFixed = document.querySelector('.hero-fixed');

    // Only run slideshow logic if slides exist
    if (heroSlides.length > 0) {
        let currentSlide = 0;
        let slideInterval;

        function goToSlide(n) {
            // Remove active class from current
            heroSlides[currentSlide].classList.remove('active');
            if (heroDots[currentSlide]) heroDots[currentSlide].classList.remove('active');

            // Calculate next index
            currentSlide = (n + heroSlides.length) % heroSlides.length;

            // Add active class to new
            heroSlides[currentSlide].classList.add('active');
            if (heroDots[currentSlide]) heroDots[currentSlide].classList.add('active');
        }

        function nextSlide() {
            goToSlide(currentSlide + 1);
        }

        function startSlideAuto() {
            slideInterval = setInterval(nextSlide, 5000);
        }

        function stopSlideAuto() {
            clearInterval(slideInterval);
        }

        // Add click events to dots
        heroDots.forEach(function (dot, i) {
            dot.addEventListener('click', function () {
                stopSlideAuto();
                goToSlide(i);
                startSlideAuto();
            });
        });

        // Start the slideshow
        startSlideAuto();
    }

    // Scroll Fade Effect for Hero
    if (heroFixed) {
        window.addEventListener('scroll', function () {
            const scrollY = window.scrollY;
            const winH = window.innerHeight;
            if (scrollY < winH) {
                let opacity = 1;
                
                if (scrollY > (winH * 0.1)) {
                     opacity = 1 - ((scrollY - (winH * 0.1)) / (winH * 0.9));
                }
                
                heroFixed.style.opacity = Math.max(0, opacity);
            } else {
                heroFixed.style.opacity = 0;
            }
        });
    }

});
