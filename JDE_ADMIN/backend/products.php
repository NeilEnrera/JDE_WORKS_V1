<?php
session_start();
require_once '../../JDE_USER/backend/db_connection.php';

// Auth check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || !in_array($_SESSION['access_level'], [1, 3])) {
    header('Location: ../../JDE_USER/backend/login.php');
    exit;
}

// 1. Fetch Products (active only)
// Compatibility-safe migration for isActive column
$checkColumn = $conn->query("SHOW COLUMNS FROM `tbl_product` LIKE 'isActive'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_product ADD isActive TINYINT(1) NOT NULL DEFAULT 1");
}

// --- PAGINATION LOGIC ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

// Count total products for pagination
$totalResult = $conn->query("SELECT COUNT(*) as total FROM tbl_product");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$productsResult = $conn->query("
    SELECT p.*, c.categoryName, CONCAT(e.firstName, ' ', e.lastName) as creatorName
    FROM tbl_product p
    LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID
    LEFT JOIN tbl_employee e ON p.employeeID = e.employeeID
    ORDER BY p.isActive DESC, p.productID DESC
    LIMIT $limit OFFSET $offset
");
$products = [];
while ($row = $productsResult->fetch_assoc()) {
    $products[] = $row;
}

// 2. Fetch Categories (for add/edit product dropdowns)
$categoriesResult = $conn->query("SELECT * FROM tbl_productCategory ORDER BY categoryName ASC");
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

$pageTitle = "Products Inventory";
require '../html/products.view.php';
?>