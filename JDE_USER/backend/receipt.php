<?php
/**
 * receipt.php — Backend controller
 * Fetches order and customer data, then renders the receipt view.
 */
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Capture Secure Token or Fallback ID
$orderToken = $_GET['token'] ?? '';
$orderID = intval($_GET['order_id'] ?? 0);
$customerID = (int)($_SESSION['user_id'] ?? 0);
$userType = $_SESSION['user_type'] ?? '';

// Security Fix: Ensure only 'Customer' type can access this page
if ($userType !== 'Customer' || $customerID <= 0) {
    die("Order not found or access denied.");
}

if (empty($orderToken) && $orderID <= 0) {
    header('Location: index.php');
    exit();
}

// 1. Mandatory Ownership Verification using Token (or ID if token is missing)
if (!empty($orderToken)) {
    $checkStmt = $conn->prepare("SELECT orderID, customerID FROM tbl_order WHERE orderToken = ? AND customerID = ? LIMIT 1");
    $checkStmt->bind_param("si", $orderToken, $customerID);
} else {
    $checkStmt = $conn->prepare("SELECT orderID, customerID FROM tbl_order WHERE orderID = ? AND customerID = ? LIMIT 1");
    $checkStmt->bind_param("ii", $orderID, $customerID);
}

$checkStmt->execute();
$checkResult = $checkStmt->get_result()->fetch_assoc();

// SECURITY: Use a generic message for all failure cases to avoid confirming ID existence
if (!$checkResult) {
    die("Order not found or you do not have permission to view this receipt.");
}

// Extract real ID for detailed fetch
$realOrderID = $checkResult['orderID'];
$orderID = $realOrderID; // Synchronize for the view

// 2. Fetch Full Order Details (Now that ownership is strictly verified)
$stmt = $conn->prepare("SELECT o.*, pm.methodName as paymentMethodName, pay.paymentStatus, pay.balancePaymentStatus, pay.paymentMethodID, c.dateCreated as orderDate, pay.downPaymentAmount, pay.paymentBalance,
                               (SELECT COUNT(*) FROM tbl_cartItem ci WHERE ci.cartID = o.cartID AND ci.customizeID IS NOT NULL AND ci.customizeID <> 0) as customItemsCount
                        FROM tbl_order o
                        JOIN tbl_payments pay ON o.orderID = pay.orderID
                        JOIN tbl_paymentMethod pm ON pay.paymentMethodID = pm.paymentMethodID
                        JOIN tbl_cart c ON o.cartID = c.cartID
                        WHERE o.orderID = ? LIMIT 1");
$stmt->bind_param("i", $realOrderID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found or detailed data is currently unavailable.");
}

// Fetch Cart Items
$stmt = $conn->prepare("SELECT ci.*, p.productName, p.productImage, p.slug
                        FROM tbl_cartItem ci
                        LEFT JOIN tbl_product p ON ci.productID = p.productID
                        WHERE ci.cartID = ?");
$stmt->bind_param("i", $order['cartID']);
$stmt->execute();
$items = $stmt->get_result();

// Pre-calculate subtotal
$subtotal = 0;
$items_array = [];
$items->data_seek(0);
while ($item = $items->fetch_assoc()) {
    $item['itemSub'] = $item['price'] * $item['quantity'];
    $subtotal += $item['itemSub'];
    $items_array[] = $item;
}

// Fetch Customer Details
$stmt = $conn->prepare("SELECT firstName, lastName, email, phoneNumber FROM tbl_customer WHERE customerID = ?");
$stmt->bind_param("i", $order['customerID']);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

require '../html/receipt.view.php';