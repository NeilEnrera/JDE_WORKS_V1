<?php
/**
 * ActivityService.php
 * Centralized service for fetching the Recent Activity feed.
 * Ensures that the admin_dashboard.php, admin_api.php, and chat_stream.php
 * all share the exact same logic for what constitutes "Recent Activity".
 */
class ActivityService
{

    /**
     * Gets the top recent activities across orders, appointments, inquiries, and chats.
     * 
     * @param mysqli $conn The database connection
     * @param int $limit The maximum number of total items to return
     * @return array The sorted array of recent activities
     */
    public static function getRecentActivity($conn, $limit = 15)
    {
        $recentAct = [];

        // 1. Orders (Recent)
        $resO = $conn->query("
            SELECT o.orderID as id, 
                   CONCAT(c.firstName, ' ', c.lastName) as customer, 
                   CONCAT('Order #', o.orderID, ' - ', o.orderStatus) as status, 
                   p.dateCreated as timestamp, 
                   'order' as type 
            FROM tbl_order o 
            JOIN tbl_customer c ON o.customerID = c.customerID 
            JOIN tbl_payments p ON o.orderID = p.orderID 
            ORDER BY p.dateCreated DESC 
            LIMIT 5
        ");
        if ($resO) {
            while ($row = $resO->fetch_assoc()) {
                $recentAct[] = $row;
            }
        }

        // 2. Appointments (Upcoming within 1 hour)
        $resA = $conn->query("
            SELECT appointmentID as id, 
                   name as customer, 
                   CONCAT('Upcoming: ', serviceType, ' at ', TIME_FORMAT(appointmentTime, '%h:%i %p')) as status, 
                   CONCAT(appointmentDate, ' ', appointmentTime) as timestamp, 
                   'upcoming' as type 
            FROM tbl_appointment 
            WHERE CONCAT(appointmentDate, ' ', appointmentTime) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR) 
              AND appointmentStatus NOT IN ('Cancelled', 'Completed') 
            ORDER BY appointmentDate ASC, appointmentTime ASC 
            LIMIT 5
        ");
        if ($resA) {
            while ($row = $resA->fetch_assoc()) {
                $recentAct[] = $row;
            }
        }

        // 2b. Appointments (Newly Booked)
        $resANew = $conn->query("
            SELECT appointmentID as id, 
                   name as customer, 
                   CONCAT('New Appointment: ', serviceType) as status, 
                   dateCreated as timestamp, 
                   'appointment' as type 
            FROM tbl_appointment 
            ORDER BY dateCreated DESC 
            LIMIT 5
        ");
        if ($resANew) {
            while ($row = $resANew->fetch_assoc()) {
                $recentAct[] = $row;
            }
        }

        // 3. Inquiries (Messages)
        if ($conn->query("SHOW TABLES LIKE 'tbl_contactMessages'")->num_rows > 0) {
            $resM = $conn->query("
                SELECT id as id, 
                       name as customer, 
                       subject as status, 
                       dateCreated as timestamp, 
                       'message' as type 
                FROM tbl_contactMessages 
                ORDER BY dateCreated DESC 
                LIMIT 5
            ");
            if ($resM) {
                while ($row = $resM->fetch_assoc()) {
                    $recentAct[] = $row;
                }
            }
        }

        // 4. Chats (New Messages from Customers)
        if ($conn->query("SHOW TABLES LIKE 'tbl_chatLogs'")->num_rows > 0) {
            $resC = $conn->query("
                SELECT cl.chatID as id, 
                       cl.customerID, 
                       CONCAT(c.firstName, ' ', c.lastName) as customer, 
                       LEFT(cl.message, 30) as status, 
                       cl.timeSent as timestamp, 
                       'chat' as type 
                FROM tbl_chatLogs cl 
                JOIN tbl_customer c ON cl.customerID = c.customerID 
                WHERE cl.employeeID IS NULL OR cl.employeeID = 0 
                ORDER BY cl.timeSent DESC 
                LIMIT 5
            ");
            if ($resC) {
                while ($row = $resC->fetch_assoc()) {
                    $recentAct[] = $row;
                }
            }
        }

        // Sort & Limit
        usort($recentAct, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return array_slice($recentAct, 0, $limit);
    }

    /**
     * Checks for appointments that are within 1 hour of their scheduled time
     * to trigger real-time notifications, preventing duplicate alerts.
     * 
     * @param mysqli $conn The database connection
     * @param int $iterationCount The current tick of the SSE loop
     * @param array $notifiedAppts Array passed by reference to track notified IDs
     * @return array List of new almost-due events to send to the client
     */
    public static function checkAlmostDueAppointments($conn, $iterationCount, &$notifiedAppts)
    {
        $eventsToPush = [];

        if ($iterationCount === 0) {
            // Initial load - don't notify, just track existing ones
            $resInit = $conn->query("
                SELECT appointmentID 
                FROM tbl_appointment 
                WHERE CONCAT(appointmentDate, ' ', appointmentTime) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                  AND appointmentStatus NOT IN ('Cancelled', 'Completed')
            ");
            if ($resInit) {
                while ($row = $resInit->fetch_assoc()) {
                    $notifiedAppts[] = $row['appointmentID'];
                }
            }
        } else {
            // Subsequent loads - look for newly matured ones
            $resDue = $conn->query("
                SELECT * 
                FROM tbl_appointment 
                WHERE CONCAT(appointmentDate, ' ', appointmentTime) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                  AND appointmentStatus NOT IN ('Cancelled', 'Completed')
            ");
            if ($resDue) {
                while ($row = $resDue->fetch_assoc()) {
                    if (!in_array($row['appointmentID'], $notifiedAppts)) {
                        $notifiedAppts[] = $row['appointmentID'];
                        $eventsToPush[] = [
                            'appointmentID' => $row['appointmentID'],
                            'name' => 'Reminder: ' . $row['name'],
                            'date' => $row['appointmentDate'],
                            'time' => $row['appointmentTime']
                        ];
                    }
                }
            }
        }

        return $eventsToPush;
    }

    /**
     * Fetches comprehensive dashboard statistics for the admin.
     */
    public static function getDashboardStats($conn)
    {
        $totalP = $conn->query("SELECT COUNT(*) as total FROM tbl_product")->fetch_assoc()['total'];
        $totalO = $conn->query("SELECT COUNT(*) as total FROM tbl_order")->fetch_assoc()['total'];
        $pendingO = $conn->query("SELECT COUNT(*) as total FROM tbl_order WHERE orderStatus = 'Pending'")->fetch_assoc()['total'];
        $totalU = $conn->query("SELECT COUNT(*) as total FROM tbl_customer")->fetch_assoc()['total'];
        $unreadI = $conn->query("SELECT COUNT(*) as total FROM tbl_contactMessages WHERE isRead = 0")->fetch_assoc()['total'];
        $unreadC = $conn->query("SELECT COUNT(DISTINCT customerID) as total FROM tbl_chatLogs WHERE employeeID IS NULL AND isRead = 0")->fetch_assoc()['total'];
        $todayAppts = $conn->query("SELECT COUNT(*) as total FROM tbl_appointment WHERE appointmentDate = CURDATE()")->fetch_assoc()['total'];

        $dailyRev = $conn->query("SELECT SUM(fullPaymentAmount) as total FROM tbl_payments WHERE DATE(dateCreated) = CURDATE()")->fetch_assoc()['total'] ?? 0;
        $monthlyRev = $conn->query("SELECT SUM(fullPaymentAmount) as total FROM tbl_payments WHERE MONTH(dateCreated) = MONTH(CURDATE()) AND YEAR(dateCreated) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;

        $statusCounts = $conn->query("SELECT orderStatus, COUNT(*) as count FROM tbl_order GROUP BY orderStatus");
        $breakdown = [];
        while ($row = $statusCounts->fetch_assoc()) {
            $breakdown[$row['orderStatus']] = (int) $row['count'];
        }

        return [
            'totalProducts' => (int) $totalP,
            'totalOrders' => (int) $totalO,
            'pendingOrders' => (int) $pendingO,
            'totalUsers' => (int) $totalU,
            'totalInquiries' => (int) $unreadI,
            'unreadChatUsers' => (int) $unreadC,
            'upcomingAppointments' => (int) $todayAppts,
            'dailyRevenue' => (float) $dailyRev,
            'monthlyRevenue' => (float) $monthlyRev,
            'statusBreakdown' => $breakdown,
            'recentActivity' => self::getRecentActivity($conn)
        ];
    }

    /**
     * Checks for new orders, appointments, and inquiries for real-time notifications.
     */
    public static function checkGlobalActivities($conn, &$lastIds)
    {
        $activities = [];

        // Check for New Orders
        $resNewO = $conn->query("SELECT o.*, c.firstName, c.lastName FROM tbl_order o JOIN tbl_customer c ON o.customerID = c.customerID WHERE o.orderID > {$lastIds['order']} ORDER BY o.orderID ASC");
        while ($row = $resNewO->fetch_assoc()) {
            $lastIds['order'] = $row['orderID'];
            $activities[] = [
                'event' => 'new_order',
                'data' => [
                    'orderID' => $row['orderID'],
                    'customerName' => $row['firstName'] . ' ' . $row['lastName'],
                    'totalPrice' => $row['totalPrice'],
                    'status' => $row['orderStatus']
                ]
            ];
        }

        // Check for Payment Verifications (NEW)
        // We use a separate key in lastIds to track this
        $lastPaymentVerif = $lastIds['payment_verified'] ?? '1970-01-01 00:00:00';
        $resNewPay = $conn->query("
            SELECT p.*, c.firstName, c.lastName, o.orderStatus
            FROM tbl_payments p
            JOIN tbl_order o ON p.orderID = o.orderID
            JOIN tbl_customer c ON o.customerID = c.customerID
            WHERE p.verifiedAt > '{$lastPaymentVerif}'
            ORDER BY p.verifiedAt ASC
        ");
        if ($resNewPay) {
            while ($row = $resNewPay->fetch_assoc()) {
                $lastIds['payment_verified'] = $row['verifiedAt'];
                $activities[] = [
                    'event' => 'payment_verified',
                    'data' => [
                        'orderID' => $row['orderID'],
                        'customerName' => $row['firstName'] . ' ' . $row['lastName'],
                        'paymentStatus' => $row['paymentStatus'],
                        'orderStatus' => $row['orderStatus'],
                        'verifiedAt' => $row['verifiedAt']
                    ]
                ];
            }
        }

        // Check for Order Cancellations (isAdminSeen = 0 AND orderStatus = 'Cancelled')
        $resCanc = $conn->query("SELECT o.*, c.firstName, c.lastName FROM tbl_order o JOIN tbl_customer c ON o.customerID = c.customerID WHERE o.orderStatus = 'Cancelled' AND o.isAdminSeen = 0 ORDER BY o.updatedAt DESC");
        while ($row = $resCanc->fetch_assoc()) {
            $activities[] = [
                'event' => 'order_cancelled',
                'data' => [
                    'orderID' => $row['orderID'],
                    'customerName' => $row['firstName'] . ' ' . $row['lastName'],
                    'status' => $row['orderStatus'],
                    'reason' => $row['cancelReason']
                ]
            ];
            // We don't mark as seen here, the frontend call to mark as seen will do that.
        }

        // Check for New Appointments
        $resNewA = $conn->query("SELECT * FROM tbl_appointment WHERE appointmentID > {$lastIds['appointment']} ORDER BY appointmentID ASC");
        while ($row = $resNewA->fetch_assoc()) {
            $lastIds['appointment'] = $row['appointmentID'];
            $activities[] = [
                'event' => 'new_appointment_booked',
                'data' => [
                    'appointmentID' => $row['appointmentID'],
                    'name' => $row['name'],
                    'serviceType' => $row['serviceType']
                ]
            ];
        }

        // Check for New Inquiries
        $resNewI = $conn->query("SELECT * FROM tbl_contactMessages WHERE id > {$lastIds['inquiry']} ORDER BY id ASC");
        while ($row = $resNewI->fetch_assoc()) {
            $lastIds['inquiry'] = $row['id'];
            $activities[] = [
                'event' => 'new_inquiry',
                'data' => [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'subject' => $row['subject']
                ]
            ];
        }

        // Check for New Chat Messages (from customers)
        if (isset($lastIds['chat'])) {
            $resNewC = $conn->query("SELECT cl.*, c.firstName, c.lastName FROM tbl_chatLogs cl JOIN tbl_customer c ON cl.customerID = c.customerID WHERE (cl.employeeID IS NULL OR cl.employeeID = 0) AND cl.chatID > {$lastIds['chat']} ORDER BY cl.chatID ASC");
            while ($row = $resNewC->fetch_assoc()) {
                $lastIds['chat'] = $row['chatID'];
                $activities[] = [
                    'event' => 'new_message',
                    'data' => [
                        'id' => $row['chatID'],
                        'customerID' => $row['customerID'],
                        'sender' => $row['firstName'] . ' ' . $row['lastName'],
                        'message' => $row['message']
                    ]
                ];
            }
        }

        return $activities;
    }

    /**
     * Fetches the current online customers from the presence file.
     */
    public static function getOnlineCustomers($presenceFile)
    {
        $rawPresence = @file_get_contents($presenceFile);
        $presenceState = $rawPresence ? (json_decode($rawPresence, true) ?: []) : [];
        $onlineUsers = [];
        $now = time();
        foreach ($presenceState as $key => $ts) {
            if ($now - $ts <= 60 && strpos($key, 'customer_') === 0) {
                $onlineUsers[] = ['userID' => (int) substr($key, 9)];
            }
        }
        return $onlineUsers;
    }
}
?>