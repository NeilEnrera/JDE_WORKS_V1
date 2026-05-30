<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();
// Only allow authenticated admin employees
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../JDE_USER/backend/db_connection.php';
require_once '../../JDE_USER/backend/services/ActivityService.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_dashboard_stats':
            getDashboardStats($conn);
            break;
        case 'get_appointments':
            getAppointments($conn);
            break;
        case 'get_all_orders':
            getAllOrders($conn);
            break;
        case 'get_unread_counts':
            getUnreadCounts($conn);
            break;
        case 'mark_order_seen':
            markOrderSeen($conn);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getUnreadCounts($conn)
{
    $lastOrder = (int) ($_GET['last_order'] ?? 0);
    $lastAppt = (int) ($_GET['last_appt'] ?? 0);

    // 1. New Pending Orders (since last seen)
    $resOrders = $conn->query("SELECT COUNT(*) as total FROM tbl_order WHERE orderID > $lastOrder AND orderStatus = 'Pending' AND isActive = 1");
    $newOrders = $resOrders->fetch_assoc()['total'] ?? 0;

    // 1b. Unread Cancellations (isAdminSeen = 0)
    $resCanc = $conn->query("SELECT COUNT(*) as total FROM tbl_order WHERE orderStatus = 'Cancelled' AND isAdminSeen = 0");
    $unreadCancellations = $resCanc->fetch_assoc()['total'] ?? 0;

    // 2. New Appointments (since last seen)
    $resAppts = $conn->query("SELECT COUNT(*) as total FROM tbl_appointment WHERE appointmentID > $lastAppt AND appointmentStatus NOT IN ('Completed', 'Cancelled')");
    $newAppts = $resAppts->fetch_assoc()['total'] ?? 0;

    // 3. Unread Messages (Inquiries + Chats)
    $totalInquiries = 0;
    $tableCheck = $conn->query("SHOW TABLES LIKE 'tbl_contactMessages'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $resIn = $conn->query("SELECT COUNT(*) as total FROM tbl_contactMessages WHERE isRead = 0");
        $totalInquiries = $resIn->fetch_assoc()['total'] ?? 0;
    }
    $resUnreadChats = $conn->query("SELECT COUNT(DISTINCT customerID) as total FROM tbl_chatLogs WHERE employeeID IS NULL AND isRead = 0");
    $unreadChatUsers = $resUnreadChats->fetch_assoc()['total'] ?? 0;

    // 4. Low Stock Products (Less than 5)
    $resLowStock = $conn->query("SELECT COUNT(*) as total FROM tbl_product WHERE stocks < 5 AND isActive = 1");
    $lowStockCount = $resLowStock->fetch_assoc()['total'] ?? 0;

    // 5. Current Max IDs (to help the frontend update its 'last seen' state)
    $resMaxO = $conn->query("SELECT MAX(orderID) as maxID FROM tbl_order");
    $maxOrderID = $resMaxO->fetch_assoc()['maxID'] ?? 0;

    $resMaxA = $conn->query("SELECT MAX(appointmentID) as maxID FROM tbl_appointment");
    $maxApptID = $resMaxA->fetch_assoc()['maxID'] ?? 0;

    // 6. Recent Cancelled Details (for sidebar toasts if polling)
    $cancelledDetails = [];
    if ($unreadCancellations > 0) {
        $resDet = $conn->query("SELECT o.orderID, CONCAT(c.firstName, ' ', c.lastName) as customerName FROM tbl_order o JOIN tbl_customer c ON o.customerID = c.customerID WHERE o.orderStatus = 'Cancelled' AND o.isAdminSeen = 0 LIMIT 5");
        while ($d = $resDet->fetch_assoc())
            $cancelledDetails[] = $d;
    }

    echo json_encode([
        'success' => true,
        'counts' => [
            'orders' => (int) ($newOrders + $unreadCancellations),
            'cancellations' => (int) $unreadCancellations,
            'appointments' => (int) $newAppts,
            'messages' => (int) ($totalInquiries + $unreadChatUsers),
            'lowStock' => (int) $lowStockCount,
            'maxOrderID' => (int) $maxOrderID,
            'maxApptID' => (int) $maxApptID,
            'cancelledDetails' => $cancelledDetails
        ]
    ]);
}

function markOrderSeen($conn)
{
    $orderID = (int) ($_POST['orderID'] ?? 0);
    if ($orderID > 0) {
        $conn->query("UPDATE tbl_order SET isAdminSeen = 1 WHERE orderID = $orderID");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    }
}

function getAllOrders($conn)
{
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    $sql = "
        SELECT o.orderID, o.totalPrice, o.discountAmount, o.subtotal, o.shippingFee, o.originalShippingFee, o.orderStatus, o.updatedBy, o.shippingAddress, o.proofOfDelivery, o.distributionMethod, o.isActive,
               o.returnReason, o.returnRemarks, o.cancelReason, o.paymentOption, o.isPreOrder,
               o.paidAt, o.processingAt, o.shippedAt, o.outForDeliveryAt, o.completedAt, crt.dateCreated as orderPlacedDate,
               CONCAT(c.firstName, ' ', c.lastName) as customerName,
               pay.dateCreated as orderDate, pay.proofOfPayment, pm.methodName, pay.paymentStatus, pay.downPaymentAmount, pay.paymentBalance,
               pay.balanceProofOfPayment, pay.balanceReferenceNumber, pay.balancePaymentStatus,
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
    ";
    $result = $conn->query($sql);
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    echo json_encode(['success' => true, 'orders' => $orders]);
}

function getAppointments($conn)
{
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    $res = $conn->query("SELECT * FROM tbl_appointment ORDER BY appointmentID DESC LIMIT $limit OFFSET $offset");
    $appts = [];
    while ($row = $res->fetch_assoc()) {
        $appts[] = $row;
    }
    echo json_encode(['success' => true, 'appointments' => $appts]);
}

function getDashboardStats($conn)
{
    $resProducts = $conn->query("SELECT COUNT(*) as total FROM tbl_product");
    $totalProducts = $resProducts->fetch_assoc()['total'];

    $resOrders = $conn->query("SELECT COUNT(*) as total FROM tbl_order WHERE orderStatus = 'Pending'");
    $pendingOrders = $resOrders->fetch_assoc()['total'];

    $resUsers = $conn->query("SELECT COUNT(*) as total FROM tbl_customer");
    $totalUsers = $resUsers->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT o.orderID, c.firstName, c.lastName, o.orderStatus, o.updatedBy,
               (SELECT dateCreated FROM tbl_payments WHERE orderID = o.orderID LIMIT 1) as orderDate
        FROM tbl_order o
        JOIN tbl_customer c ON o.customerID = c.customerID
        WHERE o.orderStatus = 'In Progress'
        ORDER BY o.orderID DESC
        LIMIT 5
    ");
    $stmt->execute();
    $resRecent = $stmt->get_result();
    $recentOrders = [];
    while ($row = $resRecent->fetch_assoc()) {
        $recentOrders[] = [
            'orderID' => $row['orderID'],
            'customerName' => $row['firstName'] . ' ' . $row['lastName'],
            'date' => $row['orderDate'] ?? 'N/A',
            'status' => $row['orderStatus']
        ];
    }

    $tableCheck = $conn->query("SHOW TABLES LIKE 'tbl_contactMessages'");
    $totalInquiries = 0;
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $resInquiries = $conn->query("SELECT COUNT(*) as total FROM tbl_contactMessages WHERE isRead = 0");
        $totalInquiries = $resInquiries->fetch_assoc()['total'] ?? 0;
    }

    $resUnreadChats = $conn->query("SELECT COUNT(DISTINCT customerID) as total FROM tbl_chatLogs WHERE employeeID IS NULL AND isRead = 0");
    $unreadChatUsers = $resUnreadChats->fetch_assoc()['total'] ?? 0;

    $resTodayOrders = $conn->query("SELECT COUNT(*) as total FROM tbl_payments WHERE DATE(dateCreated) = CURDATE()");
    $todayOrders = $resTodayOrders->fetch_assoc()['total'] ?? 0;

    $resUpcomingAppts = $conn->query("SELECT COUNT(*) as total FROM tbl_appointment WHERE appointmentDate >= CURDATE()");
    $upcomingAppts = $resUpcomingAppts->fetch_assoc()['total'] ?? 0;

    $resTotalOrders = $conn->query("SELECT COUNT(*) as total FROM tbl_order");
    $totalOrders = $resTotalOrders->fetch_assoc()['total'] ?? 0;

    // Revenue Metrics (Completed orders only)
    $dailyRevRes = $conn->query("SELECT SUM(p.fullPaymentAmount) as total FROM tbl_payments p JOIN tbl_order o ON p.orderID = o.orderID WHERE DATE(o.completedAt) = CURDATE() AND o.orderStatus = 'Completed' AND p.paymentStatus = 'Approved'");
    $dailyRevenue = ($dailyRevRes && $row = $dailyRevRes->fetch_assoc()) ? (float) $row['total'] : 0.0;

    $monthlyRevRes = $conn->query("SELECT SUM(p.fullPaymentAmount) as total FROM tbl_payments p JOIN tbl_order o ON p.orderID = o.orderID WHERE MONTH(o.completedAt) = MONTH(CURDATE()) AND YEAR(o.completedAt) = YEAR(CURDATE()) AND o.orderStatus = 'Completed' AND p.paymentStatus = 'Approved'");
    $monthlyRevenue = ($monthlyRevRes && $row = $monthlyRevRes->fetch_assoc()) ? (float) $row['total'] : 0.0;

    $yearlyRevRes = $conn->query("SELECT SUM(p.fullPaymentAmount) as total FROM tbl_payments p JOIN tbl_order o ON p.orderID = o.orderID WHERE YEAR(o.completedAt) = YEAR(CURDATE()) AND o.orderStatus = 'Completed' AND p.paymentStatus = 'Approved'");
    $yearlyRevenue = ($yearlyRevRes && $row = $yearlyRevRes->fetch_assoc()) ? (float) $row['total'] : 0.0;

    echo json_encode([
        'success' => true,
        'stats' => [
            'totalProducts' => (int) $totalProducts,
            'totalOrders' => (int) $totalOrders,
            'pendingOrders' => (int) $pendingOrders,
            'totalUsers' => (int) $totalUsers,
            'totalInquiries' => (int) $totalInquiries,
            'unreadChatUsers' => (int) $unreadChatUsers,
            'todayOrders' => (int) $todayOrders,
            'upcomingAppointments' => (int) $upcomingAppts,
            'dailyRevenue' => $dailyRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'yearlyRevenue' => $yearlyRevenue,
            'recentOrders' => $recentOrders,
            'recentActivity' => ActivityService::getRecentActivity($conn)
        ]
    ]);
}
?>