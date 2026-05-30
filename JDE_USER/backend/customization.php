<?php
/**
 * customization.php — Backend controller
 * Handles product lookup for customization, then renders the view.
 */
session_start();
require_once 'db_connection.php';
?>
<script>
  window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
  // Load size guides from JSON file
  <?php
    $jsonPath = __DIR__ . '/size_guides.json';
    $guides = file_exists($jsonPath) ? file_get_contents($jsonPath) : '{}';
  ?>
  window.SIZE_GUIDES = <?php echo $guides; ?>;
</script>
<?php
$productId = isset($_GET['product']) ? $_GET['product'] : '';

// 1. Fetch product from database
$currentProduct = null;
if (!empty($productId)) {
  $stmt = $conn->prepare("SELECT p.*, c.categoryName FROM tbl_product p LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID WHERE p.slug = ? AND p.isActive = 1 LIMIT 1");
  $stmt->bind_param("s", $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $currentProduct = [
      'name' => $row['productName'],
      'category' => $row['categoryName'],
      'price' => $row['price'],
      'image' => !empty($row['productImage']) ? $row['productImage'] : "../assets/img/default-product.jpg",
      'id' => $row['productID'],
      'sizeStocks' => $row['sizeStocks'] ?? '{}'
    ];
  }
  $stmt->close();
}

// Fallback if not found
if (!$currentProduct) {
  $res = $conn->query("SELECT p.*, c.categoryName FROM tbl_product p LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID WHERE p.isActive = 1 LIMIT 1");
  if ($row = $res->fetch_assoc()) {
    $currentProduct = [
      'name' => $row['productName'],
      'category' => $row['categoryName'],
      'price' => $row['price'],
      'image' => !empty($row['productImage']) ? $row['productImage'] : "../assets/img/default-product.jpg",
      'id' => $row['productID'],
      'sizeStocks' => $row['sizeStocks'] ?? '{}'
    ];
    $productId = $row['slug'];
  }
}

require '../html/customization.view.php';