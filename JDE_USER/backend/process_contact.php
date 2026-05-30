<?php
session_start();
include 'email_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($subject) || empty($message)) {
        header("Location: index.php?contact=error&reason=missing_fields#Contact");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?contact=error&reason=invalid_email#Contact");
        exit();
    }

    // Send email notification directly
    if (EmailHelper::sendContactMessage($name, $email, $phone, $subject, $message)) {
        header("Location: index.php?contact=success#Contact");
    } else {
        header("Location: index.php?contact=error&reason=email_error#Contact");
    }
} else {
    header("Location: index.php");
}
?>
