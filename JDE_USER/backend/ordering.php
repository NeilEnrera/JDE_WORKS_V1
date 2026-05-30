<?php
/**
 * ordering.php — Backend controller
 * Resolves product data by URL slug, then renders the ordering/product-details view.
 */
session_start();
require_once 'db_connection.php';
?>
<?php
$wishlist = isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];
$productId = isset($_GET['product']) ? $_GET['product'] : '';

// 1. Fetch products from database
// Use slug to find the specific product
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
      'description' => $row['description'],
      'price' => $row['price'],
      'image' => resolveProductPath($row['productImage']),
      'image2' => !empty($row['productImage2']) ? resolveProductPath($row['productImage2']) : null,
      'image3' => !empty($row['productImage3']) ? resolveProductPath($row['productImage3']) : null,
      'image4' => !empty($row['productImage4']) ? resolveProductPath($row['productImage4']) : null,
      'id' => $row['productID'],
      'stocks' => $row['stocks'],
      'sizeStocks' => $row['sizeStocks'] ?? '{}',
      'type' => $row['type']
    ];
  }
  $stmt->close();
}

// Fallback if not found
if (!$currentProduct) {
  // Just grab the first active product as default fallback
  $res = $conn->query("SELECT p.*, c.categoryName FROM tbl_product p LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID WHERE p.isActive = 1 LIMIT 1");
  if ($row = $res->fetch_assoc()) {
    $currentProduct = [
      'name' => $row['productName'],
      'category' => $row['categoryName'],
      'description' => $row['description'],
      'price' => $row['price'],
      'image' => resolveProductPath($row['productImage']),
      'image2' => !empty($row['productImage2']) ? resolveProductPath($row['productImage2']) : null,
      'image3' => !empty($row['productImage3']) ? resolveProductPath($row['productImage3']) : null,
      'image4' => !empty($row['productImage4']) ? resolveProductPath($row['productImage4']) : null,
      'id' => $row['productID'],
      'stocks' => $row['stocks'],
      'sizeStocks' => $row['sizeStocks'] ?? '{}',
      'type' => $row['type']
    ];
    $productId = $row['slug'];
  }
}

// Determine size chart type
$sizeChartType = 'polo';
if ($currentProduct && (strpos($currentProduct['type'], 'trouser') !== false || strpos($currentProduct['type'], 'pants') !== false)) {
  $sizeChartType = 'pants';
}
?>
<script>
  window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
  window.currentProductID = "<?php echo $currentProduct['id'] ?? ''; ?>";
</script>
<?php
require '../html/ordering.view.php';
?>