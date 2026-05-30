<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

require_once 'db_connection.php';
require_once 'discount_helper.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'add':
        addToCart($conn);
        break;
    case 'count':
        echo json_encode(['success' => true, 'count' => $_SESSION['cart_count']]);
        break;
    case 'get':
        $justOrdered = isset($_SESSION['cart_just_ordered']) ? true : false;
        unset($_SESSION['cart_just_ordered']);
        
        $itemsWithDiscounts = calculateCartDiscounts($conn, $_SESSION['cart']);
        
        echo json_encode([
            'success' => true, 
            'items' => $itemsWithDiscounts, 
            'count' => $_SESSION['cart_count'],
            'just_ordered' => $justOrdered
        ]);
        break;
    case 'remove':
        removeFromCart($conn);
        break;
    case 'update':
        updateQuantity($conn);
        break;
    case 'toggle_selection':
        toggleSelection();
        break;
    case 'clear':
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) {
                // Keep the item if it's NOT selected
                return !(isset($item['selected']) && $item['selected']);
            });
            // Re-index to avoid gaps in indices
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            
            // Recalculate cart count
            $newCount = 0;
            foreach($_SESSION['cart'] as $item) {
                $newCount += (int)($item['quantity'] ?? 0);
            }
            $_SESSION['cart_count'] = $newCount;
        }
        echo json_encode(['success' => true, 'count' => $_SESSION['cart_count'], 'items' => $_SESSION['cart']]);
        break;
    case 'restore':
        restoreCart($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function addToCart($conn)
{
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!$input || !isset($input['name'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }

    $productID = isset($input['id']) ? $input['id'] : null;
    $isPreOrder = false; // Default to false, will be verified from DB
    $isCustom = isset($input['custom']) ? (bool)$input['custom'] : false;
    $price = 0;
    $maxStock = 999;
    $size = isset($input['size']) ? $input['size'] : 'Standard';

    // Security Fix: Fetch price, STOCKS, and Pre-Order status from database
    if ($productID && !$isCustom) {
        $stmt = $conn->prepare("SELECT price, productName, stocks, sizeStocks, (stocks <= 0) as canBePreOrder FROM tbl_product WHERE productID = ? OR slug = ? LIMIT 1");
        $stmt->bind_param("ss", $productID, $productID);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $price = (float)$row['price'];
            $maxStock = (int)$row['stocks'];
            
            // Re-verify Pre-Order status: only allow if client requested AND product supports it (e.g. stock is low/out)
            // Or better: check if the product is explicitly marked as pre-orderable in a hypothetical column.
            // Since we don't have an 'isPreOrderable' column yet, we'll check the logic used in Conversation ae83a302:
            // "Update order_handler.php to bypass stock validation for items marked as pre-orders"
            // For now, we'll trust the client's INTENT but only if the product actually exists.
            // A more robust way would be a 'preOrderEnabled' column.
            $isPreOrder = isset($input['preOrder']) ? (bool)$input['preOrder'] : false;

            // Size-specific stock AND PRICE check
            $sizeStocks = json_decode($row['sizeStocks'] ?? '{}', true);
            if (!empty($sizeStocks) && isset($sizeStocks[$size])) {
                $sizeData = $sizeStocks[$size];
                if (is_array($sizeData)) {
                    $qtyInSize = $sizeData['qty'] ?? 0;
                    $maxStock = (int)$qtyInSize;
                    
                    if (isset($sizeData['price']) && (float)$sizeData['price'] > 0) {
                        $price = (float)$sizeData['price'];
                    }
                } else {
                    $maxStock = (int)$sizeData;
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }
        $stmt->close();
    } else {
        $price = (float)($input['price'] ?? 0);
    }

    $quantityToAdd = isset($input['quantity']) ? (int) $input['quantity'] : 1;
    
    // Check if adding this will exceed stock (considering items already in cart)
    $currentInCart = 0;
    foreach ($_SESSION['cart'] as $cartItem) {
        if ($cartItem['id'] === $productID && $cartItem['size'] === $size && !$cartItem['custom']) {
            $currentInCart += $cartItem['quantity'];
        }
    }

    // ENFORCEMENT: Only allow skip-stock if isPreOrder is true AND it's a valid product
    if (!$isCustom && !$isPreOrder && ($currentInCart + $quantityToAdd) > $maxStock) {
        $quantityToAdd = $maxStock - $currentInCart;
        if ($quantityToAdd <= 0) {
            // If already at max, just return success with current count
            echo json_encode([
                'success' => true,
                'message' => 'Maximum quantity already in cart',
                'count' => $_SESSION['cart_count'],
                'cart' => $_SESSION['cart'],
                'reachedMax' => true
            ]);
            return;
        }
    }

    $item = [
        'id' => $productID ?? uniqid(),
        'name' => $input['name'],
        'price' => $price,
        'quantity' => $quantityToAdd,
        'size' => $size,
        'color' => isset($input['color']) ? $input['color'] : 'Default',
        'image' => isset($input['image']) ? $input['image'] : 'assets/img/unifrom.jpeg',
        'category' => isset($input['category']) ? $input['category'] : 'General',
        'custom' => $isCustom,
        'isPreOrder' => $isPreOrder,
        'selected' => true,
        'details' => isset($input['details']) ? $input['details'] : []
    ];

    // Check if item exists (same id AND characteristics)
    $found = false;
    foreach ($_SESSION['cart'] as &$cartItem) {
        // Merging Fix: check for name, size, AND color
        if (
            !$item['custom'] && !$cartItem['custom'] &&
            $cartItem['name'] === $item['name'] &&
            $cartItem['size'] === $item['size'] &&
            $cartItem['color'] === $item['color'] &&
            ($cartItem['isPreOrder'] ?? false) === $item['isPreOrder']
        ) {
            $cartItem['quantity'] += $item['quantity'];
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = $item;
    }

    updateCartCount();

    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart',
        'count' => $_SESSION['cart_count'],
        'cart' => $_SESSION['cart']
    ]);
}

function removeFromCart($conn)
{
    $index = isset($_POST['index']) ? (int) $_POST['index'] : -1;

    if ($index >= 0 && isset($_SESSION['cart'][$index])) {
        array_splice($_SESSION['cart'], $index, 1);
        updateCartCount();
        
        $itemsWithDiscounts = calculateCartDiscounts($conn, $_SESSION['cart']);
        $grandTotal = 0;
        foreach ($itemsWithDiscounts as $item) {
            if ($item['selected']) {
                $grandTotal += $item['discount']['finalTotal'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'count' => $_SESSION['cart_count'],
            'items' => $itemsWithDiscounts,
            'total' => $grandTotal,
            'totalFormatted' => number_format($grandTotal, 2)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
}

function updateQuantity($conn)
{
    $index = isset($_POST['index']) ? (int) $_POST['index'] : -1;
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

    if ($index >= 0 && isset($_SESSION['cart'][$index])) {
        $item = $_SESSION['cart'][$index];
        
        if (!$item['custom']) {
            $productID = $item['id'];
            $size = $item['size'];
            $maxStock = 999;

            $stmt = $conn->prepare("SELECT stocks, sizeStocks FROM tbl_product WHERE productID = ? OR slug = ? LIMIT 1");
            $stmt->bind_param("ss", $productID, $productID);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $maxStock = (int)$row['stocks'];
                $sizeStocks = json_decode($row['sizeStocks'] ?? '{}', true);
                if (!empty($sizeStocks) && isset($sizeStocks[$size])) {
                    $qtyInSize = is_array($sizeStocks[$size]) ? ($sizeStocks[$size]['qty'] ?? 0) : $sizeStocks[$size];
                    $maxStock = (int)$qtyInSize;
                }
            }
            $stmt->close();

            $isPreOrder = $item['isPreOrder'] ?? false;
            if ($quantity > $maxStock && !$isPreOrder) $quantity = $maxStock;
        }

        if ($quantity > 0) {
            $_SESSION['cart'][$index]['quantity'] = $quantity;
        } else {
            array_splice($_SESSION['cart'], $index, 1);
        }
        
        updateCartCount();
        
        $itemsWithDiscounts = calculateCartDiscounts($conn, $_SESSION['cart']);
        $grandTotal = 0;
        foreach ($itemsWithDiscounts as $item) {
            if ($item['selected']) {
                $grandTotal += $item['discount']['finalTotal'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'count' => $_SESSION['cart_count'],
            'items' => $itemsWithDiscounts,
            'quantity' => isset($_SESSION['cart'][$index]) ? $_SESSION['cart'][$index]['quantity'] : 0,
            'total' => $grandTotal,
            'totalFormatted' => number_format($grandTotal, 2)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
}

/**
 * calculateCartDiscounts
 * Helper to enrich cart items with discount info from the DiscountSystem.
 */
function calculateCartDiscounts($conn, $cartItems) {
    if (empty($cartItems)) return [];
    
    // 1. Group quantities by productID
    $productTotals = [];
    foreach ($cartItems as $item) {
        $pId = $item['id'] ?? 'custom';
        if (!isset($productTotals[$pId])) $productTotals[$pId] = 0;
        $productTotals[$pId] += (int)$item['quantity'];
    }

    $enrichedItems = [];
    foreach ($cartItems as $item) {
        $productData = null;
        if (!isset($item['custom']) || !$item['custom']) {
            $pid = $item['id'];
            $stmt = $conn->prepare("SELECT customDiscountPercent, customMinQty FROM tbl_product WHERE productID = ? OR slug = ? LIMIT 1");
            $stmt->bind_param("ss", $pid, $pid);
            $stmt->execute();
            $productData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        
        // Use the total quantity of this product in the cart for threshold check
        $totalQty = $productTotals[$item['id'] ?? 'custom'] ?? $item['quantity'];
        $item['discount'] = DiscountSystem::calculateItemDiscount($totalQty, $item['price'], $productData);
        
        // Recalculate amount for THIS row's quantity
        $pct = $item['discount']['percentage'];
        $item['discount']['amount'] = ($item['price'] * $item['quantity']) * ($pct / 100);
        $item['discount']['finalTotal'] = ($item['price'] * $item['quantity']) - $item['discount']['amount'];
        
        $enrichedItems[] = $item;
    }
    return $enrichedItems;
}

function updateCartCount()
{
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    $_SESSION['cart_count'] = $count;
}

function toggleSelection()
{
    $index = isset($_POST['index']) ? $_POST['index'] : '';
    $selected = isset($_POST['selected']) ? (bool)$_POST['selected'] : true;

    if ($index === 'all') {
        foreach ($_SESSION['cart'] as &$item) {
            $item['selected'] = $selected;
        }
        echo json_encode(['success' => true]);
        return;
    }

    $idx = (int)$index;
    if (isset($_SESSION['cart'][$idx])) {
        $_SESSION['cart'][$idx]['selected'] = $selected;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
}

function restoreCart($conn)
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['items']) || !is_array($input['items'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }

    $localItems = $input['items'];
    $validatedItems = [];

    // Security Fix: Re-validate prices for all items being restored from localStorage
    foreach ($localItems as $item) {
        $productID = $item['id'] ?? null;
        $isCustom = isset($item['custom']) ? (bool)$item['custom'] : false;
        
        if ($productID && !$isCustom) {
            $stmt = $conn->prepare("SELECT price, sizeStocks FROM tbl_product WHERE productID = ? OR slug = ? LIMIT 1");
            $stmt->bind_param("ss", $productID, $productID);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $basePrice = (float)$row['price'];
                $itemSize = $item['size'] ?? 'Standard';
                
                // Re-validate size-specific price
                $sizeStocks = json_decode($row['sizeStocks'] ?? '{}', true);
                if (!empty($sizeStocks) && isset($sizeStocks[$itemSize])) {
                    $sizeData = $sizeStocks[$itemSize];
                    if (is_array($sizeData) && isset($sizeData['price']) && (float)$sizeData['price'] > 0) {
                        $basePrice = (float)$sizeData['price'];
                    }
                }
                $item['price'] = $basePrice;
            }
            $stmt->close();
        }
        $validatedItems[] = $item;
    }
    
    // Simple restoration: Replace session cart with local cart if session is empty,
    // or merge them if you want to be fancy.
    if (empty($_SESSION['cart'])) {
        $_SESSION['cart'] = $validatedItems;
    } else {
        // Merge logic: Add only items not already in session (by ID)
        foreach ($validatedItems as $vItem) {
            $exists = false;
            foreach ($_SESSION['cart'] as $sessionItem) {
                if ($sessionItem['id'] === $vItem['id']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $_SESSION['cart'][] = $vItem;
            }
        }
    }

    updateCartCount();
    echo json_encode(['success' => true, 'count' => $_SESSION['cart_count']]);
}
?>