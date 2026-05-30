<?php
session_start();

// Auth check — Level 1 only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || $_SESSION['access_level'] != 1) {
    header('Location: ../../JDE_USER/backend/login.php');
    exit;
}

require_once '../../JDE_USER/backend/db_connection.php';

// ── Sanitised inputs ──────────────────────────────────────────────────────────
$range = in_array($_GET['range'] ?? '', ['daily', 'monthly', 'yearly']) ? $_GET['range'] : 'monthly';
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
$month = max(1, min(12, (int) ($_GET['month'] ?? date('n'))));
$year = max(2020, min((int) date('Y'), (int) ($_GET['yearMonth'] ?? $_GET['year'] ?? date('Y'))));
$startDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['startDate'] ?? '') ? $_GET['startDate'] : '';
$endDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['endDate'] ?? '') ? $_GET['endDate'] : '';

// ── CSV export endpoint ───────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales_report_' . $range . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Order ID', 'Customer', 'Payment Method', 'Amount Paid (₱)']);

    $baseQ = "SELECT o.completedAt as dateCreated, p.orderID, c.firstName, c.lastName,
                     p.paymentMethodID, p.fullPaymentAmount
              FROM tbl_payments p
              JOIN tbl_order o ON p.orderID = o.orderID
              LEFT JOIN tbl_customer c ON o.customerID = c.customerID
              WHERE o.orderStatus = 'Completed' AND p.paymentStatus = 'Approved' AND o.completedAt IS NOT NULL";

    if ($startDate && $endDate) {
        $stmt = $conn->prepare($baseQ . " AND DATE(o.completedAt) BETWEEN ? AND ?");
        $stmt->bind_param('ss', $startDate, $endDate);
    } elseif ($range === 'daily') {
        $stmt = $conn->prepare($baseQ . " AND DATE(o.completedAt) = ?");
        $stmt->bind_param('s', $date);
    } elseif ($range === 'monthly') {
        $stmt = $conn->prepare($baseQ . " AND MONTH(o.completedAt) = ? AND YEAR(o.completedAt) = ?");
        $stmt->bind_param('ii', $month, $year);
    } else {
        $stmt = $conn->prepare($baseQ . " AND YEAR(o.completedAt) = ?");
        $stmt->bind_param('i', $year);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $methodMap = [1 => 'GCash', 2 => 'COD'];
        $mName = $methodMap[$row['paymentMethodID'] ?? 0] ?? 'Other';

        fputcsv($out, [
            date('M d, Y', strtotime($row['dateCreated'])),
            '#ORD-' . str_pad($row['orderID'], 3, '0', STR_PAD_LEFT),
            $row['firstName'] . ' ' . $row['lastName'],
            $mName,
            number_format($row['fullPaymentAmount'], 2)
        ]);
    }
    fclose($out);
    exit;
}

// ── Title ─────────────────────────────────────────────────────────────────────
if ($startDate && $endDate) {
    $title = 'Sales Report — ' . date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate));
} else {
    $title = match ($range) {
        'daily' => 'Daily Sales Report — ' . date('F j, Y', strtotime($date)),
        'monthly' => 'Monthly Sales Report — ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)),
        'yearly' => 'Yearly Sales Report — ' . $year,
    };
}

// ── Main query (prepared) ─────────────────────────────────────────────────────
$baseSQL = "SELECT o.completedAt as dateCreated, p.orderID, c.firstName, c.lastName,
                   p.paymentMethodID, p.fullPaymentAmount
            FROM tbl_payments p
            JOIN tbl_order o ON p.orderID = o.orderID
            LEFT JOIN tbl_customer c ON o.customerID = c.customerID
            WHERE o.orderStatus = 'Completed' AND p.paymentStatus = 'Approved' AND o.completedAt IS NOT NULL";

if ($startDate && $endDate) {
    $stmt = $conn->prepare($baseSQL . " AND DATE(o.completedAt) BETWEEN ? AND ? ORDER BY o.completedAt ASC");
    $stmt->bind_param('ss', $startDate, $endDate);
} elseif ($range === 'daily') {
    $stmt = $conn->prepare($baseSQL . " AND DATE(o.completedAt) = ? ORDER BY o.completedAt ASC");
    $stmt->bind_param('s', $date);
} elseif ($range === 'monthly') {
    $stmt = $conn->prepare($baseSQL . " AND MONTH(o.completedAt) = ? AND YEAR(o.completedAt) = ? ORDER BY o.completedAt ASC");
    $stmt->bind_param('ii', $month, $year);
} else {
    $stmt = $conn->prepare($baseSQL . " AND YEAR(o.completedAt) = ? ORDER BY o.completedAt ASC");
    $stmt->bind_param('i', $year);
}
$stmt->execute();
$result = $stmt->get_result();

$sales = [];
$totalRevenue = 0;
$methodTotals = [];
$methodMap = [1 => 'GCash', 2 => 'COD'];

while ($row = $result->fetch_assoc()) {
    $sales[]       = $row;
    $totalRevenue += $row['fullPaymentAmount'];
    
    // Mapping IDs to Names
    $mID = $row['paymentMethodID'] ?? 0;
    $mName = $methodMap[$mID] ?? 'Other';
    
    $methodTotals[$mName] = ($methodTotals[$mName] ?? 0) + $row['fullPaymentAmount'];
}

$pageTitle = 'Sales Report';
$bodyClass = '';
require '../html/sales_report.view.php';
?>