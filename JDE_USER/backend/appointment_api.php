<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_connection.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_booked_slots':
            getBookedSlots($conn);
            break;
        case 'dismiss_reschedule_notice':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userID = $_SESSION['user_id'] ?? 0;
            if ($userID) {
                // Persistent dismissal: Mark all currently affected appointments as prompted
                $blocked_json = __DIR__ . '/blocked_dates.json';
                $blocked_dates = [];
                if (file_exists($blocked_json)) {
                    $data = json_decode(file_get_contents($blocked_json), true) ?: [];
                    $blocked_dates = array_column($data, 'blockedDate');
                }

                if (!empty($blocked_dates)) {
                    $placeholders = implode(',', array_fill(0, count($blocked_dates), '?'));
                    $types = "i" . str_repeat('s', count($blocked_dates));
                    $stmt = $conn->prepare("
                        UPDATE tbl_appointment 
                        SET isReschedulePrompted = 1 
                        WHERE customerID = ? 
                          AND appointmentStatus = 'Cancelled' 
                          AND appointmentDate IN ($placeholders)
                    ");
                    if ($stmt) {
                        $params = array_merge([$userID], $blocked_dates);
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
            $_SESSION['dismissed_reschedule_notice'] = true;
            echo json_encode(['success' => true]);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getBookedSlots($conn)
{
    // Fetch all confirmed and pending appointments
    $stmt = $conn->prepare("
        SELECT appointmentDate, TRIM(appointmentTime) as appointmentTime, COUNT(*) as bookingCount
        FROM tbl_appointment 
        WHERE (appointmentStatus NOT IN ('Cancelled', 'Rejected'))
           OR (appointmentStatus = 'Cancelled' AND internalRemarks LIKE 'Cancelled due to date blocking%' AND isReschedulePrompted = 0)
        GROUP BY appointmentDate, appointmentTime
        ORDER BY appointmentDate ASC
    ");

    if (!$stmt->execute()) {
        throw new Exception("Failed to fetch appointments");
    }

    $result = $stmt->get_result();
    $bookings = [];

    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'date' => $row['appointmentDate'],
            'period' => $row['appointmentTime'],
            'count' => (int) $row['bookingCount']
        ];
    }

    $stmt->close();

    // Fetch blocked dates from JSON file
    $blocked = [];
    $jsonPath = __DIR__ . '/blocked_dates.json';
    if (file_exists($jsonPath)) {
        $data = json_decode(file_get_contents($jsonPath), true) ?: [];
        foreach ($data as $item) {
            $blocked[] = $item['blockedDate'];
        }
    }

    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'blocked_dates' => $blocked
    ]);
}
?>