<?php
session_start();
require_once 'db_connection.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<script>
    window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>
<?php
$user_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'all';

// Database migrations for consistency
$checkDiscount = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'discountAmount'");
if ($checkDiscount->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_order ADD COLUMN discountAmount DECIMAL(10,2) DEFAULT 0.00");
}
$checkBalance = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'paymentBalance'");
if ($checkBalance->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_order ADD COLUMN paymentBalance DECIMAL(10,2) DEFAULT NULL");
    $conn->query("UPDATE tbl_order SET paymentBalance = totalPrice WHERE paymentBalance IS NULL");
}
$trackingCols = [
    'paidAt' => 'DATETIME DEFAULT NULL',
    'processingAt' => 'DATETIME DEFAULT NULL',
    'shippedAt' => 'DATETIME DEFAULT NULL',
    'outForDeliveryAt' => 'DATETIME DEFAULT NULL',
    'completedAt' => 'DATETIME DEFAULT NULL',
    'isAdminSeen' => 'TINYINT(1) DEFAULT 0',
    'cancelReason' => 'TEXT DEFAULT NULL'
];
foreach ($trackingCols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE tbl_order ADD COLUMN $col $def");
    }
}

// Pagination setup
$items_per_page = 5;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

$orders = [];
$total_orders = 0;
$total_pages = 0;

try {
    // 1. Build Base Conditions and Joins for both Count and Select
    $base_joins = " FROM tbl_order o JOIN tbl_cart crt ON o.cartID = crt.cartID LEFT JOIN tbl_payments p ON o.orderID = p.orderID ";
    $where_clauses = ["o.customerID = ?"];
    $params = [$user_id];
    $types = "i";

    if ($status_filter === 'history') {
        $where_clauses[] = "o.orderStatus IN ('Delivered', 'Completed', 'Cancelled', 'Return Items')";
    } else {
        // "Active" orders logic
        if ($status_filter === 'all') {
            $where_clauses[] = "o.orderStatus NOT IN ('Delivered', 'Completed', 'Cancelled', 'Return Items')";
        } elseif ($status_filter === 'pending') {
            $where_clauses[] = "(p.paymentStatus = 'Unpaid' OR p.paymentStatus IS NULL OR o.orderStatus = 'Awaiting Balance') AND o.orderStatus NOT IN ('Delivered', 'Completed', 'Cancelled', 'Return Items')";
        } elseif ($status_filter === 'paid') {
            $where_clauses[] = "o.orderStatus = 'Paid'";
        } elseif ($status_filter === 'processing') {
            $where_clauses[] = "o.orderStatus = 'Processing'";
        } elseif ($status_filter === 'out_for_delivery') {
            $where_clauses[] = "o.orderStatus IN ('Shipped', 'Out for delivery', 'Ready for Pick Up')";
        }
    }

    $where_sql = " WHERE " . implode(" AND ", $where_clauses);

    // 2. Execute Count Query with identical conditions
    $count_sql = "SELECT COUNT(DISTINCT o.orderID) as total " . $base_joins . $where_sql;
    $stmt_count = $conn->prepare($count_sql);
    if ($stmt_count) {
        $stmt_count->bind_param($types, ...$params);
        $stmt_count->execute();
        $total_orders = $stmt_count->get_result()->fetch_assoc()['total'];
        $stmt_count->close();
    }
    $total_pages = ceil($total_orders / $items_per_page);

    // 3. Execute Select Query with identical conditions + stable sorting
    $select_fields = "o.*, crt.dateCreated as orderDate, p.paymentStatus, p.paymentMethodID, 
                      (SELECT COUNT(*) FROM tbl_cartItem WHERE cartID = o.cartID AND customizeID IS NOT NULL AND customizeID <> 0) as customCount";
    
    // Add extra joins only for Select if needed (avoiding duplicates with DISTINCT if necessary)
    $select_sql = "SELECT " . $select_fields . $base_joins . $where_sql;
    $select_sql .= " ORDER BY crt.dateCreated DESC, o.orderID DESC LIMIT ? OFFSET ?";
    
    $stmt_select = $conn->prepare($select_sql);
    if ($stmt_select) {
        $select_params = array_merge($params, [$items_per_page, $offset]);
        $select_types = $types . "ii";
        $stmt_select->bind_param($select_types, ...$select_params);
        $stmt_select->execute();
        $orders = $stmt_select->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_select->close();
    }

} catch (mysqli_sql_exception $e) {
    error_log("Order fetch error: " . $e->getMessage());
}

function getStatusClass($status)
{
    switch (strtolower($status)) {
        case 'unpaid':
        case 'pending':
            return 'status-pending';
        case 'paid':
        case 'order paid':
            return 'status-paid';
        case 'processing':
            return 'status-processing';
        case 'awaiting balance':
            return 'status-pending';
        case 'shipped':
        case 'out for delivery':
        case 'ready for pick up':
            return 'status-shipped';
        case 'delivered':
        case 'completed':
            return 'status-delivered';
        case 'cancelled':
        case 'return items':
            return 'status-cancelled';
        default:
            return '';
    }
}

require '../html/orders.view.php';