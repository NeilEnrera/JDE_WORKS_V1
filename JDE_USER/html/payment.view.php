<?php
// Prevent direct access — must be routed through payment.php
if (!isset($grandTotal)) {
    header('Location: ../backend/payment.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payment - JDE Works of Our Hands</title>

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
                <a href="payment.php" class="progress-step active">
                    <div class="step-number">3</div>
                    <div class="step-label">Payment Option</div>
                </a>
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
                <h1 class="fw-bold" style="color: var(--primary-dark-blue); font-size: 2.5rem;">Payment</h1>
            </div>

            <form action="payment_method.php" method="POST" class="payment-form w-100">
                <div class="cart-layout">
                    <div style="flex: 2;">
                        <div class="checkout-form-section">
                            <div class="section-header mb-4">
                                <h3><i class="bi bi-wallet2"></i> Payment Option</h3>
                            </div>

                            <!-- Step 3: Payment Option -->
                            <div class="payment-options mb-4">
                                <!-- Full Payment Option -->
                                <div class="form-check payment-option mb-3">
                                    <input class="form-check-input" type="radio" name="payment_option" id="optionFull"
                                        value="full">
                                    <label class="form-check-label w-100 ps-2" for="optionFull">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Full Payment</strong>
                                            <i class="bi bi-circle-fill fs-5 text-secondary"></i>
                                        </div>
                                        <div class="text-muted small mt-1">Pay the total amount upfront.</div>
                                    </label>
                                </div>

                                <!-- Downpayment Option -->
                                <div class="form-check payment-option mb-3 selected">
                                    <input class="form-check-input" type="radio" name="payment_option" id="optionDown"
                                        value="half" checked>
                                    <label class="form-check-label w-100 ps-2" for="optionDown">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>50% Down Payment</strong>
                                            <i class="bi bi-circle-half fs-5 text-warning"></i>
                                        </div>
                                        <div class="text-muted small mt-1">Pay half now, and the remaining balance upon
                                            receipt.</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cart-summary-section">
                        <div class="summary-card">
                            <div class="summary-header">
                                <h3>Order Summary</h3>
                            </div>
                            <div class="summary-calculations">
                                <div class="summary-row">
                                    <span>Subtotal</span>
                                    <span>₱<?php echo number_format($originalSubtotal ?? $grandTotal, 2); ?></span>
                                </div>
                                <?php if (isset($discountAmount) && $discountAmount > 0): ?>
                                    <div class="summary-row text-success">
                                        <span>Bulk Discount</span>
                                        <span>-₱<?php echo number_format($discountAmount, 2); ?></span>
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
                                    <span class="fw-bold text-danger" id="summaryRequiredPaymentLabel">Required Down
                                        Payment (50%)</span>
                                    <span class="fw-bold text-danger text-end"
                                        id="summaryRequiredPaymentValue">₱<?php echo number_format($minDownPayment, 2); ?></span>
                                </div>

                            </div>
                            <button type="submit" class="btn-place-order w-100 mt-4">
                                CONTINUE TO PAYMENT METHOD <i class="bi bi-arrow-right-circle ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../js/notifications.js"></script>
        <script src="../js/navbar_cart.js"></script>
        <script src="../js/site.js"></script>
        <script src="../js/checkout.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const optionFull = document.getElementById('optionFull');
                const optionDown = document.getElementById('optionDown');

                const summaryReqLabel = document.getElementById('summaryRequiredPaymentLabel');
                const summaryReqValue = document.getElementById('summaryRequiredPaymentValue');


                const finalTotal = <?php echo $finalTotal; ?>;
                const downPayment = <?php echo $minDownPayment; ?>;

                const btnContinue = document.querySelector('.btn-place-order');

                function updateUI() {
                    const isFull = optionFull.checked;

                    // Update styling
                    document.querySelectorAll('.payment-option').forEach(opt => {
                        opt.classList.remove('selected');
                        const icon = opt.querySelector('i');
                        if (icon) {
                            icon.classList.remove('text-warning');
                            icon.classList.add('text-secondary');
                        }
                    });

                    const selectedOpt = isFull ? optionFull.closest('.payment-option') : optionDown.closest('.payment-option');
                    if (selectedOpt) {
                        selectedOpt.classList.add('selected');
                        const icon = selectedOpt.querySelector('i');
                        if (icon) {
                            icon.classList.remove('text-secondary');
                            icon.classList.add('text-warning');
                        }
                    }

                    // Update summary totals
                    if (isFull) {
                        summaryReqLabel.textContent = 'Required Full Payment';
                        summaryReqValue.textContent = '₱' + finalTotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
                        btnContinue.innerHTML = 'CONTINUE TO VERIFICATION <i class="bi bi-arrow-right-circle ms-2"></i>';
                    } else {
                        summaryReqLabel.textContent = 'Required Down Payment (50%)';
                        summaryReqValue.textContent = '₱' + downPayment.toLocaleString('en-US', { minimumFractionDigits: 2 });
                        btnContinue.innerHTML = 'CONTINUE TO PAYMENT METHOD <i class="bi bi-arrow-right-circle ms-2"></i>';
                    }
                }

                optionFull.addEventListener('change', updateUI);
                optionDown.addEventListener('change', updateUI);

                // Initialize on load
                updateUI();
            });
        </script>
</body>

</html>