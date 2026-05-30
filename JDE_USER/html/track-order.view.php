<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track My Order - JDE WORK OF OUR HANDS</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/receipt.css?v=<?php echo time(); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>
    <?php
    include '../backend/navbar.php';
    ?>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/track-order.css?v=<?php echo time(); ?>">

    <div class="track-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3">
                    <?php include 'fragments/user_sidebar.php'; ?>
                </div>
                <div class="col-lg-9">
                    <div class="track-header rounded-4">
                        <h1>Track Your Order</h1>
                        <p>Enter your Order ID to see real-time status and details</p>
                    </div>

                    <!-- Search Box -->
                    <div class="search-box">
                        <form action="track-order.php" method="GET">
                            <div class="search-input-group">
                                <input type="text" name="order_id" class="search-input"
                                    placeholder="Enter Order ID (e.g. 1025)"
                                    value="<?php echo htmlspecialchars($search_query ?? ''); ?>" required>
                                <button type="submit" class="search-btn">Track Now</button>
                            </div>
                        </form>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger text-center rounded-3 mb-4">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_message) && $success_message): ?>
                        <div class="alert alert-success text-center rounded-3 mb-4">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($order_details): ?>
                        <?php
                        $status = strtolower($order_details['orderStatus']);
                        $payment = strtolower($order_details['paymentStatus'] ?? '');
                        // Standardized tracking progress logic
                        $currentStep = 1;
                        $isFullPayment = (strtolower($order_details['paymentOption'] ?? '') === 'full');
                        $method_low = strtolower($order_details['methodName'] ?? '');
                        $isCOD = strpos($method_low, 'cash on delivery') !== false;
                        $dist_low = strtolower($order_details['distributionMethod'] ?? '');
                        $isPickUp = strpos($dist_low, 'pickup') !== false || strpos($dist_low, 'pick up') !== false;
                        $isUnpaidCOD = $isCOD && $status === 'unpaid';

                        // Step 2: Paid / Approved
                        if ($payment === 'approved' || in_array($status, ['paid', 'processing', 'awaiting balance', 'out for delivery', 'ready for pick up', 'completed', 'delivered'])) {
                            $currentStep = 2;
                        }
                        // Step 3: Processing
                        if (in_array($status, ['processing', 'order processing', 'tailoring in progress', 'awaiting balance', 'out for delivery', 'ready for pick up', 'completed', 'delivered'])) {
                            $currentStep = 3;
                        }
                        // Step 4: Awaiting Balance (Only for Half Payment)
                        if (!$isFullPayment) {
                            if (in_array($status, ['awaiting balance', 'out for delivery', 'ready for pick up', 'completed', 'delivered'])) {
                                $currentStep = 4;
                            }
                        }
                        // Step 5: Out for delivery / Ready for Pick Up
                        if (in_array($status, ['out_for_delivery', 'out for delivery', 'ready for pick up', 'completed', 'delivered'])) {
                            $currentStep = $isFullPayment ? 4 : 5;
                        }
                        // Step 6: Order Completed
                        if (in_array($status, ['completed', 'delivered', 'picked up'])) {
                            $currentStep = $isFullPayment ? 5 : 6;
                        }

                        // Progress percentage calculation (for the line)
                        $totalIntervals = $isFullPayment ? 4 : 5;
                        $progress_w = min(($currentStep - 1) * (100 / $totalIntervals), 100);
                        ?>
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="track-card">
                                    <div class="track-body">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h2 class="h4 fw-bold mb-0">Order #
                                                <?php echo $order_details['orderID']; ?>
                                            </h2>
                                            <?php
                                            $raw_status = strtolower($order_details['orderStatus']);
                                            $sClass = 'status-' . str_replace(' ', '-', $raw_status);
                                            $displayText = $order_details['orderStatus'];

                                            if ($raw_status === 'awaiting balance' && $isCOD) {
                                                $sClass .= ' status-to-be-paid-upon-' . ($isPickUp ? 'pickup' : 'delivery');
                                                $displayText = $isPickUp ? 'To be paid upon Pickup' : 'To be paid upon Delivery';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $sClass; ?>">
                                                <?php echo $displayText; ?>
                                            </span>
                                        </div>

                                        <div class="mb-4">
                                            <div class="py-2 px-3"
                                                style="background: #fcfcfc; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
                                                <div style="font-size: 0.85rem; color: #555;">
                                                    <div class="py-1">
                                                        <span class="text-muted">Placed on:</span>
                                                        <span
                                                            class="ms-1 fw-500"><?php echo $order_details['orderPlacedDate'] ? date('F j, Y, g:i a', strtotime($order_details['orderPlacedDate'])) : 'N/A'; ?></span>
                                                    </div>
                                                    <?php if (strtolower($order_details['distributionMethod'] ?? '') === 'delivery'): ?>
                                                        <div class="py-1 border-top" style="border-color: #f0f0f0 !important;">
                                                            <span class="text-muted">Delivery:</span>
                                                            <span
                                                                class="ms-1 fw-500"><?php echo date('m/d', strtotime($order_details['orderPlacedDate'] . ' + 1 days')); ?>-<?php echo date('m/d', strtotime($order_details['orderPlacedDate'] . ' + 3 days')); ?>
                                                                (1-3 business days.)</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <?php 
                                            $distMethodPOD = strtolower($order_details['distributionMethod'] ?? '');
                                            $orderStatusPOD = strtolower($order_details['orderStatus'] ?? '');
                                            $proofPOD = $order_details['proofOfDelivery'] ?? '';

                                            $isDeliveryPOD = ($distMethodPOD === 'delivery');
                                            $isCompletedPOD = ($orderStatusPOD === 'completed' || $orderStatusPOD === 'delivered');
                                            $hasValidProofPOD = (!empty($proofPOD) && strtolower($proofPOD) !== 'null' && strpos(strtolower($proofPOD), 'default') === false && strpos(strtolower($proofPOD), 'placeholder') === false);

                                            if ($isDeliveryPOD && $isCompletedPOD && $hasValidProofPOD): 
                                            ?>
                                                <div class="mt-3 pt-3" style="border-top: 1px solid #e9ecef;">
                                                    <p class="mb-2"
                                                        style="font-size: 0.78rem; font-weight: 600; color: #198754;"><i
                                                            class="bi bi-check-circle-fill me-1"></i>Proof of Delivery</p>
                                                    <img src="../<?php echo $proofPOD; ?>"
                                                        alt="Proof of Delivery" class="rounded-3 shadow-sm"
                                                        style="width: 120px; height: 120px; object-fit: cover; cursor: pointer; border: 1px solid #dee2e6;"
                                                        onclick="openProofModal('../<?php echo $proofPOD; ?>')">
                                                </div>
                                            <?php endif; ?>


                                        </div>

                                        <!-- Progress Tracker -->
                                        <?php
                                        $isUnpaidCOD = ((strtolower($order_details['methodName'] ?? '') === 'cash on delivery') &&
                                            !(in_array(strtolower($order_details['paymentStatus'] ?? ''), ['approved', 'paid']) || strtolower($order_details['orderStatus']) === 'paid'));

                                        $distMethod = strtolower($order_details['distributionMethod'] ?? '');
                                        $isPickUp = (strpos($distMethod, 'pick') !== false);

                                        // Recalculate steps based on method and payment option
                                        $currentStep = 1;
                                        $status = strtolower($order_details['orderStatus']);
                                        $payment = strtolower($order_details['paymentStatus'] ?? '');
                                        
                                        $totalSteps = $isFullPayment ? 5 : 6;
                                        $startOffset = $isFullPayment ? 10 : 8.3333;
                                        $totalLength = $isFullPayment ? 80 : 83.3333;

                                        if ($isPickUp) {
                                            // Pick-up Flow: Placed -> Paid -> Processing -> [Remaining Balance] -> Ready for Pick-up -> Completed
                                            if ($payment === 'approved' || in_array($status, ['paid', 'processing', 'awaiting balance', 'ready for pick up', 'completed', 'delivered']))
                                                $currentStep = 2;
                                            if (in_array($status, ['processing', 'awaiting balance', 'ready for pick up', 'completed', 'delivered']))
                                                $currentStep = 3;
                                            
                                            if (!$isFullPayment) {
                                                if (in_array($status, ['awaiting balance', 'ready for pick up', 'completed', 'delivered']))
                                                    $currentStep = 4;
                                                if (in_array($status, ['ready for pick up', 'completed', 'delivered']))
                                                    $currentStep = 5;
                                                if (in_array($status, ['completed', 'delivered']))
                                                    $currentStep = 6;
                                            } else {
                                                if (in_array($status, ['ready for pick up', 'completed', 'delivered']))
                                                    $currentStep = 4;
                                                if (in_array($status, ['completed', 'delivered']))
                                                    $currentStep = 5;
                                            }
                                        } else {
                                            // Delivery Flow: Placed -> Paid -> Processing -> [Remaining Balance] -> Out for Delivery -> Completed
                                            if ($payment === 'approved' || in_array($status, ['paid', 'processing', 'awaiting balance', 'shipped', 'out for delivery', 'completed', 'delivered']))
                                                $currentStep = 2;
                                            if (in_array($status, ['processing', 'awaiting balance', 'shipped', 'out for delivery', 'completed', 'delivered']))
                                                $currentStep = 3;

                                            if (!$isFullPayment) {
                                                if (in_array($status, ['awaiting balance', 'shipped', 'out for delivery', 'completed', 'delivered']))
                                                    $currentStep = 4;
                                                if (in_array($status, ['out for delivery', 'completed', 'delivered']))
                                                    $currentStep = 5;
                                                if (in_array($status, ['completed', 'delivered']))
                                                    $currentStep = 6;
                                            } else {
                                                if (in_array($status, ['out for delivery', 'completed', 'delivered']))
                                                    $currentStep = 4;
                                                if (in_array($status, ['completed', 'delivered']))
                                                    $currentStep = 5;
                                            }
                                        }

                                        $gapPercent = $totalLength / ($totalSteps - 1);
                                        $currentStepIdx = $currentStep - 1;
                                        $progress_w = $currentStepIdx * $gapPercent;
                                        ?>
                                        <div class="progress-track <?php echo $isPickUp ? 'pickup-flow' : ''; ?>" 
                                             style="--total-steps: <?php echo $totalSteps; ?>; --start-offset: <?php echo $startOffset; ?>%;">
                                            <div class="progress-line" style="width: <?php echo $progress_w; ?>%;"></div>



                                            <div
                                                class="step-item <?php echo $currentStep >= 1 ? 'active' : ''; ?> <?php echo $currentStep == 1 ? 'current' : ''; ?>">
                                                <div class="step-icon"><i class="bi bi-file-earmark-text"></i></div>
                                                <div class="step-label">Order<br>Placed</div>
                                                <div class="step-time">
                                                    <?php echo date('m/d/Y', strtotime($order_details['orderPlacedDate'])); ?>
                                                </div>
                                            </div>

                                            <div
                                                class="step-item <?php echo $currentStep >= 2 && !$isUnpaidCOD ? 'active' : ''; ?> <?php echo $currentStep == 2 ? 'current' : ''; ?> <?php echo ($currentStep >= 3 && $isUnpaidCOD) ? 'unpaid-cod' : ''; ?>">
                                                <div class="step-icon"><i class="bi bi-cash-stack"></i></div>
                                                <div class="step-label">
                                                    <?php echo $isFullPayment ? 'Full' : 'Down'; ?><br>Payment
                                                </div>
                                                <?php
                                                $showPaidTime = !empty($order_details['paidAt']) ? $order_details['paidAt'] : ($order_details['paymentDate'] ?? '');
                                                if ($showPaidTime && $currentStep >= 2 && !$isUnpaidCOD):
                                                    ?>
                                                    <div class="step-time">
                                                        <?php echo date('m/d/Y', strtotime($showPaidTime)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div
                                                class="step-item <?php echo $currentStep >= 3 ? 'active' : ''; ?> <?php echo $currentStep == 3 ? 'current' : ''; ?>">
                                                <div class="step-icon"><i
                                                        class="<?php echo ($order_details['customItemsCount'] > 0) ? 'bi bi-scissors' : 'bi bi-gear'; ?>"></i>
                                                </div>
                                                <div class="step-label">
                                                    <?php echo ($order_details['customItemsCount'] > 0) ? 'Tailoring in<br>Progress' : 'Order<br>Processing'; ?>
                                                </div>
                                                <?php if (!empty($order_details['processingAt'])): ?>
                                                    <div class="step-time">
                                                        <?php echo date('m/d/Y', strtotime($order_details['processingAt'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!$isFullPayment): ?>
                                                <?php
                                                $balanceStatus = strtolower($order_details['balancePaymentStatus'] ?? 'pending');
                                                $hasBalanceProof = !empty($order_details['balanceProofOfPayment']);
                                                $isBalanceVerified = ($balanceStatus === 'approved' || $balanceStatus === 'paid');
                                                $isGCash = strpos($method_low, 'gcash') !== false;
                                                $isCOD_COP = strpos($method_low, 'cash on delivery') !== false || strpos($method_low, 'pickup') !== false || strpos($method_low, 'pick up') !== false;
                                                
                                                $balanceClass = 'is-neutral-step';
                                                if ($isCOD_COP) {
                                                    $balanceClass = 'is-cod-step';
                                                } elseif ($isGCash) {
                                                    if ($hasBalanceProof && !$isBalanceVerified) {
                                                        $balanceClass = 'is-pending-verification';
                                                    } elseif ($isBalanceVerified) {
                                                        $balanceClass = 'is-verified-paid';
                                                    } else {
                                                        $balanceClass = 'is-awaiting-upload';
                                                    }
                                                }
                                                ?>
                                                <div
                                                    class="step-item <?php echo $balanceClass; ?> <?php echo $currentStep >= 4 ? 'active' : ''; ?> <?php echo $currentStep == 4 ? 'current' : ''; ?> <?php echo (in_array($status, ['completed', 'delivered', 'picked up']) || $isBalanceVerified) ? 'is-order-completed' : ''; ?>">
                                                    <div class="step-icon"><i class="bi bi-wallet2"></i></div>
                                                    <div class="step-label">
                                                        <?php
                                                        // Determine the label based on payment method, not status
                                                        $isCOP = strpos($method_low, 'cash on pickup') !== false || strpos($method_low, 'cash on pick') !== false;
                                                        if ($isCOD) {
                                                            echo 'To be paid<br>upon Delivery';
                                                        } elseif ($isCOP || $isPickUp) {
                                                            echo 'To be paid<br>upon Pickup';
                                                        } else {
                                                            echo 'Awaiting<br>Balance';
                                                        }
                                                        ?>
                                                    </div>
                                                    <?php
                                                    $awaitingTime = !empty($order_details['balancePaidAt']) ? $order_details['balancePaidAt'] : ($order_details['awaitingBalanceAt'] ?? '');
                                                    if ($awaitingTime && $currentStep >= 4):
                                                        ?>
                                                        <div class="step-time">
                                                            <?php echo date('m/d/Y', strtotime($awaitingTime)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($isPickUp): ?>
                                                <div
                                                    class="step-item <?php echo $currentStep >= ($isFullPayment ? 4 : 5) ? 'active' : ''; ?> <?php echo $currentStep == ($isFullPayment ? 4 : 5) ? 'current' : ''; ?>">
                                                    <div class="step-icon"><i class="bi bi-shop"></i></div>
                                                    <div class="step-label">Ready for<br>Pick up</div>
                                                    <?php if (!empty($order_details['readyAt']) || !empty($order_details['shippedAt'])): ?>
                                                        <div class="step-time">
                                                            <?php echo date('m/d/Y', strtotime($order_details['readyAt'] ?? $order_details['shippedAt'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div
                                                    class="step-item <?php echo $currentStep >= ($isFullPayment ? 5 : 6) ? 'active' : ''; ?> <?php echo $currentStep == ($isFullPayment ? 5 : 6) ? 'current' : ''; ?>">
                                                    <div class="step-icon"><i class="bi bi-patch-check-fill"></i></div>
                                                    <div class="step-label">Order<br>Completed</div>
                                                    <?php if (!empty($order_details['completedAt'])): ?>
                                                        <div class="step-time">
                                                            <?php echo date('m/d/Y', strtotime($order_details['completedAt'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div
                                                    class="step-item <?php echo $currentStep >= ($isFullPayment ? 4 : 5) ? 'active' : ''; ?> <?php echo $currentStep == ($isFullPayment ? 4 : 5) ? 'current' : ''; ?>">
                                                    <div class="step-icon"><i class="bi bi-geo-fill"></i></div>
                                                    <div class="step-label">Out for<br>delivery</div>
                                                    <?php if (!empty($order_details['outForDeliveryAt'])): ?>
                                                        <div class="step-time">
                                                            <?php echo date('m/d/Y', strtotime($order_details['outForDeliveryAt'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div
                                                    class="step-item <?php echo $currentStep >= ($isFullPayment ? 5 : 6) ? 'active' : ''; ?> <?php echo $currentStep == ($isFullPayment ? 5 : 6) ? 'current' : ''; ?>">
                                                    <div class="step-icon"><i class="bi bi-patch-check-fill"></i></div>
                                                    <div class="step-label">Order<br>Completed</div>
                                                    <?php if (!empty($order_details['completedAt'])): ?>
                                                        <div class="step-time">
                                                            <?php echo date('m/d/Y', strtotime($order_details['completedAt'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <hr>

                                        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                                            <h3 class="h6 fw-bold mb-0 text-dark">Order Items</h3>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <?php
                                                $hasReviewableItems = false;
                                                foreach ($order_items as $item) {
                                                    if (!empty($item['productSlug'])) {
                                                        $hasReviewableItems = true;
                                                        break;
                                                    }
                                                }
                                                if ($currentStep >= 5 && $hasReviewableItems): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-gold px-3 rounded-pill"
                                                        data-bs-toggle="modal" data-bs-target="#reviewModal">
                                                        <i class="bi bi-star"></i> Rate Product
                                                    </button>
                                                <?php endif; ?>

                                                <?php
                                                $order_status_low = strtolower($order_details['orderStatus']);
                                                $dist_method_low = strtolower($order_details['distributionMethod'] ?? '');
                                                $is_custom_order = ($order_details['customItemsCount'] > 0);
                                                $can_cancel_ui = false;

                                                if ($is_custom_order) {
                                                    $can_cancel_ui = in_array($order_status_low, ['pending', 'unpaid', '']);
                                                } else {
                                                    if (strpos($dist_method_low, 'pick') !== false) {
                                                        $can_cancel_ui = !in_array($order_status_low, ['completed', 'delivered', 'cancelled']);
                                                    } else {
                                                        $forbidden_del_ui = ['out for delivery', 'on the way', 'delivered', 'completed', 'cancelled'];
                                                        $can_cancel_ui = !in_array($order_status_low, $forbidden_del_ui);
                                                    }
                                                }

                                                if ($can_cancel_ui):
                                                    ?>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger px-3 rounded-pill"
                                                        onclick="confirmCancel(<?php echo $order_details['orderID']; ?>, <?php echo $is_custom_order ? 'true' : 'false'; ?>)">
                                                        <i class="bi bi-x-circle me-1"></i> Cancel Order
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <?php
                                            require_once '../backend/discount_helper.php';
                                            $productGroups = [];
                                            foreach ($order_items as $item) {
                                                $pId = !empty($item['productID']) ? $item['productID'] : 'custom_' . $item['orderItemID'];
                                                if (!isset($productGroups[$pId])) {
                                                    $productGroups[$pId] = [
                                                        'qty' => 0,
                                                        'price' => $item['price'],
                                                        'productID' => $item['productID']
                                                    ];
                                                }
                                                $productGroups[$pId]['qty'] += (int) $item['quantity'];
                                            }

                                            $discountPerProduct = [];
                                            foreach ($productGroups as $pId => $group) {
                                                $pData = null;
                                                if (!empty($group['productID'])) {
                                                    $stmt = $conn->prepare("SELECT customDiscountPercent, customMinQty FROM tbl_product WHERE productID = ? LIMIT 1");
                                                    $stmt->bind_param("i", $group['productID']);
                                                    $stmt->execute();
                                                    $pData = $stmt->get_result()->fetch_assoc();
                                                    $stmt->close();
                                                }

                                                $calc = DiscountSystem::calculateItemDiscount($group['qty'], $group['price'], $pData);
                                                $discountPerProduct[$pId] = $calc['percentage'];
                                            }
                                            ?>
                                            <table class="table recent-orders-table align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th class="text-center">Size</th>
                                                        <th class="text-center">Quantity</th>
                                                        <th class="text-end">Line Total</th>
                                                        <th class="text-center" style="width: 50px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($order_items as $idx => $item):
                                                        $pId = !empty($item['productID']) ? $item['productID'] : 'custom_' . $item['orderItemID'];
                                                        $itemDiscountPct = $discountPerProduct[$pId] ?? 0;
                                                        $itemTotalOriginal = $item['price'] * $item['quantity'];
                                                        $itemDiscountAmt = $itemTotalOriginal * ($itemDiscountPct / 100);
                                                        $itemFinalSubtotal = $itemTotalOriginal - $itemDiscountAmt;
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php
                                                                    $itemImg = $item['productImage'];
                                                                    $isCustom = !empty($item['customizeID']);
                                                                    $displayName = !empty($item['productName']) ? $item['productName'] : ($isCustom ? 'Custom Tailoring' : 'Product Item');

                                                                    if (empty($itemImg)) {
                                                                        if ($isCustom) {
                                                                            $itemImg = '../assets/img/scissor.png';
                                                                        } else {
                                                                            $itemImg = '../assets/img/logojd.png';
                                                                        }
                                                                    } else {
                                                                        // Clean path and ensure it starts with ../ if not already relative to root
                                                                        $cleanPath = ltrim(str_replace('../', '', $itemImg), './');
                                                                        $itemImg = '../' . $cleanPath;
                                                                    }
                                                                    ?>
                                                                    <img src="<?php echo $itemImg; ?>" class="rounded-3 me-3"
                                                                        style="width: 60px; height: 60px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.05);"
                                                                        alt="" onerror="this.src='../assets/img/logojd.png'">
                                                                    <div class="d-flex flex-column">
                                                                         <span class="fw-bold text-dark"
                                                                             style="font-size: 0.9rem; margin-bottom: 2px;">
                                                                             <?php echo htmlspecialchars($displayName); ?>
                                                                         </span>
                                                                         <div class="item-classification">
                                                                             <?php if ($isCustom): ?>
                                                                                 <span class="badge-custom" style="font-size: 0.6rem; padding: 1px 6px;">Handcrafted</span>
                                                                             <?php elseif (!empty($item['isPreOrder'])): ?>
                                                                                 <span class="badge-preorder" style="font-size: 0.6rem; padding: 1px 6px;">Pre-Order</span>
                                                                             <?php else: ?>
                                                                                 <span class="badge-premade" style="font-size: 0.6rem; padding: 1px 6px;">Ready Stock</span>
                                                                             <?php endif; ?>
                                                                         </div>
                                                                     </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-muted small text-center">
                                                                <?php if (!$isCustom && !empty($item['size'])): ?>
                                                                    <span
                                                                        class="fw-medium text-dark"><?php echo htmlspecialchars($item['size']); ?></span>
                                                                <?php else: ?>
                                                                    -
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-muted small text-center">x
                                                                <?php echo $item['quantity']; ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <span
                                                                    class="fw-bold text-dark">₱<?php echo number_format($itemFinalSubtotal, 2); ?></span>
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-light border-0 rounded-circle text-muted d-flex align-items-center justify-content-center mx-auto hover-bg-light"
                                                                    style="width: 32px; height: 32px; transition: all 0.2s ease;"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#orderItemModal<?php echo $idx; ?>"
                                                                    title="View Item Breakdown">
                                                                    <i class="bi bi-three-dots"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <?php
                                $isPickUp = (strpos(strtolower($order_details['distributionMethod'] ?? ''), 'pick') !== false);
                                if ($isPickUp):
                                    ?>
                                    <div class="track-card border-0 mb-4">
                                        <div class="track-body order-summary-card">
                                            <h3 class="h6 fw-bold mb-3"><i class="bi bi-shop me-1 text-gold"></i>Collection
                                                Point</h3>
                                            <div class="store-details">
                                                <p class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">Hilltop Branch</p>
                                                <p class="text-muted small mb-3" style="line-height: 1.4;">
                                                    11 Esperanza, Novaliches<br>
                                                    Hilltop Subd. Greater Lagro<br>
                                                    Quezon City
                                                </p>
                                                <div class="bg-light p-2 rounded-3">
                                                    <p class="mb-1 fw-bold text-secondary"
                                                        style="font-size: 0.7rem; text-transform: uppercase;">Opening Hours</p>
                                                    <p class="text-muted small mb-0" style="font-size: 0.75rem;">Mon-Fri: 8AM -
                                                        6PM<br>Sat: 8AM - 5PM</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif (!empty($order_details['shippingAddress']) && $currentStep >= 5): ?>
                                    <div class="track-card border-0 mb-4">
                                        <div class="track-body order-summary-card">
                                            <h3 class="h6 fw-bold mb-3"><i class="bi bi-geo-alt-fill me-1"></i>Delivery Address
                                            </h3>
                                            <p class="text-muted small mb-0" style="line-height: 1.5;">
                                                <?php echo nl2br(htmlspecialchars($order_details['shippingAddress'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="track-card border-0">
                                    <div class="track-body order-summary-card">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h3 class="h6 fw-bold mb-0">Payment Summary</h3>
                                        </div>
                                        <?php
                                        // Calculate subtotal from order items
                                        $itemsSubtotal = 0;
                                        foreach ($order_items as $item) {
                                            $itemsSubtotal += $item['price'] * $item['quantity'];
                                        }
                                        $discountAmount = (float) ($order_details['discountAmount'] ?? 0);
                                        $discountPercentage = (int) ($order_details['discountPercentage'] ?? 0);
                                        
                                        // Use stored subtotal and shippingFee if available (new orders), otherwise derive them
                                        $actualSubtotal = $order_details['subtotal'] > 0 ? $order_details['subtotal'] : $itemsSubtotal;
                                        $derivedShippingFee = ($order_details['totalPrice'] + $discountAmount) - $actualSubtotal;
                                        $displayShippingFee = isset($order_details['shippingFee']) ? (float)$order_details['shippingFee'] : $derivedShippingFee;

                                        if ($displayShippingFee < 0)
                                            $displayShippingFee = 0;
                                        ?>
                                        <div
                                            class="d-flex justify-content-between gap-3 mb-3 text-secondary align-items-start">
                                            <span class="text-nowrap">Payment Method</span>
                                            <span class="fw-medium text-dark text-end" style="line-height: 1.3;">
                                                <?php
                                                $method = $order_details['methodName'] ?: 'Not Specified';
                                                echo str_ireplace('gcash', 'GCash', $method);
                                                ?>
                                            </span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between gap-3 mb-3 text-secondary align-items-start">
                                            <span class="text-nowrap">Payment Status</span>
                                            <?php
                                            $pOption = strtolower($order_details['paymentOption'] ?? 'full');
                                            $isFullPay = ($pOption === 'full');
                                            $pStatus = strtolower($order_details['paymentStatus'] ?? '');
                                            $bStatus = strtolower($order_details['balancePaymentStatus'] ?? '');
                                            $oStatus = strtolower($order_details['orderStatus'] ?? '');
                                            $isApproved = ($pStatus === 'approved' || $oStatus === 'paid');
                                            $isBalanceApproved = ($bStatus === 'approved');
                                            $isCompleted = in_array($oStatus, ['completed', 'delivered', 'picked up']);
                                            $isCOD = (strpos(strtolower($order_details['methodName'] ?? ''), 'cash on delivery') !== false);
                                            $isCOP = (strpos(strtolower($order_details['methodName'] ?? ''), 'cash on pickup') !== false || strpos(strtolower($order_details['methodName'] ?? ''), 'cash on pick up') !== false);

                                            $statusClass = '';
                                            $paymentStatusText = '';

                                            if ($isFullPay) {
                                                if ($isApproved || $isCompleted) {
                                                    $statusClass = 'text-success';
                                                    $paymentStatusText = 'PAID';
                                                } else {
                                                    // Pending, Unpaid, Rejected
                                                    if ($pStatus === 'pending' || $pStatus === 'unpaid' || $pStatus === '') {
                                                        $statusClass = 'text-warning';
                                                        $paymentStatusText = 'Pending Verification';
                                                    } elseif ($pStatus === 'rejected') {
                                                        $statusClass = 'text-danger';
                                                        $paymentStatusText = 'Payment Rejected';
                                                    } else {
                                                        $statusClass = 'text-danger';
                                                        $paymentStatusText = strtoupper($order_details['paymentStatus'] ?: 'Unpaid');
                                                    }
                                                }
                                            } else {
                                                // Down Payment
                                                if ($isCompleted || $isBalanceApproved) {
                                                    $statusClass = 'text-success';
                                                    $paymentStatusText = $isBalanceApproved ? 'FULLY PAID' : 'PAID';
                                                } else {
                                                    if ($oStatus === 'awaiting balance' && ($isCOD || $isCOP)) {
                                                        $statusClass = 'text-danger';
                                                        $paymentStatusText = 'To be paid upon ' . ($isPickUp ? 'Pickup' : 'Delivery');
                                                    } else {
                                                        // Before completion, for down payment, it's either Partially Paid (if DP approved) or Pending
                                                        if ($isApproved) {
                                                            $statusClass = 'text-primary';
                                                            $paymentStatusText = 'Partially Paid';
                                                        } else {
                                                            $statusClass = 'text-warning';
                                                            $paymentStatusText = 'Pending Verification';
                                                        }
                                                    }
                                                }
                                            }
                                            ?>
                                            <span class="fw-medium text-uppercase text-end <?php echo $statusClass; ?>"
                                                style="line-height: 1.3;">
                                                <?php echo $paymentStatusText; ?>
                                            </span>
                                        </div>

                                        <div class="order-summary-details">
                                            <?php if (!$isPickUp): ?>
                                              <div class="d-flex justify-content-between mb-2 text-secondary">
                                            <span>Subtotal</span>
                                            <span class="fw-medium text-dark">₱<?php echo number_format($actualSubtotal, 2); ?></span>
                                        </div>            
                                        <div class="d-flex justify-content-between mb-2 text-secondary">
                                            <span>Shipping Fee</span>
                                            <span class="fw-medium text-dark">
                                                <?php
                                                if ($displayShippingFee <= 0) {
                                                    echo '<span class="text-success">FREE</span>';
                                                } else {
                                                    echo '₱' . number_format($displayShippingFee, 2);
                                                }
                                                ?>
                                            </span>
                                        </div>            
                                            <?php endif; ?>

                                            <?php if ($discountAmount > 0): ?>
                                                <div class="d-flex justify-content-between mb-2 text-success align-items-start"
                                                    style="font-size:0.88rem;">
                                                    <span class="text-nowrap">Bulk Discount
                                                        <?php echo $discountPercentage > 0 ? "({$discountPercentage}%)" : ""; ?></span>
                                                    <span
                                                        class="text-end">-₱<?php echo number_format($discountAmount, 2); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="summary-total">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span class="text-nowrap">Total Amount</span>
                                                <span
                                                    class="text-end">₱<?php echo number_format($order_details['totalPrice'], 2); ?></span>
                                            </div>
                                        </div>

                                        <?php
                                        $isVerified = (strtolower($order_details['paymentStatus'] ?? '') === 'approved');
                                        $isGCash = strpos(strtolower($order_details['methodName'] ?? ''), 'gcash') !== false;
                                        ?>
                                        <hr class="my-3 text-muted" style="opacity: 0.15;">
                                        <?php if ($isGCash): ?>
                                            <div class="d-flex justify-content-between mb-2 text-secondary align-items-start"
                                                style="font-size:0.88rem; line-height: 1.3;">
                                                <span
                                                    class="text-nowrap"><?php echo $isFullPayment ? 'Total Payment' : 'Required<br>Downpayment (50%)'; ?></span>
                                                <span
                                                    class="fw-bold <?php echo $isVerified ? 'text-success' : 'text-danger'; ?>">
                                                    <?php
                                                    if ($isVerified) {
                                                        echo '₱0.00';
                                                    } elseif (!empty($order_details['proofOfPayment'])) {
                                                        echo '<span class="text-warning" style="font-size: 0.75rem;">VERIFYING...</span>';
                                                    } else {
                                                        echo '₱' . number_format($order_details['downPaymentAmount'], 2);
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between mb-2 text-secondary align-items-start"
                                            style="font-size:0.88rem; line-height: 1.3;">
                                            <span class="text-nowrap">Balance</span>
                                            <?php
                                            $isCompleted = in_array(strtolower($order_details['orderStatus']), ['completed', 'delivered', 'picked up']);
                                            $hasBalanceProof = !empty($order_details['balanceProofOfPayment']);
                                            $balanceVerified = (strtolower($order_details['balancePaymentStatus'] ?? '') === 'approved');

                                            if ($isCompleted || $balanceVerified) {
                                                $balanceToDisplay = 0;
                                            } else {
                                                // If DP is verified, show the paymentBalance (remaining)
                                                // If DP is NOT verified, the full price is technically still the balance
                                                $balanceToDisplay = $isVerified ? $order_details['paymentBalance'] : $order_details['totalPrice'];
                                            }
                                            ?>
                                            <span
                                                class="fw-bold text-end <?php echo $balanceToDisplay <= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php
                                                if ($balanceToDisplay > 0 && $hasBalanceProof && !$balanceVerified) {
                                                    echo '<span class="text-warning" style="font-size: 0.75rem;">VERIFYING...</span>';
                                                } else {
                                                    echo '₱' . number_format($balanceToDisplay, 2);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <?php if ($isGCash): ?>
                                            <?php if (!$isVerified && !empty($order_details['proofOfPayment'])): ?>
                                                <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                                                    <i class="bi bi-info-circle text-warning me-1"></i> Downpayment verification
                                                    pending.
                                                </div>
                                            <?php elseif ($isVerified && $hasBalanceProof && !$balanceVerified): ?>
                                                <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                                                    <i class="bi bi-info-circle text-warning me-1"></i> Balance verification
                                                    pending.
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <div class="mt-4 pt-3 border-top">
                                            <button
                                                onclick="downloadPDF('<?php echo str_pad($order_details['orderID'], 8, '0', STR_PAD_LEFT); ?>')"
                                                class="btn btn-gold w-100 rounded-pill fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2"
                                                style="background-color: #d6b25e; border: none; color: white;">
                                                <i class="bi bi-download"></i> Download Receipt
                                            </button>
                                            <p class="text-center text-muted small mt-2 mb-0">Official Digital Receipt</p>
                                        </div>

                                        <?php
                                        $status_low = strtolower($order_details['orderStatus'] ?? '');
                                        $method_low = strtolower($order_details['methodName'] ?? '');
                                        $isGCash = strpos($method_low, 'gcash') !== false;
                                        $isCOD = strpos($method_low, 'cash on delivery') !== false;
                                        $isPickUp = strpos($method_low, 'pickup') !== false || strpos($method_low, 'pick up') !== false;

                                        $isAwaiting = in_array($status_low, ['awaiting balance', 'downpayment']);
                                        $isProcessing = in_array($status_low, ['processing', 'order processing', 'tailoring in progress']);
                                        $balanceDue = (float) ($order_details['paymentBalance'] ?? 0);

                                        // Show section if balance is due and order is in a relevant state
                                        $showSettleBalance = ($isAwaiting || $isProcessing) && $balanceDue > 0;

                                        if ($showSettleBalance): ?>
                                            <div class="remaining-balance-sidebar mt-4 p-3 rounded-3"
                                                style="background: <?php echo ($isCOD || $isPickUp) ? '#e8f4fd' : '#fff9e6'; ?>; 
                                                       border: 1px solid <?php echo ($isCOD || $isPickUp) ? '#bee5eb' : '#ffeeba'; ?>;">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <div class="icon-circle-sm <?php echo ($isCOD || $isPickUp) ? 'bg-primary' : 'bg-warning'; ?> text-white"
                                                        style="width: 24px; height: 24px; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                                        <i
                                                            class="bi <?php echo ($isCOD || $isPickUp) ? 'bi-truck' : 'bi-credit-card-2-front'; ?>"></i>
                                                    </div>
                                                    <h3 class="h6 fw-bold mb-0" style="font-size: 0.85rem;">
                                                        <?php echo ($isCOD || $isPickUp) ? 'Payment Notice' : 'Settle Balance'; ?>
                                                    </h3>
                                                    <?php if (!$isCOD && !$isPickUp): ?>
                                                        <span class="badge bg-danger rounded-pill"
                                                            style="font-size: 0.55rem; padding: 2px 6px;">REQUIRED</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info text-dark rounded-pill"
                                                            style="font-size: 0.55rem; padding: 2px 6px;">INFO</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($isCOD || $isPickUp): ?>
                                                    <!-- COD / Pickup Message -->
                                                    <p class="text-muted mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                                                        Your remaining balance of
                                                        <strong
                                                            class="text-dark">₱<?php echo number_format($balanceDue, 2); ?></strong>
                                                        will be collected via
                                                        <strong><?php echo $isCOD ? 'Cash on Delivery' : 'Cash on Pickup'; ?></strong>
                                                        once your order arrives.
                                                    </p>
                                                    <div class="mt-2 pt-2 border-top"
                                                        style="border-color: rgba(0,0,0,0.05) !important;">
                                                        <small class="text-primary" style="font-size: 0.65rem;">
                                                            <i class="bi bi-info-circle-fill me-1"></i> No advance upload needed.
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- GCash Advance Payment Flow -->
                                                    <p class="text-muted mb-3" style="font-size: 0.75rem; line-height: 1.3;">
                                                        <?php if ($status_low === 'awaiting balance'): ?>
                                                            Your order is ready! Please settle the balance of
                                                            <strong>₱<?php echo number_format($balanceDue, 2); ?></strong> to proceed
                                                            with delivery.
                                                        <?php elseif ($status_low === 'downpayment'): ?>
                                                            Your downpayment has been verified! You may now settle the remaining balance
                                                            of
                                                            <strong>₱<?php echo number_format($balanceDue, 2); ?></strong> at any time.
                                                        <?php else: ?>
                                                            Production is ongoing. You may settle the balance of
                                                            <strong>₱<?php echo number_format($balanceDue, 2); ?></strong> now for
                                                            faster processing.
                                                        <?php endif; ?>
                                                    </p>

                                                    <?php if (empty($order_details['balanceProofOfPayment'])): ?>
                                                        <!-- GCash Payment QR Info -->
                                                        <div class="text-center p-3 border rounded-3 bg-white mb-3 shadow-sm">
                                                            <img src="../assets/img/gcash-qr-placeholder.png" alt="GCash QR"
                                                                style="max-width: 130px;"
                                                                onerror="this.src='https://placehold.co/150x150?text=GCash+QR'">
                                                            <div class="mt-2 fw-bold" style="font-size: 0.8rem; color: #012b43;">JDE
                                                                Works - 0912 345 6789</div>
                                                            <p class="text-muted mb-0" style="font-size: 0.65rem;">Scan to pay the
                                                                remaining balance</p>
                                                        </div>

                                                        <form action="track-order.php?order_id=<?php echo $order_details['orderID']; ?>"
                                                            method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="action" value="upload_balance_receipt">
                                                            <input type="hidden" name="order_id"
                                                                value="<?php echo $order_details['orderID']; ?>">

                                                            <div class="mb-2">
                                                                <label class="form-label small fw-bold mb-1"
                                                                    style="font-size: 0.7rem;">GCash Ref #</label>
                                                                <input type="text" name="reference_number"
                                                                    class="form-control form-control-sm rounded-2"
                                                                    placeholder="13-digit Ref #" required style="font-size: 0.8rem;">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold mb-1"
                                                                    style="font-size: 0.7rem;">Receipt Image</label>
                                                                <input type="file" name="balance_receipt"
                                                                    class="form-control form-control-sm rounded-2" accept="image/*"
                                                                    required style="font-size: 0.8rem;">
                                                            </div>

                                                            <button type="submit"
                                                                class="btn btn-gold btn-sm w-100 rounded-pill fw-bold py-2 shadow-sm"
                                                                style="font-size: 0.8rem;">
                                                                <i class="bi bi-cloud-arrow-up me-1"></i>Submit Payment
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <div class="p-2 bg-white rounded-2 border">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span class="fw-bold" style="font-size: 0.7rem;">Verification:</span>
                                                                <span
                                                                    class="badge <?php echo (strtolower($order_details['balancePaymentStatus'] ?? '') === 'pending') ? 'bg-warning text-dark' : 'bg-success'; ?>"
                                                                    style="font-size: 0.6rem;">
                                                                    <?php echo $order_details['balancePaymentStatus'] ?? 'Pending'; ?>
                                                                </span>
                                                            </div>
                                                            <p class="text-muted mt-1 mb-0" style="font-size: 0.65rem;">
                                                                Receipt received (Ref:
                                                                <?php echo $order_details['balanceReferenceNumber']; ?>).
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <?php
                                $isCancelled = strtolower($order_details['orderStatus']) === 'cancelled';
                                $hasReason = !empty($order_details['cancelReason']);
                                $isCustomerCancelled = $order_details['cancelReason'] === 'Cancelled by Customer';

                                if ($isCancelled && $hasReason && !$isCustomerCancelled): ?>
                                    <div class="track-card border-0 mt-4">
                                        <div class="track-body p-4">
                                            <h3 class="h6 fw-bold mb-3 text-danger"><i
                                                    class="bi bi-x-circle-fill me-2"></i>Cancellation Note</h3>
                                            <div class="p-3 rounded-3"
                                                style="background: #f8f9fa; border-left: 3px solid #dee2e6;">
                                                <p class="text-secondary small mb-0"
                                                    style="line-height: 1.6; font-style: italic;">
                                                    "<?php echo htmlspecialchars($order_details['cancelReason']); ?>"
                                                </p>
                                            </div>
                                            <p class="text-muted mt-3 mb-0" style="font-size: 0.72rem;">If you have questions
                                                about this cancellation, please contact our support.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Proof of Delivery Lightbox Modal -->
    <div id="proofModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center;">
        <div style="position:relative; max-width:90vw; max-height:90vh;">
            <button onclick="closeProofModal()"
                style="position:absolute; top:-18px; right:-18px; width:36px; height:36px; border-radius:50%; background:#fff; border:none; font-size:1.2rem; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:10000; line-height:1;">&times;</button>
            <img id="proofModalImg" src="" alt="Proof of Delivery"
                style="max-width:100%; max-height:88vh; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.5); display:block;">
        </div>
    </div>
    <!-- Per-Item Breakdown Modal -->
    <!-- Per-Item Breakdown Modal -->
    <?php foreach ($order_items as $idx => $item): ?>
        <?php
        $modImg = $item['productImage'];
        $modIsCustom = !empty($item['customizeID']);
        $modName = !empty($item['productName']) ? $item['productName'] : ($modIsCustom ? 'Custom Tailoring' : 'Product Item');
        if (empty($modImg)) {
            $modImg = $modIsCustom ? '../assets/img/scissor.png' : '../assets/img/logojd.png';
        } else {
            $modImg = '../' . ltrim(str_replace('../', '', $modImg), './');
        }

        $pId = !empty($item['productID']) ? $item['productID'] : 'custom_' . $item['orderItemID'];
        $itemDiscountPct = $discountPerProduct[$pId] ?? 0;
        $itemTotalOriginal = $item['price'] * $item['quantity'];
        $itemDiscountAmt = $itemTotalOriginal * ($itemDiscountPct / 100);
        $itemFinalSubtotal = $itemTotalOriginal - $itemDiscountAmt;
        ?>
        <div class="modal fade" id="orderItemModal<?php echo $idx; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg"
                    style="border-radius: 24px; overflow: hidden; background: #fafafa;">

                    <!-- Premium Header Gradient Accent -->
                    <div style="height: 6px; background: linear-gradient(90deg, #052b47, #d6b25e);"></div>

                    <div class="modal-header border-0 pb-0 pt-0 px-0 position-relative">
                        <button type="button" class="modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="modal-body p-0">
                        <div class="row g-0">
                            <!-- Left Column: Product Visual & Identity -->
                            <div
                                class="col-lg-5 p-4 p-md-5 bg-white d-flex flex-column align-items-center justify-content-center text-center border-end">
                                <div class="product-visual-wrapper mb-4">
                                    <div class="product-image-container">
                                        <img src="<?php echo htmlspecialchars($modImg); ?>" alt="Product"
                                            class="product-main-img" onerror="this.src='../assets/img/logojd.png'">
                                    </div>
                                    <div class="image-reflection"></div>
                                </div>

                                <div class="product-identity">
                                    <?php if (!empty($item['productSlug'])): ?>
                                        <a href="ordering.php?product=<?php echo urlencode($item['productSlug']); ?>"
                                            class="product-modal-link" title="View Product Details">
                                            <h3 class="product-name-premium">
                                                <?php echo htmlspecialchars($modName); ?>
                                                <i class="bi bi-arrow-up-right ms-1"></i>
                                            </h3>
                                        </a>
                                    <?php else: ?>
                                        <h3 class="product-name-premium"><?php echo htmlspecialchars($modName); ?></h3>
                                    <?php endif; ?>

                                    <div class="size-specification mt-3">
                                        <?php if (!$modIsCustom): ?>
                                            <div class="spec-pill">
                                                <span class="spec-label">Size</span>
                                                <span
                                                    class="spec-value"><?php echo htmlspecialchars($item['size'] ?? 'M'); ?></span>
                                            </div>
                                             <?php if (!empty($item['isPreOrder'])): ?>
                                                 <div class="spec-pill preorder ms-2" style="background: rgba(230, 126, 34, 0.1); color: #d35400; border: 1px solid rgba(230, 126, 34, 0.2);">
                                                     <i class="bi bi-calendar-event me-2"></i>
                                                     <span class="spec-label">Pre-Order Item</span>
                                                 </div>
                                             <?php else: ?>
                                                 <div class="spec-pill premade ms-2" style="background: rgba(39, 174, 96, 0.1); color: #27ae60; border: 1px solid rgba(39, 174, 96, 0.2);">
                                                     <i class="bi bi-box-seam me-2"></i>
                                                     <span class="spec-label">Ready Stock</span>
                                                 </div>
                                             <?php endif; ?>
                                        <?php else: ?>
                                            <div class="spec-pill custom">
                                                <i class="bi bi-scissors me-2"></i>
                                                <span class="spec-label">Custom Tailoring</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($modIsCustom): ?>
                                    <div class="measurements-grid-premium mt-4">
                                        <div class="row g-2 px-2">
                                            <?php
                                            $fields = [
                                                'Neck' => 'neck',
                                                'Shoulder' => 'shoulder',
                                                'Chest' => 'chest',
                                                'Waist' => 'waist',
                                                'Hips' => 'hips',
                                                'Sleeve' => 'sleeveLength',
                                                'Length' => 'shirtLength',
                                                'Armhole' => 'armhole',
                                                'Bicep' => 'bicep',
                                                'Wrist' => 'wrist',
                                                'Crotch' => 'crotch',
                                                'Thigh' => 'thigh',
                                                'Knee' => 'knee',
                                                'Leg Open' => 'legOpening',
                                                'Pants Len' => 'pantsLength'
                                            ];
                                            foreach ($fields as $label => $key):
                                                if (!empty($item[$key]) && $item[$key] > 0):
                                                    ?>
                                                    <div class="col-4">
                                                        <div class="measurement-card-mini">
                                                            <span class="m-mini-label"><?php echo $label; ?></span>
                                                            <span class="m-mini-value"><?php echo $item[$key]; ?>"</span>
                                                        </div>
                                                    </div>
                                                    <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Right Column: Digital Receipt Breakdown -->
                            <div class="col-lg-7 p-4 p-md-5 bg-light-alt">
                                <div class="receipt-header d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="receipt-title">Order Breakdown</h4>
                                    <div class="receipt-id">
                                        #<?php echo str_pad($order_details['orderID'], 6, '0', STR_PAD_LEFT); ?></div>
                                </div>

                                <div class="premium-receipt-card">
                                    <!-- Status Indicator -->
                                    <div class="receipt-status-section mb-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="status-dot-pulse"></div>
                                                <span class="status-label">Current Status</span>
                                            </div>
                                            <?php
                                            $status = strtolower($order_details['orderStatus']);
                                            $statusClass = 'status-processing';
                                            if ($status === 'completed' || $status === 'delivered')
                                                $statusClass = 'status-delivered';
                                            if ($status === 'cancelled')
                                                $statusClass = 'status-cancelled';
                                            ?>
                                            <span class="status-badge-premium <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($order_details['orderStatus']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Price Details -->
                                    <div class="receipt-details">
                                        <div class="receipt-row">
                                            <span class="receipt-label"><i class="bi bi-tag-fill me-2 opacity-50"></i>Unit
                                                Price</span>
                                            <span
                                                class="receipt-value">₱<?php echo number_format($item['price'], 2); ?></span>
                                        </div>
                                        <div class="receipt-row">
                                            <span class="receipt-label"><i
                                                    class="bi bi-box-seize-fill me-2 opacity-50"></i>Quantity</span>
                                            <span class="receipt-value">× <?php echo (int) $item['quantity']; ?></span>
                                        </div>

                                        <?php if ($itemDiscountPct > 0): ?>
                                            <div class="receipt-row text-success">
                                                <span class="receipt-label">Bulk Discount
                                                    (<?php echo $itemDiscountPct; ?>%)</span>
                                                <span
                                                    class="receipt-value">-₱<?php echo number_format($itemDiscountAmt, 2); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="receipt-divider-dashed"></div>

                                        <div class="receipt-row total">
                                            <span class="receipt-label">Total Amount</span>
                                            <span
                                                class="receipt-value-total">₱<?php echo number_format($itemFinalSubtotal, 2); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Policy/Return Info: Shown only when order is completed -->
                                <?php
                                $status_low = strtolower($order_details['orderStatus']);
                                if (in_array($status_low, ['completed', 'delivered'])):
                                    $returnText = "before " . date('m/d/Y', strtotime(($order_details['completedAt'] ?? $order_details['deliveredAt'] ?? date('Y-m-d')) . ' + 7 days'));
                                    ?>
                                    <div class="return-policy-card mt-4">
                                        <div class="d-flex gap-3">
                                            <div class="policy-icon">
                                                <i class="bi bi-arrow-return-left"></i>
                                            </div>
                                            <div>
                                                <h6 class="policy-title">Return Policy</h6>
                                                <p class="policy-text">Returnable within <?php echo $returnText; ?>. Conditions
                                                    apply.</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="receipt-footer-note mt-4">
                                    <i class="bi bi-shield-check me-1"></i> This is a verified order detail from JDE Work of
                                    Our Hands.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Product Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white border-0 py-3" style="background-color: #052b47 !important;">
                    <h5 class="modal-title fw-bold">Rate Your Items</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="max-height: 75vh; overflow-y: auto;">
                    <form id="trackOrderReviewForm" enctype="multipart/form-data">
                        <input type="hidden" name="order_id"
                            value="<?php echo htmlspecialchars($order_details['orderID']); ?>">

                        <div class="p-4">
                            <?php
                            // Group items by productSlug to prevent duplicates
                            $groupedReviewItems = [];
                            foreach ($order_items as $rawItem) {
                                if (empty($rawItem['productSlug']))
                                    continue;
                                $slug = $rawItem['productSlug'];
                                if (!isset($groupedReviewItems[$slug])) {
                                    $groupedReviewItems[$slug] = $rawItem;
                                    $groupedReviewItems[$slug]['review_sizes'] = [];
                                }
                                if (!empty($rawItem['size'])) {
                                    $groupedReviewItems[$slug]['review_sizes'][] = $rawItem['size'];
                                }
                            }
                            $finalItems = array_values($groupedReviewItems);
                            $countTotal = count($finalItems);

                            foreach ($finalItems as $idx => $item):
                                $revCount = $idx + 1;

                                // Resolve image
                                $itemImg = $item['productImage'];
                                if (empty($itemImg)) {
                                    $itemImg = !empty($item['customizeID']) ? '../assets/img/scissor.png' : '../assets/img/logojd.png';
                                } else {
                                    $itemImg = '../' . ltrim(str_replace('../', '', $itemImg), './');
                                }

                                // Format size list
                                $uniqueSizes = array_unique($item['review_sizes']);
                                $sizeDisplay = !empty($uniqueSizes) ? implode(', ', $uniqueSizes) : (isset($item['size']) ? $item['size'] : 'Standard');
                                ?>
                                <div
                                    class="review-item-row <?php echo ($revCount < $countTotal) ? 'mb-5 pb-5 border-bottom' : 'mb-3'; ?>">
                                    <input type="hidden" name="reviews[<?php echo $idx; ?>][product_id]"
                                        value="<?php echo htmlspecialchars($item['productSlug']); ?>">
                                    <input type="hidden" name="reviews[<?php echo $idx; ?>][size]"
                                        value="<?php echo htmlspecialchars($sizeDisplay); ?>">

                                    <div class="row g-4 align-items-start">
                                        <!-- LEFT: Image & Order ID -->
                                        <div class="col-md-3 text-center">
                                            <div class="mb-2">
                                                <img src="<?php echo $itemImg; ?>" class="rounded-3 shadow-sm border"
                                                    style="width: 100%; max-width: 140px; aspect-ratio: 1/1; object-fit: cover;"
                                                    alt="">
                                            </div>
                                            <p class="text-muted small fw-bold mb-0">Order ID
                                                #<?php echo htmlspecialchars($order_details['orderID']); ?></p>
                                        </div>

                                        <!-- RIGHT: Details & Controls -->
                                        <div class="col-md-9 border-start ps-md-4">
                                            <!-- Product Identity -->
                                            <div class="mb-2">
                                                <h6 class="fw-bold mb-0 text-dark">
                                                    <?php echo htmlspecialchars($item['productName'] ?: 'Product'); ?>
                                                </h6>
                                                <div class="mt-1">
                                                    <span class="badge bg-light text-dark border fw-normal"
                                                        style="font-size: 0.75rem;">Size ordered:
                                                        <?php echo htmlspecialchars($sizeDisplay); ?></span>
                                                </div>
                                            </div>

                                            <!-- TOP: Rating and Fit -->
                                            <div class="row mb-3 bg-light rounded-3 p-2 mx-0 align-items-center">
                                                <div class="col-sm-6 text-center border-end py-1">
                                                    <label class="d-block mb-1 fw-bold text-secondary small">Overall
                                                        Rating</label>
                                                    <div
                                                        class="star-rating-input d-inline-flex flex-row-reverse justify-content-center">
                                                        <input type="radio" id="mstar5_<?php echo $idx; ?>"
                                                            name="reviews[<?php echo $idx; ?>][rating]" value="5" /><label
                                                            for="mstar5_<?php echo $idx; ?>"><i
                                                                class="bi bi-star-fill"></i></label>
                                                        <input type="radio" id="mstar4_<?php echo $idx; ?>"
                                                            name="reviews[<?php echo $idx; ?>][rating]" value="4" /><label
                                                            for="mstar4_<?php echo $idx; ?>"><i
                                                                class="bi bi-star-fill"></i></label>
                                                        <input type="radio" id="mstar3_<?php echo $idx; ?>"
                                                            name="reviews[<?php echo $idx; ?>][rating]" value="3" /><label
                                                            for="mstar3_<?php echo $idx; ?>"><i
                                                                class="bi bi-star-fill"></i></label>
                                                        <input type="radio" id="mstar2_<?php echo $idx; ?>"
                                                            name="reviews[<?php echo $idx; ?>][rating]" value="2" /><label
                                                            for="mstar2_<?php echo $idx; ?>"><i
                                                                class="bi bi-star-fill"></i></label>
                                                        <input type="radio" id="mstar1_<?php echo $idx; ?>"
                                                            name="reviews[<?php echo $idx; ?>][rating]" value="1" /><label
                                                            for="mstar1_<?php echo $idx; ?>"><i
                                                                class="bi bi-star-fill"></i></label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 text-center py-1">
                                                    <label class="d-block mb-1 fw-bold text-secondary small">Product
                                                        Fit</label>
                                                    <div class="btn-group btn-group-sm w-100" role="group">
                                                        <input type="radio" class="btn-check"
                                                            name="reviews[<?php echo $idx; ?>][fit]"
                                                            id="mfitSmall_<?php echo $idx; ?>" value="Small"
                                                            autocomplete="off">
                                                        <label class="btn btn-outline-secondary py-1"
                                                            for="mfitSmall_<?php echo $idx; ?>">Small</label>

                                                        <input type="radio" class="btn-check"
                                                            name="reviews[<?php echo $idx; ?>][fit]"
                                                            id="mfitTrue_<?php echo $idx; ?>" value="True to Size" checked
                                                            autocomplete="off">
                                                        <label class="btn btn-outline-secondary py-1"
                                                            for="mfitTrue_<?php echo $idx; ?>">True</label>

                                                        <input type="radio" class="btn-check"
                                                            name="reviews[<?php echo $idx; ?>][fit]"
                                                            id="mfitLarge_<?php echo $idx; ?>" value="Large"
                                                            autocomplete="off">
                                                        <label class="btn btn-outline-secondary py-1"
                                                            for="mfitLarge_<?php echo $idx; ?>">Large</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- BOTTOM: Experience and Photo -->
                                            <div class="mb-3">
                                                <label class="form-label fw-bold text-secondary small mb-1">Your
                                                    Experience</label>
                                                <textarea name="reviews[<?php echo $idx; ?>][comment]"
                                                    class="form-control form-control-sm rounded-3 shadow-sm" rows="2"
                                                    style="resize: none;"
                                                    placeholder="Tell us about the quality and performance..."></textarea>
                                            </div>

                                            <div class="row align-items-center">
                                                <div class="col-sm-4 text-start">
                                                    <label class="form-label fw-bold text-secondary small mb-0">Photo
                                                        (Optional)</label>
                                                </div>
                                                <div class="col-sm-8 text-end">
                                                    <input type="file" name="review_image_<?php echo $idx; ?>"
                                                        accept="image/*" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary px-4 shadow-sm"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="trackOrderReviewForm" class="btn px-5 fw-bold text-white shadow-sm"
                        style="background-color: #d6b25e; border: none; transition: all 0.3s ease;"
                        onmouseover="this.style.backgroundColor='#c49e4a'"
                        onmouseout="this.style.backgroundColor='#d6b25e'">Post All Reviews</button>
                </div>
            </div>
        </div>
    </div>

    </div>


    <!-- Review CSS Overrides -->

    <!-- Hidden POS-Style Receipt for PDF Generation -->
    <div style="display: none;">
        <div class="pos-receipt shadow-sm" id="printableReceipt">
            <!-- POS Header -->
            <div class="pos-header">
                <div class="pos-logo-container">
                    <img src="../assets/img/logojd.png" alt="JDE Works Logo" class="pos-logo">
                </div>
                <h2 class="pos-store-name">JDE WORKS</h2>
                <p class="pos-store-info">
                    Of Our Hands Custom Tailoring<br>
                    11 Esperanza, Novaliches, Hilltop Subd.<br>
                    Greater Lagro, Quezon City<br>
                    Tel: +63 912 345 6789<br>
                    jdeworks0@gmail.com
                </p>
                <div class="pos-divider"></div>
            </div>

            <!-- Transaction Info -->
            <div class="pos-section">
                <div class="pos-row">
                    <span>Receipt #:</span>
                    <span><?php echo str_pad($order_details['orderID'], 8, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="pos-row">
                    <span>Date:</span>
                    <span><?php echo date('M d, Y H:i', strtotime($order_details['orderPlacedDate'] ?? 'now')); ?></span>
                </div>
                <div class="pos-row">
                    <span>Customer:</span>
                    <span><?php echo htmlspecialchars($order_details['firstName'] . ' ' . $order_details['lastName']); ?></span>
                </div>
                <div class="pos-row">
                    <span>Method:</span>
                    <span><?php echo ucfirst(htmlspecialchars($order_details['methodName'] ?? 'Not Specified')); ?></span>
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
                <?php foreach ($order_items as $item): ?>
                    <div class="pos-item">
                        <div class="pos-item-main">
                            <span class="pos-item-name">
                                <?php
                                $isCustomItem = !empty($item['customizeID']);
                                $itemName = !empty($item['productName']) ? $item['productName'] : ($isCustomItem ? 'Custom Tailoring' : 'Product Item');
                                echo htmlspecialchars($itemName);
                                ?>
                                <?php if (!$isCustomItem && !empty($item['size'])): ?>
                                    [<?php echo htmlspecialchars($item['size']); ?>]
                                <?php endif; ?>
                            </span>
                            <span class="pos-item-qty">₱<?php echo number_format($item['price'], 2); ?> x
                                <?php echo $item['quantity']; ?></span>
                            <span
                                class="pos-item-total">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pos-divider"></div>

            <!-- Totals -->
            <div class="pos-totals">
                <div class="pos-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($itemsSubtotal, 2); ?></span>
                </div>
                <div class="pos-row">
                    <span>Shipping:</span>
                    <?php if ($isPickUp): ?>
                        <span class="text-success fw-bold">FREE</span>
                    <?php elseif ($derivedShippingFee <= 0 && !empty($order_details['originalShippingFee'])): ?>
                        <span><span class="text-success fw-bold">FREE</span> <del
                                class="text-muted ms-1">₱<?php echo number_format($order_details['originalShippingFee'], 0); ?></del></span>
                    <?php elseif ($derivedShippingFee <= 0): ?>
                        <span class="text-success fw-bold">FREE</span>
                    <?php else: ?>
                        <span>₱<?php echo number_format($derivedShippingFee, 2); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($discountAmount > 0): ?>
                    <div class="pos-row text-success">
                        <span>Bulk Discount <?php echo $discountPercentage > 0 ? "({$discountPercentage}%)" : ""; ?>:</span>
                        <span>-₱<?php echo number_format($discountAmount, 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="pos-divider"></div>
                <div class="pos-row pos-grand-total">
                    <span>TOTAL:</span>
                    <span>₱<?php echo number_format($order_details['totalPrice'], 2); ?></span>
                </div>

                <div class="pos-row pos-balance">
                    <span>BALANCE:</span>
                    <span>₱<?php echo number_format($balanceToDisplay, 2); ?></span>
                </div>
            </div>

            <div class="pos-divider"></div>

            <!-- Footer Message -->
            <div class="pos-footer">
                <p>Payment Status: <?php echo strtoupper($order_details['paymentStatus'] ?: 'UNPAID'); ?></p>
                <p>Tailoring Lead Time: 7-14 Days</p>
                <div class="pos-qr-container">
                    <i class="bi bi-qr-code"></i>
                </div>
                <p class="pos-thank-you">THANK YOU FOR YOUR ORDER!</p>
                <p class="pos-mini-note">This is a system-generated receipt.</p>
            </div>
        </div>
    </div>

    <script src="../js/receipt.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
    <script src="../js/track-order.js?v=<?php echo time(); ?>"></script>
    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="feedback-icon mb-3">
                        <i id="feedbackIcon" class="bi bi-check-circle-fill"
                            style="font-size: 3.5rem; color: #198754;"></i>
                    </div>
                    <h4 id="feedbackTitle" class="fw-bold mb-2">Success!</h4>
                    <p id="feedbackMessage" class="text-muted small">Action completed successfully.</p>
                    <button type="button" class="btn w-100 py-2 mt-3 text-white fw-bold shadow-sm"
                        data-bs-dismiss="modal"
                        style="border-radius: 12px; background-color: #052b47; border: none;">GREAT</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimalist Cancellation Confirmation Modal -->
    <div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-sm" style="border-radius: 16px;">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-circle text-danger" style="font-size: 3.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #2d3436;">Cancel Order?</h4>
                    <p id="cancelModalMessage" class="text-secondary mb-4 small" style="line-height: 1.6;">Are you sure
                        you want to cancel this
                        order? This action cannot be undone once confirmed.</p>
                    <div id="cancelPolicyNote" class="alert alert-warning border-0 small text-start d-none"
                        style="border-radius: 12px; font-size: 0.82rem;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <span id="cancelPolicyText"></span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary w-100 py-2 fw-semibold"
                            data-bs-dismiss="modal" style="border-radius: 8px; font-size: 0.9rem; color: #636e72;">NO,
                            KEEP IT</button>
                        <button type="button" id="confirmCancelBtn" class="btn btn-danger w-100 py-2 fw-semibold"
                            style="border-radius: 8px; font-size: 0.9rem; background-color: #eb4d4b; border: none;">YES,
                            CANCEL</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-dismiss alerts after 3 seconds
            const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function () {
                        alert.style.display = 'none';
                    }, 500);
                }, 3000);
            });
        });
    </script>
</body>

</html>