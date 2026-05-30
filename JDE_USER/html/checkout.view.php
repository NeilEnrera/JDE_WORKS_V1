<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - JDE Works of Our Hands</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/navbar.css">
</head>

<body style="background-color: #F3EAD9;">
    <?php
    $activePage = 'cart';
    include '../backend/navbar.php';
    ?>
    <script>
        window._hasCustomItems = <?php echo isset($hasCustomItems) && $hasCustomItems ? 'true' : 'false'; ?>;
    </script>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" href="../css/cart.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/checkout.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/address-picker.css?v=<?php echo time(); ?>">
    <!-- Flatpickr CSS for styled date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Main Content -->
    <div class="cart-container pb-5">

        <!-- Checkout Progress Steps -->
        <div class="checkout-progress-bar mb-5 mt-4 d-flex justify-content-between align-items-center w-100 mx-auto"
            style="max-width: 900px;">
            <a href="cart.php" class="progress-step completed">
                <div class="step-number"><i class="bi bi-check-lg"></i></div>
                <div class="step-label">Cart</div>
            </a>
            <div class="progress-line completed"></div>
            <a href="checkout.php" class="progress-step active">
                <div class="step-number">2</div>
                <div class="step-label">Distribution</div>
            </a>
            <div class="progress-line"></div>
            <div class="progress-step disabled">
                <div class="step-number">3</div>
                <div class="step-label">Payment Option</div>
            </div>
            <div class="progress-line"></div>
            <?php if (($_SESSION['payment_option'] ?? '') === 'full'): ?>
                <div class="progress-step disabled" title="Not required for Full Payment">
                    <div class="step-number">4</div>
                    <div class="step-label">Balance Payment Method</div>
                </div>
            <?php else: ?>
                <div class="progress-step disabled">
                    <div class="step-number">4</div>
                    <div class="step-label">Balance Payment Method</div>
                </div>
            <?php endif; ?>
            <div class="progress-line"></div>
            <div class="progress-step disabled">
                <div class="step-number">5</div>
                <div class="step-label">Payment Processing</div>
            </div>
        </div>

        <div class="text-center mb-5">
            <h1 class="fw-bold" style="color: var(--primary-dark-blue); font-size: 2.5rem;">Checkout</h1>
            <?php if (isset($_SESSION['checkout_error'])): ?>
                <div class="alert alert-danger mt-3 mx-auto" style="max-width: 600px; border-radius: 12px;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php
                    echo $_SESSION['checkout_error'];
                    unset($_SESSION['checkout_error']);
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart-state">
                <div class="empty-icon">
                    <i class="bi bi-bag-x"></i>
                </div>
                <h3>Your session expired or cart is empty</h3>
                <a href="product.php" class="btn btn-checkout" style="max-width: 250px; margin: 0 auto;">Shop Now</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Left Column: Forms -->
                <div style="flex: 2;">

                    <form action="payment.php" method="POST" class="checkout-form">
                        <!-- Distribution Method Selection -->
                        <div class="checkout-form-section">
                            <div class="section-header">
                                <h3><i class="bi bi-box-seam"></i> Distribution Method</h3>
                            </div>
                            <div class="distribution-options d-flex gap-4 mb-2">
                                <div class="form-check distribution-option flex-fill border rounded-3 p-4 cursor-pointer selected"
                                    id="methodDelivery">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input mt-0" type="radio" name="distribution_method"
                                            id="distDelivery" value="delivery" checked>
                                        <label class="form-check-label w-100 cursor-pointer" for="distDelivery">
                                            <div class="fw-bold text-dark" style="font-size: 1.1rem;">Delivery</div>
                                            <div class="text-muted small">Receive at your doorstep</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-check distribution-option flex-fill border rounded-3 p-4 cursor-pointer"
                                    id="methodPickup">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input mt-0" type="radio" name="distribution_method"
                                            id="distPickup" value="pickup">
                                        <label class="form-check-label w-100 cursor-pointer" for="distPickup">
                                            <div class="fw-bold text-dark" style="font-size: 1.1rem;">Pick up</div>
                                            <div class="text-muted small">Collect from our store</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Information (Visible for Delivery) -->
                        <div id="shippingSection" class="checkout-form-section">
                            <div class="section-header d-flex justify-content-between align-items-center">
                                <h3><i class="bi bi-geo-alt"></i> Shipping Information</h3>
                                <?php if (!empty($savedAddresses)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#addressSelectionModal"
                                        style="border-radius: 50px; font-weight: 600; padding: 5px 15px; border-color: var(--primary-gold); color: var(--primary-gold);">
                                        Choose Saved Address
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="address-field-group">
                                        <label class="address-field-label">Street Address*</label>
                                        <input type="text" class="address-field-input" name="address" required
                                            placeholder="House number and street name"
                                            value="<?php echo htmlspecialchars($customerData['address']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="address-field-group">
                                        <label class="address-field-label">Province / State*</label>
                                        <select class="address-field-select" name="province" id="checkout-province"
                                            required>
                                            <option value="">-- SELECT PROVINCE --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="address-field-group">
                                        <label class="address-field-label">City / Town*</label>
                                        <select class="address-field-select" name="city" id="checkout-city" required
                                            disabled>
                                            <option value="">-- SELECT CITY --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="address-field-group">
                                        <label class="address-field-label">Barangay*</label>
                                        <select class="address-field-select" name="barangay" id="checkout-barangay" required
                                            disabled>
                                            <option value="">-- SELECT BARANGAY --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="address-field-group">
                                        <label class="address-field-label">Postal Code*</label>
                                        <input type="text" class="address-field-input" name="zip" required
                                            placeholder="ZIP">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pickup Location (Visible for Pick up) -->
                        <div id="pickupSection" class="checkout-form-section d-none">
                            <div class="section-header">
                                <h3><i class="bi bi-shop"></i> Pickup Details</h3>
                            </div>
                            <div class="pickup-info border rounded-3 p-4" style="background: rgba(5, 43, 71, 0.03);">
                                <h6 class="fw-bold mb-3" style="color: var(--primary-dark-blue);">JDE Works of Our Hands
                                    Store</h6>
                                <div class="d-flex flex-column gap-2">
                                    <p class="mb-0 small text-muted"><i class="bi bi-geo-alt-fill text-danger me-2"></i>
                                        Hilltop Branch,
                                        11 Esperanza, Novaliches, Quezon City</p>
                                    <p class="mb-0 small text-muted"><i class="bi bi-clock-fill text-primary me-2"></i> Mon
                                        - Sat: 8:00
                                        AM - 6:00 PM</p>
                                    <p class="mb-0 small text-muted"><i class="bi bi-telephone-fill text-success me-2"></i>
                                        0933-598-7864
                                        / 0997-892-7142</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="form-label" id="pickupDateLabel">Preferred Pickup Date
                                    <?php echo !$hasCustomItems ? '<span class="text-danger">*</span>' : '<span class="text-muted small fw-normal ms-1">(Optional)</span>'; ?></label>
                                <div class="position-relative">
                                    <input type="date" class="form-control" name="pickup_date" id="pickup_date"
                                        min="<?php echo date('Y-m-d'); ?>" <?php echo !$hasCustomItems ? 'required' : ''; ?>
                                        style="padding-right: 40px; background-color: white;">
                                    <i class="bi bi-calendar3 position-absolute top-50 end-0 translate-middle-y me-3 text-muted"
                                        style="pointer-events: none; z-index: 5;"></i>
                                </div>
                                <?php if ($hasCustomItems): ?>
                                    <div class="form-text mt-2 text-muted">
                                        Selecting a pickup date is optional for custom orders. You will receive an email
                                        notification once the order is completed and ready for pickup.
                                    </div>
                                <?php else: ?>
                                    <div class="form-text mt-2">Choose your earliest preferred date for collection.</div>
                                <?php endif; ?>

                                <?php if ($hasCustomItems): ?>
                                    <div class="alert alert-info mt-4 border-0 shadow-sm"
                                        style="background: rgba(214, 178, 94, 0.1); border-left: 4px solid var(--primary-gold) !important;">
                                        <div class="d-flex gap-3">
                                            <i class="bi bi-info-circle-fill"
                                                style="color: var(--primary-gold); font-size: 1.2rem;"></i>
                                            <div>
                                                <h6 class="fw-bold mb-1" style="color: var(--primary-dark-blue);">Order
                                                    Notice</h6>
                                                <p class="mb-0 small text-muted">Your order includes
                                                    <strong>custom size tailored items</strong> which may take <strong>2 to 3
                                                        business days </strong>of production.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contact Information (Always Required) -->
                        <div class="checkout-form-section">
                            <div class="section-header">
                                <h3><i class="bi bi-person-lines-fill"></i> Contact Information</h3>
                            </div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required
                                        value="<?php echo htmlspecialchars($customerData['firstName']); ?>" readonly
                                        style="background-color: #f8f9fa; color: #333; opacity: 1;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required
                                        value="<?php echo htmlspecialchars($customerData['lastName']); ?>" readonly
                                        style="background-color: #f8f9fa; color: #333; opacity: 1;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" required
                                        value="<?php echo htmlspecialchars($customerData['email']); ?>" readonly
                                        style="background-color: #f8f9fa; color: #333; opacity: 1;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" required
                                        placeholder="e.g. 09123456789"
                                        value="<?php echo htmlspecialchars($customerData['phoneNumber']); ?>" maxlength="11"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Order Summary (Sticky) -->
                <div class="cart-summary-section">
                    <div class="summary-card shadow-sm border-0 p-4">
                        <div class="summary-header mb-4">
                            <h3 style="font-size: 1.3rem;">Order Summary</h3>
                        </div>

                        <div class="summary-items-list mb-4">
                            <?php
                            $grandTotal = 0;
                            $originalSubtotal = 0;
                            $totalQuantity = 0;
                            $selectedItemsExist = false;

                            // Calculate total discount and group logic FIRST
                            $productGroups = [];
                            foreach ($_SESSION['cart'] as $idx => $item) {
                                if (isset($item['selected']) && !$item['selected'])
                                    continue;
                                $pId = $item['id'] ?? 'custom_' . $idx;
                                if (!isset($productGroups[$pId])) {
                                    $productGroups[$pId] = ['qty' => 0, 'subtotal' => 0];
                                }
                                $price = (float) $item['price'];
                                $quantity = (int) $item['quantity'];
                                $productGroups[$pId]['qty'] += $quantity;
                                $productGroups[$pId]['subtotal'] += ($price * $quantity);
                            }

                            $discountPerProduct = [];
                            $totalDiscountAmount = 0;
                            $maxPct = 0;
                            require_once '../backend/discount_helper.php';

                            foreach ($productGroups as $pId => $group) {
                                // Fetch product data for override check
                                $pData = null;
                                if (strpos($pId, 'custom_') === false) {
                                    $stmt = $conn->prepare("SELECT customDiscountPercent, customMinQty FROM tbl_product WHERE productID = ? OR slug = ? LIMIT 1");
                                    $stmt->bind_param("ss", $pId, $pId);
                                    $stmt->execute();
                                    $pData = $stmt->get_result()->fetch_assoc();
                                    $stmt->close();
                                }

                                $calc = DiscountSystem::calculateItemDiscount($group['qty'], (float) ($group['subtotal'] / ($group['qty'] ?: 1)), $pData);
                                $discountPct = $calc['percentage'];
                                $discountPerProduct[$pId] = $discountPct;

                                if ($discountPct > 0) {
                                    $totalDiscountAmount += $calc['amount'];
                                }
                                if ($discountPct > $maxPct)
                                    $maxPct = $discountPct;
                            }
                            $discountAmount = $totalDiscountAmount;

                            foreach ($_SESSION['cart'] as $index => $item):
                                if (isset($item['selected']) && !$item['selected'])
                                    continue;
                                $selectedItemsExist = true;
                                $itemTotal = (float) $item['price'] * (int) $item['quantity'];
                                $originalSubtotal += $itemTotal;
                                $totalQuantity += (int) $item['quantity'];

                                $pId = $item['id'] ?? 'custom_' . $index;
                                $itemDiscountPct = $discountPerProduct[$pId] ?? 0;
                                $itemDiscountAmt = $itemTotal * ($itemDiscountPct / 100);
                                $itemFinalSubtotal = $itemTotal - $itemDiscountAmt;

                                $grandTotal += $itemFinalSubtotal;
                                ?>
                                <div class="order-item-mini mb-3 pb-3 border-bottom" data-index="<?php echo $index; ?>">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt=""
                                        class="order-img shadow-sm" style="width: 50px; height: 50px; border-radius: 8px;">
                                    <div class="order-info flex-grow-1 ps-2">
                                        <h6 class="mb-0 fw-bold" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h6>
                                        <div class="order-meta small text-muted">
                                            Size: <?php echo htmlspecialchars($item['size']); ?>
                                        </div>
                                    </div>
                                    <div class="order-actions d-flex flex-column align-items-end">
                                        <?php if ($itemDiscountPct > 0): ?>
                                            <div class="original-price"
                                                style="text-decoration: line-through; color: #6c757d; font-size: 0.8rem;">
                                                ₱<?php echo number_format($itemTotal, 2); ?>
                                            </div>
                                            <div class="discount-badge"
                                                style="color: #198754; font-size: 0.75rem; font-weight: 600; margin-bottom: 2px;">
                                                <?php echo $itemDiscountPct; ?>% Off
                                                (-₱<?php echo number_format($itemDiscountAmt, 2); ?>)
                                            </div>
                                        <?php endif; ?>
                                        <div class="order-price fw-bold"
                                            style="font-size: 0.95rem; color: var(--primary-dark-blue);">
                                            ₱<?php echo number_format($itemDiscountPct > 0 ? $itemFinalSubtotal : $itemTotal, 2); ?>
                                        </div>
                                        <div class="checkout-qty-selector mt-2">
                                            <button type="button" class="btn-qty-minus text-muted"
                                                onclick="changeCheckoutQty(event, this, <?php echo $index; ?>, <?php echo (int) $item['quantity'] - 1; ?>)">-</button>
                                            <span class="qty-display fw-bold mx-2">
                                                <?php echo (int) $item['quantity']; ?>
                                            </span>
                                            <button type="button" class="btn-qty-plus text-muted"
                                                onclick="changeCheckoutQty(event, this, <?php echo $index; ?>, <?php echo (int) $item['quantity'] + 1; ?>)">+</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$selectedItemsExist): ?>
                                <div class="alert alert-warning">No items selected for checkout.</div>
                            <?php endif; ?>
                        </div>
                        <div class="summary-calculations">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span>₱<?php echo number_format($originalSubtotal, 2); ?></span>
                            </div>

                            <?php if ($discountAmount > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Discount</span>
                                    <span>-₱<?php echo number_format($discountAmount, 2); ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Hidden field for JS to read the discounted subtotal -->
                            <div class="d-none discounted-subtotal fw-bold">₱<?php echo number_format($grandTotal, 2); ?>
                            </div>

                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                <span class="text-muted">Shipping Fee</span>
                                <span class="fw-bold" id="checkout-shipping-fee">₱0.00</span>
                            </div>

                            <div class="d-flex justify-content-between mb-4 mt-2">
                                <span class="fw-bold fs-5" style="color: var(--primary-dark-blue);">Total</span>
                                <span class="fw-bold fs-4" style="color: var(--primary-dark-blue);"
                                    id="checkout-total-price">
                                    ₱<?php echo number_format($grandTotal, 2); ?>
                                </span>
                            </div>
                        </div>

                        <div class="terms-agreement-section mb-4">
                            <div class="form-check custom-checkbox">
                                <input class="form-check-input" type="checkbox" id="agreeTerms" disabled
                                    style="cursor: not-allowed;">

                                <label class="form-check-label small text-secondary cursor-pointer" for="agreeTerms"
                                    style="line-height: 1.4;">
                                    I have read and agree to the
                                    <a href="#" class="text-decoration-none fw-bold highlight-terms" id="openTermsCheckout"
                                        style="color: var(--primary-gold);" data-bs-toggle="modal"
                                        data-bs-target="#termsModal">Terms and Conditions</a>
                                </label>

                            </div>
                        </div>

                        <button type="button" class="btn-continue-to-payment w-100 disabled" id="btnContinueCheckout"
                            onclick="submitCheckoutForm()" style="opacity: 0.6; pointer-events: none;">
                            CONTINUE TO PAYMENT
                        </button>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const termsCheck = document.getElementById('agreeTerms');
                                const continueBtn = document.getElementById('btnContinueCheckout');
                                const acceptBtn = document.getElementById('acceptCheckoutTerms');
                                const openTermsBtn = document.getElementById('openTermsCheckout');

                                if (acceptBtn) {
                                    acceptBtn.addEventListener('click', function () {
                                        termsCheck.disabled = false;
                                        termsCheck.checked = true;
                                        termsCheck.style.cursor = 'pointer';

                                        if (openTermsBtn) openTermsBtn.classList.remove('highlight-terms');

                                        // Trigger button update
                                        continueBtn.classList.remove('disabled');
                                        continueBtn.style.opacity = '1';
                                        continueBtn.style.pointerEvents = 'auto';
                                    });
                                }


                                termsCheck.addEventListener('change', function () {
                                    if (this.checked) {
                                        continueBtn.classList.remove('disabled');
                                        continueBtn.style.opacity = '1';
                                        continueBtn.style.pointerEvents = 'auto';
                                    } else {
                                        continueBtn.classList.add('disabled');
                                        continueBtn.style.opacity = '0.6';
                                        continueBtn.style.pointerEvents = 'none';
                                    }
                                });

                            });

                            function submitCheckoutForm() {
                                const form = document.querySelector('.checkout-form');
                                if (document.getElementById('agreeTerms').checked) {
                                    if (form.reportValidity()) {
                                        if (typeof form.requestSubmit === 'function') {
                                            form.requestSubmit();
                                        } else {
                                            form.submit();
                                        }
                                    }
                                }
                            }
                        </script>

                        <div class="text-center mt-4">
                            <span class="text-muted" style="font-size: 0.8rem;"><i class="bi bi-lock-fill me-1"></i> Secure
                                Encrypted Transaction</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Address Selection Modal -->
    <div class="modal fade" id="addressSelectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-3 pt-4 px-4">
                    <div class="d-flex flex-column">
                        <h5 class="modal-title fw-bold mb-1"
                            style="color: var(--primary-dark-blue); font-size: 1.4rem;">Select Shipping Address</h5>
                        <p class="text-muted small mb-0">Choose from your saved addresses below</p>
                    </div>
                    <button type="button" class="btn-close ms-auto align-self-start" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="mx-4 position-relative">
                    <div style="height: 1px; width: 100%; background: rgba(214, 178, 94, 0.2);"></div>
                    <div
                        style="height: 3px; width: 60px; background: var(--primary-gold); border-radius: 10px; position: absolute; top: -1px; left: 0;">
                    </div>
                </div>
                <div class="modal-body p-0 mt-2">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($savedAddresses)): ?>
                            <?php foreach ($savedAddresses as $addr): ?>
                                <button type="button" class="list-group-item list-group-item-action p-4 border-bottom"
                                    onclick='selectSavedAddress(<?php echo htmlspecialchars(json_encode($addr), ENT_QUOTES, "UTF-8"); ?>)'>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-bold d-flex align-items-center" style="color: var(--primary-dark-blue);">
                                            <?php echo htmlspecialchars($addr['receiverName']); ?>
                                            <?php if (!empty($addr['isDefault'])): ?>
                                                <span class="badge rounded-pill ms-2 d-flex align-items-center gap-1"
                                                    style="background: rgba(214, 178, 94, 0.1); color: var(--primary-gold); border: 1px solid rgba(214, 178, 94, 0.2); font-size: 10px; padding: 4px 8px;">
                                                    <i class="bi bi-star-fill" style="font-size: 9px;"></i> Default
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($addr['label'])): ?>
                                                <span class="badge bg-light text-dark ms-2 fw-normal border"
                                                    style="font-size: 10px;"><?php echo htmlspecialchars($addr['label']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small fw-bold text-muted">
                                            <?php echo htmlspecialchars($addr['receiverPhone']); ?>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($addr['addressLine']); ?>,
                                        <?php echo htmlspecialchars($addr['barangay'] ?? ''); ?><br>
                                        <?php echo htmlspecialchars($addr['city']); ?>,
                                        <?php echo htmlspecialchars($addr['province'] ?? ''); ?>,
                                        <?php echo htmlspecialchars($addr['zip']); ?>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true"
        style="z-index: 9999;">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-bottom-0 pb-0 mx-3 pt-4">
                    <h5 class="modal-title fw-bold" id="termsModalLabel"
                        style="color: var(--primary-dark-blue); font-size: 1.5rem;">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 mx-3">
                    <div class="terms-content" style="color: #555; line-height: 1.1;">
                        <?php $termNum = 1; ?>
                        <section class="mb-4">
                            <h6 class="fw-bold text-dark border-bottom pb-2"><?php echo $termNum++; ?>. General Policy
                            </h6>
                            <p class="small">By placing an order, you agree to our processing timelines and quality
                                standards.</p>
                            <ul class="small">
                                <li><strong>Peak season/enrollment:</strong> up to 7 days</li>
                                <li><strong>Regular days:</strong> 1–3 days</li>
                            </ul>
                        </section>

                        <section class="mb-4">
                            <h6 class="fw-bold text-dark border-bottom pb-2"><?php echo $termNum++; ?>. Delivery and
                                Pickup</h6>
                            <p class="small">Customers may choose delivery or store pickup.</p>
                            <ul class="small">
                                <li>You will be <strong>notified when items are ready</strong></li>
                                <li><strong>Premade items:</strong> select a pickup date upon ordering</li>
                                <li><strong>Custom items:</strong> available after production and notification</li>
                            </ul>
                        </section>

                        <section class="mb-4">
                            <h6 class="fw-bold text-dark border-bottom pb-2"><?php echo $termNum++; ?>. Returns &
                                Refunds</h6>
                            <p class="small">Returns are accepted within <strong>7 days</strong> if items are in
                                original condition and accompanied by the <strong>official receipt or proof of
                                    purchase.</strong></p>
                            <p class="small"><strong>Note:</strong> Custom tailored or altered items are non-refundable
                                once production has started.</p>
                        </section>

                        <section class="mb-4">
                            <h6 class="fw-bold text-dark border-bottom pb-2"><?php echo $termNum++; ?>. Payment Policy
                            </h6>
                            <p class="small">
                                Payment must be completed as per the chosen method.
                                <strong>For custom orders:</strong> Production will only begin once the payment or
                                required downpayment has been verified.
                            </p>
                        </section>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 mx-3 pb-4">
                    <button type="button" class="btn w-100 py-3" id="acceptCheckoutTerms" data-bs-dismiss="modal"
                        style="background: var(--primary-gold); color: #fff; font-weight: 700; border-radius: 12px; border: none;">
                        I AGREE!
                    </button>
                </div>

            </div>
        </div>
    </div>
    <!-- Global Deletion Popover -->
    <div id="globalDeletePopover" class="delete-popover d-none">
        <div class="popover-content">
            <p>Delete this item from your cart?</p>
            <div class="popover-actions">
                <button type="button" class="btn-popover-no" onclick="closeAllPopovers()">No</button>
                <button type="button" id="confirmDeleteBtn" class="btn-popover-yes">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
</body>

</html>

<!-- Flatpickr JS for date styling -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- Page Specific Scripts -->
<script src="../js/ph-locations.js?v=<?php echo time(); ?>"></script>
<script src="../js/checkout.js?v=<?php echo time(); ?>"></script>
<?php
// Find the default address (first entry, already sorted by isDefault DESC)
$defaultAddress = null;
foreach ($savedAddresses as $addr) {
    if (!empty($addr['isDefault'])) {
        $defaultAddress = $addr;
        break;
    }
}
// Fall back to the first address if none is explicitly marked default
if (!$defaultAddress && !empty($savedAddresses)) {
    $defaultAddress = $savedAddresses[0];
}
if ($defaultAddress):
    ?>
    <script>
        // Auto-apply default saved address once the location picker is ready
        window._checkoutDefaultAddress = <?php echo json_encode($defaultAddress, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
<?php endif; ?>