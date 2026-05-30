<?php
/**
 * appointment.php — Backend controller
 * Handles form submission, DB queries for conflicts and insert, user data fetch.
 * Then renders the appointment view.
 */
session_start();
require_once 'db_connection.php';

// Initialize variables used in the view
$success_message = null;
$error_message = null;
$service_type = '';
$appointment_date = '';
$appointment_time = '';
$user_name = '';
$user_email = '';
$user_phone = '';
$appointment_id = null;

// Pre-fill from session if logged in
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT firstName, lastName, email, phoneNumber, userName FROM tbl_customer WHERE customerID = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_name = trim($row['firstName'] . ' ' . $row['lastName']);
        if (empty($user_name)) {
            $user_name = $row['userName'];
        }
        $user_email = $row['email'];
        $user_phone = $row['phoneNumber'];
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $service_type = trim($_POST['service_type']);
    $appointment_date = trim($_POST['appointment_date']);
    $appointment_time = trim($_POST['appointment_time']);
    $message = trim($_POST['message'] ?? '');
    $customer_id = $_SESSION['user_id'] ?? null;

    // Backend Input Validation
    $allowedServices = [
        'Measurement', 'Fitting', 'Consultation', 'Pickup', 'Other',
        'school-uniform', 'custom-tailoring', 'alteration'
    ];
    $allowedTimes = [
        '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM',
        '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM'
    ];

    if (empty($name) || empty($email) || empty($appointment_date) || empty($appointment_time) || empty($service_type)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (!in_array($service_type, $allowedServices) && !in_array(strtolower($service_type), array_map('strtolower', $allowedServices))) {
        $error_message = "Invalid service type selected.";
    } elseif (!in_array($appointment_time, $allowedTimes)) {
        $error_message = "Invalid appointment time selected.";
    } elseif (strtotime($appointment_date) < strtotime('today')) {
        $error_message = "Appointment date cannot be in the past.";
    } elseif (date('N', strtotime($appointment_date)) == 7) {
        $error_message = "Appointments are not available on Sundays. Please choose another date.";
    } else {
        // Check if the date is blocked by admin (JSON file)
        $is_blocked = false;
        $jsonPath = __DIR__ . '/blocked_dates.json';
        if (file_exists($jsonPath)) {
            $blocked_data = json_decode(file_get_contents($jsonPath), true) ?: [];
            foreach ($blocked_data as $item) {
                if ($item['blockedDate'] === $appointment_date) {
                    $is_blocked = true;
                    break;
                }
            }
        }

        // Check for conflicts (Only against active/pending appointments)
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tbl_appointment WHERE appointmentDate = ? AND appointmentTime = ? AND appointmentStatus NOT IN ('Cancelled', 'Rejected')");
        $stmt->bind_param("ss", $appointment_date, $appointment_time);
        $stmt->execute();
        $conflict = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($is_blocked) {
            $error_message = "The selected date is currently unavailable for booking. Please choose another date.";
        } elseif ($conflict['cnt'] >= 1) {
            $error_message = "This time slot is already booked. Please choose another date or period.";
        } else {
            $stmt = $conn->prepare("INSERT INTO tbl_appointment (customerID, name, email, phone, serviceType, appointmentDate, appointmentTime, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $customer_id, $name, $email, $phone, $service_type, $appointment_date, $appointment_time, $message);
            if ($stmt->execute()) {
                $success_message = "Appointment booked successfully!";
                $appointment_id = $stmt->insert_id;

                // Trigger background email confirmation
                require_once 'email_helper.php';
                EmailHelper::dispatchAsync('appointment_confirmation', [
                    'to' => $email,
                    'name' => $name,
                    'date' => $appointment_date,
                    'time' => $appointment_time,
                    'service' => $service_type
                ]);
            } else {
                $error_message = "Failed to book appointment. Please try again.";
            }
            $stmt->close();
        }
    }
}

require '../html/appointment.view.php';

// Check if user has cancelled upcoming appointments (specifically due to date blocking)
$reschedule_notice = false;
if (isset($_SESSION['user_id']) && empty($_SESSION['dismissed_reschedule_notice'])) {
    // 1. Get blocked dates from JSON
    $blocked_json = __DIR__ . '/blocked_dates.json';
    $blocked_dates = [];
    if (file_exists($blocked_json)) {
        $data = json_decode(file_get_contents($blocked_json), true) ?: [];
        $blocked_dates = array_column($data, 'blockedDate');
    }

    if (!empty($blocked_dates)) {
        $placeholders = implode(',', array_fill(0, count($blocked_dates), '?'));
        $types = str_repeat('s', count($blocked_dates)) . "i";
        
        $rStmt = $conn->prepare("
            SELECT COUNT(*) as cnt FROM tbl_appointment
            WHERE appointmentDate IN ($placeholders)
              AND (customerID = ? OR (customerID IS NULL AND email = ?))
              AND appointmentStatus = 'Cancelled'
              AND isReschedulePrompted = 0
              AND appointmentDate >= CURDATE()
        ");
        
        if ($rStmt) {
            $params = array_merge($blocked_dates, [$_SESSION['user_id'], $user_email]);
            $rParams = array_merge([str_repeat('s', count($blocked_dates)) . "is"], $params);
            
            // Re-evaluating types for safety
            $all_types = str_repeat('s', count($blocked_dates)) . "is";
            $rStmt->bind_param($all_types, ...$params);
            $rStmt->execute();
            $rRow = $rStmt->get_result()->fetch_assoc();
            $reschedule_notice = ($rRow['cnt'] > 0);
            $rStmt->close();
        }
    }
}