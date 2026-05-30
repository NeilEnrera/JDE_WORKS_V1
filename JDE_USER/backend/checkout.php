<?php
/**
 * checkout.php — Backend controller
 * Fetches customer data and saved addresses, then renders the checkout view.
 */
session_start();
require_once 'db_connection.php';

// Guest access restriction and User Type gating
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'Customer') {
    header("Location: login.php");
    exit();
}

// Prepare customer data defaults
$customerData = [
    'firstName' => '',
    'lastName' => '',
    'email' => $_SESSION['email'] ?? '',
    'phoneNumber' => '',
    'address' => ''
];

$savedAddresses = [];

// Fetch saved profile details
$stmt = $conn->prepare("SELECT firstName, lastName, email, phoneNumber, address, userName FROM tbl_customer WHERE customerID = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $customerData = array_merge($customerData, $row);
    // If names are empty, use userName as fallback
    if (empty($customerData['firstName']) && empty($customerData['lastName'])) {
        $customerData['firstName'] = $row['userName'];
    }
}

// Fetch Saved Addresses from Address Book
$stmt = $conn->prepare("SELECT * FROM tbl_addressBook WHERE customerID = ? ORDER BY isDefault DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$savedAddresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fallback for names if not in DB but in session
if (empty($customerData['firstName']) && isset($_SESSION['name'])) {
    $nameParts = explode(' ', $_SESSION['name']);
    $customerData['firstName'] = $nameParts[0];
    if (count($nameParts) > 1) {
        $customerData['lastName'] = $nameParts[1];
    }
}

// Check if there are any custom items in the cart
$hasCustomItems = false;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['selected']) && !$item['selected']) continue;
        
        if (isset($item['custom']) && $item['custom'] == true) {
            $hasCustomItems = true;
            break;
        }
    }
}

require '../html/checkout.view.php';