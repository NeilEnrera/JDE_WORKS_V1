<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopping Cart - JDE Works of Our Hands</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/navbar.css">
</head>

<body>
    <?php
    $activePage = 'cart';
    include '../backend/navbar.php';
    ?>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" href="../css/cart.css?v=<?php echo time(); ?>">

    <main class="cart-page py-5">
        <div class="cart-container pb-5">

            <!-- Progress Stepper -->
            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="checkout-progress-bar mb-5 mt-4 d-flex justify-content-between align-items-center w-100 mx-auto"
                    style="max-width: 900px;">
                    <a href="cart.php" class="progress-step active">
                        <div class="step-number">1</div>
                        <div class="step-label">Cart</div>
                    </a>
                    <div class="progress-line"></div>
                    <div class="progress-step disabled">
                        <div class="step-number">2</div>
                        <div class="step-label">Distribution</div>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step disabled">
                        <div class="step-number">3</div>
                        <div class="step-label">Payment Option</div>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step disabled">
                        <div class="step-number">4</div>
                        <div class="step-label">Balance Payment Method</div>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step disabled">
                        <div class="step-number">5</div>
                        <div class="step-label">Payment Processing</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center mb-5">
                <h1 class="cart-title">Shopping Cart</h1>
            </div>

            <?php if (empty($_SESSION['cart'])): ?>
                <div class="empty-cart-state">
                    <div class="empty-icon">
                        <i class="bi bi-bag-x"></i>
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything to your cart yet.</p>
                    <a href="product.php" class="btn-action-primary"
                        style="max-width: 250px; margin: 0 auto; display: block;">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <!-- Cart Items Section -->
                    <div class="cart-items-section">
                        <!-- All Items Header Bar -->
                        <?php
                        $totalItems = array_sum(array_column($_SESSION['cart'], 'quantity'));
                        $allSelected = true;
                        foreach ($_SESSION['cart'] as $item) {
                            if (isset($item['selected']) && !$item['selected']) {
                                $allSelected = false;
                                break;
                            }
                        }
                        ?>
                        <div class="cart-all-items-header"
                            style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="selectAllItems" <?php echo $allSelected ? 'checked' : ''; ?>
                                    style="cursor: pointer; width: 18px; height: 18px; margin: 0;">
                                <span class="cart-all-items-label"
                                    style="margin: 0; display: inline-flex; align-items: center;">ALL ITEMS
                                    (<?php echo $totalItems; ?>)</span>
                            </div>
                            <button type="button" onclick="toggleClearAllPopover(event, this)"
                                style="background: none; border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; opacity: 0.9; transition: opacity 0.2s;"
                                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'"
                                title="Delete Selected Items">
                                <i class="bi bi-trash3" style="font-size: 18px;"></i>
                            </button>
                        </div>

                        <div class="cart-table-wrapper">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%; text-align: center;"></th>
                                        <th style="width: 40%;">Product</th>
                                        <th style="width: 15%;">Price</th>
                                        <th style="width: 15%;">Quantity</th>
                                        <th style="width: 20%;">Total</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $grandTotal = 0;
                                    $selectedCount = 0;
                                    $totalQuantity = 0;
                                    foreach ($_SESSION['cart'] as $index => $item):
                                        $price = (float) $item['price'];
                                        $quantity = (int) $item['quantity'];
                                        $itemTotal = $price * $quantity;
                                        $isSelected = isset($item['selected']) ? (bool) $item['selected'] : true;

                                        if ($isSelected) {
                                            $grandTotal += $itemTotal;
                                            $selectedCount++;
                                            $totalQuantity += $quantity;
                                        }
                                        ?>
                                        <tr data-index="<?php echo $index; ?>"
                                            data-product-id="<?php echo htmlspecialchars($item['id'] ?? 'custom_' . $index); ?>"
                                            class="<?php echo !$isSelected ? 'item-deselected' : ''; ?>">
                                            <td style="text-align: center; vertical-align: middle;">
                                                <input type="checkbox" class="cart-item-checkbox"
                                                    data-index="<?php echo $index; ?>" <?php echo $isSelected ? 'checked' : ''; ?> style="cursor: pointer; width: 18px; height: 18px;">
                                            </td>
                                            <td data-label="Product">
                                                <div class="cart-product">
                                                    <img src="<?php echo resolveProductPath($item['image']); ?>"
                                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                        class="cart-product-img">
                                                    <div class="cart-product-details">
                                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                        <p class="cart-product-meta">
                                                            Size: <?php echo htmlspecialchars($item['size']); ?>
                                                        </p>
                                                        <?php if (!empty($item['custom'])): ?>
                                                            <span class="badge-custom">Handcrafted Order</span>
                                                        <?php elseif (!empty($item['isPreOrder'])): ?>
                                                            <span class="badge-preorder">Pre-Order Item</span>
                                                        <?php else: ?>
                                                            <span class="badge-premade">Ready Stock</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Price">
                                                <span class="cart-price">₱<?php echo number_format($price, 2); ?></span>
                                            </td>
                                            <td data-label="Quantity">
                                                <div class="qty-selector">
                                                    <button type="button" class="qty-btn"
                                                        onclick="updateCartQuantity(<?php echo $index; ?>, <?php echo $quantity - 1; ?>)">-</button>
                                                    <input type="number" class="qty-input" value="<?php echo $quantity; ?>"
                                                        readonly>
                                                    <button type="button" class="qty-btn"
                                                        onclick="updateCartQuantity(<?php echo $index; ?>, <?php echo $quantity + 1; ?>)">+</button>
                                                </div>
                                            </td>
                                            <td data-label="Total">
                                                <span class="cart-subtotal">₱<?php echo number_format($itemTotal, 2); ?></span>
                                            </td>
                                            <td>
                                                <button class="btn-remove"
                                                    onclick="toggleDeletePopover(event, this, <?php echo $index; ?>)"
                                                    title="Remove item">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Order Summary Section -->
                    <div class="cart-summary-section">
                        <div class="summary-card">
                            <div class="summary-header">
                                <h3>Order Summary</h3>
                            </div>

                            <?php
                            $discountAmount = 0;
                            $originalSubtotal = $grandTotal;

                            // Group by product ID for per-product bulk discount
                            $productGroups = [];
                            foreach ($_SESSION['cart'] as $idx => $item) {
                                $isSelected = isset($item['selected']) ? (bool) $item['selected'] : true;
                                if ($isSelected) {
                                    $pId = $item['id'] ?? 'custom_' . $idx;
                                    if (!isset($productGroups[$pId])) {
                                        $productGroups[$pId] = ['qty' => 0, 'subtotal' => 0];
                                    }
                                    $price = (float) $item['price'];
                                    $quantity = (int) $item['quantity'];
                                    $productGroups[$pId]['qty'] += $quantity;
                                    $productGroups[$pId]['subtotal'] += ($price * $quantity);
                                }
                            }

                            // Calculate discount per product and sum them up
                            $totalDiscountAmount = 0;
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
                                if ($calc['amount'] > 0) {
                                    $totalDiscountAmount += $calc['amount'];
                                }
                            }

                            $discountAmount = $totalDiscountAmount;

                            $grandTotal -= $discountAmount;
                            ?>
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span id="cartSubtotalOriginal">₱<?php echo number_format($originalSubtotal, 2); ?></span>
                            </div>

                            <div class="summary-row text-success" id="cartDiscountRow"
                                style="<?php echo $discountAmount > 0 ? '' : 'display:none;'; ?>">
                                <span id="cartDiscountLabel">Discount</span>
                                <span id="cartDiscountAmount">-₱<?php echo number_format($discountAmount, 2); ?></span>
                            </div>

                            <div class="summary-row">
                                <span>Shipping</span>
                                <span id="cartShipping"><?php echo $selectedCount > 0 ? 'TBD' : '₱0.00'; ?></span>
                            </div>

                            <div class="summary-row total">
                                <span>Total</span>
                                <span id="cartTotal">₱<?php echo number_format($grandTotal, 2); ?></span>
                            </div>

                            <a href="checkout.php"
                                class="btn-checkout <?php echo $selectedCount === 0 ? 'btn-disabled' : ''; ?>" <?php echo $selectedCount === 0 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>Proceed to
                                Checkout</a>
                            <a href="product.php" class="btn-continue">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

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

    <!-- Clear All Popover -->
    <div id="clearAllPopover" class="delete-popover d-none">
        <div class="popover-content">
            <p>Delete selected items from your cart?</p>
            <div class="popover-actions">
                <button type="button" class="btn-popover-no" onclick="closeAllPopovers()">No</button>
                <button type="button" id="confirmClearBtn" class="btn-popover-yes" onclick="clearAllCartItems()">Yes,
                    Delete</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
    <script src="../js/cart.js?v=<?php echo time(); ?>"></script>
</body>

</html>