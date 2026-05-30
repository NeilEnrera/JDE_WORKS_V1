<?php
session_start();
// auth check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || $_SESSION['access_level'] != 1) {
    header('Location: ../../JDE_USER/backend/login.php');
    exit;
}

require_once '../../JDE_USER/backend/db_connection.php';

function getTableColumns(mysqli $conn, string $tableName): array
{
    $columns = [];
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

function firstAvailableColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }
    return null;
}

// 1. Core Summary Metrics
$totalOrdersRes = $conn->query("SELECT COUNT(*) as total FROM tbl_order");
$totalOrders = ($totalOrdersRes && $row = $totalOrdersRes->fetch_assoc()) ? $row['total'] : 0;

$pendingOrdersRes = $conn->query("SELECT COUNT(*) as total FROM tbl_order WHERE orderStatus = 'Pending'");
$pendingOrders = ($pendingOrdersRes && $row = $pendingOrdersRes->fetch_assoc()) ? $row['total'] : 0;

$todayApptsRes = $conn->query("SELECT COUNT(*) as total FROM tbl_appointment WHERE appointmentDate = CURDATE()");
$todayAppointments = ($todayApptsRes && $row = $todayApptsRes->fetch_assoc()) ? $row['total'] : 0;

$unreadInquiries = 0;
$unreadInqRes = $conn->query("SHOW TABLES LIKE 'tbl_contactMessages'");
if ($unreadInqRes && $unreadInqRes->num_rows > 0) {
    $countRes = $conn->query("SELECT COUNT(*) as total FROM tbl_contactMessages WHERE isRead = 0");
    $unreadInquiries = ($countRes && $row = $countRes->fetch_assoc()) ? $row['total'] : 0;
}

$totalUsersRes = $conn->query("SELECT COUNT(*) as total FROM tbl_customer");
$totalUsers = ($totalUsersRes && $row = $totalUsersRes->fetch_assoc()) ? $row['total'] : 0;

$unreadChatUsers = 0;
$unreadChatRes = $conn->query("SELECT COUNT(DISTINCT customerID) as total FROM tbl_chatLogs WHERE employeeID IS NULL AND isRead = 0");
$unreadChatUsers = ($unreadChatRes && $row = $unreadChatRes->fetch_assoc()) ? $row['total'] : 0;

// 2. Status Breakdown
$statusCountsResult = $conn->query("SELECT orderStatus, COUNT(*) as count FROM tbl_order GROUP BY orderStatus");
$statusBreakdown = [];
while ($row = $statusCountsResult->fetch_assoc()) {
    $statusBreakdown[$row['orderStatus']] = $row['count'];
}

// 3. Inventory Management Data
$productColumns = getTableColumns($conn, 'tbl_product');
$orderColumns = getTableColumns($conn, 'tbl_order');
$stockThresholdColumn = firstAvailableColumn($productColumns, ['stockThreshold', 'reorderLevel', 'minStock', 'lowStockThreshold']);
$stockDateColumn = firstAvailableColumn($productColumns, ['updatedAt', 'dateUpdated', 'lastUpdated', 'modifiedAt', 'dateModified']);
$orderDateColumn = firstAvailableColumn($orderColumns, ['dateCreated', 'orderDate', 'createdAt', 'updatedAt']);
$defaultThreshold = 5;
$thresholdExpr = $stockThresholdColumn ? "COALESCE(NULLIF(p.`{$stockThresholdColumn}`, 0), {$defaultThreshold})" : (string) $defaultThreshold;

// Improved Inventory Logic: Peek into sizeStocks JSON for granular alerts
$inventoryProductsResult = $conn->query("
    SELECT
        p.productID,
        p.productName,
        p.stocks,
        p.sizeStocks,
        {$thresholdExpr} AS stockThreshold,
        p.price
    FROM tbl_product p
    WHERE p.isActive = 1
    ORDER BY p.productName ASC
");

$inventorySummary = [
    'totalProducts' => 0,
    'totalStockQty' => 0,
    'lowStockItems' => 0,
    'outOfStockItems' => 0
];

$allInventoryProducts = [];
$lowStockProducts = [];

while ($inventoryProductsResult && $row = $inventoryProductsResult->fetch_assoc()) {
    $inventorySummary['totalProducts']++;
    $inventorySummary['totalStockQty'] += (int)$row['stocks'];
    
    $sizeStocks = json_decode($row['sizeStocks'] ?? '{}', true);
    $defaultThreshold = (int)$row['stockThreshold'];
    
    // If no specific size JSON, use product-level total
    if (empty($sizeStocks)) {
        $stock = (int)$row['stocks'];
        if ($stock <= 0) {
            $status = 'Out of Stock';
            $statusClass = 'stock-out';
            $inventorySummary['outOfStockItems']++;
        } elseif ($stock <= $defaultThreshold) {
            $status = 'Low Stock';
            $statusClass = 'stock-low';
            $inventorySummary['lowStockItems']++;
        } else {
            $status = 'In Stock';
            $statusClass = 'stock-in';
        }

        $item = [
            'id' => $row['productID'],
            'productName' => $row['productName'],
            'size' => 'All',
            'stocks' => $stock,
            'stockThreshold' => $defaultThreshold,
            'status' => $status,
            'statusClass' => $statusClass
        ];
        $allInventoryProducts[] = $item;
        if ($status !== 'In Stock') $lowStockProducts[] = $item;
    } else {
        // Evaluate each size individually
        foreach ($sizeStocks as $sizeName => $data) {
            $qty = is_array($data) ? (int)($data['qty'] ?? 0) : (int)$data;
            // threshold for specific size if ever implemented, else default
            $threshold = is_array($data) ? (int)($data['threshold'] ?? $defaultThreshold) : $defaultThreshold;

            if ($qty <= 0) {
                $status = 'Out of Stock';
                $statusClass = 'stock-out';
                $inventorySummary['outOfStockItems']++;
            } elseif ($qty <= $threshold) {
                $status = 'Low Stock';
                $statusClass = 'stock-low';
                $inventorySummary['lowStockItems']++;
            } else {
                continue; // Don't list healthy sizes in the "low stock" logic usually
            }

            $item = [
                'id' => $row['productID'],
                'productName' => $row['productName'],
                'size' => $sizeName,
                'stocks' => $qty,
                'stockThreshold' => $threshold,
                'status' => $status,
                'statusClass' => $statusClass
            ];
            $lowStockProducts[] = $item;
        }
    }
}

usort($lowStockProducts, function ($a, $b) {
    if ($a['status'] === $b['status']) {
        return $a['stocks'] <=> $b['stocks'];
    }
    return ($a['status'] === 'Out of Stock') ? -1 : 1;
});
$lowStockItems = array_slice($lowStockProducts, 0, 12);





if (isset($_GET['inventory_data']) && $_GET['inventory_data'] === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'inventorySummary' => $inventorySummary,
        'lowStockItems' => $lowStockItems
    ]);
    exit;
}

// 4. Revenue Performance (Daily / Monthly / Yearly)
$dailyRevRes = $conn->query("SELECT SUM(p.fullPaymentAmount) as total FROM tbl_payments p JOIN tbl_order o ON p.orderID = o.orderID WHERE DATE(o.completedAt) = CURDATE() AND o.orderStatus = 'Completed' AND p.paymentStatus = 'Approved'");
$dailyRevenue = ($dailyRevRes && $row = $dailyRevRes->fetch_assoc()) ? $row['total'] : 0;

$monthlyRevRes = $conn->query("SELECT SUM(p.fullPaymentAmount) as total FROM tbl_payments p JOIN tbl_order o ON p.orderID = o.orderID WHERE MONTH(o.completedAt) = MONTH(CURDATE()) AND YEAR(o.completedAt) = YEAR(CURDATE()) AND o.orderStatus = 'Completed' AND p.paymentStatus = 'Approved'");
$monthlyRevenue = ($monthlyRevRes && $row = $monthlyRevRes->fetch_assoc()) ? $row['total'] : 0;

$yearlyRevRes = $conn->query("SELECT SUM(p.fullPaymentAmount) as total FROM tbl_payments p JOIN tbl_order o ON p.orderID = o.orderID WHERE YEAR(o.completedAt) = YEAR(CURDATE()) AND o.orderStatus = 'Completed' AND p.paymentStatus = 'Approved'");
$yearlyRevenue = ($yearlyRevRes && $row = $yearlyRevRes->fetch_assoc()) ? $row['total'] : 0;

// Preserving recentActivity and categoryDist for potential integrations
require_once '../../JDE_USER/backend/services/ActivityService.php';
$recentActivity = ActivityService::getRecentActivity($conn);

$categoryDistResult = $conn->query("
    SELECT c.categoryName, COUNT(p.productID) as count
    FROM tbl_product p
    JOIN tbl_productCategory c ON p.categoryID = c.categoryID
    GROUP BY p.categoryID
");
$categoryDist = [];
while ($row = $categoryDistResult->fetch_assoc()) {
    $categoryDist[] = $row;
}

$pageTitle = "Admin Dashboard";
require '../html/admin_dashboard.view.php';
?>
