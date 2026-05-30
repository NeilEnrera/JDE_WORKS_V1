<?php
/**
 * verify.php — Email Verification Controller
 * Handles token validation and database updates, then renders the view.
 */
session_start();
include("db_connection.php");

$message = "";
$status = "error"; // Default to error
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = "Invalid verification link. No token provided.";
} else {
    // Check if token exists and user is not verified yet
    $stmt = $conn->prepare("SELECT customerID, firstName, is_verified FROM tbl_customer WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['is_verified'] == 1) {
            $message = "Your email has already been verified. You can proceed to login.";
            $status = "success";
        } else {
            // Update user to verified
            $updateStmt = $conn->prepare("UPDATE tbl_customer SET is_verified = 1, verification_token = NULL WHERE customerID = ?");
            $updateStmt->bind_param("i", $user['customerID']);
            if ($updateStmt->execute()) {
                $message = "Your email has been successfully verified, " . htmlspecialchars($user['firstName']) . "!";
                $status = "success";
            } else {
                $message = "An error occurred during verification. Please try again later.";
            }
            $updateStmt->close();
        }
    } else {
        $message = "The verification link is invalid or has expired.";
    }
    $stmt->close();
}

// Render the view
require '../html/verify.view.php';
?>
