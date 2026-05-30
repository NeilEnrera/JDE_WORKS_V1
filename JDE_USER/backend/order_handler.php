<?php
session_start();
require_once 'db_connection.php';
require_once 'discount_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Double-submission protection
    if (isset($_SESSION['is_ordering'])) {
        die("Your order is already being processed. Please wait or check your email for confirmation.");
    }
    $_SESSION['is_ordering'] = true;
    // Basic Order Data from Session (Step 2) and POST (Step 3)
    $details = $_SESSION['checkout_details'] ?? [];
    $customerID = $_SESSION['user_id'];

    // Distribution Method (from Step 2)
    $distributionMethod = $details['distribution_method'] ?? 'delivery';

    // Payment Method (from Step 3)
    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    // Contact Info (from Step 2)
    $firstName = $details['first_name'] ?? '';
    $lastName = $details['last_name'] ?? '';
    $email = $details['email'] ?? '';
    $phone = $details['phone'] ?? '';

    // Shipping Info (if delivery, from Step 2)
    $address = $details['address'] ?? null;
    $barangay = $details['barangay'] ?? '';
    $city = $details['city'] ?? null;
    $province = $details['province'] ?? '';
    $zip = $details['zip'] ?? null;

    // Pickup Info (if pickup, from Step 2)
    $pickupDate = $details['pickup_date'] ?? null;

    // Cart Validation
    if (empty($_SESSION['cart'])) {
        unset($_SESSION['is_ordering']);
        header('Location: cart.php');
        exit();
    }

    // Check if it's a custom order to apply downpayment and validation logic
    $isCustomOrder = false;
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['selected']) && !$item['selected'])
            continue;
        if (isset($item['custom']) && $item['custom'] === true) {
            $isCustomOrder = true;
            break;
        }
    }

    // --- Backend Validation Check ---
    if ($distributionMethod === 'delivery') {
        if (empty($address) || empty($province) || empty($city) || empty($barangay) || empty($zip)) {
            $_SESSION['checkout_error'] = "Shipping address is incomplete. Please ensure all fields are filled.";
            unset($_SESSION['is_ordering']);
            header('Location: checkout.php');
            exit();
        }
    } elseif ($distributionMethod === 'pickup') {
        if (!$isCustomOrder && empty($pickupDate)) {
            $_SESSION['checkout_error'] = "Please select a pickup date.";
            unset($_SESSION['is_ordering']);
            header('Location: checkout.php');
            exit();
        }
    }



    // ── DDL Safety: Ensure schema columns exist BEFORE starting transaction ──
    // ALTER TABLE causes an implicit commit in MySQL, so these must run OUTSIDE
    // the transaction to avoid silently committing partial data on failure.
    $conn->query("ALTER TABLE tbl_cartItem ADD COLUMN IF NOT EXISTS size VARCHAR(50) NULL DEFAULT NULL");
    $conn->query("ALTER TABLE tbl_cartItem ADD COLUMN IF NOT EXISTS productImage VARCHAR(500) NULL DEFAULT NULL");
    $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'distributionMethod'")->num_rows === 0
        && $conn->query("ALTER TABLE tbl_order ADD distributionMethod VARCHAR(20) DEFAULT 'delivery'");
    $conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'originalShippingFee'")->num_rows === 0
        && $conn->query("ALTER TABLE tbl_order ADD originalShippingFee DECIMAL(10,2) DEFAULT NULL");
    if ($conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'discountAmount'")->num_rows === 0) {
        $conn->query("ALTER TABLE tbl_order ADD discountAmount DECIMAL(10,2) DEFAULT 0.00");
    }
    if ($conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'discountPercentage'")->num_rows === 0) {
        $conn->query("ALTER TABLE tbl_order ADD discountPercentage INT DEFAULT 0");
    }
    if ($conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'paymentOption'")->num_rows === 0) {
        $conn->query("ALTER TABLE tbl_order ADD paymentOption VARCHAR(20) DEFAULT 'full'");
    }
    if ($conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'shippingFee'")->num_rows === 0) {
        $conn->query("ALTER TABLE tbl_order ADD shippingFee DECIMAL(10,2) DEFAULT 0.00");
    }
    if ($conn->query("SHOW COLUMNS FROM `tbl_order` LIKE 'subtotal'")->num_rows === 0) {
        $conn->query("ALTER TABLE tbl_order ADD subtotal DECIMAL(10,2) DEFAULT 0.00");
    }

    $conn->begin_transaction();

    try {
        // Backend Validation: Distribution & Payment checks can be added here if needed
        // (COD/Pickup for balance is now allowed for all order types)

        // Group items by productID to determine per-product bulk discounts
        $productGroups = [];
        foreach ($_SESSION['cart'] as $idx => $item) {
            if (isset($item['selected']) && !$item['selected'])
                continue;
            
            $pId = $item['id'] ?? 'custom_' . $idx;
            $unitPrice = floatval($item['price'] ?? 0);
            
            // Re-verify unit price from DB if not custom
            if (!$item['custom']) {
                $stmt = $conn->prepare("SELECT price, sizeStocks FROM tbl_product WHERE productID = ? OR slug = ? LIMIT 1");
                $stmt->bind_param("ss", $pId, $pId);
                $stmt->execute();
                if ($row = $stmt->get_result()->fetch_assoc()) {
                    $unitPrice = floatval($row['price']);
                    $sizeStocks = json_decode($row['sizeStocks'] ?? '{}', true);
                    $size = $item['size'] ?? 'Standard';
                    if (isset($sizeStocks[$size]) && is_array($sizeStocks[$size]) && isset($sizeStocks[$size]['price']) && $sizeStocks[$size]['price'] > 0) {
                        $unitPrice = floatval($sizeStocks[$size]['price']);
                    }
                }
                $stmt->close();
            }

            if (!isset($productGroups[$pId])) {
                $productGroups[$pId] = [
                    'qty' => 0, 
                    'price' => $unitPrice,
                    'isCustom' => (bool)$item['custom']
                ];
            }
            $productGroups[$pId]['qty'] += (int) $item['quantity'];
        }

        // Calculate total discounts using centralized system
        $totalDiscountAmount = 0;
        $maxPct = 0;
        foreach ($productGroups as $pid => $group) {
            $pData = null;
            if (!$group['isCustom']) {
                $stmt = $conn->prepare("SELECT customDiscountPercent, customMinQty FROM tbl_product WHERE productID = ? OR slug = ? LIMIT 1");
                $stmt->bind_param("ss", $pid, $pid);
                $stmt->execute();
                $pData = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
            
            $calc = DiscountSystem::calculateItemDiscount($group['qty'], $group['price'], $pData);
            $totalDiscountAmount += $calc['amount'];
            if ($calc['percentage'] > $maxPct) $maxPct = $calc['percentage'];
        }

        // 1. Calculate Total & Validate Stock/Price
        $totalPrice = 0;
        $discountAmount = 0;

        foreach ($_SESSION['cart'] as $idx => $item) {
            if (isset($item['selected']) && !$item['selected'])
                continue;

            $productRef = $item['id'];
            $isCustom = isset($item['custom']) && $item['custom'] === true;

            $itemBasePrice = 0;

            if ($productRef && !$isCustom) {
                // Security Fix: Re-verify price and stock status
                $vStmt = $conn->prepare("SELECT price, stocks, productName, sizeStocks FROM tbl_product WHERE productID = ? OR slug = ? LIMIT 1 FOR UPDATE");
                $vStmt->bind_param("ss", $productRef, $productRef);
                $vStmt->execute();
                $vRes = $vStmt->get_result();
                if ($pData = $vRes->fetch_assoc()) {
                    // Update session price to real price if it was tampered
                    // Also use size-specific price if it exists
                    $realPrice = (float) $pData['price'];
                    $sizeStocks = json_decode($pData['sizeStocks'] ?? '{}', true);
                    $orderedSize = $item['size'] ?? null;
                    if ($orderedSize && isset($sizeStocks[$orderedSize]) && is_array($sizeStocks[$orderedSize]) && isset($sizeStocks[$orderedSize]['price']) && (float) $sizeStocks[$orderedSize]['price'] > 0) {
                        $realPrice = (float) $sizeStocks[$orderedSize]['price'];
                    }
                    $itemBasePrice = $realPrice;

                    // Stock Validation Check
                    $orderedQty = (int) $item['quantity'];
                    $availableStock = (int) $pData['stocks'];

                    // Check size-specific stock if applicable
                    $sizeStocks = json_decode($pData['sizeStocks'] ?? '{}', true);
                    $orderedSize = $item['size'] ?? null;
                    $sizeStockAvailable = true;
                    $sizeQtyAvailable = $availableStock; // default to total stock for error message
                $isItemPreOrder = $item['isPreOrder'] ?? false;
                if (!$isItemPreOrder) {
                    if ($orderedSize && isset($sizeStocks[$orderedSize])) {
                        $qtyInSize = is_array($sizeStocks[$orderedSize]) ? ($sizeStocks[$orderedSize]['qty'] ?? 0) : $sizeStocks[$orderedSize];
                        $sizeQtyAvailable = (int) $qtyInSize;
                        if ($qtyInSize < $orderedQty) {
                            $sizeStockAvailable = false;
                        }
                    }

                    if (!$sizeStockAvailable) {
                        throw new Exception("Insufficient stock for item: " . $pData['productName'] . " (Size: $orderedSize). Only $sizeQtyAvailable available. If you need more, please use the PRE-ORDER option.");
                    }
                    if ($availableStock < $orderedQty) {
                        throw new Exception("Insufficient stock for item: " . $pData['productName'] . ". Only $availableStock available.");
                    }
                }
            } else {
                    throw new Exception("Generic Error: One of your items is no longer available.");
                }
                $vStmt->close();
            } else {
                // For custom items, we use the price calculated during the customization step
                $itemBasePrice = $item['price'];
            }

            $itemTotal = $itemBasePrice * $item['quantity'];
            $pId = $item['id'] ?? 'custom_' . $idx;
            $productGroups[$pId]['total_value'] = ($productGroups[$pId]['total_value'] ?? 0) + $itemTotal;
            $totalPrice += $itemTotal;
            
            // Store the verified base price back into the session item so the second loop uses it
            $_SESSION['cart'][$idx]['verified_price'] = $itemBasePrice;
        }

        $grossSubtotal = $totalPrice; // Store for DB snapshot before discount and shipping

        // Apply the pre-calculated discounts from DiscountSystem
        $discountAmount = $totalDiscountAmount;
        $discountPercentage = $maxPct;
        $totalPrice -= $discountAmount;

        // Calculate Shipping Fee
        $shippingFee = 0;
        if ($distributionMethod === 'delivery') {
            // Use the fee calculated by shipping_api.php if available, fallback to 150
            $shippingFee = $_SESSION['calculated_shipping_fee'] ?? 150;
            $totalPrice += $shippingFee;
        }

        // 2. Create Cart Entry
        $stmt = $conn->prepare("INSERT INTO tbl_cart (customerID, cartStatus) VALUES (?, 'Ordered')");
        $stmt->bind_param("i", $customerID);
        $stmt->execute();
        $cartID = $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO tbl_cartItem (cartID, productID, customizeID, quantity, price, size, productImage, isPreOrder) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        // Prepare product lookup for snapshots
        $lookupStmt = $conn->prepare("SELECT productID, productImage FROM tbl_product WHERE slug = ? OR productID = ? LIMIT 1");

        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['selected']) && !$item['selected'])
                continue;

            $productRef = $item['id'];
            $resolvedProductID = null;
            $resolvedProductImage = null;


            // Try to resolve string IDs to integer productIDs
            if (!is_numeric($productRef) && !empty($productRef)) {
                $lookupStmt->bind_param("ss", $productRef, $productRef);
                $lookupStmt->execute();
                $result = $lookupStmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $resolvedProductID = (int) $row['productID'];
                    $resolvedProductImage = $row['productImage'];
                }
            } elseif (is_numeric($productRef) && (int) $productRef > 0) {
                $resolvedProductID = (int) $productRef;
                // Look up the image directly from the product table
                $imgLookup = $conn->prepare("SELECT productImage FROM tbl_product WHERE productID = ? LIMIT 1");
                $imgLookup->bind_param("i", $resolvedProductID);
                $imgLookup->execute();
                $imgResult = $imgLookup->get_result();
                if ($imgRow = $imgResult->fetch_assoc()) {
                    $resolvedProductImage = $imgRow['productImage'];
                }
                $imgLookup->close();
            }

            // Use the image from the cart session as fallback
            if (empty($resolvedProductImage) && !empty($item['image'])) {
                $resolvedProductImage = $item['image'];
            }

            $customizeID = isset($item['customize_id']) ? $item['customize_id'] : null;

            // ... (Custom measurements logic remains same) ...
            if (isset($item['custom']) && $item['custom'] === true && !empty($item['details'])) {
                $m = $item['details'];
                $neck = isset($m['neck']) ? (float) $m['neck'] : 0;
                $shoulder = isset($m['shoulder']) ? (float) $m['shoulder'] : 0;
                $armhole = isset($m['armhole']) ? (float) $m['armhole'] : 0;
                $bicep = isset($m['bicep']) ? (float) $m['bicep'] : 0;
                $wrist = isset($m['wrist']) ? (float) $m['wrist'] : 0;
                $sleeveLength = isset($m['sleeveLength']) ? (float) $m['sleeveLength'] : 0;
                $chest = isset($m['chest']) ? (float) $m['chest'] : 0;
                $waist = isset($m['waist']) ? (float) $m['waist'] : 0;
                $hips = isset($m['hip']) ? (float) $m['hip'] : 0;
                $shirtLength = isset($m['length']) ? (float) $m['length'] : 0;
                $crotch = isset($m['crotch']) ? (float) $m['crotch'] : 0;
                $thigh = isset($m['thigh']) ? (float) $m['thigh'] : 0;
                $knee = isset($m['knee']) ? (float) $m['knee'] : 0;
                $legOpening = isset($m['legOpening']) ? (float) $m['legOpening'] : 0;
                $pantsLength = isset($m['pantsLength']) ? (float) $m['pantsLength'] : 0;

                $custStmt = $conn->prepare("INSERT INTO tbl_customizeSize 
                    (productID, neck, shoulder, armhole, bicep, wrist, sleeveLength, chest, waist, hips, shirtLength, crotch, thigh, knee, legOpening, pantsLength) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if ($resolvedProductID !== null) {
                    $custStmt->bind_param("iddddddddddddddd", $resolvedProductID, $neck, $shoulder, $armhole, $bicep, $wrist, $sleeveLength, $chest, $waist, $hips, $shirtLength, $crotch, $thigh, $knee, $legOpening, $pantsLength);
                } else {
                    $nullPID = null;
                    $custStmt->bind_param("iddddddddddddddd", $nullPID, $neck, $shoulder, $armhole, $bicep, $wrist, $sleeveLength, $chest, $waist, $hips, $shirtLength, $crotch, $thigh, $knee, $legOpening, $pantsLength);
                }
                $custStmt->execute();
                $custStmt->close();
                $customizeID = $conn->insert_id;
            }

            // Use the verified price from the first loop to ensure consistency
            $itemPrice = $item['verified_price'] ?? $item['price'];

            $isPreOrderVal = ($item['isPreOrder'] ?? false) ? 1 : 0;
            $stmt->bind_param("iiiidssi", $cartID, $resolvedProductID, $customizeID, $item['quantity'], $itemPrice, $item['size'], $resolvedProductImage, $isPreOrderVal);
            $stmt->execute();
        }

        // Construct Shipping Address Snapshot
        $shippingAddressSnapshot = null;
        if ($distributionMethod === 'delivery') {
            $shippingAddressSnapshot = "$firstName $lastName\n$phone\n$address, $barangay, $city, $province, $zip";
        } else {
            $shippingAddressSnapshot = "Store Pickup (Date: $pickupDate)";
        }


        $originalShippingFee = $_SESSION['original_shipping_fee'] ?? null;

        $orderToken = 'JDE' . time() . '-' . mt_rand(1000, 9999);
        $origFee = ($originalShippingFee !== null) ? (float) $originalShippingFee : null;
        $dAmt = (float) $discountAmount;
        $dPct = (int) $discountPercentage;
        $paymentOptionValue = $_POST['payment_option'] ?? 'half';

        // Check if overall order has pre-orders
        $isPreOrderOrder = 0;
        foreach($_SESSION['cart'] as $ci) {
            if (isset($ci['selected']) && $ci['selected'] && ($ci['isPreOrder'] ?? false)) {
                $isPreOrderOrder = 1;
                break;
            }
        }

        $stmt = $conn->prepare("INSERT INTO tbl_order (customerID, cartID, totalPrice, orderStatus, shippingAddress, distributionMethod, originalShippingFee, shippingFee, subtotal, orderToken, discountAmount, discountPercentage, paymentOption, isPreOrder) VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidssdddsdisi", $customerID, $cartID, $totalPrice, $shippingAddressSnapshot, $distributionMethod, $origFee, $shippingFee, $grossSubtotal, $orderToken, $dAmt, $dPct, $paymentOptionValue, $isPreOrderOrder);
        if (!$stmt->execute()) {
            throw new Exception("Order Creation Failed: " . $stmt->error);
        }
        $orderID = $conn->insert_id;

        // 5. Create Payment
        // --- Payment Processing ---
        // Every order now requires a GCash receipt for the initial DP/Full payment
        $paymentMethodID = ($paymentMethod === 'gcash') ? 1 : 2; // 1=GCash, 2=COD/Pickup
        $proofOfPayment = null;

        // Handle GCash receipt upload (MANDATORY for all orders as per DP requirement)
        if (isset($_FILES['gcash_receipt']) && $_FILES['gcash_receipt']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['gcash_receipt']['tmp_name'];
            $fileName = $_FILES['gcash_receipt']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Sanitize filename
            $newFileName = 'gcash_' . $orderID . '_' . time() . '.' . $fileExtension;

            // Use absolute path for moving the file
            $uploadFileDir = __DIR__ . '/uploads/receipts/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            chmod($uploadFileDir, 0777); 
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $proofOfPayment = 'backend/uploads/receipts/' . $newFileName;
            } else {
                throw new Exception("Failed to save your receipt. Please try again or contact support.");
            }
        } else {
            throw new Exception("Proof of Downpayment (GCash receipt) is required to confirm your order.");
        }

        // Unified Payment Logic: 50% Downpayment vs Full Payment
        $downPayment = 0;
        $paymentBalance = $totalPrice;
        $paymentStatus = 'Pending'; // Always Pending for admin verification of the receipt

        $paymentOption = $_POST['payment_option'] ?? 'half';

        if ($paymentOption === 'full') {
            $downPayment = $totalPrice; // Full amount
        } else {
            $downPayment = $totalPrice * 0.5; // 50% downpayment
        }
        
        // paymentBalance remains as $totalPrice because the admin hasn't confirmed the payment yet.
        // Once admin confirms, they will subtract the downpayment from the balance.

        $referenceNumber = $_POST['reference_number'] ?? null;

        $stmt = $conn->prepare("INSERT INTO tbl_payments (orderID, customerID, paymentMethodID, paymentStatus, downPaymentAmount, paymentBalance, fullPaymentAmount, proofOfPayment, referenceNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisdddss", $orderID, $customerID, $paymentMethodID, $paymentStatus, $downPayment, $paymentBalance, $totalPrice, $proofOfPayment, $referenceNumber);

        try {
            if (!$stmt->execute()) {
                throw new Exception("Payment Recording Failed: " . $stmt->error);
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) { // Duplicate entry
                throw new Exception("This GCash Reference Number has already been used for another order. Please provide a valid, unique receipt.");
            }
            throw $e;
        }

        // ── STOCK REDUCTION: Runs AFTER all inserts succeed, so rollback works ──
        $stockLookupStmt = $conn->prepare("SELECT productID, productName FROM tbl_product WHERE productID = ? LIMIT 1");
        foreach ($_SESSION['cart'] as $cartItem) {
            if (isset($cartItem['selected']) && !$cartItem['selected'])
                continue;
            $isItemCustom = isset($cartItem['custom']) && $cartItem['custom'] === true;
            if ($isItemCustom || empty($cartItem['id']))
                continue;

            $productRef = $cartItem['id'];
            $resolvedID = is_numeric($productRef) ? (int) $productRef : null;
            if (!$resolvedID) {
                // Resolve slug to ID
                $slugRes = $conn->prepare("SELECT productID FROM tbl_product WHERE slug = ? LIMIT 1");
                $slugRes->bind_param("s", $productRef);
                $slugRes->execute();
                $slugRow = $slugRes->get_result()->fetch_assoc();
                $slugRes->close();
                $resolvedID = $slugRow ? (int) $slugRow['productID'] : null;
            }
            if (!$resolvedID)
                continue;

            // SKIP STOCK REDUCTION FOR PRE-ORDERS
            if ($cartItem['isPreOrder'] ?? false) {
                continue;
            }

            $sfStmt = $conn->prepare("SELECT stocks, sizeStocks FROM tbl_product WHERE productID = ? FOR UPDATE");
            $sfStmt->bind_param("i", $resolvedID);
            $sfStmt->execute();
            $sfData = $sfStmt->get_result()->fetch_assoc();
            $sfStmt->close();

            if ($sfData) {
                $deductQty = (int) $cartItem['quantity'];
                $newTotal = max(0, (int) $sfData['stocks'] - $deductQty);
                $szJson = json_decode($sfData['sizeStocks'] ?? '{}', true);
                $sz = $cartItem['size'] ?? null;
                if ($sz && isset($szJson[$sz])) {
                    if (is_array($szJson[$sz])) {
                        $szJson[$sz]['qty'] = max(0, (int) ($szJson[$sz]['qty'] ?? 0) - $deductQty);
                    } else {
                        $szJson[$sz] = max(0, (int) $szJson[$sz] - $deductQty);
                    }
                }
                $updJson = json_encode($szJson);
                $upd = $conn->prepare("UPDATE tbl_product SET stocks = ?, sizeStocks = ? WHERE productID = ?");
                $upd->bind_param("isi", $newTotal, $updJson, $resolvedID);
                $upd->execute();
                $upd->close();
            }
        }

        $conn->commit();

        // Selective Cart Clearing: Rebuild cart with ONLY UNselected items
        $remainingCart = [];
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['selected']) && !$item['selected']) {
                $remainingCart[] = $item;
            }
        }
        $_SESSION['cart'] = $remainingCart;

        // Update Cart Count
        $newCount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $newCount += $item['quantity'];
        }
        $_SESSION['cart_count'] = $newCount;

        unset($_SESSION['checkout_details']);
        unset($_SESSION['is_ordering']);
        $_SESSION['cart_just_ordered'] = true; // Flag for frontend to clear localStorage cart persistence

        // Redirect to digital receipt page
        // Redirect to digital receipt page using the secure token
        header("Location: receipt.php?token=$orderToken");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        unset($_SESSION['is_ordering']);

        // Log the detailed error server-side
        $errMsg = $e->getMessage();
        error_log("JDE Order Error: " . $errMsg . " | User ID: " . ($_SESSION['user_id'] ?? 'Unknown'));

        // Expose actual error for debugging (remove in production)
        $userFriendlyMsg = "DEBUG ERROR: " . $errMsg;

        $_SESSION['checkout_error'] = $userFriendlyMsg;
        header('Location: checkout.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>