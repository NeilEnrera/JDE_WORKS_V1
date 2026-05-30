<?php
error_log("admin_dashboard_stream.php: Entry point reached.");
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

include('../../JDE_USER/backend/db_connection.php');
require_once '../../JDE_USER/backend/services/ActivityService.php';

@set_time_limit(0);
session_start();
$userType = $_SESSION['user_type'] ?? 'Guest';
$myID = $_SESSION['user_id'] ?? 0;
session_write_close();

if ($userType !== 'Employee') {
    echo "event: error\n";
    echo "data: " . json_encode(['message' => 'Unauthorized']) . "\n\n";
    exit;
}

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');
@ini_set('implicit_flush', 'on');
while (ob_get_level() > 0)
    ob_end_flush();
ob_implicit_flush(1);
ignore_user_abort(false);

// Presence file lives next to the original stream so we keep same path
define('PRESENCE_FILE', dirname(__FILE__) . '/../../JDE_USER/backend/chat_presence.json');

function sendEvent($event, $data)
{
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

sendEvent('connected', ['message' => 'Admin Dashboard Connected']);

$lastIds = [
    'order'            => 0,
    'appointment'      => 0,
    'inquiry'          => 0,
    'chat'             => 0,
    'payment_verified' => '1970-01-01 00:00:00'
];

$resO = $conn->query("SELECT MAX(orderID) as maxID FROM tbl_order");
$lastIds['order'] = $resO->fetch_assoc()['maxID'] ?? 0;

$resA = $conn->query("SELECT MAX(appointmentID) as maxID FROM tbl_appointment");
$lastIds['appointment'] = $resA->fetch_assoc()['maxID'] ?? 0;

$resI = $conn->query("SELECT MAX(id) as maxID FROM tbl_contactMessages");
$lastIds['inquiry'] = $resI->fetch_assoc()['maxID'] ?? 0;

$resC = $conn->query("SELECT MAX(chatID) as maxID FROM tbl_chatLogs");
$lastIds['chat'] = $resC->fetch_assoc()['maxID'] ?? 0;

$resP = $conn->query("SELECT MAX(verifiedAt) as maxV FROM tbl_payments");
$lastIds['payment_verified'] = $resP->fetch_assoc()['maxV'] ?? '1970-01-01 00:00:00';

$iterationCount = 0;
$notifiedAppts  = [];

while (true) {
    if (!$conn || $conn->connect_error) {
        error_log("admin_dashboard_stream.php: DB Connection lost. Attempting reconnect...");
        include('../../JDE_USER/backend/db_connection.php');
    }

    $newActivities  = ActivityService::checkGlobalActivities($conn, $lastIds);
    $needsStatsUpdate = !empty($newActivities);

    foreach ($newActivities as $act) {
        error_log("admin_dashboard_stream.php: New Activity Event: " . $act['event']);
        sendEvent($act['event'], $act['data']);
    }

    $newAppts = ActivityService::checkAlmostDueAppointments($conn, $iterationCount, $notifiedAppts);
    foreach ($newAppts as $appData) {
        error_log("admin_dashboard_stream.php: Almost Due Appt: " . $appData['appointmentID']);
        sendEvent('new_appointment', $appData);
        $needsStatsUpdate = true;
    }

    if ($iterationCount % 5 === 0 || $needsStatsUpdate) {
        $stats = ActivityService::getDashboardStats($conn);
        if ($needsStatsUpdate) {
            error_log("admin_dashboard_stream.php: Sending stats refresh (forced by activity)");
        }
        sendEvent('dashboard_stats', $stats);
    }

    if ($iterationCount % 10 === 0) {
        $onlineUsers = ActivityService::getOnlineCustomers(PRESENCE_FILE);
        sendEvent('online_users', ['users' => $onlineUsers]);
    }

    if ($iterationCount % 5 === 0) {
        sendEvent('ping', ['timestamp' => time()]);
    }

    $iterationCount++;

    echo ": keep-alive\n\n";
    if (ob_get_level() > 0)
        ob_flush();
    flush();

    if (connection_aborted()) {
        error_log("admin_dashboard_stream.php: Connection aborted by client.");
        break;
    }
    sleep(1);
}
?>
