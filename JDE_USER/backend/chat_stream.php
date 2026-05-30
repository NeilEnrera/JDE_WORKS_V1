<?php
error_log("chat_stream.php: Entry point reached.");
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no');

include('db_connection.php');

@set_time_limit(0);
error_log("chat_stream.php: Attempting session_start");
session_start();
$userType = $_SESSION['user_type'] ?? 'Guest';
$myID = $_SESSION['user_id'] ?? 0;
session_write_close();
error_log("chat_stream.php: session_start successful. user=$userType ($myID), session_id=" . session_id());

@set_time_limit(0);
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');
@ini_set('implicit_flush', 'on');
while (ob_get_level() > 0)
    ob_end_flush();
ob_implicit_flush(1);
ignore_user_abort(false);

$lastMessageId = isset($_GET['lastMessageId']) ? (int) $_GET['lastMessageId'] : 0;
$iterationCount = 0;

define('PRESENCE_FILE', __DIR__ . '/chat_presence.json');

function trackPresence($userID, $userType)
{
    $now = time();
    $raw = @file_get_contents(PRESENCE_FILE);
    $state = $raw ? (json_decode($raw, true) ?: []) : [];

    $key = ($userType === 'Employee') ? "admin_{$userID}" : "customer_{$userID}";
    $state[$key] = $now;

    // Cleanup old entries (older than 5 mins)
    foreach ($state as $k => $ts) {
        if ($now - $ts > 300)
            unset($state[$k]);
    }

    file_put_contents(PRESENCE_FILE, json_encode($state), LOCK_EX);
}

function sendEvent($event, $data)
{
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

sendEvent('connected', ['message' => 'Connected (New DB)']);

// Helper to fetch messages
function fetchNewMessages($conn, $lastId, $userType, $myID)
{
    // If Customer, fetch my messages
    // If Admin, fetch ALL messages (or filter)? 
    // Admin usually needs to see all incoming messages to update the list, 
    // AND messages for the currently selected chat.
    // Simplifying: Admin receives ALL new messages, frontend filters by selected customer.

    $sql = "SELECT c.*, 
                   cust.firstName as custFN, cust.lastName as custLN,
                   emp.firstName as empFN, emp.lastName as empLN
            FROM tbl_chatLogs c
            LEFT JOIN tbl_customer cust ON c.customerID = cust.customerID
            LEFT JOIN tbl_employee emp ON c.employeeID = emp.employeeID
            WHERE c.chatID > ? ";

    if ($userType === 'Customer') {
        // Only my messages
        $sql .= " AND c.customerID = $myID ";
    } else if ($userType === 'Guest') {
        // Guests receive NO messages
        $sql .= " AND 1=0 ";
    }

    $sql .= " ORDER BY c.chatID ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("chat_stream.php: Prepare failed: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $lastId);
    $stmt->execute();
    return $stmt->get_result();
}

while (true) {
    // 1. Send typing status IMMEDIATELY to minimize latency
    $typingState = [];
    if (file_exists(__DIR__ . '/chat_typing.json')) {
        $raw = @file_get_contents(__DIR__ . '/chat_typing.json');
        $typingState = $raw ? (json_decode($raw, true) ?: []) : [];
    }

    $now = time();
    $activeTyping = [];

    // Admin needs to know which customers are typing
    if ($userType === 'Employee') {
        foreach ($typingState as $key => $entry) {
            if (strpos($key, 'customer_') === 0 && ($now - ($entry['timestamp'] ?? 0) <= 4)) {
                $activeTyping[] = [
                    'customerID' => $entry['customerID'],
                    'name' => $entry['name'],
                    'type' => 'customer'
                ];
            }
        }
    }
    // Customer needs to know if admin is typing FOR THEM
    else if ($userType === 'Customer') {
        $key = "admin_{$myID}";
        if (isset($typingState[$key]) && ($now - ($typingState[$key]['timestamp'] ?? 0) <= 4)) {
            $activeTyping[] = [
                'name' => $typingState[$key]['name'],
                'type' => 'admin'
            ];
        }
    }

    // Track last sent typing state to avoid spamming the same data
    static $lastActiveTyping = null;

    // Sort to ensure stable json_encode comparison
    usort($activeTyping, function ($a, $b) {
        return strcmp(($a['name'] ?? $a['customerID'] ?? ''), ($b['name'] ?? $b['customerID'] ?? ''));
    });

    $currentEncoded = json_encode($activeTyping);
    if ($currentEncoded !== $lastActiveTyping) {
        sendEvent('typing_status', ['typing' => $activeTyping]);
        $lastActiveTyping = $currentEncoded;
    }

    // 2. Ping every 10 seconds to keep connection alive
    if ($iterationCount % 10 === 0) {
        sendEvent('ping', ['timestamp' => time(), 'status' => 'healthy']);
    }

    // 3. Fetch and send new messages
    $needsStatsUpdate = false;
    $result = fetchNewMessages($conn, $lastMessageId, $userType, $myID);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lastMessageId = $row['chatID'];

            // Format message payload
            // Use is_null or check for > 0 to be more robust than !empty
            $isFromEmployee = isset($row['employeeID']) && is_numeric($row['employeeID']) && (int)$row['employeeID'] > 0;

            if ($isFromEmployee) {
                $senderName = ($row['empFN'] || $row['empLN']) ? ($row['empFN'] . ' ' . $row['empLN']) : 'Admin';
                $role = 'Admin';
                $isAdmin = true;
                $userID = $row['employeeID'];
            } else {
                $senderName = $row['custFN'] . ' ' . $row['custLN'];
                $role = 'Customer';
                $isAdmin = false;
                $userID = $row['customerID'];
            }

            $msg = [
                'id' => $row['chatID'],
                'customerID' => $row['customerID'],
                'userID' => $userID,
                'message' => $row['message'],
                'timeSent' => $row['timeSent'],
                'sender' => $senderName,
                'role' => $role,
                'isAdmin' => $isAdmin,
                'isFromEmployee' => $isFromEmployee,
                'employeeID' => $row['employeeID'],
                'isRead' => (int)$row['isRead']
            ];

            sendEvent('new_message', $msg);
            $needsStatsUpdate = true;
        }
    }

    // Record presence every 5 seconds (roughly every 5 iterations since sleep(1))
    if ($iterationCount % 5 === 0) {
        if ($myID > 0) {
            trackPresence($myID, $userType);

            // Periodically check if the user has been blocked
            if ($userType === 'Customer') {
                $checkBlocked = $conn->prepare("SELECT isBlocked FROM tbl_customer WHERE customerID = ?");
                $checkBlocked->bind_param("i", $myID);
                $checkBlocked->execute();
                $blockRes = $checkBlocked->get_result();
                if ($blockRow = $blockRes->fetch_assoc()) {
                    if ($blockRow['isBlocked'] == 1) {
                        sendEvent('user_status', [
                            'status' => 'suspended', 
                            'message' => 'Your account has been suspended or blocked by an administrator.'
                        ]);
                        $checkBlocked->close();
                        break; // Exit the stream
                    }
                }
                $checkBlocked->close();
            }
        }
    }

    $iterationCount++;

    // Keep alive to prevent proxies/browsers from closing the connection
    echo ": keep-alive\n\n";
    if (ob_get_level() > 0)
        ob_flush();
    flush();

    if (connection_aborted())
        break;
    sleep(1);
}
?>