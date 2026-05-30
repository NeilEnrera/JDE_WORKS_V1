<?php
/**
 * payment.php — Backend controller
 * Handles checkout session data and cart totals, then renders the payment view.
 */
session_start();
require_once 'db_connection.php';
require_once 'discount_helper.php';

// Guest access restriction
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Store Step 2 data in session for processing later
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation for distribution method and required fields
    $distMethod = $_POST['distribution_method'] ?? 'delivery';
    $errors = [];
    
    // Check for customized items to determine pickup requirement
    $isCustomOrder = false;
    $grandTotal = 0;
    $totalQuantity = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['selected']) && !$item['selected']) continue;
            $grandTotal += (float)$item['price'] * (int)$item['quantity'];
            $totalQuantity += (int)$item['quantity'];
            if (isset($item['custom']) && ($item['custom'] ?? false)) {
                $isCustomOrder = true;
            }
        }
    }

    if ($distMethod === 'delivery') {
        $requiredFields = [
            'address' => 'Street Address',
            'province' => 'Province',
            'city' => 'City',
            'barangay' => 'Barangay',
            'zip' => 'Zip Code',
            'phone' => 'Phone Number'
        ];
        foreach ($requiredFields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors[] = "$label is required for delivery.";
            }
        }
    } else {
        if (!$isCustomOrder && empty($_POST['pickup_date'])) {
            $errors[] = "Pickup date is required for store pickup.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['checkout_error'] = implode(' ', $errors);
        header('Location: checkout.php');
        exit();
    }

    $_SESSION['checkout_details'] = $_POST;
} elseif (!isset($_SESSION['checkout_details'])) {
    header('Location: checkout.php');
    exit();
}

$details = $_SESSION['checkout_details'];
$distMethod = $details['distribution_method'] ?? 'delivery';
$shippingFee = ($distMethod === 'delivery') ? ($_SESSION['calculated_shipping_fee'] ?? 0) : 0;

// Check for customized items to enforce 50% downpayment (Recalculated in case we skipped POST processing)
if (!isset($isCustomOrder)) {
    $isCustomOrder = false;
    $grandTotal = 0;
    $totalQuantity = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['selected']) && !$item['selected']) continue;
            $grandTotal += (float)$item['price'] * (int)$item['quantity'];
            $totalQuantity += (int)$item['quantity'];
            if (isset($item['custom']) && ($item['custom'] ?? false)) {
                $isCustomOrder = true;
            }
        }
    }
}

// Group items by productID to determine per-product bulk discounts
$productGroups = [];
foreach ($_SESSION['cart'] as $idx => $item) {
    if (isset($item['selected']) && !$item['selected']) continue;
    $pId = $item['id'] ?? 'custom_' . $idx;
    if (!isset($productGroups[$pId])) {
        $productGroups[$pId] = ['id' => $item['id'] ?? 0, 'qty' => 0, 'subtotal' => 0, 'price' => (float)$item['price']];
    }
    $price = (float)$item['price'];
    $qty = (int)$item['quantity'];
    $productGroups[$pId]['qty'] += $qty;
    $productGroups[$pId]['subtotal'] += ($price * $qty);
}

// Calculate the per-product discount
$totalDiscountAmount = 0;
$originalSubtotal = $grandTotal;
foreach ($productGroups as $pId => $group) {
    $groupQty = $group['qty'];
    $groupSub = $group['subtotal'];
    
    // Fetch product data for potential admin overrides
    $pData = null;
    $pStmt = $conn->prepare("SELECT customDiscountPercent, customMinQty FROM tbl_product WHERE productID = ? LIMIT 1");
    $targetId = $group['id'];
    $pStmt->bind_param("i", $targetId);
    $pStmt->execute();
    $pData = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();

    $calc = DiscountSystem::calculateItemDiscount($groupQty, $group['price'], $pData);
    $discountPct = $calc['percentage'];

    if ($discountPct > 0) {
        $currentDiscount = $groupSub * ($discountPct / 100);
        $totalDiscountAmount += $currentDiscount;
    }
}

$discountAmount = $totalDiscountAmount;

$grandTotal -= $discountAmount;

$finalTotal = $grandTotal + $shippingFee;
$minDownPayment = $finalTotal * 0.5;

require '../html/payment.view.php';