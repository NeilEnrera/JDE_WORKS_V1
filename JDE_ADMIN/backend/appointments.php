<?php
session_start();

// Auth check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || !in_array($_SESSION['access_level'], [1, 2])) {
    header('Location: ../../JDE_USER/backend/login.php');
    exit;
}
require_once '../../JDE_USER/backend/db_connection.php';

$pageTitle = 'Appointments Management';

// --- PAGINATION LOGIC ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

// Count total appointments for pagination
$totalResult = $conn->query("SELECT COUNT(*) as total FROM tbl_appointment");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch appointments from DB
$appointments = [];
$res = $conn->query("
    SELECT * FROM tbl_appointment
    ORDER BY appointmentDate DESC, STR_TO_DATE(appointmentTime, '%l:%i %p') ASC
    LIMIT $limit OFFSET $offset
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $appointments[] = $row;
    }
}

require '../html/appointments.view.php';
?>