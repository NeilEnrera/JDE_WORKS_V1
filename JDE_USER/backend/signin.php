<?php
/**
 * signin.php — Backend controller
 * Handles user registration validation and DB insertion. Then renders the signup view.
 */
session_start();
include("db_connection.php");

$error_msg = "";
$registered = false;

// Show success modal if redirected back after registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
  $registered = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $givenName = trim($_POST['txt_givenName']);
  $middleName = trim($_POST['txt_middleName']);
  $surname = trim($_POST['txt_surname']);
  $gender = trim($_POST['txt_gender']);
  $birthday = trim($_POST['txt_birthday']);
  $email = trim($_POST['txt_email']);
  $username = trim($_POST['txt_uname']);
  $phone = trim($_POST['txt_phone']);
  $password = trim($_POST['txt_pw']);
  $confirm_pw = trim($_POST['txt_confirm_pw']);

  // Validation
  if (empty($givenName) || empty($surname) || empty($email) || empty($username) || empty($phone) || empty($password)) {
    $error_msg = "Please fill in all required fields.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_msg = "Please enter a valid email address.";
  } elseif (!preg_match('/^09[0-9]{9}$/', $phone)) {
    $error_msg = "Phone number must start with 09 and be exactly 11 digits.";
  } elseif (date_diff(date_create($birthday), date_create('today'))->y < 18) {
    $error_msg = "You must be at least 18 years old to register.";
  } elseif (strlen($password) < 8) {
    $error_msg = "Password must be at least 8 characters.";
  } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
    $error_msg = "Password must include uppercase, lowercase, a number, and a special character.";
  } elseif ($password !== $confirm_pw) {
    $error_msg = "Passwords do not match.";
  } else {
    // Check for existing username
    $stmt = $conn->prepare("SELECT customerID FROM tbl_customer WHERE userName = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $error_msg = "Username is already taken.";
      $stmt->close();
    } else {
      $stmt->close();
      // Check for existing email
      $stmt = $conn->prepare("SELECT customerID FROM tbl_customer WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
        $error_msg = "Email is already registered.";
        $stmt->close();
      } else {
        $stmt->close();
        $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));
        
        $stmt = $conn->prepare("INSERT INTO tbl_customer (firstName, middleName, lastName, gender, birthday, email, userName, phoneNumber, password, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $givenName, $middleName, $surname, $gender, $birthday, $email, $username, $phone, $hashed_pw, $verificationToken);
        
        if ($stmt->execute()) {
          // Send verification email in the background (Async)
          require_once 'email_helper.php';
          EmailHelper::dispatchAsync('verification_email', [
            'to' => $email,
            'name' => $givenName,
            'token' => $verificationToken
          ]);
          
          header("Location: ../html/signin.view.php?registered=1");
          exit();
        } else {
          $error_msg = "Registration failed. Please try again.";
        }
        $stmt->close();
      }
    }
  }
}

require '../html/signin.view.php';