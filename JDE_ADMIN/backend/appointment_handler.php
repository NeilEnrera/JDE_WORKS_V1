<?php
session_start();
require_once '../../JDE_USER/backend/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id = intval($_POST['appointmentID'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!$id || !$status) {
            echo json_encode(['success' => false, 'message' => 'Missing ID or status']);
            exit;
        }

        // Fetch appointment details before update for email notification
        $detailsQuery = $conn->prepare("SELECT name, email, appointmentDate, appointmentTime, serviceType FROM tbl_appointment WHERE appointmentID = ?");
        $detailsQuery->bind_param("i", $id);
        $detailsQuery->execute();
        $details = $detailsQuery->get_result()->fetch_assoc();
        $detailsQuery->close();

        $stmt = $conn->prepare("UPDATE tbl_appointment SET appointmentStatus = ? WHERE appointmentID = ?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            if ($details && in_array($status, ['Accepted', 'Declined'])) {
                require_once '../../JDE_USER/backend/email_helper.php';
                EmailHelper::dispatchAsync('appointment_status', [
                    'to' => $details['email'],
                    'name' => $details['name'],
                    'date' => $details['appointmentDate'],
                    'time' => $details['appointmentTime'],
                    'service' => $details['serviceType'],
                    'status' => $status
                ]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'check_conflicts') {
        $id = intval($_POST['appointmentID'] ?? 0);
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';

        $stmt = $conn->prepare("
            SELECT name, appointmentTime 
            FROM tbl_appointment 
            WHERE appointmentDate = ? 
            AND appointmentID != ? 
            AND (appointmentStatus = 'Accepted' OR (appointmentStatus = 'Cancelled' AND internalRemarks LIKE 'Cancelled due to date blocking%' AND isReschedulePrompted = 0))
        ");
        $stmt->bind_param("si", $date, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $conflicts = [];
        while ($row = $result->fetch_assoc()) {
            // Check if exact time matches
            if ($row['appointmentTime'] === $time) {
                $conflicts[] = $row;
            }
        }

        echo json_encode(['success' => true, 'conflicts' => $conflicts]);
        $stmt->close();
        exit;
    }

    if ($action === 'save_advanced') {
        $id = intval($_POST['appointmentID'] ?? 0);
        $remarks = $_POST['internalRemarks'] ?? '';
        $empID = $_POST['employeeID'] ? intval($_POST['employeeID']) : null;

        $stmt = $conn->prepare("UPDATE tbl_appointment SET internalRemarks = ?, employeeID = ? WHERE appointmentID = ?");
        $stmt->bind_param("sii", $remarks, $empID, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get_staff') {
        $result = $conn->query("SELECT employeeID, firstName, lastName FROM tbl_employee ORDER BY firstName ASC");
        $staff = [];
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
        echo json_encode(['success' => true, 'staff' => $staff]);
        exit;
    }

    if ($action === 'get_details') {
        $id = intval($_POST['appointmentID'] ?? 0);

        $stmt = $conn->prepare("
            SELECT a.*, e.firstName, e.lastName 
            FROM tbl_appointment a 
            LEFT JOIN tbl_employee e ON a.employeeID = e.employeeID 
            WHERE a.appointmentID = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();

        if ($appointment) {
            echo json_encode(['success' => true, 'data' => $appointment]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'check_date_appointments') {
        $date = $_POST['date'] ?? '';

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_appointment WHERE appointmentDate = ? AND appointmentStatus IN ('Accepted', 'Pending', 'Confirmed')");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();

        echo json_encode(['success' => true, 'count' => $row['count']]);
        $stmt->close();
        exit;
    }

    if ($action === 'get_blocked_dates') {
        $jsonPath = __DIR__ . '/../../JDE_USER/backend/blocked_dates.json';
        $blocked = [];
        if (file_exists($jsonPath)) {
            $blocked = json_decode(file_get_contents($jsonPath), true) ?: [];
        }

        // Return with dummy IDs to maintain compatibility with JS logic
        foreach ($blocked as $index => &$item) {
            if (!isset($item['blockedID'])) {
                $item['blockedID'] = $index + 1;
            }
        }

        echo json_encode(['success' => true, 'data' => $blocked]);
        exit;
    }

    if ($action === 'block_date') {
        $date = $_POST['date'] ?? '';
        $reason = $_POST['reason'] ?? '';

        if (!$date) {
            echo json_encode(['success' => false, 'message' => 'Date is required']);
            exit;
        }

        $jsonPath = __DIR__ . '/../../JDE_USER/backend/blocked_dates.json';
        $blocked = [];
        if (file_exists($jsonPath)) {
            $blocked = json_decode(file_get_contents($jsonPath), true) ?: [];
        }

        // Check if date already exists
        $exists = false;
        foreach ($blocked as $item) {
            if ($item['blockedDate'] === $date) {
                $exists = true;
                break;
            }
        }

        $affected = [];
        if (!$exists) {
            $blocked[] = [
                'blockedID' => time() . rand(100, 999), 
                'blockedDate' => $date,
                'reason' => $reason,
                'createdAt' => date('Y-m-d H:i:s')
            ];
            file_put_contents($jsonPath, json_encode($blocked, JSON_PRETTY_PRINT));

            // Fetch affected appointments *before* cancelling to return detailed data to frontend
            $stmt = $conn->prepare("SELECT name, email, appointmentDate, appointmentTime, serviceType FROM tbl_appointment WHERE appointmentDate = ? AND appointmentStatus IN ('Accepted', 'Pending', 'Confirmed')");
            if ($stmt) {
                $stmt->bind_param("s", $date);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $affected[] = $row;
                }
                $stmt->close();
            }

            // Cancel all affected appointments
            $cancelStmt = $conn->prepare("UPDATE tbl_appointment SET appointmentStatus = 'Cancelled', internalRemarks = 'Cancelled due to date blocking', isReschedulePrompted = 0 WHERE appointmentDate = ? AND appointmentStatus IN ('Accepted', 'Pending', 'Confirmed')");
            if ($cancelStmt) {
                $cancelStmt->bind_param("s", $date);
                $cancelStmt->execute();
                $cancelStmt->close();
            }
        }

        echo json_encode(['success' => true, 'affected' => $affected]);
        exit;
    }

    if ($action === 'send_blocked_notification') {
        require_once '../../JDE_USER/backend/email_helper.php';
        $email = $_POST['email'] ?? '';
        $name = $_POST['name'] ?? '';
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $svc = $_POST['service'] ?? '';

        if (!$email || !$name) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }

        $sent = EmailHelper::sendAppointmentBlockedNotification($email, $name, $date, $time, $svc);
        echo json_encode(['success' => $sent]);
        exit;
    }

    if ($action === 'unblock_date') {
        $id = $_POST['blockedID'] ?? '';

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            exit;
        }

        $jsonPath = __DIR__ . '/../../JDE_USER/backend/blocked_dates.json';
        if (file_exists($jsonPath)) {
            $blocked = json_decode(file_get_contents($jsonPath), true) ?: [];
            $newBlocked = array_filter($blocked, function ($item) use ($id) {
                return (string) $item['blockedID'] !== (string) $id;
            });
            file_put_contents($jsonPath, json_encode(array_values($newBlocked), JSON_PRETTY_PRINT));
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_settings') {
        $jsonPath = __DIR__ . '/appointment_settings.json';
        $settings = ['reminder_time' => '06:00'];
        if (file_exists($jsonPath)) {
            $settings = json_decode(file_get_contents($jsonPath), true) ?: $settings;
        }
        echo json_encode(['success' => true, 'data' => $settings]);
        exit;
    }

    if ($action === 'save_settings') {
        $time = $_POST['reminder_time'] ?? '';
        if (!$time) {
            echo json_encode(['success' => false, 'message' => 'Time is required']);
            exit;
        }

        $jsonPath = __DIR__ . '/appointment_settings.json';
        $settings = ['reminder_time' => $time];
        file_put_contents($jsonPath, json_encode($settings, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>