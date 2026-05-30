<?php
session_start();
header('Content-Type: application/json');

// Only allow admin-level employees
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || $_SESSION['access_level'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../JDE_USER/backend/db_connection.php';

$action     = $_POST['action'] ?? '';
$customerID = intval($_POST['customerID'] ?? 0);

if (!$customerID) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID.']);
    exit;
}

// Ensure the isBlocked column exists
$conn->query("ALTER TABLE tbl_customer ADD COLUMN IF NOT EXISTS isBlocked TINYINT(1) NOT NULL DEFAULT 0");

if ($action === 'block_user') {
    $stmt = $conn->prepare("UPDATE tbl_customer SET isBlocked = 1 WHERE customerID = ?");
    $stmt->bind_param("i", $customerID);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Customer has been blocked.', 'isBlocked' => 1]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to block customer.']);
    }
    $stmt->close();

} elseif ($action === 'unblock_user') {
    $stmt = $conn->prepare("UPDATE tbl_customer SET isBlocked = 0 WHERE customerID = ?");
    $stmt->bind_param("i", $customerID);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Customer has been unblocked.', 'isBlocked' => 0]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unblock customer.']);
    }
    $stmt->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
?>
