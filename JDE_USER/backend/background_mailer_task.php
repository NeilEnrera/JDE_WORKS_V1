<?php
/**
 * background_mailer_task.php
 * Standalone CLI script to send emails in the background.
 * Prevents the web user from waiting for SMTP handshakes.
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

// Ensure we are in the correct directory to include dependencies
chdir(__DIR__);
require_once 'email_helper.php';

// Get JSON input from the file passed as an argument
$tmpFile = $argv[1] ?? '';

if (empty($tmpFile) || !file_exists($tmpFile)) {
    file_put_contents('mailer_error.log', "[" . date('Y-m-d H:i:s') . "] No input file provided or file not found.\n", FILE_APPEND);
    exit(1);
}

$input = file_get_contents($tmpFile);
$data = json_decode($input, true);

if (!$data || !isset($data['type'])) {
    file_put_contents('mailer_error.log', "[" . date('Y-m-d H:i:s') . "] Invalid mailer data in $tmpFile: " . $input . "\n", FILE_APPEND);
    @unlink($tmpFile); // Clean up
    exit(1);
}

try {
    $type = $data['type'];
    $params = $data['params'];

    switch ($type) {
        case 'verification_code':
            EmailHelper::sendVerificationCode($params['to'], $params['code']);
            break;
        case 'verification_email':
            EmailHelper::sendVerificationEmail($params['to'], $params['name'], $params['token']);
            break;
        case 'order_update':
            EmailHelper::sendOrderStatusUpdate($params['to'], $params['name'], $params['orderID'], $params['status'], $params['items'] ?? []);
            break;
        case 'appointment_reminder':
            EmailHelper::sendAppointmentReminder($params['to'], $params['name'], $params['date'], $params['time'], $params['service']);
            break;
        case 'contact_message':
            EmailHelper::sendContactMessage($params['name'], $params['email'], $params['phone'], $params['subject'], $params['message']);
            break;
        case 'appointment_confirmation':
            EmailHelper::sendAppointmentConfirmation($params['to'], $params['name'], $params['date'], $params['time'], $params['service']);
            break;
        case 'appointment_status':
            EmailHelper::sendAppointmentStatusNotification($params['to'], $params['name'], $params['date'], $params['time'], $params['service'], $params['status']);
            break;
    }
} catch (Exception $e) {
    file_put_contents('mailer_error.log', "[" . date('Y-m-d H:i:s') . "] Mailer Exception: " . $e->getMessage() . "\n", FILE_APPEND);
} finally {
    @unlink($tmpFile); // Always clean up the temp file
}
