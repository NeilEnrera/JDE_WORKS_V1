<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payment Verification - JDE Works of Our Hands</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/navbar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/cart.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/checkout.css?v=<?php echo time(); ?>">
</head>

<body style="background-color: #F3EAD9;">
    <?php
    $activePage = 'products';
    include '../backend/navbar.php';
    ?>

    <main class="payment-page py-5">
        <div class="cart-container pb-5">

            <!-- Progress Stepper -->
            <div class="checkout-progress-bar mb-5 mt-4 d-flex justify-content-between align-items-center w-100 mx-auto"
                style="max-width: 900px;">
                <a href="cart.php" class="progress-step completed">
                    <div class="step-number"><i class="bi bi-check-lg"></i></div>
                    <div class="step-label">Cart</div>
                </a>
                <div class="progress-line completed"></div>
                <a href="checkout.php" class="progress-step completed">
                    <div class="step-number"><i class="bi bi-check-lg"></i></div>
                    <div class="step-label">Distribution</div>
                </a>
                <div class="progress-line completed"></div>
                <a href="payment.php" class="progress-step completed">
                    <div class="step-number"><i class="bi bi-check-lg"></i></div>
                    <div class="step-label">Payment Option</div>
                </a>
                <div class="progress-line completed"></div>
                <?php if (($paymentOption ?? '') === 'full'): ?>
                    <div class="progress-step disabled">
                        <div class="step-number">4</div>
                        <div class="step-label">Balance Payment Method</div>
                    </div>
                <?php else: ?>
                    <a href="payment_method.php" class="progress-step completed">
                        <div class="step-number"><i class="bi bi-check-lg"></i></div>
                        <div class="step-label">Balance Payment Method</div>
                    </a>
                <?php endif; ?>
                <div class="progress-line completed"></div>
                <div class="progress-step active">
                    <div class="step-number">5</div>
                    <div class="step-label">Payment Processing</div>
                </div>
            </div>

            <div class="text-center mb-5">
                <h1 class="fw-bold" style="color: var(--primary-dark-blue); font-size: 2.5rem;">
                    <?php echo ($paymentMethod === 'gcash') ? 'Payment Verification' : 'Confirm Your Order'; ?>
                </h1>
            </div>

            <div class="cart-layout">
                <div style="flex: 2;">
                    <form action="order_handler.php" method="POST" class="payment-form" enctype="multipart/form-data">
                        <input type="hidden" name="payment_option"
                            value="<?php echo htmlspecialchars($paymentOption); ?>">
                        <input type="hidden" name="payment_method"
                            value="<?php echo htmlspecialchars($paymentMethod); ?>">

                        <div class="checkout-form-section">
                            <!-- GCash UI is now mandatory for all orders to handle Downpayment/Full Payment -->
                            <div class="payment-details mt-3 ps-4 border-start border-3 border-primary"
                                style="display:block; position: relative;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h3 class="fw-bold mb-0" style="font-size: 1.25rem;">GCash Payment Verification</h3>
                                    <i class="bi bi-qr-code text-primary fs-4"></i>
                                </div>
                                <p class="text-muted small mb-4">A
                                    <strong><?php echo ($paymentOption === 'full' ? 'Full Payment' : 'Downpayment'); ?></strong>
                                    is required to confirm your order.
                                </p>

                                <?php if ($paymentMethod !== 'gcash'): ?>
                                    <div class="alert alert-warning border-0 rounded-3 small mb-4">
                                        <i class="bi bi-info-circle-fill me-2"></i> You selected
                                        <strong><?php echo ($paymentMethod === 'cod' ? 'Cash on Delivery' : 'Cash on Pickup'); ?></strong>
                                        for the remaining balance. Please pay the initial DP below to proceed.
                                    </div>
                                <?php endif; ?>

                                <div class="text-center p-3 border rounded bg-white mb-4">
                                    <img src="../assets/img/gcash-qr-placeholder.png" alt="GCash QR"
                                        style="max-width: 150px;"
                                        onerror="this.src='https://placehold.co/150x150?text=GCash+QR'">
                                    <div class="mt-2 fw-bold" style="font-size: 1.1rem;">JDE Works - 0912 345 6789</div>
                                    <div class="text-muted small mt-1">Please scan the QR code to pay
                                        <strong>₱<?php echo number_format($paymentOption === 'full' ? $finalTotal : $minDownPayment, 2); ?></strong>.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="gcash_receipt" class="form-label fw-bold small mb-2">Upload Proof of
                                        Payment (GCash Receipt)</label>
                                    <input type="file" class="form-control form-control-sm" name="gcash_receipt"
                                        id="gcash_receipt" accept="image/*" required>
                                    <div class="text-muted small mt-1">Please upload a screenshot or photo of your GCash
                                        transaction.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="reference_number" class="form-label fw-bold small mb-2">GCash Reference
                                        Number</label>
                                    <input type="text" class="form-control form-control-sm" name="reference_number"
                                        id="reference_number" placeholder="Enter 13-digit number" maxlength="13"
                                        required>
                                    <div class="text-muted small mt-2 mb-3">Example: 9012 345 678 901</div>
                                    <div class="text-muted small">Scan the QR code to pay. Then upload the receipt and
                                        enter the reference number from the receipt above to proceed.</div>
                                </div>

                                <div class="alert alert-secondary border-0 rounded-3 small mt-4">
                                    <i class="bi bi-clock-history me-2"></i> <strong>Production Lead Time:</strong>
                                    Standard orders take <strong>2-3 business days</strong> to process before
                                    shipping/pickup.
                                </div>
                            </div>
                        </div>
                </div>

                <!-- Right Sidebar: Summary -->
                <div class="cart-summary-section">
                    <div class="summary-card">
                        <div class="summary-header">
                            <h3>Order Summary</h3>
                        </div>
                        <div class="summary-calculations">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₱<?php echo number_format($originalSubtotal ?? $grandTotal + $totalDiscountAmount, 2); ?></span>
                            </div>
                            <?php if ($totalDiscountAmount > 0): ?>
                                <div class="summary-row text-success">
                                    <span>Bulk Discount</span>
                                    <span>-₱<?php echo number_format($totalDiscountAmount, 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span><?php
                                if ($distMethod === 'pickup') {
                                    echo 'N/A';
                                } elseif ($shippingFee == 0) {
                                    echo '<span class="text-success fw-bold">FREE</span>';
                                } else {
                                    echo '₱' . number_format($shippingFee, 2);
                                }
                                ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>₱<?php echo number_format($finalTotal, 2); ?></span>
                            </div>

                            <div class="summary-row mt-2 pt-2 border-top border-secondary">
                                <span class="fw-bold text-danger">
                                    <?php echo ($paymentOption === 'full' ? 'Required Full Payment' : 'Required Down Payment (50%)'); ?>
                                </span>
                                <span class="fw-bold text-danger text-end">
                                    ₱<?php echo number_format($paymentOption === 'full' ? $finalTotal : $minDownPayment, 2); ?>
                                </span>
                            </div>
                            <?php if ($paymentOption !== 'full'): ?>
                                <div class="summary-row">
                                    <span class="text-muted">Balance upon Receipt</span>
                                    <span
                                        class="text-muted text-end">₱<?php echo number_format($finalTotal - $minDownPayment, 2); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn-place-order w-100" id="btnConfirmOrder">
                                CONFIRM ORDER <i class="bi bi-check-circle ms-2"></i>
                            </button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <style>
        .icon-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-input-group {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #ced4da;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .custom-input-group:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }

        .custom-input-group .input-group-text {
            border: none;
            padding-left: 1.25rem;
            padding-right: 0.75rem;
        }

        .custom-input-group .form-control {
            border: none;
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
        }

        .custom-input-group .form-control:focus {
            box-shadow: none;
        }

        /* Custom file input styling */
        input[type="file"]::file-selector-button {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            margin-right: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        input[type="file"]::file-selector-button:hover {
            background-color: #e9ecef;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
    <script>
        // Form submission handling
        document.getElementById('btnConfirmOrder').addEventListener('click', function (e) {
            // Native validation will handle required fields
        });
    </script>
</body>

</html>