<?php
/**
 * login.php — Backend controller
 * Handles credential checking, session setup, and redirects. Then renders the login view.
 */
session_start();
include("db_connection.php");

$error_msg = "";
$success_msg = "";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'Employee') {
        $target = "../../JDE_ADMIN/backend/admin_dashboard.php";
        if (isset($_SESSION['access_level'])) {
            if ($_SESSION['access_level'] == 2) {
                $target = "../../JDE_ADMIN/backend/appointments.php";
            } elseif ($_SESSION['access_level'] == 3) {
                $target = "../../JDE_ADMIN/backend/orders.php";
            }
        }
        header("Location: $target");
    } else {
        header("Location: index.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['txt_uname']);
    $password = trim($_POST['txt_pw']);
    $ip = $_SERVER['REMOTE_ADDR'];

    // --- BRUTE FORCE PROTECTION ---
    $conn->query("CREATE TABLE IF NOT EXISTS tbl_login_attempts (ip VARCHAR(45), attempts INT DEFAULT 0, last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (ip))");
    $attempt_res = $conn->query("SELECT attempts, UNIX_TIMESTAMP(last_attempt) as last_time FROM tbl_login_attempts WHERE ip = '$ip'");
    $attempts = 0;
    $last_time = 0;
    if ($attempt_row = $attempt_res->fetch_assoc()) {
        $attempts = $attempt_row['attempts'];
        $last_time = $attempt_row['last_time'];
    }

    if ($attempts >= 5 && (time() - $last_time) < 30) {
        $error_msg = "Too many failed attempts. Please wait " . (30 - (time() - $last_time)) . " seconds.";
    } else {
        // Reset attempts if lockout period has passed
        if ((time() - $last_time) >= 30) {
            $conn->query("UPDATE tbl_login_attempts SET attempts = 0 WHERE ip = '$ip'");
            $attempts = 0;
        }

        // Check employees first
        $stmt = $conn->prepare("SELECT * FROM tbl_employee WHERE userName = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        $login_success = false;

        if ($result->num_rows > 0) {
            $employee = $result->fetch_assoc();
            if (password_verify($password, $employee['password'])) {
                $login_success = true;
                $conn->query("DELETE FROM tbl_login_attempts WHERE ip = '$ip'");

            // Regenerate session ID for security
            session_regenerate_id(true);

            // Set new user data
            $_SESSION['user_id'] = $employee['employeeID'];
            $_SESSION['username'] = $employee['userName'];
            $_SESSION['user_type'] = 'Employee';
            $_SESSION['access_level'] = $employee['accessLevelID'];
            $_SESSION['name'] = $employee['firstName'] . ' ' . $employee['lastName'];

            // Role-based redirection
            $target = "../../JDE_ADMIN/backend/admin_dashboard.php";
            if ($_SESSION['access_level'] == 2) {
                $target = "../../JDE_ADMIN/backend/appointments.php";
            } elseif ($_SESSION['access_level'] == 3) {
                $target = "../../JDE_ADMIN/backend/orders.php";
            }

            header("Location: $target");
            exit();
        } else {
            $error_msg = "Invalid email or password.";
        }
    } else {
        // Check customers
        $stmt = $conn->prepare("SELECT * FROM tbl_customer WHERE userName = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
                if (password_verify($password, $customer['password'])) {
                    // Check if email is verified
                    if ($customer['is_verified'] == 0) {
                        $error_msg = "Please verify your email address before logging in. Check your inbox for the verification link.";
                    } elseif (!empty($customer['isBlocked'])) {
                        $error_msg = "Your account has been suspended. Please contact support for assistance.";
                    } else {
                        $login_success = true;
                        $conn->query("DELETE FROM tbl_login_attempts WHERE ip = '$ip'");
                        // Regenerate session ID for security
                        session_regenerate_id(true);

                    // Set user data
                    $_SESSION['user_id'] = $customer['customerID'];
                    $_SESSION['username'] = $customer['userName'];
                    $_SESSION['email'] = $customer['email'];
                    $_SESSION['user_type'] = 'Customer';
                    $_SESSION['name'] = $customer['firstName'] . ' ' . $customer['lastName'];

                    // Initialize wishlist if not exists
                    if (!isset($_SESSION['wishlist'])) {
                        $_SESSION['wishlist'] = [];
                    }

                    // Load DB wishlist
                    $cID = $customer['customerID'];
                    $wStmt = $conn->prepare("SELECT productSKU FROM tbl_wishlist WHERE customerID = ?");
                    $wStmt->bind_param("i", $cID);
                    $wStmt->execute();
                    $wResult = $wStmt->get_result();
                    while ($wRow = $wResult->fetch_assoc()) {
                        if (!in_array($wRow['productSKU'], $_SESSION['wishlist'])) {
                            $_SESSION['wishlist'][] = $wRow['productSKU'];
                        }
                    }
                    $wStmt->close();

                    header("Location: index.php");
                    exit();
                }
            } else {
                $error_msg = "Invalid email or password.";
            }
        } else {
            $error_msg = "Invalid email or password.";
        }
    }
    }
    $stmt->close();

    // If we reached here and success is false, increment attempts
    if (isset($login_success) && !$login_success) {
        $conn->query("INSERT INTO tbl_login_attempts (ip, attempts) VALUES ('$ip', 1) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP");
    }
}

require '../html/login.view.php';