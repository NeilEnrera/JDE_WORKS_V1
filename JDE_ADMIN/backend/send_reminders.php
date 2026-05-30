<?php
/**
 * send_reminders.php
 * Automated appointment reminder mailer.
 * Can be triggered:
 *   1. Via pseudo-cron (HTTP call from admin pages) — primary method for local XAMPP.
 *   2. Via Windows Task Scheduler (CLI) — optional for production reliability.
 *
 * Location: JDE_ADMIN/backend/
 */

chdir(__DIR__);

// Allow HTTP calls (pseudo-cron) and block direct browser access
$isCli = php_sapi_name() === 'cli';
$isHttpInternal = isset($_GET['key']) && $_GET['key'] === 'jde_internal_cron_2024';

if (!$isCli && !$isHttpInternal) {
    http_response_code(403);
    die('Forbidden');
}

if (!$isCli) {
    // Silence output for HTTP calls; caller reads JSON
    ob_start();
    // Start session so EmailHelper can write SMTP errors
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// --- File-based logger (more reliable than session for async pseudo-cron) ---
$logPath = __DIR__ . '/reminder_log.txt';
function reminderLog($msg) {
    global $logPath;
    file_put_contents($logPath, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

require_once '../../JDE_USER/backend/db_connection.php';
require_once '../../JDE_USER/backend/email_helper.php';

date_default_timezone_set('Asia/Manila');

// --- Guard: prevent duplicate sends on the same day ---
$sentFlagPath = __DIR__ . '/reminder_sent_flag.json';
$sentFlag = [];
if (file_exists($sentFlagPath)) {
    $sentFlag = json_decode(file_get_contents($sentFlagPath), true) ?: [];
}
$today = date('Y-m-d');
if (isset($sentFlag['last_sent_date']) && $sentFlag['last_sent_date'] === $today) {
    if ($isCli) {
        echo "Reminders already sent today ($today). Skipping.\n";
    } else {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'skipped', 'reason' => 'already_sent_today']);
    }
    exit;
}

// --- Check configured reminder time ---
$jsonPath = __DIR__ . '/appointment_settings.json';
$configuredTime = '06:00';
if (file_exists($jsonPath)) {
    $settings = json_decode(file_get_contents($jsonPath), true) ?: [];
    $configuredTime = $settings['reminder_time'] ?? $configuredTime;
}

$currentHour   = (int) date('H');
$currentMinute = (int) date('i');
$scheduledHour = (int) date('H', strtotime($configuredTime));
$scheduledMin  = (int) date('i', strtotime($configuredTime));

// Run any time the admin visits AFTER the scheduled time (pseudo-cron friendly).
// The daily-once flag below prevents duplicate sends.
$nowTotal   = $currentHour * 60 + $currentMinute;
$schedTotal = $scheduledHour * 60 + $scheduledMin;

if ($nowTotal < $schedTotal) {
    if ($isCli) {
        die("Not yet time. Scheduled: {$configuredTime}, Current: " . date('H:i') . ". Skipping.\n");
    } else {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'skipped', 'reason' => 'not_scheduled_time', 'scheduled' => $configuredTime, 'now' => date('H:i')]);
        exit;
    }
}

// --- Query today's accepted appointments ---
$query = "SELECT * FROM tbl_appointment
          WHERE appointmentDate = ?
          AND appointmentStatus IN ('Accepted')";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();

$successCount = 0;
$failCount    = 0;

reminderLog("--- Reminder run started. Target date: {$today} ---");

while ($row = $result->fetch_assoc()) {
    $sent = EmailHelper::sendAppointmentReminder(
        $row['email'],
        $row['name'],
        $row['appointmentDate'],
        $row['appointmentTime'],
        $row['serviceType']
    );
    if ($sent) {
        $successCount++;
        reminderLog("✅ Sent to {$row['email']} ({$row['name']}) for {$row['appointmentDate']} at {$row['appointmentTime']}");
    } else {
        $failCount++;
        $smtpErr = $_SESSION['smtp_error'] ?? 'Unknown SMTP error';
        reminderLog("❌ FAILED for {$row['email']} ({$row['name']}) — {$smtpErr}");
    }
}

$stmt->close();
$conn->close();

// --- Mark as sent for today to prevent duplicate runs ---
file_put_contents($sentFlagPath, json_encode([
    'last_sent_date' => $today,
    'sent'           => $successCount,
    'failed'         => $failCount,
    'ran_at'         => date('Y-m-d H:i:s')
]));

reminderLog("--- Done. Sent: {$successCount} | Failed: {$failCount} ---");

if ($isCli) {
    echo "Reminders sent: $successCount | Failed: $failCount | Date: $today\n";
} else {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'done', 'sent' => $successCount, 'failed' => $failCount, 'date' => $today]);
}
?>
