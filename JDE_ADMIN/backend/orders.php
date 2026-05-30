<?php
session_start();
require_once '../../JDE_USER/backend/db_connection.php';

// Auth check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || !in_array($_SESSION['access_level'], [1, 3])) {
    header('Location: ../../JDE_USER/backend/login.php');
    exit;
}

// 2. Fetch Orders
// Compatibility-safe migration for isActive column on tbl_order
$checkColOrder = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'isActive'");
if ($checkColOrder->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_order ADD isActive TINYINT(1) NOT NULL DEFAULT 1");
}

// Ensure productImage snapshot column exists before referencing it in queries
$conn->query("ALTER TABLE tbl_cartItem ADD COLUMN IF NOT EXISTS productImage VARCHAR(500) NULL DEFAULT NULL");

// Clear unread cancellation notifications for admin when viewing the list
$conn->query("UPDATE tbl_order SET isAdminSeen = 1 WHERE orderStatus = 'Cancelled' AND isAdminSeen = 0");

// Compatibility migration for discount and balance columns
$checkDiscount = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'discountAmount'");
if ($checkDiscount->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_order ADD COLUMN discountAmount DECIMAL(10,2) DEFAULT 0.00");
}
$checkBalance = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'paymentBalance'");
if ($checkBalance->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_order ADD COLUMN paymentBalance DECIMAL(10,2) DEFAULT NULL");
    // Initialize paymentBalance with totalPrice for existing records
    $conn->query("UPDATE tbl_order SET paymentBalance = totalPrice WHERE paymentBalance IS NULL");
}

// Migration for tracking timestamps
$trackingCols = [
    'paidAt' => 'DATETIME DEFAULT NULL',
    'processingAt' => 'DATETIME DEFAULT NULL',
    'shippedAt' => 'DATETIME DEFAULT NULL',
    'outForDeliveryAt' => 'DATETIME DEFAULT NULL',
    'completedAt' => 'DATETIME DEFAULT NULL',
    'isAdminSeen' => 'TINYINT(1) DEFAULT 0',
    'cancelReason' => 'TEXT DEFAULT NULL',
    'returnReason' => 'VARCHAR(255) DEFAULT NULL',
    'returnRemarks' => 'TEXT DEFAULT NULL'
];
foreach ($trackingCols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE tbl_order ADD COLUMN $col $def");
    }
}

// Migration for paymentOption
$checkPaymentOption = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'paymentOption'");
if ($checkPaymentOption->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_order ADD COLUMN paymentOption VARCHAR(20) DEFAULT 'full'");
}

// --- PAGINATION LOGIC ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

// Count total orders for pagination (using same joins as main query to avoid ghost counts)
$totalResult = $conn->query("
    SELECT COUNT(o.orderID) as total 
    FROM tbl_order o
    JOIN tbl_customer c ON o.customerID = c.customerID
    JOIN tbl_cart crt ON o.cartID = crt.cartID
");
$totalRows = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $limit);

$ordersResult = $conn->query("
    SELECT o.orderID, o.totalPrice, o.discountAmount, o.paymentBalance, o.orderStatus, o.updatedBy, o.shippingAddress, o.proofOfDelivery, o.distributionMethod, o.isActive,
           o.returnReason, o.returnRemarks, o.paymentOption,
           o.paidAt, o.processingAt, o.shippedAt, o.outForDeliveryAt, o.completedAt, crt.dateCreated as orderPlacedDate,
           CONCAT(c.firstName, ' ', c.lastName) as customerName,
           pay.dateCreated as orderDate, pay.proofOfPayment, pm.methodName, pay.paymentStatus, pay.downPaymentAmount,
           pay.balanceProofOfPayment, pay.balanceReferenceNumber, pay.balancePaymentStatus, pay.balancePaidAt,
           pi.productImage, pi.productName, pi.categoryName, pi.isCustom
    FROM tbl_order o
    JOIN tbl_customer c ON o.customerID = c.customerID
    JOIN tbl_cart crt ON o.cartID = crt.cartID
    LEFT JOIN tbl_payments pay ON o.orderID = pay.orderID
    LEFT JOIN tbl_paymentMethod pm ON pay.paymentMethodID = pm.paymentMethodID
    LEFT JOIN (
        SELECT ci.cartID, 
               MAX(CASE WHEN ci.customizeID IS NOT NULL AND ci.customizeID <> 0 THEN 1 ELSE 0 END) as isCustom,
               COALESCE(MAX(p.productName), 'Custom Tailoring') as productName, 
               COALESCE(MAX(p.productImage), MAX(ci.productImage)) as productImage,
               COALESCE(MAX(cat.categoryName), 'Custom') as categoryName
        FROM tbl_cartItem ci
        LEFT JOIN tbl_product p ON ci.productID = p.productID
        LEFT JOIN tbl_productCategory cat ON p.categoryID = cat.categoryID
        GROUP BY ci.cartID
    ) pi ON pi.cartID = o.cartID
    -- Removed WHERE o.isActive = 1 to allow tracking of all orders
    ORDER BY o.orderID DESC
    LIMIT $limit OFFSET $offset
");
$orders = [];
while ($row = $ordersResult->fetch_assoc()) {
    // Pre-calculate gender labels for the view
    $cat = $row['categoryName'] ?? '';
    if (stripos($cat, 'Men') !== false) $row['genderLabel'] = 'Men';
    elseif (stripos($cat, 'Women') !== false) $row['genderLabel'] = 'Women';
    else $row['genderLabel'] = '-';

    $orders[] = $row;
}

$pageTitle = "Management Hub";
require '../html/orders.view.php';
?>