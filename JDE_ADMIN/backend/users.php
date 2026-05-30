<?php
session_start();

// Auth check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || $_SESSION['access_level'] != 1) {
    header('Location: ../../JDE_USER/backend/login.php');
    exit;
}

require_once '../../JDE_USER/backend/db_connection.php';

$pageTitle = "Users Management";

$users = [];

// Ensure isBlocked column exists on tbl_customer
$conn->query("ALTER TABLE tbl_customer ADD COLUMN IF NOT EXISTS isBlocked TINYINT(1) NOT NULL DEFAULT 0");

// Fetch employees
$res_emp = $conn->query("SELECT * FROM tbl_employee ORDER BY employeeID DESC");
if ($res_emp) {
    while ($row = $res_emp->fetch_assoc()) {
        $userTypeID = ($row['accessLevelID'] == 1) ? 1 : 2;
        $users[] = [
            'userID' => $row['employeeID'],
            'userName' => $row['userName'],
            'firstName' => $row['firstName'],
            'lastName' => $row['lastName'],
            'middleName' => $row['middleName'] ?? '',
            'birthday' => $row['birthday'],
            'gender' => $row['gender'],
            'email' => $row['email'],
            'phoneNumber' => $row['phoneNumber'],
            'userTypeID' => $userTypeID,
            'createdBy' => $row['createdBy'],
            'updatedBy' => $row['updatedBy']
        ];
    }
}

// Fetch customers
$res_cust = $conn->query("SELECT * FROM tbl_customer ORDER BY customerID DESC");
if ($res_cust) {
    while ($row = $res_cust->fetch_assoc()) {
        $users[] = [
            'userID'      => $row['customerID'],
            'userName'    => $row['userName'],
            'firstName'   => $row['firstName'],
            'lastName'    => $row['lastName'],
            'middleName'  => $row['middleName'] ?? '',
            'birthday'    => $row['birthday'],
            'gender'      => $row['gender'],
            'email'       => $row['email'],
            'phoneNumber' => $row['phoneNumber'],
            'userTypeID'  => 3, // Customer
            'isBlocked'   => $row['isBlocked'] ?? 0,
            'createdBy'   => $row['createdBy'],
            'updatedBy'   => $row['updatedBy']
        ];
    }
}

// Sort the unified array by first name
usort($users, function($a, $b) {
    return strcmp($a['firstName'], $b['firstName']);
});

// --- PAGINATION LOGIC ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

$totalRows = count($users);
$totalPages = ceil($totalRows / $limit);

// Slice the unified array for the current page
$users = array_slice($users, $offset, $limit);

require '../html/users.view.php';
