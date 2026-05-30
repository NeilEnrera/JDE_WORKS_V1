<?php
/**
 * forgot_password.php — Backend controller
 * Handles password reset email dispatch, then renders the forgot_password view.
 */
session_start();
include("db_connection.php");
include("email_helper.php");

// Create the password resets table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS tbl_password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    dateCreated DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['txt_email']);

    if (empty($email)) {
        $error_msg = "Please enter your email address.";
    } else {
        // Rate limiting check: prevent more than one request per minute per email
        // We use TIMESTAMPDIFF(SECOND, ...) to avoid timezone mismatch issues between PHP and DB
        $checkLimit = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, dateCreated, NOW()) as seconds_ago FROM tbl_password_resets WHERE LOWER(email) = LOWER(?) ORDER BY dateCreated DESC LIMIT 1");
        $checkLimit->bind_param("s", $email);
        $checkLimit->execute();
        $limitRes = $checkLimit->get_result();
        
        if ($limitRes->num_rows > 0) {
            $secondsAgo = $limitRes->fetch_assoc()['seconds_ago'];
            if ($secondsAgo < 10) {
                $error_msg = "Please wait " . (10 - $secondsAgo) . " seconds before requesting another code.";
                $checkLimit->close();
                goto render;
            }
        }
        $checkLimit->close();

        // Check if user exists in tbl_customer
        $stmt = $conn->prepare("SELECT customerID FROM tbl_customer WHERE LOWER(email) = LOWER(?)");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // 1. Generate a cryptographically secure 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // 2. Clear any old codes for this email
            $clearStmt = $conn->prepare("DELETE FROM tbl_password_resets WHERE email = ?");
            $clearStmt->bind_param("s", $email);
            $clearStmt->execute();
            $clearStmt->close();

            // 3. Store the new code
            $stmtRes = $conn->prepare("INSERT INTO tbl_password_resets (email, code, expires_at) VALUES (?, ?, ?)");
            $stmtRes->bind_param("sss", $email, $code, $expires_at);

            if ($stmtRes->execute()) {
                // 4. Dispatch the email in the background (Async)
                // This makes the UI respond "fast" while SMTP happens behind the scenes
                EmailHelper::dispatchAsync('verification_code', [
                    'to' => $email,
                    'code' => $code
                ]);

                $_SESSION['reset_email'] = $email;
                $_SESSION['code_expires_at'] = $expires_at;
                header("Location: verify_code.php");
                exit();
            } else {
                $error_msg = "An internal error occurred. Please try again later.";
            }
            $stmtRes->close();
        } else {
            // Generic message — do NOT say whether the email exists or not
            $success_msg = "If that email is registered, a reset code has been sent.";
        }
        $stmt->close();
    }
}

render:
require '../html/forgot_password.view.php';