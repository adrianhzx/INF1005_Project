<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-credit-card me-2"></i>Checkout</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cart">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="checkoutForm" method="POST" class="ekea-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="place_order" value="1">

            <div class="row g-4">
                <div class="col-lg-8">

                    <div class="summary-card mb-4">
                        <h4 class="mb-3"><i class="bi bi-bag text-accent me-2"></i>Order Items (<?php echo count($cart); ?>)</h4>
                        <?php foreach ($cart as $item): ?>
                            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                                <img src="<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                     class="cart-item-img">
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <p class="mb-0 text-muted-ekea small">Qty: <?php echo (int)$item['quantity']; ?> x $<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <a href="<?= BASE_URL ?>/cart" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit Cart</a>
                    </div>

                    <div class="summary-card mb-4">
                        <h4 class="mb-3"><i class="bi bi-geo-alt text-accent me-2"></i>Delivery Address</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="postal_code" class="form-label">Postal Code <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="postal_code" name="postal_code"
                                        maxlength="6" pattern="\d{6}" placeholder="e.g. 828608" required aria-required="true">
                                    <button type="button" class="btn btn-dark-ekea px-3" id="lookupPostalBtn" title="Look up address" aria-label="Look up address">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <div class="form-text">Enter 6-digit postal code, then click search</div>
                            </div>
                            <div class="col-md-4">
                                <label for="unit_number" class="form-label">Unit Number <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control" id="unit_number" name="unit_number"
                                       placeholder="e.g. #12-345" required aria-required="true">
                            </div>
                            <div class="col-md-4">
                                <label for="building_name" class="form-label">Building Name</label>
                                <input type="text" class="form-control" id="building_name" name="building_name"
                                       placeholder="Auto-filled" readonly>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="street_address" class="form-label">Street Address <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control" id="street_address" name="street_address"
                                   placeholder="Auto-filled from postal code lookup" required aria-required="true">
                        </div>
                        <div id="postalLookupStatus" class="mt-2" style="display: none;"></div>
                    </div>

                    <div class="summary-card mb-4">
                        <h4 class="mb-3"><i class="bi bi-credit-card text-accent me-2"></i>Payment Method</h4>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Select Payment <span class="text-danger" aria-hidden="true">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required aria-required="true">
                                <option value="">Choose payment method...</option>
                                <option value="credit_card">Credit / Debit Card (Stripe Test)</option>
                                <option value="paypal">PayPal</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <div id="stripeCardSection" style="display: none;">
                            <label class="form-label">Card Details</label>
                            <div id="card-element" class="form-control" style="padding: 12px; min-height: 44px;"></div>
                            <div id="card-errors" class="text-danger small mt-1" role="alert"></div>
                            <div class="form-text mt-1">
                                <i class="bi bi-shield-lock me-1"></i>Test mode -- use card number <code>4242 4242 4242 4242</code>, any future expiry, any CVC.
                            </div>
                        </div>
                    </div>

                    <div class="summary-card mb-4">
                        <h4 class="mb-3"><i class="bi bi-tag text-accent me-2"></i>Discount Coupon</h4>
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <label for="coupon_code" class="form-label">Coupon Code</label>
                                <input type="text" class="form-control" id="coupon_code" name="coupon_code"
                                       placeholder="Enter coupon code (e.g. SAVE10)" style="text-transform: uppercase;">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-dark-ekea" id="applyCouponBtn">
                                    <i class="bi bi-check2 me-1"></i>Apply
                                </button>
                            </div>
                        </div>
                        <div id="couponFeedback" class="mt-2" style="display: none;"></div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="summary-card" style="position: sticky; top: 90px;">
                        <h4 class="mb-3"><i class="bi bi-receipt me-2"></i>Order Summary</h4>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span class="<?php echo $shipping === 0 ? 'text-success fw-semibold' : ''; ?>">
                                <?php echo $shipping === 0 ? 'FREE' : '$' . number_format($shipping, 2); ?>
                            </span>
                        </div>
                        <div class="summary-row" id="discountRow" style="display: none;">
                            <span>Discount</span>
                            <span class="text-success fw-semibold" id="discountAmount">-$0.00</span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span id="orderTotal">$<?php echo number_format($subtotal + $shipping, 2); ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary-ekea w-100 mt-3 btn-lg">
                            <i class="bi bi-lock me-2"></i>Place Order
                        </button>
                        <p class="text-center small text-muted-ekea mt-2 mb-0">
                            <i class="bi bi-shield-check me-1"></i>Secure checkout -- your data is encrypted
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script src="https://js.stripe.com/v3/"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Stripe Init (Test Mode) ---
    var stripe = Stripe('pk_test_51TCFUWLMsORLAKthAmU0BHpiKozLmKCYpqLinOGJjQme2ZESzvxop2HeqVW1RG1vYnviXCSfQUANUJIiVU7nPbIg00TxXOXM2L');
    var elements = stripe.elements();
    var cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#1b2a4a',
                fontFamily: 'Inter, sans-serif',
                '::placeholder': { color: '#6c7a89' },
            },
            invalid: { color: '#e74c3c' },
        }
    });

    var cardMounted = false;
    var paymentSelect = document.getElementById('payment_method');
    var stripeSection = document.getElementById('stripeCardSection');

    paymentSelect.addEventListener('change', function() {
        if (this.value === 'credit_card') {
            stripeSection.style.display = 'block';
            if (!cardMounted) {
                cardElement.mount('#card-element');
                cardMounted = true;
            }
        } else {
            stripeSection.style.display = 'none';
        }
    });

    cardElement.on('change', function(event) {
        var errEl = document.getElementById('card-errors');
        errEl.textContent = event.error ? event.error.message : '';
    });

    // --- OneMap Postal Code Lookup ---
    var lookupBtn = document.getElementById('lookupPostalBtn');
    var postalInput = document.getElementById('postal_code');
    var streetInput = document.getElementById('street_address');
    var buildingInput = document.getElementById('building_name');
    var statusEl = document.getElementById('postalLookupStatus');

    lookupBtn.addEventListener('click', function() {
        var code = postalInput.value.trim();
        if (!/^\d{6}$/.test(code)) {
            showLookupStatus('Please enter a valid 6-digit postal code.', false);
            return;
        }

        showLookupStatus('Looking up address...', null);

        fetch('https://www.onemap.gov.sg/api/common/elastic/search?searchVal=' + code + '&returnGeom=Y&getAddrDetails=Y&pageNum=1')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.found && data.found > 0) {
                    var result = data.results[0];
                    streetInput.value = result.ADDRESS || '';
                    buildingInput.value = result.BUILDING || 'N/A';
                    showLookupStatus('Address found: ' + (result.ADDRESS || ''), true);
                } else {
                    showLookupStatus('No address found for this postal code. Enter manually.', false);
                }
            })
            .catch(function() {
                showLookupStatus('Lookup service unavailable. Please enter address manually.', false);
            });
    });

    // Also trigger lookup on Enter key in postal code field
    postalInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            lookupBtn.click();
        }
    });

    function showLookupStatus(msg, success) {
        statusEl.style.display = 'block';
        if (success === null) {
            statusEl.className = 'mt-2 text-muted-ekea small';
            statusEl.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>' + msg;
        } else if (success) {
            statusEl.className = 'mt-2 text-success small';
            statusEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + msg;
        } else {
            statusEl.className = 'mt-2 text-danger small';
            statusEl.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + msg;
        }
    }

    // --- Coupon Apply via AJAX ---
    var applyCouponBtn = document.getElementById('applyCouponBtn');
    var couponInput = document.getElementById('coupon_code'); // Corrected ID reference
    var couponFeedback = document.getElementById('couponFeedback');
    var discountRow = document.getElementById('discountRow');
    var discountAmount = document.getElementById('discountAmount');
    var orderTotal = document.getElementById('orderTotal');
    
    // PHP variables injected safely
    var subtotal = <?php echo (float)$subtotal; ?>;
    var shipping = <?php echo (float)$shipping; ?>;

    applyCouponBtn.addEventListener('click', function() {
        var code = couponInput.value.trim().toUpperCase();
        
        if (!code) {
            showCouponFeedback('Please enter a coupon code.', false);
            return;
        }

        // Disable button during check
        applyCouponBtn.disabled = true;
        applyCouponBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Checking...';

        // Make the POST request to the correct route (/checkout/coupon)
        fetch('<?= BASE_URL ?>/checkout/coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'coupon_code': code,
                // Make sure we grab the CSRF token from the main form
                'csrf_token': document.querySelector('input[name="csrf_token"]').value
            })
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(function(data) {
            showCouponFeedback(data.message, data.valid);
            
            if (data.valid) {
                // Update UI for success
                discountRow.style.display = 'flex';
                // discount comes from the JSON response
                discountAmount.textContent = '-$' + parseFloat(data.discount).toFixed(2);
                
                var total = (subtotal + shipping) - parseFloat(data.discount);
                orderTotal.textContent = '$' + Math.max(0, total).toFixed(2); // Ensure it doesn't go below 0
                
                // Lock the input
                couponInput.readOnly = true;
                applyCouponBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Applied';
                // Leave the button disabled so they can't click it again
            } else {
                // Reset UI on failure
                discountRow.style.display = 'none';
                orderTotal.textContent = '$' + (subtotal + shipping).toFixed(2);
                applyCouponBtn.disabled = false;
                applyCouponBtn.innerHTML = '<i class="bi bi-check2 me-1"></i>Apply';
            }
        })
        .catch(function(error) {
            showCouponFeedback('Could not validate coupon. Please try again.', false);
            applyCouponBtn.disabled = false;
            applyCouponBtn.innerHTML = '<i class="bi bi-check2 me-1"></i>Apply';
            console.error('Coupon Error:', error);
        });
    });

    function showCouponFeedback(msg, success) {
        couponFeedback.style.display = 'block';
        couponFeedback.className = 'mt-2 small ' + (success ? 'text-success' : 'text-danger');
        couponFeedback.innerHTML = '<i class="bi bi-' + (success ? 'check-circle' : 'x-circle') + ' me-1"></i>' + msg;
    }

    // --- Stripe Form Intercept ---
    var form = document.getElementById('checkoutForm');
    
    form.addEventListener('submit', function(event) {
        var paymentSelect = document.getElementById('payment_method');
        
        // Only intercept if Credit Card is selected
        if (paymentSelect.value === 'credit_card') {
            event.preventDefault(); // STOP the form from submitting to PHP
            
            // Disable button to prevent double-charging
            var submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Verifying Card...';
            
            // Ask Stripe to verify the card details
            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    // The card is empty or invalid! Show the error.
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                    
                    // Re-enable the button so they can try again
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-lock me-2"></i>Place Order';
                } else {
                    // Success! The card is valid. Stripe gave us a secure "Token".
                    // Create a hidden input to hold this token
                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'stripeToken');
                    hiddenInput.setAttribute('value', result.token.id);
                    form.appendChild(hiddenInput);
                    
                    // NOW we finally let the form submit to your PHP Controller
                    form.submit();
                }
            });
        }
    });
});
</script>