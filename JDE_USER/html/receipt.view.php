<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Receipt #<?php echo $orderID; ?> - JDE Works</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/receipt.css?v=<?php echo time(); ?>">
    <!-- html2pdf Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>

    <div class="receipt-wrapper" data-order-id="<?php echo $orderID; ?>"
        data-order-customer-id="<?php echo $order['customerID']; ?>">
        <!-- Success Banner -->
        <div class="success-banner no-print">
            <div class="success-icon"><i class="bi bi-patch-check-fill"></i></div>
            <h2 class="fw-bold">
                <?php
                $pMethod = strtolower($order['paymentMethodName'] ?? '');
                if (strpos($pMethod, 'gcash') !== false) {
                    echo "Awaiting Payment Confirmation";
                } else {
                    echo "Order Processing!";
                }
                ?>
            </h2>
            <p class="text-muted mb-0">
                <?php if (strpos($pMethod, 'gcash') !== false): ?>
                    We're currently verifying your payment. Please wait for an update.
                <?php else: ?>
                    We're getting your items ready for tailoring.
                <?php endif; ?>
            </p>
        </div>

        <!-- On-Screen Modern Layout -->
        <div class="receipt-grid no-print">
            <!-- Left Side: Items & Metadata -->
            <div class="main-content">
                <!-- Order Metadata Card -->
                <div class="info-card">
                    <div class="card-title">
                        <i class="bi bi-receipt"></i> Order Overview
                    </div>
                    <div class="order-meta">
                        <div class="meta-item">
                            <label>Order ID</label>
                            <span>#<?php echo str_pad($orderID, 8, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Placed On</label>
                            <span><?php echo date('M d, Y', strtotime($order['orderDate'] ?? 'today')); ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Status</label>
                            <span
                                class="order-status"><?php echo ucfirst($order['orderStatus'] ?? 'Confirmed'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Purchased Items Card -->
                <div class="info-card">
                    <div class="card-title">
                        <i class="bi bi-box-seam"></i> Purchased Items
                    </div>
                    <div class="items-list">
                        <?php
                        require_once '../backend/discount_helper.php';
                        $idx = 0;
                        foreach ($items_array as $item):
                            $pId = $item['productID'];

                            // Fetch product data for override check
                            $pData = null;
                            if ($pId) {
                                $stmt = $conn->prepare("SELECT customDiscountPercent, customMinQty FROM tbl_product WHERE productID = ? LIMIT 1");
                                $stmt->bind_param("i", $pId);
                                $stmt->execute();
                                $pData = $stmt->get_result()->fetch_assoc();
                                $stmt->close();
                            }

                            // Use the same grouping logic as the checkout
                            $qtyInGroup = 0;
                            foreach ($items_array as $it) {
                                if (($it['productID'] ?: 'custom') === ($pId ?: 'custom')) {
                                    $qtyInGroup += (int) $it['quantity'];
                                }
                            }

                            $calc = DiscountSystem::calculateItemDiscount($qtyInGroup, $item['price'], $pData);
                            $itemDiscountPct = $calc['percentage'];
                            $itemTotalOriginal = $item['price'] * $item['quantity'];
                            $itemDiscountAmt = ($itemTotalOriginal) * ($itemDiscountPct / 100);
                            $itemFinalSubtotal = $itemTotalOriginal - $itemDiscountAmt;
                            $idx++;
                            ?>
                            <div class="purchased-item">
                                <div class="item-img-wrapper">
                                    <img src="<?php echo htmlspecialchars($item['productImage']); ?>" alt="Product">
                                </div>
                                <div class="item-details">
                                    <h4 class="item-name">
                                        <?php echo htmlspecialchars($item['productName']); ?>
                                        <?php if (empty($item['customizeID']) && !empty($item['size'])): ?>
                                            <span class="text-muted"
                                                style="font-size: 0.75rem; font-weight: 500; margin-left: 4px;">[
                                                <?php echo htmlspecialchars($item['size']); ?> ]</span>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="item-meta">Category:
                                        <?php echo htmlspecialchars($item['category'] ?? 'Uniform'); ?>
                                    </div>
                                    <div class="item-price-qty d-flex flex-column align-items-start mt-2">
                                        <?php if ($itemDiscountPct > 0): ?>
                                            <div class="text-muted small mb-1" style="text-decoration: line-through;">
                                                ₱<?php echo number_format($itemTotalOriginal, 2); ?></div>
                                            <div class="text-success small fw-bold"
                                                style="font-size: 0.75rem; margin-bottom: 2px;"><?php echo $itemDiscountPct; ?>%
                                                Off (-₱<?php echo number_format($itemDiscountAmt, 2); ?>)</div>
                                        <?php endif; ?>
                                        <div class="text-muted small mb-1">₱<?php echo number_format($item['price'], 2); ?>
                                            x <?php echo $item['quantity']; ?></div>
                                        <div class="item-total fw-bold text-gold" style="color: #c5a880;">
                                            ₱<?php echo number_format($itemFinalSubtotal, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side: Customer & Billing -->
            <div class="side-content d-flex flex-column gap-4">
                <!-- Customer Details Card -->
                <div class="info-card">
                    <div class="card-title">
                        <i class="bi bi-person"></i> Customer Info
                    </div>
                    <div class="customer-info-grid">
                        <div class="customer-item">
                            <label>Name</label>
                            <p><?php echo htmlspecialchars($customer['firstName'] . ' ' . $customer['lastName']); ?></p>
                        </div>
                        <div class="customer-item">
                            <label>Phone</label>
                            <p><?php echo htmlspecialchars($customer['phoneNumber'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="customer-item">
                            <label>Shipping Address</label>
                            <p class="text-muted" style="font-size: 0.85rem; line-height: 1.4;">
                                <?php echo nl2br(htmlspecialchars($order['shippingAddress'] ?? 'No address provided')); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary Card -->
                <div class="info-card">
                    <div class="card-title">
                        <i class="bi bi-cash-stack"></i> Order Summary
                    </div>
                    <div class="summary-calculations">
                        <div class="summary-row">
                            <span class="text-muted">Subtotal</span>
                            <span>₱<?php echo number_format($order['subtotal'] > 0 ? $order['subtotal'] : $subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="text-muted">Shipping</span>
                            <?php
                            $discountAmount = (float) ($order['discountAmount'] ?? 0);
                            $discountPercentage = (int) ($order['discountPercentage'] ?? 0);
                            
                            // Use stored shippingFee if available (new orders), otherwise derive it (backward compatibility)
                            $actualSubtotal = $order['subtotal'] > 0 ? $order['subtotal'] : $subtotal;
                            $derivedShippingFee = ($order['totalPrice'] + $discountAmount) - $actualSubtotal;
                            $displayShippingFee = isset($order['shippingFee']) ? (float)$order['shippingFee'] : $derivedShippingFee;

                            if ($displayShippingFee < 0)
                                $displayShippingFee = 0;
                            $isPickup = strpos(strtolower($order['distributionMethod'] ?? ''), 'pick') !== false;
                            ?>
                            <?php if ($isPickup): ?>
                                <span class="text-success fw-bold">FREE</span>
                            <?php elseif ($displayShippingFee <= 0 && !empty($order['originalShippingFee'])): ?>
                                <span><span class="text-success fw-bold">FREE</span> <del
                                        class="text-muted ms-1">₱<?php echo number_format($order['originalShippingFee'], 0); ?></del></span>
                            <?php elseif ($displayShippingFee <= 0): ?>
                                <span class="text-success fw-bold">FREE</span>
                            <?php else: ?>
                                <span class="fw-bold">₱<?php echo number_format($displayShippingFee, 2); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($discountAmount > 0): ?>
                            <div class="summary-row text-success">
                                <span>Discount
                                    <?php echo $discountPercentage > 0 ? "({$discountPercentage}%)" : ""; ?></span>
                                <span>-₱<?php echo number_format($discountAmount, 2); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-row summary-total">
                            <span>TOTAL</span>
                            <span>₱<?php echo number_format($order['totalPrice'], 2); ?></span>
                        </div>

                        <?php
                        $isVerified = (strtolower($order['paymentStatus'] ?? '') === 'approved');
                        $isCompletedRec = in_array(strtolower($order['orderStatus'] ?? ''), ['completed', 'delivered']);
                        $balRec = $isCompletedRec ? 0 : ($isVerified ? $order['paymentBalance'] : $order['totalPrice']);
                        ?>
                        <div class="summary-row">
                            <span class="fw-bold">BALANCE</span>
                            <span class="fw-bold <?php echo $balRec <= 0 ? 'text-success' : 'text-danger'; ?>">
                                ₱<?php echo number_format($balRec, 2); ?>
                            </span>
                        </div>
                        <?php 
                        $isGcash = strpos(strtolower($order['paymentMethodName'] ?? ''), 'gcash') !== false;
                        $balancePending = (strtolower($order['balancePaymentStatus'] ?? '') === 'pending');
                        if ($isGcash && (!$isVerified || $balancePending)): 
                        ?>
                            <div class="summary-row">
                                <span class="text-muted small italic">Note: <?php echo $balancePending ? 'Balance' : 'Payment'; ?> verification pending.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="payment-method-badge">
                        <i class="bi bi-credit-card"></i>
                        <div>
                            <div class="small text-muted">Paid using</div>
                            <span><?php echo htmlspecialchars($order['paymentMethodName']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- POS-Style Receipt (Hidden on screen, visible on print/PDF) -->
        <div class="pos-receipt shadow-sm" id="printableReceipt">
            <!-- POS Header -->
            <div class="pos-header">
                <div class="pos-logo-container">
                    <img src="../assets/img/logojd.png" alt="JDE Works Logo" class="pos-logo">
                </div>
                <h2 class="pos-store-name">JDE WORKS</h2>
                <p class="pos-store-info">
                    Of Our Hands Custom Tailoring<br>
                    123 Tailor Lane, Fashion District<br>
                    Tel: +63 912 345 6789<br>
                    jdeworks0@gmail.com
                </p>
                <div class="pos-divider"></div>
            </div>

            <!-- Transaction Info -->
            <div class="pos-section">
                <div class="pos-row">
                    <span>Receipt #:</span>
                    <span><?php echo str_pad($orderID, 8, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="pos-row">
                    <span>Date:</span>
                    <span><?php echo date('M d, Y H:i', strtotime($order['orderDate'] ?? 'now')); ?></span>
                </div>
                <div class="pos-row">
                    <span>Customer:</span>
                    <span><?php echo htmlspecialchars($customer['firstName'] . ' ' . $customer['lastName']); ?></span>
                </div>
                <div class="pos-row">
                    <span>Method:</span>
                    <span><?php echo ucfirst(htmlspecialchars($order['paymentMethodName'])); ?></span>
                </div>
            </div>

            <div class="pos-divider"></div>

            <!-- Itemized List -->
            <div class="pos-items-header">
                <span class="col-item">ITEM</span>
                <span class="col-qty">QTY</span>
                <span class="col-price">PRICE</span>
            </div>

            <div class="pos-items-list">
                <?php
                $idx = 0;
                foreach ($items_array as $item):
                    $pId = $item['productID'];

                    // Fetch product data for override check
                    $pData = null;
                    if ($pId) {
                        $stmt = $conn->prepare("SELECT customDiscountPercent, customMinQty FROM tbl_product WHERE productID = ? LIMIT 1");
                        $stmt->bind_param("i", $pId);
                        $stmt->execute();
                        $pData = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }

                    // Use the same grouping logic as the checkout
                    $qtyInGroup = 0;
                    foreach ($items_array as $it) {
                        if (($it['productID'] ?: 'custom') === ($pId ?: 'custom')) {
                            $qtyInGroup += (int) $it['quantity'];
                        }
                    }

                    $calc = DiscountSystem::calculateItemDiscount($qtyInGroup, $item['price'], $pData);
                    $itemDiscountPct = $calc['percentage'];
                    $itemTotalOriginal = $item['price'] * $item['quantity'];
                    $itemDiscountAmt = ($itemTotalOriginal) * ($itemDiscountPct / 100);
                    $itemFinalSubtotal = $itemTotalOriginal - $itemDiscountAmt;
                    $idx++;
                    ?>
                    <div class="pos-item">
                        <div class="pos-item-main">
                            <span class="pos-item-name">
                                <?php echo htmlspecialchars($item['productName']); ?>
                                <?php if (empty($item['customizeID']) && !empty($item['size'])): ?>
                                    [<?php echo htmlspecialchars($item['size']); ?>]
                                <?php endif; ?>
                            </span>
                            <span class="pos-item-qty">₱<?php echo number_format($item['price'], 2); ?> x
                                <?php echo $item['quantity']; ?></span>
                            <span class="pos-item-total">₱<?php echo number_format($itemFinalSubtotal, 2); ?></span>
                        </div>
                        <?php if ($itemDiscountPct > 0): ?>
                            <div class="pos-item-main" style="margin-top: 2px;">
                                <span class="pos-item-name" style="font-size: 0.8em; color: #666; padding-left: 5px;">- Discount
                                    (<?php echo $itemDiscountPct; ?>%)</span>
                                <span class="pos-item-qty"></span>
                                <span class="pos-item-total"
                                    style="font-size: 0.8em; color: #666;">-₱<?php echo number_format($itemDiscountAmt, 2); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pos-divider"></div>

            <!-- Totals -->
            <div class="pos-totals">
                <div class="pos-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($order['subtotal'] > 0 ? $order['subtotal'] : $subtotal, 2); ?></span>
                </div>
                <div class="pos-row">
                    <span>Shipping:</span>
                    <?php if ($isPickup): ?>
                        <span class="text-success fw-bold">FREE</span>
                    <?php elseif ($displayShippingFee <= 0 && !empty($order['originalShippingFee'])): ?>
                        <span><span class="text-success fw-bold">FREE</span> <del
                                class="text-muted ms-1">₱<?php echo number_format($order['originalShippingFee'], 0); ?></del></span>
                    <?php elseif ($displayShippingFee <= 0): ?>
                        <span class="text-success fw-bold">FREE</span>
                    <?php else: ?>
                        <span>₱<?php echo number_format($displayShippingFee, 2); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($discountAmount > 0): ?>
                    <div class="pos-row text-success">
                        <span>Discount <?php echo $discountPercentage > 0 ? "({$discountPercentage}%)" : ""; ?>:</span>
                        <span>-₱<?php echo number_format($discountAmount, 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="pos-divider"></div>
                <div class="pos-row pos-grand-total">
                    <span>TOTAL:</span>
                    <span>₱<?php echo number_format($order['totalPrice'], 2); ?></span>
                </div>

                <div class="pos-row pos-balance">
                    <span>BALANCE:</span>
                    <?php
                    $balPos = $isCompletedRec ? 0 : ($isVerified ? $order['paymentBalance'] : $order['totalPrice']);
                    ?>
                    <span>₱<?php echo number_format($balPos, 2); ?></span>
                </div>
            </div>

            <div class="pos-divider"></div>

            <!-- Footer Message -->
            <div class="pos-footer">
                <?php
                $pStat = strtolower($order['paymentStatus'] ?? '');
                $bStat = strtolower($order['balancePaymentStatus'] ?? '');
                $pOpt = strtolower($order['paymentOption'] ?? 'full');
                $isFull = ($pOpt === 'full');
                $isComp = in_array(strtolower($order['orderStatus'] ?? ''), ['completed', 'delivered', 'picked up']);
                
                $displayStatus = 'PENDING';
                if ($isFull) {
                    $displayStatus = ($pStat === 'approved' || $isComp) ? 'PAID' : strtoupper($pStat ?: 'PENDING');
                } else {
                    if ($isComp || $bStat === 'approved') {
                        $displayStatus = ($bStat === 'approved') ? 'FULLY PAID' : 'PAID';
                    } elseif ($pStat === 'approved') {
                        $displayStatus = 'PARTIALLY PAID';
                    } else {
                        $displayStatus = strtoupper($pStat ?: 'PENDING');
                    }
                }
                ?>
                <p>Payment Status: <?php echo $displayStatus; ?></p>
                <p>Tailoring Lead Time: 7-14 Days</p>
                <div class="pos-qr-container">
                    <i class="bi bi-qr-code"></i>
                </div>
                <p class="pos-thank-you">THANK YOU FOR YOUR ORDER!</p>
                <p class="pos-mini-note">This is a system-generated receipt.</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="btn-actions no-print">
            <button onclick="window.print()" class="btn-premium btn-outline">
                <i class="bi bi-printer"></i> Print
            </button>
            <button onclick="downloadPDF('<?php echo str_pad($orderID, 8, "0", STR_PAD_LEFT); ?>')"
                class="btn-premium btn-gold">
                <i class="bi bi-download"></i> Download Receipt
            </button>
            <a href="index.php" class="btn-premium btn-outline">
                <i class="bi bi-house"></i> Go to Home
            </a>
        </div>

        <script src="../js/receipt.js"></script>

        <div class="text-center mt-5 no-print text-muted small">
            <p>If you have any questions, please contact our support at <br> jdeworks0@gmail.com</p>
        </div>
    </div>

</body>

</html>