<?php
// Prevent direct access — must be routed through payment.php or payment_method.php
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
    <title>Payment Method - JDE Works of Our Hands</title>

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
                <a href="payment_method.php" class="progress-step active">
                    <div class="step-number">4</div>
                    <div class="step-label">Balance Payment Method</div>
                </a>
                <div class="progress-line"></div>
                <div class="progress-step disabled">
                    <div class="step-number">5</div>
                    <div class="step-label">Payment Processing</div>
                </div>
            </div>

            <div class="text-center mb-5">
                <h1 class="fw-bold" style="color: var(--primary-dark-blue); font-size: 2.5rem;">Payment</h1>
            </div>

            <form action="upload_proof.php" method="POST" class="payment-form w-100">
                <div class="cart-layout">
                    <div style="flex: 2;">
                        <input type="hidden" name="payment_option"
                            value="<?php echo htmlspecialchars($paymentOption); ?>">
                        <div class="checkout-form-section">
                            <div class="section-header mb-2">
                                <h3><i class="bi bi-credit-card"></i> How would you like to pay your remaining balance?
                                </h3>
                            </div>
                            <p class="text-muted mb-4">
                                Please select your preferred payment method for settling the remaining 50% balance once
                                your order is ready for delivery or pickup.
                            </p>

                            <!-- Step 4: Payment Method Selection -->
                            <div class="payment-options">
                                <!-- GCash Option (Primary for DP/Full) -->
                                <div class="form-check payment-option selected" id="gcashContainer">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paymentGCash"
                                        value="gcash" checked>
                                    <label class="form-check-label w-100 ps-2" for="paymentGCash">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>GCash Payment</strong>
                                            <i class="bi bi-qr-code-scan fs-5 text-primary"></i>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <?php if ($paymentOption === 'full'): ?>
                                                Pay your full payment now via GCash. Your order will be confirmed upon
                                                verification.
                                            <?php else: ?>
                                                Pay your initial 50% Downpayment now via GCash, and the <strong>remaining
                                                    balance</strong> later via GCash as well.
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>

                                <!-- Cash Option (Only for Balance if not Full Payment) -->
                                <?php if ($paymentOption !== 'full'): ?>
                                    <div class="form-check payment-option mt-3" id="cashContainer">
                                        <input class="form-check-input" type="radio" name="payment_method" id="paymentCash"
                                            value="<?php echo ($distMethod === 'pickup') ? 'pickup' : 'cod'; ?>">
                                        <label class="form-check-label w-100 ps-2" for="paymentCash">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong><?php echo ($distMethod === 'pickup') ? 'Cash on Pickup (For Balance)' : 'Cash on Delivery (For Balance)'; ?></strong>
                                                <i class="bi bi-cash-coin fs-5 text-success"></i>
                                            </div>
                                            <div class="text-muted small mt-1">
                                                Pay the 50% DP via GCash now, and the <strong>remaining 50%</strong> upon
                                                <?php echo ($distMethod === 'pickup' ? 'collection' : 'delivery'); ?>.
                                            </div>
                                        </label>
                                    </div>
                                <?php endif; ?>
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
                                <div class="summary-row">
                                    <span class="text-muted">Balance</span>
                                    <span class="text-muted text-end"
                                        id="summaryBalanceValue">₱<?php echo number_format($finalTotal - $minDownPayment, 2); ?></span>
                                </div>
                            </div>
                            <button type="submit" class="btn-place-order w-100 mt-4">
                                CONTINUE TO VERIFICATION <i class="bi bi-arrow-right-circle ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        </div>

        <script src="../js/checkout.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const summaryReqLabel = document.getElementById('summaryRequiredPaymentLabel');
                const summaryReqValue = document.getElementById('summaryRequiredPaymentValue');
                const summaryBalance = document.getElementById('summaryBalanceValue');

                const finalTotal = <?php echo $finalTotal; ?>;
                const downPayment = <?php echo $minDownPayment; ?>;
                const paymentOption = "<?php echo $paymentOption; ?>";

                // Update summary totals based on previous step
                if (paymentOption === 'full') {
                    if (summaryReqLabel) summaryReqLabel.textContent = 'Required Full Payment';
                    if (summaryReqValue) summaryReqValue.textContent = '₱' + finalTotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    if (summaryBalance) summaryBalance.textContent = '₱0.00';
                } else {
                    if (summaryReqLabel) summaryReqLabel.textContent = 'Required Down Payment (50%)';
                    if (summaryReqValue) summaryReqValue.textContent = '₱' + downPayment.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    if (summaryBalance) summaryBalance.textContent = '₱' + (finalTotal - downPayment).toLocaleString('en-US', { minimumFractionDigits: 2 });
                }

                // Payment Method Selection Highlighting
                const paymentOptions = document.querySelectorAll('.payment-option');
                const paymentInputs = document.querySelectorAll('input[name="payment_method"]');

                paymentInputs.forEach(input => {
                    input.addEventListener('change', function () {
                        paymentOptions.forEach(opt => opt.classList.remove('selected'));
                        this.closest('.payment-option').classList.add('selected');
                    });
                });
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../js/notifications.js"></script>
        <script src="../js/navbar_cart.js"></script>
        <script src="../js/site.js"></script>
</body>

</html>