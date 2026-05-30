<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['query'])) {
    echo json_encode([]);
    exit;
}

$query = "%" . $_GET['query'] . "%";
$sql = "SELECT p.productName, p.stocks, p.price, p.stockThreshold 
        FROM tbl_product p 
        WHERE p.isActive = 1 AND p.productName LIKE ? 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $query);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $status = 'In Stock';
    $color = '#2ed573';
    
    if ($row['stocks'] <= 0) {
        $status = 'Out of Stock';
        $color = '#ff4757';
    } elseif ($row['stocks'] <= $row['stockThreshold']) {
        $status = 'Low Stock';
        $color = '#ffa502';
    }
    
    $row['status'] = $status;
    $row['statusColor'] = $color;
    $products[] = $row;
}

echo json_encode($products);
?>
