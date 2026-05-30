<?php
session_start();
require_once 'db_connection.php';

// Migration for Remaining Balance Payment
$checkBalanceCols = $conn->query("SHOW COLUMNS FROM `tbl_payments` LIKE 'balanceProofOfPayment'");
if ($checkBalanceCols->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_payments 
        ADD COLUMN balanceProofOfPayment VARCHAR(500) NULL,
        ADD COLUMN balanceReferenceNumber VARCHAR(100) NULL,
        ADD COLUMN balancePaymentStatus VARCHAR(20) NULL DEFAULT 'Pending',
        ADD COLUMN balancePaidAt DATETIME NULL");
}

$userType = $_SESSION['user_type'] ?? '';

// Security Fix: Ensure only 'Customer' type can access this page to prevent cross-role IDOR
if ($userType !== 'Customer' || !isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header("Location: login.php");
    exit();
}

$search_query = trim($_GET['order_id'] ?? '');

// UX Improvement: If the customer pastes "ORD-1025", strip "ORD-" so it evaluates as numeric
if (stripos($search_query, 'ORD-') === 0) {
    $search_query = substr($search_query, 4);
}

$order_details = null;
$order_items = [];
$error_message = '';
$success_message = '';

// Handle Remaining Balance Receipt Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_balance_receipt') {
    $orderID = intval($_POST['order_id']);
    $referenceNumber = trim($_POST['reference_number']);
    
    // Security Check: Verify order belongs to customer and is in a state that allows balance payment
    // Allowed statuses: 'Awaiting Balance', 'Processing' (early payment), 'Downpayment' (after downpayment verified)
    $stmt = $conn->prepare("SELECT o.orderID, p.paymentID FROM tbl_order o JOIN tbl_payments p ON o.orderID = p.orderID WHERE o.orderID = ? AND o.customerID = ? AND o.orderStatus IN ('Awaiting Balance', 'Processing', 'Downpayment')");
    $stmt->bind_param("ii", $orderID, $_SESSION['user_id']);
    $stmt->execute();
    $orderCheck = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($orderCheck) {
        if (isset($_FILES['balance_receipt']) && $_FILES['balance_receipt']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['balance_receipt']['tmp_name'];
            $fileName = $_FILES['balance_receipt']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = 'balance_' . $orderID . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/uploads/receipts/';
            
            if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0777, true);
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $proofPath = 'backend/uploads/receipts/' . $newFileName;
                $updateStmt = $conn->prepare("UPDATE tbl_payments SET balanceProofOfPayment = ?, balanceReferenceNumber = ?, balancePaymentStatus = 'Pending', balancePaidAt = NOW() WHERE orderID = ?");
                $updateStmt->bind_param("ssi", $proofPath, $referenceNumber, $orderID);
                if ($updateStmt->execute()) {
                    $success_message = "Balance payment receipt uploaded successfully! Waiting for admin verification.";
                } else {
                    $error_message = "Failed to update payment record.";
                }
                $updateStmt->close();
            } else {
                $error_message = "Failed to move uploaded file.";
            }
        } else {
            $error_message = "Please select a valid receipt image.";
        }
    } else {
        $error_message = "Unauthorized or invalid order status for balance payment.";
    }
}

// Handle Initial Deposit Receipt Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_initial_receipt') {
    $orderID = intval($_POST['order_id']);
    $referenceNumber = trim($_POST['reference_number']);
    
    $stmt = $conn->prepare("SELECT o.orderID, p.paymentID FROM tbl_order o JOIN tbl_payments p ON o.orderID = p.orderID WHERE o.orderID = ? AND o.customerID = ? AND p.proofOfPayment IS NULL");
    $stmt->bind_param("ii", $orderID, $_SESSION['user_id']);
    $stmt->execute();
    $orderCheck = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($orderCheck) {
        if (isset($_FILES['initial_receipt']) && $_FILES['initial_receipt']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['initial_receipt']['tmp_name'];
            $fileName = $_FILES['initial_receipt']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = 'deposit_' . $orderID . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/uploads/receipts/';
            
            if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0777, true);
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $proofPath = 'backend/uploads/receipts/' . $newFileName;
                $updateStmt = $conn->prepare("UPDATE tbl_payments SET proofOfPayment = ?, referenceNumber = ?, paymentStatus = 'Pending' WHERE orderID = ?");
                $updateStmt->bind_param("ssi", $proofPath, $referenceNumber, $orderID);
                if ($updateStmt->execute()) {
                    $success_message = "Initial deposit receipt uploaded successfully! Waiting for admin verification.";
                } else {
                    $error_message = "Failed to update payment record.";
                }
                $updateStmt->close();
            } else {
                $error_message = "Failed to move uploaded file.";
            }
        } else {
            $error_message = "Please select a valid receipt image.";
        }
    } else {
        $error_message = "Unauthorized or receipt already exists.";
    }
}

// If a specific order ID or Token is searched
if (!empty($search_query)) {
    // Determine if query is numeric ID or Alphanumeric Token
    if (is_numeric($search_query)) {
        $stmt = $conn->prepare("
            SELECT o.*, c.firstName, c.lastName, p.paymentStatus, p.downPaymentAmount, p.paymentBalance, p.dateCreated as paymentDate, p.balancePaidAt, p.balanceProofOfPayment, p.balanceReferenceNumber, p.balancePaymentStatus, crt.dateCreated as orderPlacedDate, pm.methodName,
                   (SELECT COUNT(*) FROM tbl_cartItem ci WHERE ci.cartID = o.cartID AND ci.customizeID IS NOT NULL AND ci.customizeID <> 0) as customItemsCount
            FROM tbl_order o
            JOIN tbl_customer c ON o.customerID = c.customerID
            JOIN tbl_cart crt ON o.cartID = crt.cartID
            LEFT JOIN tbl_payments p ON o.orderID = p.orderID
            LEFT JOIN tbl_paymentMethod pm ON p.paymentMethodID = pm.paymentMethodID
            WHERE o.orderID = ? AND o.customerID = ?
        ");
        $stmt->bind_param("ii", $search_query, $_SESSION['user_id']);
    } else {
        $stmt = $conn->prepare("
            SELECT o.*, c.firstName, c.lastName, p.paymentStatus, p.downPaymentAmount, p.paymentBalance, p.dateCreated as paymentDate, p.balancePaidAt, p.balanceProofOfPayment, p.balanceReferenceNumber, p.balancePaymentStatus, crt.dateCreated as orderPlacedDate, pm.methodName,
                   (SELECT COUNT(*) FROM tbl_cartItem ci WHERE ci.cartID = o.cartID AND ci.customizeID IS NOT NULL AND ci.customizeID <> 0) as customItemsCount
            FROM tbl_order o
            JOIN tbl_customer c ON o.customerID = c.customerID
            JOIN tbl_cart crt ON o.cartID = crt.cartID
            LEFT JOIN tbl_payments p ON o.orderID = p.orderID
            LEFT JOIN tbl_paymentMethod pm ON p.paymentMethodID = pm.paymentMethodID
            WHERE o.orderToken = ? AND o.customerID = ?
        ");
        $stmt->bind_param("si", $search_query, $_SESSION['user_id']);
    }
    
    $stmt->execute();
    $order_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order_details) {
        // SECURITY: Always use a generic message to avoid confirming ID/Token existence or ownership
        $error_message = "Order not found. Please check your tracking number and try again.";
    } else {
        // Ensure productImage snapshot column exists
        $conn->query("ALTER TABLE tbl_cartItem ADD COLUMN IF NOT EXISTS productImage VARCHAR(500) NULL DEFAULT NULL");

        // Fetch order items
        $stmt = $conn->prepare("
            SELECT ci.*,
                   COALESCE(p.productName, 'Custom Tailoring') as productName,
                   COALESCE(p.productImage, ci.productImage) as productImage,
                   cs.productID as customProductID,
                   p.slug as productSlug,
                   ci.isPreOrder,
                   cs.neck, cs.shoulder, cs.armhole, cs.bicep, cs.wrist, cs.sleeveLength, cs.chest, cs.waist, cs.hips, cs.shirtLength, cs.crotch, cs.thigh, cs.knee, cs.legOpening, cs.pantsLength
            FROM tbl_cartItem ci
            LEFT JOIN tbl_customizeSize cs ON ci.customizeID = cs.customizeID
            LEFT JOIN tbl_product p ON COALESCE(ci.productID, cs.productID) = p.productID
            WHERE ci.cartID = ?
        ");
        $stmt->bind_param("i", $order_details['cartID']);
        $stmt->execute();
        $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Mark as seen (ownership already verified above)
        $seenStmt = $conn->prepare("UPDATE tbl_order SET isSeen = 1 WHERE orderID = ?");
        $seenStmt->bind_param("i", $order_details['orderID']);
        $seenStmt->execute();
        $seenStmt->close();
    }
}

// Fetch user's recent orders
$user_orders = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT o.orderID, o.totalPrice, o.orderStatus, crt.dateCreated,
               (SELECT p.slug FROM tbl_cartItem ci 
                JOIN tbl_product p ON ci.productID = p.productID 
                WHERE ci.cartID = o.cartID LIMIT 1) as productSlug
        FROM tbl_order o
        JOIN tbl_cart crt ON o.cartID = crt.cartID
        WHERE o.customerID = ?
        ORDER BY crt.dateCreated DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<script>
    window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>
<?php
require '../html/track-order.view.php';