<?php
/**
 * product.php - Backend handler for product management AJAX calls.
 * Enhanced for debugging: capturing all errors into JSON.
 */
ob_start();

// Global error handler to capture warnings/notices as part of the JSON response if relevant
function handleAjaxError($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno))
        return false;
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "PHP Error ($errno): $errstr in $errfile on line $errline"
    ]);
    exit;
}
set_error_handler("handleAjaxError");

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once 'db_connection.php';

    // 1. Migration Block
    $columnsToAdd = [
        'isActive' => "TINYINT(1) NOT NULL DEFAULT 1",
        'collection' => "VARCHAR(50) DEFAULT NULL",
        'type' => "VARCHAR(50) DEFAULT NULL",
        'fitType' => "VARCHAR(50) DEFAULT NULL",
        'productImage2' => "VARCHAR(255) DEFAULT NULL",
        'productImage3' => "VARCHAR(255) DEFAULT NULL",
        'productImage4' => "VARCHAR(255) DEFAULT NULL",
        'sizeStocks' => "TEXT DEFAULT NULL",
        'gender' => "VARCHAR(20) DEFAULT NULL",
        'stockThreshold' => "INT DEFAULT 5",
        'customDiscountPercent' => "DECIMAL(5,2) DEFAULT NULL",
        'customMinQty' => "INT DEFAULT NULL"
    ];

    foreach ($columnsToAdd as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `tbl_product` LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE tbl_product ADD `$col` $def");
        }
    }

    // Initialize Admin Logs Table
    $conn->query("CREATE TABLE IF NOT EXISTS tbl_admin_logs (
        logID INT AUTO_INCREMENT PRIMARY KEY,
        employeeID INT,
        actionType VARCHAR(100),
        actionDetails TEXT,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        ob_clean();
        header('Content-Type: application/json');

        $action = $_POST['action'];

        if ($action === 'restock') {
            // --- BACKEND IDEMPOTENCY GUARD ---
            $token = trim($_POST['idempotency_token'] ?? '');
            if ($token !== '') {
                $tokenKey = 'restock_idem_' . $token;
                if (!empty($_SESSION[$tokenKey])) {
                    echo json_encode(['success' => true, 'product' => $_SESSION[$tokenKey]]);
                    exit;
                }
            }

            $id = intval($_POST['productID'] ?? 0);
            $qtyToAdd = intval($_POST['quantity'] ?? 0);
            $sizeToRestock = $_POST['size'] ?? '';

            if ($id <= 0 || $qtyToAdd <= 0) {
                echo json_encode(['success' => false, 'message' => "Invalid ID or quantity."]);
                exit;
            }

            // Fetch current product data
            $res = $conn->query("SELECT stocks, sizeStocks FROM tbl_product WHERE productID = $id LIMIT 1");
            $product = $res->fetch_assoc();

            if (!$product) {
                echo json_encode(['success' => false, 'message' => "Product not found."]);
                exit;
            }

            $currentStocks = intval($product['stocks']);
            $sizeStocks = json_decode($product['sizeStocks'], true) ?? [];

            // Update size-specific stock if size provided
            if ($sizeToRestock && isset($sizeStocks[$sizeToRestock])) {
                if (is_array($sizeStocks[$sizeToRestock])) {
                    $sizeStocks[$sizeToRestock]['qty'] = intval($sizeStocks[$sizeToRestock]['qty'] ?? 0) + $qtyToAdd;
                } else {
                    $sizeStocks[$sizeToRestock] = intval($sizeStocks[$sizeToRestock]) + $qtyToAdd;
                }
            }

            $newTotalStocks = $currentStocks + $qtyToAdd;
            $newSizeStocksJson = json_encode($sizeStocks);

            $stmt = $conn->prepare("UPDATE tbl_product SET stocks = ?, sizeStocks = ? WHERE productID = ?");
            $stmt->bind_param("isi", $newTotalStocks, $newSizeStocksJson, $id);

            if ($stmt->execute()) {
                $res = $conn->query("SELECT p.*, c.categoryName, e.firstName as creatorName 
                                    FROM tbl_product p 
                                    LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID 
                                    LEFT JOIN tbl_employee e ON p.employeeID = e.employeeID
                                    WHERE p.productID = $id LIMIT 1");
                $updatedProd = $res->fetch_assoc();

                // Save token
                $token = trim($_POST['idempotency_token'] ?? '');
                if ($token !== '') {
                    $_SESSION['restock_idem_' . $token] = $updatedProd;
                }

                echo json_encode(['success' => true, 'product' => $updatedProd]);
            } else {
                echo json_encode(['success' => false, 'message' => "Update failed: " . $conn->error]);
            }
            exit;
        }

        if ($action === 'add' || $action === 'update') {
            $name = $_POST['productName'] ?? 'Unnamed Product';
            $desc = $_POST['description'] ?? '';
            $catID = intval($_POST['categoryID'] ?? 0);
            $price = floatval($_POST['price'] ?? 0);
            $stocks = intval($_POST['stocks'] ?? 0);
            $size = $_POST['size'] ?? 'Various';
            $collection = $_POST['collection'] ?? '';
            $type = $_POST['type'] ?? '';
            $fitType = $_POST['fitType'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $sizeStocks = $_POST['sizeStocks'] ?? '{}';
            $stockThreshold = intval($_POST['stockThreshold'] ?? 5);
            $employeeID = $_SESSION['user_id'] ?? 1;

            $originalSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = $originalSlug;
            $counter = 1;
            $currentID = intval($_POST['productID'] ?? 0);

            while (true) {
                $checkSlug = $conn->prepare("SELECT productID FROM tbl_product WHERE slug = ? AND productID != ? LIMIT 1");
                $checkSlug->bind_param("si", $slug, $currentID);
                $checkSlug->execute();
                if ($checkSlug->get_result()->num_rows === 0) {
                    $checkSlug->close();
                    break;
                }
                $checkSlug->close();
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $targetDir = __DIR__ . "/../assets/img/products/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $images = [
                'productImage' => $_POST['currentImage'] ?? 'default.jpg',
                'productImage2' => $_POST['currentImage2'] ?? null,
                'productImage3' => $_POST['currentImage3'] ?? null,
                'productImage4' => $_POST['currentImage4'] ?? null
            ];

            $imageFields = ['productImage', 'productImage2', 'productImage3', 'productImage4'];
            foreach ($imageFields as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $mime = mime_content_type($_FILES[$field]['tmp_name']);
                    if (!in_array($mime, $allowedMimes))
                        continue;

                    $mimeMap = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp'
                    ];
                    $fileExt = $mimeMap[$mime] ?? 'jpg';
                    $fileName = $slug . '-' . $field . '-' . time() . '.' . $fileExt;
                    $targetFilePath = $targetDir . $fileName;

                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetFilePath)) {
                        // Store path as '../assets/img/products/...' — the standard format for the DB
                        $images[$field] = '../assets/img/products/' . $fileName;
                    } else {
                        throw new Exception("Failed to move uploaded file: $field. Target: $targetFilePath");
                    }
                }
            }

            if ($action === 'add') {
                // --- BACKEND IDEMPOTENCY GUARD ---
                // Reject duplicate POSTs carrying the same token (e.g. from race conditions / network retries)
                $token = trim($_POST['idempotency_token'] ?? '');
                if ($token !== '') {
                    $tokenKey = 'product_idem_' . $token;
                    if (!empty($_SESSION[$tokenKey])) {
                        // Already processed — return the previously created product
                        $prevID = (int)$_SESSION[$tokenKey];
                        $res = $conn->query("SELECT p.*, c.categoryName, e.firstName as creatorName 
                                            FROM tbl_product p 
                                            LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID 
                                            LEFT JOIN tbl_employee e ON p.employeeID = e.employeeID
                                            WHERE p.productID = $prevID LIMIT 1");
                        $existingProduct = $res ? $res->fetch_assoc() : null;
                        ob_clean();
                        echo json_encode(['success' => true, 'product' => $existingProduct]);
                        exit;
                    }
                }

                $sql = "INSERT INTO tbl_product (`productName`, `description`, `categoryID`, `price`, `stocks`, `size`, `employeeID`, `slug`, `productImage`, `productImage2`, `productImage3`, `productImage4`, `collection`, `type`, `fitType`, `sizeStocks`, `gender`, `stockThreshold`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssidisissssssssssi", $name, $desc, $catID, $price, $stocks, $size, $employeeID, $slug, $images['productImage'], $images['productImage2'], $images['productImage3'], $images['productImage4'], $collection, $type, $fitType, $sizeStocks, $gender, $stockThreshold);
                }
            } else {
                $id = intval($_POST['productID'] ?? 0);
                $sql = "UPDATE tbl_product SET `productName` = ?, `description` = ?, `categoryID` = ?, `price` = ?, `stocks` = ?, `size` = ?, `slug` = ?, `productImage` = ?, `productImage2` = ?, `productImage3` = ?, `productImage4` = ?, `collection` = ?, `type` = ?, `fitType` = ?, `sizeStocks` = ?, `gender` = ?, `stockThreshold` = ? WHERE `productID` = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssidisssssssssssii", $name, $desc, $catID, $price, $stocks, $size, $slug, $images['productImage'], $images['productImage2'], $images['productImage3'], $images['productImage4'], $collection, $type, $fitType, $sizeStocks, $gender, $stockThreshold, $id);
                }
            }

            if ($stmt && $stmt->execute()) {
                $lastID = ($action === 'add') ? $conn->insert_id : intval($_POST['productID'] ?? 0);

                // --- SAVE IDEMPOTENCY TOKEN ---
                $token = trim($_POST['idempotency_token'] ?? '');
                if ($action === 'add' && $token !== '') {
                    $_SESSION['product_idem_' . $token] = $lastID;
                }

                $res = $conn->query("SELECT p.*, c.categoryName, e.firstName as creatorName 
                                    FROM tbl_product p 
                                    LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID 
                                    LEFT JOIN tbl_employee e ON p.employeeID = e.employeeID
                                    WHERE p.productID = $lastID LIMIT 1");
                $product = $res ? $res->fetch_assoc() : null;

                ob_clean();
                $json = json_encode(['success' => true, 'product' => $product]);
                if ($json === false) {
                    echo json_encode(['success' => false, 'message' => 'JSON Encoding Error: ' . json_last_error_msg()]);
                } else {
                    echo $json;
                }
            } else {
                $err = $stmt ? $stmt->error : $conn->error;
                ob_clean();
                echo json_encode(['success' => false, 'message' => "Database error: " . $err]);
            }
            if ($stmt)
                $stmt->close();
            exit;
        }

        if ($action === 'delete' || $action === 'restore') {
            ob_clean();
            header('Content-Type: application/json');
            $id = intval($_POST['productID'] ?? 0);
            $isActive = ($action === 'restore') ? 1 : 0;
            $stmt = $conn->prepare("UPDATE tbl_product SET `isActive` = ? WHERE `productID` = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $isActive, $id);
                if ($stmt->execute()) {
                    $res = $conn->query("SELECT p.*, c.categoryName, e.firstName as creatorName FROM tbl_product p LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID LEFT JOIN tbl_employee e ON p.employeeID = e.employeeID WHERE p.productID = $id LIMIT 1");
                    $product = $res ? $res->fetch_assoc() : null;
                    echo json_encode(['success' => true, 'product' => $product]);
                    exit;
                }
            }
            echo json_encode(['success' => false, 'message' => "Update failed"]);
            exit;
        }

        if ($action === 'permanent_delete') {
            ob_clean();
            header('Content-Type: application/json');
            $id = intval($_POST['productID'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM tbl_product WHERE `productID` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                    exit;
                }
            }
            echo json_encode(['success' => false, 'message' => "Delete failed"]);
            exit;
        }
    }
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    exit;
}

// Non-AJAX Fallback Logic (User Website View)
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    // Synchronize wishlist from DB if not already done or for fresh data
    $customerID = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT productSKU FROM tbl_wishlist WHERE customerID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $customerID);
        $stmt->execute();
        $res = $stmt->get_result();
        $dbWishlist = [];
        while ($row = $res->fetch_assoc()) {
            $dbWishlist[] = $row['productSKU'];
        }
        $_SESSION['wishlist'] = $dbWishlist;
        $stmt->close();
    }
}
$wishlist = isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];
$activePage = 'products';
$productsResult = $conn->query("SELECT p.*, c.categoryName, 
    (SELECT AVG(rating) FROM tbl_reviews WHERE productID_slug = p.slug) as avg_rating,
    (SELECT COUNT(*) FROM tbl_reviews WHERE productID_slug = p.slug) as total_reviews
    FROM tbl_product p 
    LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID 
    WHERE p.isActive = 1");
$products = [];
if ($productsResult) {
    while ($row = $productsResult->fetch_assoc())
        $products[] = $row;
}
require '../html/product.view.php';
?>