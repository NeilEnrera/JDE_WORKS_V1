<?php
/**
 * wishlist.php — Backend controller
 * Handles AJAX toggle/get, pre-fills wishlist data, then renders the view.
 */
session_start();

// Wishlist handled by session for guests, synced with DB for logged-in users
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initializing $action to avoid undefined variable warnings
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : null);

if (!$action):
    ?>
    <script>
        window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
<?php endif;

if ($action) {
    header('Content-Type: application/json');

    if ($action === 'toggle') {
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = isset($input['productId']) ? $input['productId'] : '';

        if (empty($productId)) {
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            exit;
        }

        require_once 'db_connection.php';

        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }

        $index = array_search($productId, $_SESSION['wishlist']);
        $added = false;

        if ($index !== false) {
            array_splice($_SESSION['wishlist'], $index, 1);
            if (isset($_SESSION['user_id'])) {
                $customerID = $_SESSION['user_id'];
                $stmt = $conn->prepare("DELETE FROM tbl_wishlist WHERE customerID = ? AND productSKU = ?");
                if ($stmt) {
                    $stmt->bind_param("is", $customerID, $productId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } else {
            $_SESSION['wishlist'][] = $productId;
            $added = true;
            if (isset($_SESSION['user_id'])) {
                $customerID = $_SESSION['user_id'];
                // Use INSERT IGNORE to prevent duplicates in case of race conditions
                $stmt = $conn->prepare("INSERT IGNORE INTO tbl_wishlist (customerID, productSKU) VALUES (?, ?)");
                if ($stmt) {
                    $stmt->bind_param("is", $customerID, $productId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        echo json_encode([
            'success' => true,
            'added' => $added,
            'wishlistCount' => count($_SESSION['wishlist']),
            'wishlist' => $_SESSION['wishlist']
        ]);
        exit;
    } elseif ($action === 'get') {
        require_once 'db_connection.php';
        $wishlist = isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];
        $valid_count = 0;
        $valid_wishlist = [];

        if (!empty($wishlist)) {
            $escaped_skus = array_map(function ($sku) use ($conn) {
                return "'" . $conn->real_escape_string($sku) . "'";
            }, $wishlist);
            $sku_list = implode(',', $escaped_skus);
            $vResult = $conn->query("SELECT slug FROM tbl_product WHERE slug IN ($sku_list) AND isActive = 1");
            if ($vResult) {
                while ($vRow = $vResult->fetch_assoc()) {
                    $valid_wishlist[] = $vRow['slug'];
                }
                $valid_count = count($valid_wishlist);
            }
        }

        echo json_encode([
            'success' => true,
            'wishlist' => $valid_wishlist,
            'wishlistCount' => $valid_count
        ]);
        exit;
    }
}

require_once 'db_connection.php';

// Migration: Ensure unique index on wishlist to prevent duplicates
$conn->query("CREATE TABLE IF NOT EXISTS `tbl_wishlist` (
    `wishlistID` int(11) NOT NULL AUTO_INCREMENT,
    `customerID` int(11) NOT NULL,
    `productSKU` varchar(50) NOT NULL,
    `dateAdded` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`wishlistID`)
)");

$checkIndex = $conn->query("SHOW INDEX FROM `tbl_wishlist` WHERE Key_name = 'uidx_customer_product'");
if ($checkIndex && $checkIndex->num_rows === 0) {
    $conn->query("ALTER TABLE `tbl_wishlist` ADD UNIQUE INDEX `uidx_customer_product` (`customerID`, `productSKU`)");
}

// 1. First, synchronize the session wishlist with the database to get latest SKUs
if (isset($_SESSION['user_id']) && !isset($action)) {
    $_SESSION['wishlist'] = [];
    $customerID = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT productSKU FROM tbl_wishlist WHERE customerID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $customerID);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $_SESSION['wishlist'][] = $row['productSKU'];
        }
        $stmt->close();
    }
}

// 2. Fetch valid items from database
$wishlist = isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];
$wishlist_items = [];

if (!empty($wishlist)) {
    $escaped_skus = array_map(function ($sku) use ($conn) {
        return "'" . $conn->real_escape_string($sku) . "'";
    }, $wishlist);
    $sku_list = implode(',', $escaped_skus);

    $query = "SELECT p.*, c.categoryName,
              (SELECT AVG(rating) FROM tbl_reviews WHERE productID_slug = p.slug) as avg_rating,
              (SELECT COUNT(*) FROM tbl_reviews WHERE productID_slug = p.slug) as total_reviews
              FROM tbl_product p 
              LEFT JOIN tbl_productCategory c ON p.categoryID = c.categoryID 
              WHERE p.slug IN ($sku_list) AND p.isActive = 1";

    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $wishlist_items[] = [
                'id' => $row['productID'],
                'sku' => $row['slug'],
                'name' => $row['productName'],
                'description' => $row['description'],
                'price' => $row['price'],
                'image' => $row['productImage'],
                'image2' => $row['productImage2'],
                'image3' => $row['productImage3'],
                'image4' => $row['productImage4'],
                'category' => $row['categoryName'],
                'sizeStocks' => $row['sizeStocks'],
                'avg_rating' => $row['avg_rating'],
                'total_reviews' => $row['total_reviews']
            ];
        }
    }

    // 3. Synchronize session ONLY IF it's the full page load (to avoid side effects on count-only calls)
    if (!isset($action)) {
        $valid_skus = array_column($wishlist_items, 'sku');
        $_SESSION['wishlist'] = $valid_skus;
    }
}

require '../html/wishlist.view.php';