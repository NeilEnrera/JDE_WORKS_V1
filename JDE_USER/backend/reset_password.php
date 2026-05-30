<?php
session_start();
include("db_connection.php");

// Ensure the code was verified
if (!isset($_SESSION['code_verified']) || $_SESSION['code_verified'] !== true || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['txt_pw'];
    $confirmPassword = $_POST['txt_confirm_pw'];

    if (empty($password) || empty($confirmPassword)) {
        $error_msg = "Please fill in all fields.";
    } elseif ($password !== $confirmPassword) {
        $error_msg = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Update the password in tbl_customer
        $stmt = $conn->prepare("UPDATE tbl_customer SET password = ? WHERE LOWER(email) = LOWER(?)");
        $stmt->bind_param("ss", $hashedPassword, $email);

        if ($stmt->execute()) {
            // Delete the reset code from the database
            $delStmt = $conn->prepare("DELETE FROM tbl_password_resets WHERE LOWER(email) = LOWER(?)");
            $delStmt->bind_param("s", $email);
            $delStmt->execute();
            $delStmt->close();

            // Clear session variables
            unset($_SESSION['code_verified']);
            unset($_SESSION['reset_email']);

            header("Location: login.php?reset=success");
            exit();
        } else {
            $error_msg = "Error updating password. Please try again later.";
        }
        $stmt->close();
    }
}

require '../html/reset_password.view.php';
