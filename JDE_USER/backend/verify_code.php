<?php
session_start();
include("db_connection.php");

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$seconds_left = 0;
$error_msg = "";
$success_msg = "";

// Fetch the remaining seconds directly from DB to avoid timezone issues in the UI timer
$stmtExp = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_left FROM tbl_password_resets WHERE LOWER(email) = LOWER(?) ORDER BY dateCreated DESC LIMIT 1");
$stmtExp->bind_param("s", $email);
$stmtExp->execute();
$resExp = $stmtExp->get_result();
if ($resExp->num_rows > 0) {
    $seconds_left = max(0, (int)$resExp->fetch_assoc()['seconds_left']);
}
$stmtExp->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['txt_code']);

    // Brute-force protection: max 5 attempts per session
    if (!isset($_SESSION['reset_attempts'])) $_SESSION['reset_attempts'] = 0;
    if ($_SESSION['reset_attempts'] >= 5) {
        $error_msg = "Too many incorrect attempts. Please request a new reset code.";
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_attempts']);
        header("Location: forgot_password.php");
        exit();
    }

    if (empty($code)) {
        $error_msg = "Please enter the verification code.";
    } else {
        // Check database for valid code using DB-native NOW() for consistency
        $stmt = $conn->prepare("SELECT id FROM tbl_password_resets WHERE LOWER(email) = LOWER(?) AND code = ? AND expires_at > NOW()");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Valid — reset the counter and clean up
            unset($_SESSION['reset_attempts']);
            $_SESSION['code_verified'] = true;

            $delStmt = $conn->prepare("DELETE FROM tbl_password_resets WHERE LOWER(email) = LOWER(?)");
            $delStmt->bind_param("s", $email);
            $delStmt->execute();
            $delStmt->close();

            header("Location: reset_password.php");
            exit();
        } else {
            $_SESSION['reset_attempts']++;
            $remaining = 5 - $_SESSION['reset_attempts'];

            // Check if it exists but expired
            $stmt2 = $conn->prepare("SELECT id FROM tbl_password_resets WHERE LOWER(email) = LOWER(?) AND code = ?");
            $stmt2->bind_param("ss", $email, $code);
            $stmt2->execute();
            $res2 = $stmt2->get_result();

            if ($res2->num_rows === 1) {
                $error_msg = "This code has expired. Please request a new one.";
            } else {
                $error_msg = "Invalid verification code. " . ($remaining > 0 ? "$remaining attempt(s) remaining." : "Account locked.");
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}

require '../html/verify_code.view.php';