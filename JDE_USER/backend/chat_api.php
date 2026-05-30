<?php
// Suppress all errors to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

@include('db_connection.php');
session_start();

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

define('PRESENCE_FILE', __DIR__ . '/chat_presence.json');
define('TYPING_FILE', __DIR__ . '/chat_typing.json');

function loadTypingState()
{
    if (!file_exists(TYPING_FILE))
        return [];
    $raw = @file_get_contents(TYPING_FILE);
    return $raw ? (json_decode($raw, true) ?: []) : [];
}

function saveTypingState($state)
{
    // Expire stale entries (older than 4 seconds)
    $now = time();
    $cleaned = [];
    foreach ($state as $key => $entry) {
        $entryTime = $entry['timestamp'] ?? 0;
        if ($now - $entryTime <= 4) {
            $cleaned[$key] = $entry;
        }
    }
    file_put_contents(TYPING_FILE, json_encode($cleaned), LOCK_EX);
}

function loadPresenceState()
{
    if (!file_exists(PRESENCE_FILE))
        return [];
    $raw = @file_get_contents(PRESENCE_FILE);
    return $raw ? (json_decode($raw, true) ?: []) : [];
}

function trackPresence($userID, $userType)
{
    if (!$userID)
        return;
    $now = time();
    $state = loadPresenceState();
    $key = ($userType === 'Employee') ? "admin_{$userID}" : "customer_{$userID}";
    $state[$key] = $now;

    // Cleanup old entries while we're at it
    foreach ($state as $k => $ts) {
        if ($now - $ts > 300)
            unset($state[$k]); // Keep for 5 mins
    }

    @file_put_contents(PRESENCE_FILE, json_encode($state), LOCK_EX);
}


$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    error_log("chat_api: raw_input=" . $rawInput);
    $input = json_decode($rawInput, true) ?? [];
}

$action = $_GET['action'] ?? $input['action'] ?? '';

// Track presence for logged in users
if (isset($_SESSION['user_id'])) {
    trackPresence($_SESSION['user_id'], $_SESSION['user_type'] ?? 'Customer');
}

try {
    switch ($action) {
        case 'send_message':
            sendMessage($conn, $input);
            break;
        case 'get_messages':
            getMessages($conn);
            break;
        case 'get_online_users': // For Admin
            getOnlineCustomers($conn);
            break;
        case 'get_user_info':
            getUserInfo($conn);
            break;
        case 'mark_as_read':
            markAsRead($conn, $input);
            break;
        case 'get_unread_count':
            getUnreadCount($conn);
            break;
        case 'set_typing':
            setTyping($input);
            break;
        case 'clear_all_messages':
            if (($_SESSION['access_level'] ?? 0) != 1) throw new Exception('Only Level 1 Admins can clear chat history');
            clearAllMessages($conn);
            break;
        case 'get_max_chat_id':
            getMaxChatID($conn);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function sendMessage($conn, $input)
{
    $message = trim($input['message'] ?? '');

    if (empty($message))
        throw new Exception("Message empty");

    $userType = $_SESSION['user_type'] ?? '';
    $userRole = $_SESSION['user_role'] ?? '';
    $senderID = $_SESSION['user_id'] ?? 0;

    if ($userType === 'Employee') {
        // Sender is an Admin/Employee
        $employeeID = $senderID;
        $customerID = $input['customerID'] ?? $input['userID'] ?? $input['targetID'] ?? null;

        if (!$customerID) {
            throw new Exception("Target customer required for admin messages");
        }
    } else if ($userType === 'Customer') {
        // Sender is a Customer
        $customerID = $senderID;
        if (!$customerID) {
            // Last-ditch effort to recover ID from session username if it exists 
            // but this is unlikely to be needed if session_start worked
            throw new Exception("Session identity lost. Please re-login.");
        }
        $employeeID = null;
    } else {
        throw new Exception("Login required to send messages");
    }

    $stmt = $conn->prepare("INSERT INTO tbl_chatLogs (customerID, employeeID, message, timeSent, isRead) VALUES (?, ?, ?, NOW(), 0)");
    $stmt->bind_param("iis", $customerID, $employeeID, $message);

    if ($stmt->execute()) {
        $msgID = $conn->insert_id;

        // Fetch back
        $row = fetchMessage($conn, $msgID);
        echo json_encode(['success' => true, 'message' => formatMessageRow($row)]);
    } else {
        throw new Exception("Send failed: " . $stmt->error);
    }
}

function getMessages($conn)
{
    $userType = $_SESSION['user_type'] ?? 'Guest';
    $myID = $_SESSION['user_id'] ?? 0;

    // Determine which conversation to fetch
    $targetCustomerID = null;

    if ($userType === 'Customer') {
        $targetCustomerID = $myID;
    } else if ($userType === 'Employee') {
        // Employee views a specific customer's chat
        $targetCustomerID = $_GET['customerID'] ?? $_GET['userID'] ?? null;
        if (!$targetCustomerID) {
            // Return empty for now
            echo json_encode(['success' => true, 'messages' => []]);
            return;
        }
    } else {
        // Guests cannot see messages
        echo json_encode(['success' => true, 'messages' => []]);
        return;
    }

    $limit = 50;
    $stmt = $conn->prepare("
        SELECT c.*, 
               cust.firstName as custFN, cust.lastName as custLN,
               emp.firstName as empFN, emp.lastName as empLN,
               eal.accessLevelName as empRole
        FROM tbl_chatLogs c
        LEFT JOIN tbl_customer cust ON c.customerID = cust.customerID
        LEFT JOIN tbl_employee emp ON c.employeeID = emp.employeeID
        LEFT JOIN tbl_employeeAccessLevel eal ON emp.accessLevelID = eal.accessLevelID
        WHERE c.customerID = ?
        ORDER BY c.timeSent DESC LIMIT ?
    ");
    $stmt->bind_param("ii", $targetCustomerID, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = formatMessageRow($row);
    }

    echo json_encode(['success' => true, 'messages' => array_reverse($messages)]);

    // After fetching, mark relevant messages as read
    if ($userType === 'Customer') {
        // Customer fetched, so mark messages from Admin as read
        $upd = $conn->prepare("UPDATE tbl_chatLogs SET isRead = 1 WHERE customerID = ? AND employeeID IS NOT NULL AND isRead = 0");
        $upd->bind_param("i", $targetCustomerID);
        $upd->execute();
    } else if ($userType === 'Employee') {
        // Admin fetched, so mark messages from this specific customer as read
        $upd = $conn->prepare("UPDATE tbl_chatLogs SET isRead = 1 WHERE customerID = ? AND employeeID IS NULL AND isRead = 0");
        $upd->bind_param("i", $targetCustomerID);
        $upd->execute();
    }
}

function fetchMessage($conn, $id)
{
    $stmt = $conn->prepare("
        SELECT c.*, 
               cust.firstName as custFN, cust.lastName as custLN,
               emp.firstName as empFN, emp.lastName as empLN,
               eal.accessLevelName as empRole
        FROM tbl_chatLogs c
        LEFT JOIN tbl_customer cust ON c.customerID = cust.customerID
        LEFT JOIN tbl_employee emp ON c.employeeID = emp.employeeID
        LEFT JOIN tbl_employeeAccessLevel eal ON emp.accessLevelID = eal.accessLevelID
        WHERE c.chatID = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function formatMessageRow($row)
{
    // Determine sender
    $isFromEmployee = isset($row['employeeID']) && $row['employeeID'] > 0;

    if ($isFromEmployee) {
        $senderName = ($row['empFN'] || $row['empLN']) ? ($row['empFN'] . ' ' . $row['empLN']) : ($row['empRole'] ?? 'Admin');
        $role = $row['empRole'] ?? 'Admin';
        $isAdmin = true;
        // Admin ID to differentiate
        $userID = $row['employeeID'];
    } else {
        $senderName = ($row['custFN'] || $row['custLN']) ? ($row['custFN'] . ' ' . $row['custLN']) : 'Customer';
        $role = 'Customer';
        $isAdmin = false;
        $userID = $row['customerID'];
    }

    return [
        'id' => $row['chatID'],
        'userID' => $userID,
        'message' => $row['message'],
        'timeSent' => $row['timeSent'],
        'sender' => $senderName,
        'role' => $role,
        'isAdmin' => $isAdmin,
        'customerID' => $row['customerID'], // Ensure this is always present
        'isRead' => (int) $row['isRead']
    ];
}

function getOnlineCustomers($conn)
{
    $userType = $_SESSION['user_type'] ?? 'Guest';
    if ($userType !== 'Employee') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        return;
    }

    // List all customers with their unread counts and last messages
    $stmt = $conn->query("
        SELECT c.customerID, c.firstName, c.lastName, c.lastLogin,
               (SELECT COUNT(*) FROM tbl_chatLogs WHERE customerID = c.customerID AND employeeID IS NULL AND isRead = 0) as unreadCount,
               (SELECT message FROM tbl_chatLogs WHERE customerID = c.customerID ORDER BY timeSent DESC LIMIT 1) as lastMessage
        FROM tbl_customer c 
        ORDER BY lastLogin DESC LIMIT 50
    ");
    $users = [];
    $presence = loadPresenceState();
    $now = time();

    while ($row = $stmt->fetch_assoc()) {
        $lastActivity = $presence["customer_{$row['customerID']}"] ?? 0;
        $isOnline = ($now - $lastActivity <= 60);

        $users[] = [
            'userID' => $row['customerID'],
            'name' => $row['firstName'] . ' ' . $row['lastName'],
            'role' => 'Customer',
            'status' => $isOnline ? 'online' : 'offline',
            'unreadCount' => (int) $row['unreadCount'],
            'lastMessage' => $row['lastMessage'] ?? ''
        ];
    }
    // Also get the current max chat ID to help frontend initialize
    $maxRes = $conn->query("SELECT MAX(chatID) as maxID FROM tbl_chatLogs");
    $maxChatID = (int) ($maxRes->fetch_assoc()['maxID'] ?? 0);

    echo json_encode(['success' => true, 'users' => $users, 'maxChatID' => $maxChatID]);
}

function getMaxChatID($conn)
{
    $res = $conn->query("SELECT MAX(chatID) as maxID FROM tbl_chatLogs");
    $maxID = (int) ($res->fetch_assoc()['maxID'] ?? 0);
    echo json_encode(['success' => true, 'maxChatID' => $maxID]);
}

function getUserInfo($conn)
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false]);
        return;
    }
    echo json_encode([
        'success' => true,
        'user' => [
            'userID' => $_SESSION['user_id'],
            'name' => $_SESSION['name'] ?? $_SESSION['username'] ?? 'User',
            'role' => $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? 'Guest',
            'isEmployee' => ($_SESSION['user_type'] === 'Employee')
        ]
    ]);
}

function getUnreadCount($conn)
{
    $userType = $_SESSION['user_type'] ?? 'Guest';
    $myID = $_SESSION['user_id'] ?? 0;

    if ($userType === 'Customer' && $myID > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_chatLogs WHERE customerID = ? AND employeeID IS NOT NULL AND isRead = 0");
        $stmt->bind_param("i", $myID);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        // Also get the latest message content for the popup
        $msgStmt = $conn->prepare("
            SELECT message, timeSent FROM tbl_chatLogs 
            WHERE customerID = ? AND employeeID IS NOT NULL AND isRead = 0 
            ORDER BY timeSent DESC LIMIT 1
        ");
        $msgStmt->bind_param("i", $myID);
        $msgStmt->execute();
        $lastMsg = $msgStmt->get_result()->fetch_assoc();

        echo json_encode([
            'success' => true,
            'unreadCount' => (int) $res['count'],
            'lastMessage' => $lastMsg ? $lastMsg['message'] : null,
            'timeSent' => $lastMsg ? $lastMsg['timeSent'] : null
        ]);
    } else if ($userType === 'Employee') {
        // For admin dashboard unread counts (all customers)
        $stmt = $conn->query("
            SELECT customerID, COUNT(*) as count 
            FROM tbl_chatLogs 
            WHERE employeeID IS NULL AND isRead = 0 
            GROUP BY customerID
        ");
        $unreadMap = [];
        while ($row = $stmt->fetch_assoc()) {
            $unreadMap[$row['customerID']] = (int) $row['count'];
        }
        echo json_encode(['success' => true, 'unreadMap' => $unreadMap]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    }
}

function markAsRead($conn, $input)
{
    $userType = $_SESSION['user_type'] ?? 'Guest';
    $myID = $_SESSION['user_id'] ?? 0;

    if ($userType === 'Customer' && $myID > 0) {
        $stmt = $conn->prepare("UPDATE tbl_chatLogs SET isRead = 1 WHERE customerID = ? AND employeeID IS NOT NULL");
        $stmt->bind_param("i", $myID);
        $stmt->execute();
    } else if ($userType === 'Employee') {
        $customerID = $input['customerID'] ?? 0;
        if ($customerID > 0) {
            $stmt = $conn->prepare("UPDATE tbl_chatLogs SET isRead = 1 WHERE customerID = ? AND employeeID IS NULL");
            $stmt->bind_param("i", $customerID);
            $stmt->execute();
        }
    }
    echo json_encode(['success' => true]);
}

// ── Typing indicator helpers moved to top ───────────────────────────────────


function setTyping($input)
{
    $isTyping = !empty($input['isTyping']);
    $userType = $_SESSION['user_type'] ?? 'Guest';
    $userID = $_SESSION['user_id'] ?? 0;

    // Ensure we have a valid name
    $senderName = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Someone';

    // For admin: input['customerID'] is required. For customer: userID is the key.
    $customerID = ($userType === 'Customer') ? $userID : ($input['customerID'] ?? 0);

    if (!$customerID) {
        echo json_encode(['success' => false, 'error' => 'Valid target ID required']);
        return;
    }

    $state = loadTypingState();
    $key = ($userType === 'Employee') ? "admin_{$customerID}" : "customer_{$customerID}";

    if ($isTyping) {
        $state[$key] = [
            'name' => htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8'),
            'timestamp' => time(),
            'customerID' => (int) $customerID
        ];
    } else {
        unset($state[$key]);
    }

    saveTypingState($state);
    echo json_encode(['success' => true]);
}

function clearAllMessages($conn)
{
    $userType = $_SESSION['user_type'] ?? 'Guest';
    if ($userType !== 'Employee') {
        throw new Exception('Unauthorized');
    }

    if ($conn->query("TRUNCATE TABLE tbl_chatLogs")) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Clear failed: " . $conn->error);
    }
}

function broadcastMessage($conn, $input)
{
    $userType = $_SESSION['user_type'] ?? 'Guest';
    if ($userType !== 'Employee') {
        throw new Exception('Unauthorized');
    }

    $message = trim($input['message'] ?? '');
    if (empty($message)) {
        throw new Exception("Message empty");
    }

    $employeeID = $_SESSION['user_id'] ?? 1;

    // Get all unique customers who have chatted before
    $result = $conn->query("SELECT DISTINCT customerID FROM tbl_chatLogs UNION SELECT customerID FROM tbl_customer");

    $successCount = 0;
    while ($row = $result->fetch_assoc()) {
        $customerID = $row['customerID'];
        $stmt = $conn->prepare("INSERT INTO tbl_chatLogs (customerID, employeeID, message, timeSent, isRead) VALUES (?, ?, ?, NOW(), 0)");
        $stmt->bind_param("iis", $customerID, $employeeID, $message);
        if ($stmt->execute()) {
            $successCount++;
        }
    }

    echo json_encode(['success' => true, 'count' => $successCount]);
}

?>