<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

error_reporting(0); // Prevent PHP errors from breaking JSON output

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'Customer') {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userID = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        try {
            $stmt = $conn->prepare("SELECT * FROM tbl_addressBook WHERE customerID = ? ORDER BY isDefault DESC, addressID DESC");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            $addresses = [];
            while ($row = $result->fetch_assoc()) {
                $addresses[] = $row;
            }
            echo json_encode(['success' => true, 'addresses' => $addresses]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    try {
        if ($action === 'add' || $action === 'edit') {
            $name = $input['receiverName'] ?? '';
            $phone = $input['receiverPhone'] ?? '';
            $addressLine = $input['addressLine'] ?? '';
            $barangay = $input['barangay'] ?? '';
            $city = $input['city'] ?? '';
            $province = $input['province'] ?? '';
            $zip = $input['zip'] ?? '';
            $isDefault = isset($input['isDefault']) ? (int) $input['isDefault'] : 0;

            if (empty($name) || empty($phone) || empty($addressLine) || empty($barangay) || empty($city) || empty($province) || empty($zip)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            if ($isDefault === 1) {
                // Unset other defaults using prepared statement
                $stmtDefault = $conn->prepare("UPDATE tbl_addressBook SET isDefault = 0 WHERE customerID = ?");
                $stmtDefault->bind_param("i", $userID);
                $stmtDefault->execute();
                $stmtDefault->close();
            }

            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO tbl_addressBook (customerID, receiverName, receiverPhone, addressLine, barangay, city, province, zip, isDefault) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt)
                    throw new Exception("Database error (insert): " . $conn->error);
                $stmt->bind_param("isssssssi", $userID, $name, $phone, $addressLine, $barangay, $city, $province, $zip, $isDefault);
            } else {
                $addressID = $input['addressID'] ?? 0;
                $stmt = $conn->prepare("UPDATE tbl_addressBook SET receiverName = ?, receiverPhone = ?, addressLine = ?, barangay = ?, city = ?, province = ?, zip = ?, isDefault = ? WHERE addressID = ? AND customerID = ?");
                if (!$stmt)
                    throw new Exception("Database error (update): " . $conn->error);
                $stmt->bind_param("sssssssiii", $name, $phone, $addressLine, $barangay, $city, $province, $zip, $isDefault, $addressID, $userID);
            }

            if ($stmt->execute()) {
                // --- Sync to Personal Information Home Address ---
                // Determine if this address should become the profile address:
                // sync if (a) it is marked as default, OR (b) it is the only address in the book
                $shouldSync = ($isDefault === 1);
                if (!$shouldSync) {
                    $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tbl_addressBook WHERE customerID = ?");
                    $countStmt->bind_param("i", $userID);
                    $countStmt->execute();
                    $countRow = $countStmt->get_result()->fetch_assoc();
                    $countStmt->close();
                    if ((int)$countRow['cnt'] === 1) $shouldSync = true;
                }

                $formattedAddress = null;
                if ($shouldSync) {
                    // Build a human-readable composite address string
                    $parts = array_filter([$addressLine, $barangay, $city, $province, $zip]);
                    $formattedAddress = implode(', ', $parts);
                    $syncStmt = $conn->prepare("UPDATE tbl_customer SET address = ? WHERE customerID = ?");
                    $syncStmt->bind_param("si", $formattedAddress, $userID);
                    $syncStmt->execute();
                    $syncStmt->close();
                }

                echo json_encode([
                    'success'          => true,
                    'message'          => 'Address saved successfully',
                    'syncedAddress'    => $formattedAddress  // null if not synced
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error saving address: ' . $stmt->error]);
            }
            exit;
        }

        if ($action === 'delete') {
            $addressID = $input['addressID'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM tbl_addressBook WHERE addressID = ? AND customerID = ?");
            if (!$stmt)
                throw new Exception("Database error (delete): " . $conn->error);
            $stmt->bind_param("ii", $addressID, $userID);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting address: ' . $stmt->error]);
            }
            exit;
        }

        if ($action === 'set_default') {
            $addressID = $input['addressID'] ?? 0;
            $conn->begin_transaction();
            try {
                // Unset other defaults using prepared statement
                $stmtDefault = $conn->prepare("UPDATE tbl_addressBook SET isDefault = 0 WHERE customerID = ?");
                $stmtDefault->bind_param("i", $userID);
                $stmtDefault->execute();
                $stmtDefault->close();

                $stmt = $conn->prepare("UPDATE tbl_addressBook SET isDefault = 1 WHERE addressID = ? AND customerID = ?");
                if (!$stmt)
                    throw new Exception("Database error (set_default): " . $conn->error);
                $stmt->bind_param("ii", $addressID, $userID);
                $stmt->execute();
                $conn->commit();

                // Sync the new default address to Personal Information
                $addrStmt = $conn->prepare("SELECT addressLine, barangay, city, province, zip FROM tbl_addressBook WHERE addressID = ? AND customerID = ?");
                $addrStmt->bind_param("ii", $addressID, $userID);
                $addrStmt->execute();
                $addrRow = $addrStmt->get_result()->fetch_assoc();
                $addrStmt->close();
                $formattedAddress = null;
                if ($addrRow) {
                    $parts = array_filter([$addrRow['addressLine'], $addrRow['barangay'], $addrRow['city'], $addrRow['province'], $addrRow['zip']]);
                    $formattedAddress = implode(', ', $parts);
                    $syncStmt = $conn->prepare("UPDATE tbl_customer SET address = ? WHERE customerID = ?");
                    $syncStmt->bind_param("si", $formattedAddress, $userID);
                    $syncStmt->execute();
                    $syncStmt->close();
                }

                echo json_encode([
                    'success'       => true,
                    'message'       => 'Default address updated',
                    'syncedAddress' => $formattedAddress
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>