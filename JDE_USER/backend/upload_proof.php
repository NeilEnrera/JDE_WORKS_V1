<?php
/**
 * upload_proof.php — Backend controller for Step 5
 * Handles GCash receipt upload or redirects Cash orders directly to handler.
 */
session_start();

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

// Store Step 4 data (payment_method) in session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['payment_method'])) {
        $_SESSION['payment_method'] = $_POST['payment_method'];
    }
} elseif (!isset($_SESSION['checkout_details']) || !isset($_SESSION['payment_method'])) {
    header('Location: checkout.php');
    exit();
}

$paymentMethod = $_SESSION['payment_method'];

// If Cash/COD/Pickup, we can either skip to handler or show a final confirm button.
// For now, let's proceed to order_handler for Cash to keep it simple, 
// OR show a final "Confirm Order" page in Step 5 for Cash without upload.
// The user said: "Step 5 – Upload Proof of Payment: This will be the final step before order confirmation"
// If payment is Cash, we'll show Step 5 as a "Review & Confirm" step.

$details = $_SESSION['checkout_details'];
$distMethod = $details['distribution_method'] ?? 'delivery';
$shippingFee = ($distMethod === 'delivery') ? ($_SESSION['calculated_shipping_fee'] ?? 0) : 0;

// Re-calculate totals
$grandTotal = 0;
$isCustomOrder = false;
foreach ($_SESSION['cart'] as $item) {
    if (isset($item['selected']) && !$item['selected']) continue;
    $grandTotal += (float)$item['price'] * (int)$item['quantity'];
    if (isset($item['custom']) && ($item['custom'] ?? false)) {
        $isCustomOrder = true;
    }
}

// Bulk Discount Calculation
$productGroups = [];
foreach ($_SESSION['cart'] as $idx => $item) {
    if (isset($item['selected']) && !$item['selected']) continue;
    $pId = $item['id'] ?? 'custom_' . $idx;
    if (!isset($productGroups[$pId])) {
        $productGroups[$pId] = ['qty' => 0, 'subtotal' => 0];
    }
    $productGroups[$pId]['qty'] += (int)$item['quantity'];
    $productGroups[$pId]['subtotal'] += ((float)$item['price'] * (int)$item['quantity']);
}

$totalDiscountAmount = 0;
foreach ($productGroups as $group) {
    $qty = $group['qty'];
    $sub = $group['subtotal'];
    $discountPct = ($qty >= 50) ? 12 : (($qty >= 30) ? 10 : (($qty >= 10) ? 5 : 0));
    if ($discountPct > 0) {
        $totalDiscountAmount += ($sub * ($discountPct / 100));
    }
}

$grandTotal -= $totalDiscountAmount;
$finalTotal = $grandTotal + $shippingFee;
$paymentOption = $_SESSION['payment_option'] ?? 'half';
$minDownPayment = $finalTotal * 0.5;

require '../html/upload_proof.view.php';
