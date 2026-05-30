<?php
session_start();
require_once '../../JDE_USER/backend/db_connection.php';

// Auth check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || $_SESSION['access_level'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: Add User
    if ($action === 'add_user') {
        $userName = $_POST['userName'] ?? '';
        $password = $_POST['password'] ?? '';
        $firstName = $_POST['firstName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $middleName = $_POST['middleName'] ?? '';
        $birthday = $_POST['birthday'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $userTypeID = intval($_POST['userTypeID'] ?? 0);
        $email = $_POST['email'] ?? '';
        $phoneNumber = $_POST['phoneNumber'] ?? '';
        
        $createdBy = "Admin"; 

        if (empty($userName) || empty($password) || empty($firstName) || empty($lastName) || empty($birthday) || empty($gender) || empty($email) || empty($phoneNumber) || empty($userTypeID)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($userTypeID == 3) {
            $stmt = $conn->prepare("INSERT INTO tbl_customer (userName, password, firstName, lastName, middleName, birthday, gender, email, phoneNumber, createdBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $userName, $hashedPassword, $firstName, $lastName, $middleName, $birthday, $gender, $email, $phoneNumber, $createdBy);
        } else {
            $accessLevelID = ($userTypeID == 1) ? 1 : 2;
            $stmt = $conn->prepare("INSERT INTO tbl_employee (accessLevelID, userName, password, firstName, lastName, middleName, birthday, gender, email, phoneNumber, createdBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssss", $accessLevelID, $userName, $hashedPassword, $firstName, $lastName, $middleName, $birthday, $gender, $email, $phoneNumber, $createdBy);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // Action: Update User
    if ($action === 'update_user') {
        $userID = intval($_POST['userID'] ?? 0);
        $userTypeID = intval($_POST['userTypeID'] ?? 0);
        $userName = $_POST['userName'] ?? '';
        $firstName = $_POST['firstName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $middleName = $_POST['middleName'] ?? '';
        $birthday = $_POST['birthday'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $email = $_POST['email'] ?? '';
        $phoneNumber = $_POST['phoneNumber'] ?? '';
        $updatedBy = "Admin";
        
        if ($userTypeID == 3) {
            $stmt = $conn->prepare("UPDATE tbl_customer SET userName=?, firstName=?, lastName=?, middleName=?, birthday=?, gender=?, email=?, phoneNumber=?, updatedBy=? WHERE customerID=?");
            $stmt->bind_param("sssssssssi", $userName, $firstName, $lastName, $middleName, $birthday, $gender, $email, $phoneNumber, $updatedBy, $userID);
        } else {
            $stmt = $conn->prepare("UPDATE tbl_employee SET userName=?, firstName=?, lastName=?, middleName=?, birthday=?, gender=?, email=?, phoneNumber=?, updatedBy=? WHERE employeeID=?");
            $stmt->bind_param("sssssssssi", $userName, $firstName, $lastName, $middleName, $birthday, $gender, $email, $phoneNumber, $updatedBy, $userID);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // Action: Get Single User
    if ($action === 'get_user') {
        $userID = intval($_POST['userID'] ?? 0);
        $userTypeID = intval($_POST['userTypeID'] ?? 0);

        if ($userTypeID == 3) {
            $stmt = $conn->prepare("SELECT customerID as id, userName, firstName, lastName, middleName, birthday, gender, email, phoneNumber, createdBy, updatedBy FROM tbl_customer WHERE customerID=?");
        } else {
            $stmt = $conn->prepare("SELECT employeeID as id, userName, firstName, lastName, middleName, birthday, gender, email, phoneNumber, createdBy, updatedBy FROM tbl_employee WHERE employeeID=?");
        }
        
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $user['userTypeID'] = $userTypeID; 
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        $stmt->close();
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
