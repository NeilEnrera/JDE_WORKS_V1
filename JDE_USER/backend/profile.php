<?php
session_start();
require_once 'db_connection.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    if (($_GET['action'] ?? '') === 'clear_order_notifs') {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle AJAX actions
if (isset($_GET['action']) && $_GET['action'] === 'clear_order_notifs') {
    $status = $_GET['status'] ?? '';
    $query = "";

    if ($status === 'pending') {
        // Pending/Unpaid logic matches the count in lines 91-93
        $query = "UPDATE tbl_order o 
                  LEFT JOIN tbl_payments p ON o.orderID = p.orderID 
                  SET o.isSeen = 1 
                  WHERE o.customerID = ? AND (p.paymentStatus = 'Unpaid' OR p.paymentStatus IS NULL) AND o.orderStatus NOT IN ('Delivered', 'Completed', 'Cancelled')";
    } elseif ($status === 'processing') {
        $query = "UPDATE tbl_order SET isSeen = 1 WHERE customerID = ? AND orderStatus = 'Processing'";
    } elseif ($status === 'paid') {
        $query = "UPDATE tbl_order SET isSeen = 1 WHERE customerID = ? AND orderStatus = 'Paid'";
    } elseif ($status === 'out_for_delivery') {
        $query = "UPDATE tbl_order SET isSeen = 1 WHERE customerID = ? AND orderStatus = 'Out for delivery'";
    } elseif ($status === 'history') {
        $query = "UPDATE tbl_order SET isSeen = 1 WHERE customerID = ? AND orderStatus IN ('Delivered', 'Completed')";
    }

    if ($query) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit();
    }
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $phone = $_POST['phone'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $birthday = $_POST['birthday'] ?? '';

        // Email uniqueness check: ensure the email is not already used by another account
        $emailCheck = $conn->prepare("SELECT customerID FROM tbl_customer WHERE email = ? AND customerID != ? LIMIT 1");
        $emailCheck->bind_param("si", $email, $user_id);
        $emailCheck->execute();
        $emailCheck->store_result();
        if ($emailCheck->num_rows > 0) {
            $msg = "This email address is already associated with another account.";
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            }
            $_SESSION['error_message'] = $msg;
            header("Location: profile.php?pane=account");
            exit();
        }
        $emailCheck->close();

        $stmt = $conn->prepare("UPDATE tbl_customer SET firstName = ?, lastName = ?, email = ?, phoneNumber = ?, gender = ?, birthday = ? WHERE customerID = ?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $gender, $birthday, $user_id);

        if ($stmt->execute()) {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => true, 'message' => "Profile updated successfully!"]);
                exit();
            }
            $_SESSION['success_message'] = "Profile updated successfully!";
            $_SESSION['email'] = $email;
            header("Location: profile.php");
            exit();
        } else {
            $msg = "Error updating profile. Please try again.";
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            }
            $_SESSION['error_message'] = $msg;
            header("Location: profile.php?pane=account");
            exit();
        }
        $stmt->close();

    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            $msg = "New passwords do not match.";
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            }
            $_SESSION['error_message'] = $msg;
            header("Location: profile.php?pane=security");
            exit();
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new_password)) {
            $msg = "Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters.";
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            }
            $_SESSION['error_message'] = $msg;
            header("Location: profile.php?pane=security");
            exit();
        } else {
            $stmt = $conn->prepare("SELECT password FROM tbl_customer WHERE customerID = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE tbl_customer SET password = ? WHERE customerID = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                if ($update_stmt->execute()) {
                    if (isset($_POST['ajax'])) {
                        echo json_encode(['success' => true, 'message' => "Password changed successfully!"]);
                        exit();
                    }
                    $_SESSION['success_message'] = "Password changed successfully!";
                    header("Location: profile.php");
                    exit();
                } else {
                    $msg = "Error updating password.";
                    if (isset($_POST['ajax'])) {
                        echo json_encode(['success' => false, 'message' => $msg]);
                        exit();
                    }
                    $_SESSION['error_message'] = $msg;
                    header("Location: profile.php?pane=security");
                    exit();
                }
                $update_stmt->close();
            } else {
                $msg = "Incorrect current password.";
                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit();
                }
                $_SESSION['error_message'] = $msg;
                header("Location: profile.php?pane=security");
                exit();
            }
            $stmt->close();
        }
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM tbl_customer WHERE customerID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user_data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch order counts
$counts = ['pending' => 0, 'paid' => 0, 'processing' => 0, 'out_for_delivery' => 0, 'history' => 0];

try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM tbl_order o
        LEFT JOIN tbl_payments p ON o.orderID = p.orderID
        WHERE o.customerID = ? AND (p.paymentStatus = 'Unpaid' OR p.paymentStatus IS NULL) AND o.orderStatus NOT IN ('Delivered', 'Completed', 'Cancelled') AND o.isSeen = 0
    ");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $counts['pending'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_order WHERE customerID = ? AND orderStatus = 'Paid' AND isSeen = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $counts['paid'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_order WHERE customerID = ? AND orderStatus = 'Processing' AND isSeen = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $counts['processing'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_order WHERE customerID = ? AND orderStatus = 'Out for delivery' AND isSeen = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $counts['out_for_delivery'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_order WHERE customerID = ? AND orderStatus IN ('Delivered', 'Completed') AND isSeen = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $counts['history'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }

    // Fetch User Appointments (Including Cancelled to show rescheduling needs)
    $appointments = [];
    $stmt = $conn->prepare("
        SELECT appointmentID, appointmentDate, appointmentTime, serviceType, message, appointmentStatus, dateCreated, isReschedulePrompted
        FROM tbl_appointment
        WHERE customerID = ? AND appointmentStatus != 'Rejected'
        ORDER BY appointmentDate DESC, appointmentTime DESC
    ");

    // Load blocked dates for flagging
    $blocked_json = __DIR__ . '/blocked_dates.json';
    $blocked_dates_raw = file_exists($blocked_json) ? json_decode(file_get_contents($blocked_json), true) : [];
    $blocked_dates = array_column($blocked_dates_raw, 'blockedDate');

    $has_blocked_conflict = false;

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $appointments_result = $stmt->get_result();
        while ($row = $appointments_result->fetch_assoc()) {
            // Flag as blocked if status is Cancelled and date is in blocked_dates.json
            $row['is_blocked_conflict'] = ($row['appointmentStatus'] === 'Cancelled' && in_array($row['appointmentDate'], $blocked_dates));
            
            // Only show modal if user hasn't seen this specific cancellation yet
            if ($row['is_blocked_conflict'] && $row['isReschedulePrompted'] == 0) {
                $has_blocked_conflict = true;
            }
            $appointments[] = $row;
        }
        $stmt->close();
    }

    // Modal popup logic (only shows if not dismissed this session or if new conflicts exist)
    $reschedule_notice = ($has_blocked_conflict && empty($_SESSION['dismissed_reschedule_notice']));

} catch (mysqli_sql_exception $e) {
    error_log("Order count error: " . $e->getMessage());
}
?>
<script>
    window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    window.customerFullName = <?php echo json_encode(trim(($user_data['firstName'] ?? '') . ' ' . ($user_data['lastName'] ?? '')) ?: ($user_data['userName'] ?? 'Customer')); ?>;
    window.rescheduleNotice = <?php echo !empty($reschedule_notice) ? 'true' : 'false'; ?>;
    window.successMessage = <?php echo json_encode($success_message); ?>;
    window.errorMessage = <?php echo json_encode($error_message); ?>;
</script>
<?php
require '../html/profile.view.php';