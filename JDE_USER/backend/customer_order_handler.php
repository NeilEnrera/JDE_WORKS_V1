<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'cancel_order') {
        $orderID = intval($_POST['orderID'] ?? 0);
        $customerID = $_SESSION['user_id'];

        if (!$orderID) {
            echo json_encode(['success' => false, 'message' => 'Missing Order ID']);
            exit;
        }

        // 1. Verify ownership and verify if its still eligible for cancellation
        $stmt = $conn->prepare("SELECT orderStatus, cartID, distributionMethod, 
               (SELECT COUNT(*) FROM tbl_cartItem WHERE cartID = tbl_order.cartID AND customizeID IS NOT NULL AND customizeID <> 0) as customCount 
               FROM tbl_order WHERE orderID = ? AND customerID = ?");
        $stmt->bind_param("ii", $orderID, $customerID);
        $stmt->execute();
        $res = $stmt->get_result();
        $orderData = $res->fetch_assoc();
        $stmt->close();

        if (!$orderData) {
            echo json_encode(['success' => false, 'message' => 'Order not found or access denied.']);
            exit;
        }

        $currentStatus = strtolower($orderData['orderStatus']);
        $dist = strtolower($orderData['distributionMethod'] ?? '');
        $hasCustom = ($orderData['customCount'] > 0);

        $canCancel = false;
        $errorMsg = "This order can no longer be cancelled through the system.";

        if ($hasCustom) {
            // Rule 3: Custom-size Products - Only allowed when Pending/Unpaid
            if (in_array($currentStatus, ['pending', 'unpaid', ''])) {
                $canCancel = true;
            } else {
                $errorMsg = "This is a custom-made order currently in production. An automatic cancellation is no longer available, and a cancellation fee will apply. Please contact our business directly for cancellation and refund processing.";
            }
        } else {
            // Pre-made Products
            $terminalStatuses = ['completed', 'delivered', 'cancelled', 'returned'];
            if (in_array($currentStatus, $terminalStatuses)) {
                $canCancel = false;
                $errorMsg = "This order is already finalized or cancelled.";
            } else {
                if (strpos($dist, 'pick') !== false) {
                    // Rule 2: Pick-up Method - Allowed at any time before completion
                    $canCancel = true;
                } else {
                    // Rule 1: Delivery Method - Allowed up until "Out for Delivery"
                    $forbiddenDelivery = ['out for delivery', 'on the way', 'delivered', 'completed'];
                    if (!in_array($currentStatus, $forbiddenDelivery)) {
                        $canCancel = true;
                    } else {
                        $errorMsg = "This order is already out for delivery and can no longer be cancelled.";
                    }
                }
            }
        }

        if (!$canCancel) {
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        }

        $conn->begin_transaction();

        try {
            // 2. Update status to Cancelled
            $upd = $conn->prepare("UPDATE tbl_order SET orderStatus = 'Cancelled', cancelReason = 'Cancelled by Customer', isAdminSeen = 0 WHERE orderID = ?");
            $upd->bind_param("i", $orderID);
            $upd->execute();
            $upd->close();

            // 3. Mark payment as Rejected/Cancelled
            $updPay = $conn->prepare("UPDATE tbl_payments SET paymentStatus = 'Rejected' WHERE orderID = ?");
            $updPay->bind_param("i", $orderID);
            $updPay->execute();
            $updPay->close();

            // 4. RESTORE STOCK
            $cartID = $orderData['cartID'];
            $itemsStmt = $conn->prepare("SELECT productID, quantity, size FROM tbl_cartItem WHERE cartID = ?");
            $itemsStmt->bind_param("i", $cartID);
            $itemsStmt->execute();
            $itemsFull = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $itemsStmt->close();

            foreach ($itemsFull as $item) {
                if ($item['productID']) {
                    $pid = $item['productID'];
                    $qtyToRestore = (int)$item['quantity'];
                    $size = $item['size'];

                    // Lock and fetch current stock
                    $stockStmt = $conn->prepare("SELECT stocks, sizeStocks FROM tbl_product WHERE productID = ? FOR UPDATE");
                    $stockStmt->bind_param("i", $pid);
                    $stockStmt->execute();
                    $pData = $stockStmt->get_result()->fetch_assoc();
                    $stockStmt->close();

                    if ($pData) {
                        $newTotal = (int)$pData['stocks'] + $qtyToRestore;
                        $sizeStocks = json_decode($pData['sizeStocks'] ?? '{}', true);
                        
                        if ($size && isset($sizeStocks[$size])) {
                            if (is_array($sizeStocks[$size])) {
                                $sizeStocks[$size]['qty'] = (int)($sizeStocks[$size]['qty'] ?? 0) + $qtyToRestore;
                            } else {
                                $sizeStocks[$size] = (int)$sizeStocks[$size] + $qtyToRestore;
                            }
                        }

                        $newJson = json_encode($sizeStocks);
                        $restoreUpd = $conn->prepare("UPDATE tbl_product SET stocks = ?, sizeStocks = ? WHERE productID = ?");
                        $restoreUpd->bind_param("isi", $newTotal, $newJson, $pid);
                        $restoreUpd->execute();
                        $restoreUpd->close();
                    }
                }
            }

            $conn->commit();
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Cancellation failed: ' . $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
?>
