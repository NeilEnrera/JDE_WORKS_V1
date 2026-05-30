<?php
session_start();
require_once '../../JDE_USER/backend/db_connection.php';

header('Content-Type: application/json');

// Auth check: Admin (1) or Production Supervisor (3)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Employee' || !in_array($_SESSION['access_level'], [1, 3])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    require_once '../../JDE_USER/backend/discount_helper.php';

    if ($action === 'verify_admin_password') {
        $password = $_POST['password'] ?? '';
        $userID = $_SESSION['user_id'] ?? 0;

        if (!$password || !$userID) {
            echo json_encode(['success' => false, 'message' => 'Missing data or session expired']);
            exit;
        }

        $stmt = $conn->prepare("SELECT password FROM tbl_employee WHERE employeeID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $orderID = $_POST['orderID'] ?? 0;
        if (!$orderID) {
            echo json_encode(['success' => false, 'message' => 'Missing order ID']);
            exit;
        }

        // Soft delete / Archiving logic
        $checkCol = $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'isActive'");
        if ($checkCol->num_rows == 0) {
            $conn->query("ALTER TABLE tbl_order ADD isActive TINYINT(1) DEFAULT 1");
        }

        $stmt = $conn->prepare("UPDATE tbl_order SET isActive = 0 WHERE orderID = ?");
        $stmt->bind_param("i", $orderID);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'update_status') {
        ob_start(); // Buffer any potential warnings or notices
        try {
            $orderID = $_POST['orderID'] ?? 0;
            $status = $_POST['status'] ?? '';
            $cancelReason = trim($_POST['cancelReason'] ?? '');

            if (!$orderID || !$status) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Missing order ID or status']);
                exit;
            }

            // Normalization: Ensure status matches whitelist casing
            $allowedStatuses = [
                'Pending', 'Paid', 'Downpayment', 'Processing', 'Awaiting Balance', 'Out for delivery', 
                'Ready for Pick Up', 'Delivered', 'Completed', 'Order Completed', 'Cancelled', 'Unpaid', 
                'Ready to Deliver', 'Return Items'
            ];

            // ── Dynamic Label Mapping ──
            // The frontend may send user-friendly labels for COD/COP orders.
            // We map these back to the canonical 'Awaiting Balance' status.
            $canonicalMapping = [
                'to be paid upon delivery' => 'Awaiting Balance',
                'to be paid upon pickup' => 'Awaiting Balance',
                'to be paid upon pick up' => 'Awaiting Balance'
            ];

            if (isset($canonicalMapping[strtolower($status)])) {
                $status = $canonicalMapping[strtolower($status)];
            }

            $foundStatus = null;
            foreach ($allowedStatuses as $template) {
                if (strcasecmp($status, $template) === 0) {
                    $foundStatus = $template;
                    break;
                }
            }

            if (!$foundStatus) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => "Invalid order status value: $status"]);
                exit;
            }
            $status = $foundStatus;

            // --- WORKFLOW ENFORCEMENT ---
            // 1. Fetch current payment and order state
            $stateStmt = $conn->prepare("SELECT o.orderStatus, o.paymentBalance, p.paymentStatus, pm.methodName 
                                       FROM tbl_order o 
                                       JOIN tbl_payments p ON o.orderID = p.orderID 
                                       JOIN tbl_paymentMethod pm ON p.paymentMethodID = pm.paymentMethodID
                                       WHERE o.orderID = ?");
            $stateStmt->bind_param("i", $orderID);
            $stateStmt->execute();
            $state = $stateStmt->get_result()->fetch_assoc();
            $stateStmt->close();

            if (!$state) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Order state not found.']);
                exit;
            }

            $isCOD = (stripos($state['methodName'], 'cash on delivery') !== false) || (stripos($state['methodName'], 'cash on pickup') !== false) || (stripos($state['methodName'], 'cash on pick up') !== false);
            $isPaid = (strtolower($state['paymentStatus']) === 'approved');
            $hasBalance = (float)$state['paymentBalance'] > 0;

            // Enforcement A: Cannot move to Processing if NOT Paid/Approved (unless COD/COP)
            if ($status === 'Processing' && !$isPaid && !$isCOD) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Cannot process order. Initial payment must be verified first.']);
                exit;
            }

            // Fetch balance payment status and payment option to refine enforcement
            $balStateStmt = $conn->prepare(
                "SELECT p.balancePaymentStatus, o.paymentOption FROM tbl_order o
                  LEFT JOIN tbl_payments p ON o.orderID = p.orderID
                 WHERE o.orderID = ? LIMIT 1"
            );
            $balStateStmt->bind_param("i", $orderID);
            $balStateStmt->execute();
            $balState = $balStateStmt->get_result()->fetch_assoc();
            $balStateStmt->close();

            $isFullPayment = (strtolower($balState['paymentOption'] ?? 'full') === 'full');
            $isBalanceApproved = (strtolower($balState['balancePaymentStatus'] ?? '') === 'approved');

            // Enforcement B: Cannot move to Delivery/Pickup/Completed if unpaid remaining balance exists
            // Exceptions: COD/COP orders, full-payment orders, or orders where balance is already approved
            $isCompletionStatus = in_array($status, ['Out for delivery', 'Ready for Pick Up', 'Delivered', 'Completed', 'Order Completed']);
            if ($isCompletionStatus && $hasBalance && !$isCOD && !$isFullPayment && !$isBalanceApproved) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Cannot progress. Customer has an unpaid remaining balance.']);
                exit;
            }

            $status_lower = strtolower($status);
            $updates = ["orderStatus = ?", "cancelReason = ?", "isAdminSeen = 1", "isSeen = 0"];
            $params = [$status, $cancelReason];
            $types = "ss";

            if ($status_lower === 'return items') {
                $updates[] = "returnReason = ?";
                $updates[] = "returnRemarks = ?";
                $params[] = trim($_POST['returnReason'] ?? '');
                $params[] = trim($_POST['returnRemarks'] ?? '');
                $types .= "ss";
            }

            // Timestamps
            if ($status_lower === 'processing') $updates[] = "processingAt = NOW()";
            elseif ($status_lower === 'awaiting balance') $updates[] = "awaitingBalanceAt = NOW()";
            elseif (in_array($status_lower, ['shipped', 'out for delivery', 'ready for pick up'])) $updates[] = "shippedAt = NOW()";
            elseif (in_array($status_lower, ['completed', 'order completed', 'delivered', 'picked up'])) {
                $updates[] = "completedAt = NOW()";
                $updPay = $conn->prepare("UPDATE tbl_payments SET paymentStatus = 'Approved' WHERE orderID = ?");
                $updPay->bind_param("i", $orderID);
                $updPay->execute();
                $updPay->close();
            }

            if (isset($_FILES['proofOfDelivery']) && $_FILES['proofOfDelivery']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../JDE_USER/assets/img/proofs/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $fileName = 'proof_' . $orderID . '_' . time() . '.' . strtolower(pathinfo($_FILES['proofOfDelivery']['name'], PATHINFO_EXTENSION));
                if (move_uploaded_file($_FILES['proofOfDelivery']['tmp_name'], $uploadDir . $fileName)) {
                    $updates[] = "proofOfDelivery = ?";
                    $params[] = 'assets/img/proofs/' . $fileName;
                    $types .= "s";
                }
            }

            $sql = "UPDATE tbl_order SET " . implode(", ", $updates) . " WHERE orderID = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

            $params[] = $orderID;
            $types .= "i";
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                if ($status === 'Cancelled' && strcasecmp($state['orderStatus'], 'Cancelled') !== 0) {
                    // Restore aggregate stock
                    $conn->query("UPDATE tbl_product p 
                        JOIN tbl_cartItem ci ON p.productID = ci.productID 
                        SET p.stocks = p.stocks + ci.quantity 
                        WHERE ci.cartID = (SELECT cartID FROM tbl_order WHERE orderID = $orderID)");

                    // Restore per-size stock in sizeStocks JSON
                    $ciRes = $conn->query("SELECT ci.productID, ci.size, ci.quantity FROM tbl_cartItem ci 
                        WHERE ci.cartID = (SELECT cartID FROM tbl_order WHERE orderID = $orderID)
                        AND ci.size IS NOT NULL AND ci.productID IS NOT NULL");
                    while ($ci = $ciRes->fetch_assoc()) {
                        $pid = (int)$ci['productID'];
                        $sz  = $conn->real_escape_string($ci['size']);
                        $qty = (int)$ci['quantity'];
                        // Atomically increment the qty field inside the JSON object for this size
                        $conn->query("UPDATE tbl_product 
                            SET sizeStocks = JSON_SET(
                                sizeStocks,
                                '$.\"{$sz}\".qty',
                                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(sizeStocks, '$.\"{$sz}\".qty')), 0) + {$qty}
                            )
                            WHERE productID = {$pid} AND JSON_CONTAINS_PATH(sizeStocks, 'one', '$.\"{$sz}\"')");
                    }
                }
                // Background Email Sending (Fast & Non-blocking)
                $notifiableStatuses = ['shipped', 'out for delivery', 'delivered', 'completed', 'ready for pick up', 'awaiting balance'];
                if (in_array(strtolower($status), $notifiableStatuses)) {
                    try {
                        require_once '../../JDE_USER/backend/email_helper.php';
                        $orderQuery = $conn->prepare("SELECT o.cartID, c.email, c.firstName, c.lastName FROM tbl_order o JOIN tbl_customer c ON o.customerID = c.customerID WHERE o.orderID = ?");
                        $orderQuery->bind_param("i", $orderID);
                        $orderQuery->execute();
                        if ($orderRow = $orderQuery->get_result()->fetch_assoc()) {
                            // Fetch items for the email
                            $itemsRes = $conn->query("SELECT ci.*, p.productName, p.productImage FROM tbl_cartItem ci LEFT JOIN tbl_product p ON ci.productID = p.productID WHERE ci.cartID = {$orderRow['cartID']}");
                            $items = [];
                            while($item = $itemsRes->fetch_assoc()) $items[] = $item;

                            EmailHelper::dispatchAsync('order_update', [
                                'to' => $orderRow['email'],
                                'name' => $orderRow['firstName'].' '.$orderRow['lastName'],
                                'orderID' => $orderID,
                                'status' => $status,
                                'items' => $items
                            ]);
                        }
                    } catch (Exception $e) {} 
                }
                
                ob_clean();
                $responseData = ['success' => true];
                
                // Fetch the actual current state from DB after update to ensure frontend sync is perfect
                $verifyStmt = $conn->prepare("SELECT proofOfDelivery FROM tbl_order WHERE orderID = ?");
                $verifyStmt->bind_param("i", $orderID);
                $verifyStmt->execute();
                $finalState = $verifyStmt->get_result()->fetch_assoc();
                $verifyStmt->close();

                $responseData['proofOfDelivery'] = $finalState['proofOfDelivery'] ?? null;
                
                echo json_encode($responseData);
            } else {
                throw new Exception($stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update_stock') {
        $productID = intval($_POST['productID'] ?? 0);
        $newStock = intval($_POST['newStock'] ?? 0);

        if (!$productID) {
            echo json_encode(['success' => false, 'message' => 'Missing product ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE tbl_product SET stocks = ? WHERE productID = ?");
        $stmt->bind_param("ii", $newStock, $productID);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'newStock' => $newStock]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get_details') {
        $orderID = intval($_POST['orderID'] ?? 0);

        if (!$orderID) {
            echo json_encode(['success' => false, 'message' => 'Invalid Order ID']);
            exit;
        }

        // Fetch order basic info first to ensure it exists
        $orderStmt = $conn->prepare("SELECT cartID FROM tbl_order WHERE orderID = ?");
        $orderStmt->bind_param("i", $orderID);
        $orderStmt->execute();
        $orderBase = $orderStmt->get_result()->fetch_assoc();
        $orderStmt->close();

        if (!$orderBase) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        $cartID = $orderBase['cartID'];

        // Mark as seen when viewed
        $conn->query("UPDATE tbl_order SET isAdminSeen = 1 WHERE orderID = $orderID");

        // Fetch Order Items with robust joins
        $itemsStmt = $conn->prepare("
            SELECT ci.*, 
                   COALESCE(p.productName, 'Custom Tailoring') as productName,
                   COALESCE(p.productImage, ci.productImage) as productImage,
                   cat.categoryName,
                   ci.isPreOrder,
                   cs.neck, cs.shoulder, cs.armhole, cs.bicep, cs.wrist, cs.sleeveLength, cs.chest, cs.waist, cs.hips, cs.shirtLength, cs.crotch, cs.thigh, cs.knee, cs.legOpening, cs.pantsLength
            FROM tbl_cartItem ci
            LEFT JOIN tbl_customizeSize cs ON ci.customizeID = cs.customizeID
            LEFT JOIN tbl_product p ON COALESCE(ci.productID, cs.productID) = p.productID
            LEFT JOIN tbl_productCategory cat ON p.categoryID = cat.categoryID
            WHERE ci.cartID = ?
        ");
        $itemsStmt->bind_param("i", $cartID);
        $itemsStmt->execute();
        $itemsRes = $itemsStmt->get_result();

        $items = [];
        while ($row = $itemsRes->fetch_assoc()) {
            $customDetails = [];
            $customSpecs = null;
            if ($row['customizeID']) {
                $customSpecs = [];
                $fields = ['neck', 'shoulder', 'armhole', 'bicep', 'wrist', 'sleeveLength', 'chest', 'waist', 'hips', 'shirtLength', 'crotch', 'thigh', 'knee', 'legOpening', 'pantsLength'];
                foreach ($fields as $f) {
                    if (isset($row[$f]) && $row[$f] > 0) {
                        $label = ucfirst(preg_replace('/(?<!^)[A-Z]/', ' $0', $f));
                        $customDetails[] = "$label: " . $row[$f] . "in";
                        $customSpecs[$f] = $row[$f];
                    }
                }
            }

            $items[] = [
                'cartItemID' => $row['cartItemID'] ?? $row['itemID'] ?? 0,
                'productID' => $row['productID'],
                'productName' => $row['productName'],
                'productImage' => $row['productImage'],
                'categoryName' => $row['categoryName'] ?? 'Bespoke',
                'price' => floatval($row['price']),
                'quantity' => intval($row['quantity']),
                'size' => $row['size'] ?? null,
                'isCustom' => !empty($customDetails),
                'isPreOrder' => (int)($row['isPreOrder'] ?? 0),
                'customDetails' => implode(', ', $customDetails),
                'customSpecs' => $customSpecs
            ];
        }
        $itemsStmt->close();

        // Fetch Payment Details
        $payStmt = $conn->prepare("
            SELECT pay.*, pm.methodName 
            FROM tbl_payments pay
            LEFT JOIN tbl_paymentMethod pm ON pay.paymentMethodID = pm.paymentMethodID
            WHERE pay.orderID = ?
            LIMIT 1
        ");
        $payStmt->bind_param("i", $orderID);
        $payStmt->execute();
        $payRes = $payStmt->get_result();
        $payRow = $payRes->fetch_assoc();
        $payStmt->close();

        $paymentInfo = null;
        if ($payRow) {
            $paymentInfo = [
                'method' => $payRow['methodName'] ?? 'N/A',
                'status' => $payRow['paymentStatus'],
                'reference' => $payRow['referenceNumber'],
                'receipt' => $payRow['proofOfPayment'],
                'uploadedAt' => $payRow['dateCreated'],
                'downPayment' => floatval($payRow['downPaymentAmount'] ?? 0),
                'totalAmount' => floatval($payRow['fullPaymentAmount'] ?? 0),
                'balanceReceipt' => $payRow['balanceProofOfPayment'] ?? null,
                'balanceReference' => $payRow['balanceReferenceNumber'] ?? null,
                'balanceStatus' => $payRow['balancePaymentStatus'] ?? null,
                'balanceUploadedAt' => $payRow['balancePaidAt'] ?? null
            ];
        }

        echo json_encode([
            'success' => true,
            'items' => $items,
            'payment' => $paymentInfo
        ]);
        exit;
    }

    if ($action === 'verify_payment') {
        $orderID = intval($_POST['orderID'] ?? 0);
        $decision = $_POST['decision'] ?? ''; // 'approved' or 'rejected'

        if (!$orderID || !in_array($decision, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        // Fetch current order status AND paymentOption to determine correct new status
        $statusQuery = $conn->prepare("SELECT orderStatus, paymentOption FROM tbl_order WHERE orderID = ?");
        $statusQuery->bind_param("i", $orderID);
        $statusQuery->execute();
        $orderRow = $statusQuery->get_result()->fetch_assoc();
        $currentStatus = $orderRow['orderStatus'] ?? '';
        $paymentOption = strtolower($orderRow['paymentOption'] ?? 'full');
        $statusQuery->close();

        // Ensure verifiedAt column exists
        $chk = $conn->query("SHOW COLUMNS FROM `tbl_payments` LIKE 'verifiedAt'");
        if ($chk->num_rows == 0) {
            $conn->query("ALTER TABLE tbl_payments ADD verifiedAt DATETIME DEFAULT NULL");
        }

        // Ensure balance columns exist in tbl_payments
        $chkBal = $conn->query("SHOW COLUMNS FROM `tbl_payments` LIKE 'paymentBalance'");
        if ($chkBal->num_rows == 0) {
            $conn->query("ALTER TABLE tbl_payments ADD paymentBalance DECIMAL(10,2) DEFAULT NULL");
        }
        $chkDown = $conn->query("SHOW COLUMNS FROM `tbl_payments` LIKE 'downPaymentAmount'");
        if ($chkDown->num_rows == 0) {
            $conn->query("ALTER TABLE tbl_payments ADD downPaymentAmount DECIMAL(10,2) DEFAULT 0.00");
        }

        // Determine the correct order status after approval:
        // - Downpayment orders -> 'Downpayment' (balance still pending)
        // - Full payment orders -> 'Paid'
        $isDownpayment = in_array($paymentOption, ['half', 'down', 'downpayment', '50%']);
        $newOrderStatus = $isDownpayment ? 'Downpayment' : 'Paid';

        // Update payment record
        if ($decision === 'approved') {
            // 1. Fetch current downpayment amount to apply to balances
            $getDP = $conn->prepare("SELECT downPaymentAmount FROM tbl_payments WHERE orderID = ?");
            $getDP->bind_param("i", $orderID);
            $getDP->execute();
            $dpRes = $getDP->get_result()->fetch_assoc();
            $dpAmount = (float) ($dpRes['downPaymentAmount'] ?? 0);
            $getDP->close();

            // 2. Update tbl_payments (Set to Approved and deduct balance)
            $stmt = $conn->prepare("UPDATE tbl_payments SET paymentStatus = 'Approved', verifiedAt = NOW(), paymentBalance = paymentBalance - ?, downPaymentAmount = 0 WHERE orderID = ?");
            $stmt->bind_param("di", $dpAmount, $orderID);
            $stmt->execute();
            $stmt->close();

            // 3. Sync tbl_order balance
            $stmtSync = $conn->prepare("UPDATE tbl_order SET paymentBalance = paymentBalance - ? WHERE orderID = ?");
            $stmtSync->bind_param("di", $dpAmount, $orderID);
            $stmtSync->execute();
            $stmtSync->close();

            // 4. Set the correct order status based on payment type
            $status_check = strtolower(trim($currentStatus));
            if (in_array($status_check, ['pending', 'unpaid', 'unverified', ''])) {
                $stmt2 = $conn->prepare("UPDATE tbl_order SET orderStatus = ?, paidAt = NOW(), isSeen = 0 WHERE orderID = ?");
                $stmt2->bind_param("si", $newOrderStatus, $orderID);
                $stmt2->execute();
                $stmt2->close();
            } else {
                // Even if not pending, mark as unseen so user sees the "Approved" badge
                $stmt2 = $conn->prepare("UPDATE tbl_order SET isSeen = 0 WHERE orderID = ?");
                $stmt2->bind_param("i", $orderID);
                $stmt2->execute();
                $stmt2->close();
            }
        } else {
            $stmt = $conn->prepare("UPDATE tbl_payments SET paymentStatus = 'Rejected', verifiedAt = NOW() WHERE orderID = ?");
            $stmt->bind_param("i", $orderID);
            $stmt->execute();
            $stmt->close();

            $stmt2 = $conn->prepare("UPDATE tbl_order SET orderStatus = 'Unpaid', isSeen = 0 WHERE orderID = ?");
            $stmt2->bind_param("i", $orderID);
            $stmt2->execute();
            $stmt2->close();
        }

        echo json_encode(['success' => true, 'paymentStatus' => ($decision === 'approved' ? 'Approved' : 'Rejected'), 'newOrderStatus' => ($decision === 'approved' ? $newOrderStatus : 'Unpaid')]);
        exit;
    }

    if ($action === 'verify_balance') {
        $orderID = intval($_POST['orderID'] ?? 0);
        $decision = $_POST['decision'] ?? ''; // 'approved' or 'rejected'

        if (!$orderID || !in_array($decision, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        if ($decision === 'approved') {
            // Update tbl_payments: balancePaymentStatus to Approved, balancePaidAt to NOW()
            // Set paymentBalance to 0 in both tbl_payments and tbl_order
            $stmt = $conn->prepare("UPDATE tbl_payments SET balancePaymentStatus = 'Approved', balancePaidAt = NOW(), paymentBalance = 0 WHERE orderID = ?");
            $stmt->bind_param("i", $orderID);
            $stmt->execute();
            $stmt->close();

            $stmtSync = $conn->prepare("UPDATE tbl_order SET paymentBalance = 0, isSeen = 0 WHERE orderID = ?");
            $stmtSync->bind_param("i", $orderID);
            $stmtSync->execute();
            $stmtSync->close();
        } else {
            $stmt = $conn->prepare("UPDATE tbl_payments SET balancePaymentStatus = 'Rejected' WHERE orderID = ?");
            $stmt->bind_param("i", $orderID);
            $stmt->execute();
            $stmt->close();

            $stmtSync = $conn->prepare("UPDATE tbl_order SET isSeen = 0 WHERE orderID = ?");
            $stmtSync->bind_param("i", $orderID);
            $stmtSync->execute();
            $stmtSync->close();
        }

        echo json_encode(['success' => true, 'balanceStatus' => ($decision === 'approved' ? 'Approved' : 'Rejected')]);
        exit;
    }

    if ($action === 'verify_admin') {
        $password = $_POST['password'] ?? '';
        $employeeID = $_SESSION['user_id'] ?? 0;
        $username = $_SESSION['username'] ?? '';

        // Fetch employee password (check by ID or username for robustness)
        $stmt = $conn->prepare("SELECT password FROM tbl_employee WHERE employeeID = ? OR (userName = ? AND userName <> '')");
        $stmt->bind_param("is", $employeeID, $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password. Please enter your correct admin password.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Session error: Admin account not found. Please re-login.']);
        }
        exit;
    }

    if ($action === 'update_discount_rule') {
        $prodID = intval($_POST['productID'] ?? 0);
        $pct = isset($_POST['discountPct']) ? floatval($_POST['discountPct']) : null;
        $minQty = isset($_POST['minQty']) ? intval($_POST['minQty']) : null;
        $employeeID = $_SESSION['user_id'] ?? 1;

        if (!$prodID) {
            echo json_encode(['success' => false, 'message' => 'Invalid Product ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE tbl_product SET customDiscountPercent = ?, customMinQty = ? WHERE productID = ?");
        $stmt->bind_param("dii", $pct, $minQty, $prodID);
        
        if ($stmt->execute()) {
            // Log action
            $details = "Updated discount for Product #$prodID: " . ($pct !== null ? "$pct% at $minQty+ qty" : "Reset to default");
            $log = $conn->prepare("INSERT INTO tbl_admin_logs (employeeID, actionType, actionDetails) VALUES (?, 'DISCOUNT_UPDATE', ?)");
            $log->bind_param("is", $employeeID, $details);
            $log->execute();
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit;
    }

    if ($action === 'get_all_products') {
        $res = $conn->query("SELECT productID, productName, price, productImage, customDiscountPercent, customMinQty, categoryID FROM tbl_product WHERE isActive = 1 ORDER BY productName ASC");
        $products = [];
        while ($row = $res->fetch_assoc()) $products[] = $row;
        echo json_encode(['success' => true, 'products' => $products]);
        exit;
    }


    if ($action === 'recalculate_discount') {
        $orderID = intval($_POST['orderID'] ?? 0);
        if (!$orderID) {
            echo json_encode(['success' => false, 'message' => 'Invalid Order ID']);
            exit;
        }

        // 1. Fetch Order Items and their current Product Rules
        $sql = "SELECT ci.quantity, ci.price as basePrice, p.customDiscountPercent, p.customMinQty, o.paymentOption
                FROM tbl_cartItem ci
                JOIN tbl_order o ON ci.cartID = o.cartID
                LEFT JOIN tbl_product p ON ci.productID = p.productID
                WHERE o.orderID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderID);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $totalBasePrice = 0;
        $totalDiscountAmount = 0;
        $paymentOption = 'full';

        while ($item = $res->fetch_assoc()) {
            $paymentOption = $item['paymentOption'];
            $qty = $item['quantity'];
            $price = $item['basePrice'];
            
            $totalBasePrice += ($price * $qty);
            
            // Re-calculate using current product rules
            $discount = DiscountSystem::calculateItemDiscount($qty, $price, $item);
            $totalDiscountAmount += $discount['amount'];
        }
        $stmt->close();

        $newTotalPrice = $totalBasePrice - $totalDiscountAmount;
        $discountPct = ($totalBasePrice > 0) ? round(($totalDiscountAmount / $totalBasePrice) * 100) : 0;

        // 2. Fetch current payment status to adjust balance correctly
        $payStmt = $conn->prepare("SELECT paymentStatus, downPaymentAmount, fullPaymentAmount FROM tbl_payments WHERE orderID = ?");
        $payStmt->bind_param("i", $orderID);
        $payStmt->execute();
        $payData = $payStmt->get_result()->fetch_assoc();
        $payStmt->close();

        $isVerified = (strtolower($payData['paymentStatus'] ?? '') === 'approved');
        
        // If not verified, the balance is the new total. 
        // If verified (downpayment paid), new balance = new total - old downpayment amount already "approved"
        // Note: In your system, when a downpayment is approved, paymentBalance is already reduced.
        // Let's use a simpler approach: recalculate balance based on (NewTotal - AmountActuallyPaid)
        
        $newBalance = $newTotalPrice;
        if ($isVerified) {
             // If verified, we assume the initial commitment (downpayment or full) was paid.
             // We need to know how much was actually "removed" from the balance.
             // Looking at verify_payment, you deduct downPaymentAmount from paymentBalance.
             // So amountPaid = (OriginalTotal - CurrentBalance)
             $oldOrder = $conn->query("SELECT totalPrice, paymentBalance FROM tbl_order WHERE orderID = $orderID")->fetch_assoc();
             $amountPaid = floatval($oldOrder['totalPrice']) - floatval($oldOrder['paymentBalance']);
             $newBalance = $newTotalPrice - $amountPaid;
        }

        // 3. Update Tables
        $conn->begin_transaction();
        try {
            $updOrder = $conn->prepare("UPDATE tbl_order SET totalPrice = ?, discountAmount = ?, discountPercentage = ?, paymentBalance = ? WHERE orderID = ?");
            $updOrder->bind_param("dddii", $newTotalPrice, $totalDiscountAmount, $discountPct, $newBalance, $orderID);
            $updOrder->execute();

            $updPay = $conn->prepare("UPDATE tbl_payments SET paymentBalance = ? WHERE orderID = ?");
            $updPay->bind_param("di", $newBalance, $orderID);
            $updPay->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'newTotal' => $newTotalPrice, 'newDiscount' => $totalDiscountAmount]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'apply_manual_discount') {
        $orderID = intval($_POST['orderID'] ?? 0);
        $discountPct = floatval($_POST['discountPercentage'] ?? 0);
        
        if (!$orderID || $discountPct < 0 || $discountPct > 50) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters (Max 50%)']);
            exit;
        }

        // 1. Fetch current order data
        $stmt = $conn->prepare("SELECT cartID, totalPrice, discountAmount, paymentBalance FROM tbl_order WHERE orderID = ?");
        $stmt->bind_param("i", $orderID);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Calculate base price (Price before existing discount)
        $baseTotal = floatval($order['totalPrice']) + floatval($order['discountAmount']);
        $newDiscountAmount = $baseTotal * ($discountPct / 100);
        $newTotalPrice = $baseTotal - $newDiscountAmount;
        
        // Adjust balance
        $amountPaid = floatval($order['totalPrice']) - floatval($order['paymentBalance']);
        $newBalance = $newTotalPrice - $amountPaid;

        // 2. Update Tables
        $conn->begin_transaction();
        try {
            $updOrder = $conn->prepare("UPDATE tbl_order SET totalPrice = ?, discountAmount = ?, discountPercentage = ?, paymentBalance = ? WHERE orderID = ?");
            $updOrder->bind_param("dddii", $newTotalPrice, $newDiscountAmount, $discountPct, $newBalance, $orderID);
            $updOrder->execute();

            $updPay = $conn->prepare("UPDATE tbl_payments SET paymentBalance = ? WHERE orderID = ?");
            $updPay->bind_param("di", $newBalance, $orderID);
            $updPay->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'newTotal' => $newTotalPrice, 'newDiscount' => $newDiscountAmount]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>