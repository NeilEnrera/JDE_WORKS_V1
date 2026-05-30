<?php
/**
 * get_order_status.php
 * Simple API for customers to poll for order and payment status updates.
 */
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderID = intval($_GET['order_id'] ?? 0);
$customerID = $_SESSION['user_id'];

if (!$orderID) {
    echo json_encode(['success' => false, 'message' => 'Missing Order ID']);
    exit;
}

// Ownership verification is CRITICAL
$stmt = $conn->prepare("
    SELECT o.orderID, o.orderStatus, o.totalPrice, o.paymentBalance, p.paymentStatus, p.verifiedAt
    FROM tbl_order o
    LEFT JOIN tbl_payments p ON o.orderID = p.orderID
    WHERE o.orderID = ? AND o.customerID = ?
    LIMIT 1
");
$stmt->bind_param("ii", $orderID, $customerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'orderID' => $order['orderID'],
    'orderStatus' => $order['orderStatus'],
    'paymentStatus' => $order['paymentStatus'] ?? 'Unpaid',
    'totalPrice' => floatval($order['totalPrice']),
    'paymentBalance' => floatval($order['paymentBalance'] ?? $order['totalPrice']),
    'verifiedAt' => $order['verifiedAt']
]);
