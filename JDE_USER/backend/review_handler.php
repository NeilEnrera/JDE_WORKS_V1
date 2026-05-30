<?php
// Prevent any HTML output by buffering and catching errors
ob_start();
session_start();
require_once 'db_connection.php';

// Check database connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

header('Content-Type: application/json');

// Ensure 'size' column exists in tbl_reviews
$conn->query("ALTER TABLE tbl_reviews ADD COLUMN IF NOT EXISTS size VARCHAR(255) NULL DEFAULT NULL");

$action = $_REQUEST['action'] ?? '';

if ($action === 'submit_review') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to post a review.']);
        exit;
    }

    $customerID = $_SESSION['user_id'];
    $customerName = $_SESSION['name'] ?? 'Customer';
    $productID_slug = $_POST['product_id'] ?? '';
    // We'll use the slug for now as an identifier, but in a real DB we'd map this to tbl_product.ID
    // For simplicity with your current array-based product mapping in ordering.php:
    $productID = 0; // Mock ID or map from slug
    $orderID = (int) ($_POST['order_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $fit = $_POST['fit'] ?? '';
    $size = $_POST['size'] ?? '';
    $comment = $_POST['comment'] ?? '';

    if ($rating < 1 || $rating > 5 || empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Rating and comment are required.']);
        exit;
    }

    // Duplicate Review Check: one review per customer per order item
    $dupCheck = $conn->prepare("SELECT reviewID FROM tbl_reviews WHERE customerID = ? AND orderID = ? AND productID_slug = ? LIMIT 1");
    $dupCheck->bind_param("iis", $customerID, $orderID, $productID_slug);
    $dupCheck->execute();
    $dupCheck->store_result();
    if ($dupCheck->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this item.']);
        $dupCheck->close();
        exit;
    }
    $dupCheck->close();

    $imagePath = null;
    if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['review_image']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, GIF, WebP allowed.']);
            exit;
        }
        $uploadDir = '../assets/img/reviews/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fileExt = pathinfo($_FILES['review_image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('rev_') . '.' . strtolower($fileExt);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['review_image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO tbl_reviews (productID_slug, orderID, customerID, customerName, rating, fit, size, comment, imagePath, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("siissssss", $productID_slug, $orderID, $customerID, $customerName, $rating, $fit, $size, $comment, $imagePath);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'submit_bulk_reviews') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to post reviews.']);
        exit;
    }

    $customerID = $_SESSION['user_id'];
    $customerName = $_SESSION['username'] ?? ($_SESSION['name'] ?? 'Customer');
    $orderID = (int) ($_POST['order_id'] ?? 0);
    $submittedItems = $_POST['reviews'] ?? [];
    
    if (empty($submittedItems)) {
        echo json_encode(['success' => false, 'message' => 'No reviews data received.']);
        exit;
    }

    $processedCount = 0;
    $errors = [];

    foreach ($submittedItems as $idx => $reviewData) {
        $productSlug = $reviewData['product_id'] ?? '';
        $rating = (int) ($reviewData['rating'] ?? 0);
        $fit = $reviewData['fit'] ?? '';
        $size = $reviewData['size'] ?? '';
        $comment = trim($reviewData['comment'] ?? '');

        // Skip items that haven't been rated or commented on
        if ($rating < 1 && empty($comment)) {
            continue;
        }

        if ($rating < 1 || empty($comment)) {
            $errors[] = "Rating and comment required for " . ($reviewData['product_name'] ?? "Item $idx");
            continue;
        }

        $imagePath = null;
        $fileKey = "review_image_$idx";
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES[$fileKey]['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Invalid image type for item $idx. Only JPG, PNG, GIF, WebP allowed.";
                // Skip image but still allow review text to be submitted
            } else {
                $uploadDir = '../assets/img/reviews/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $fileExt = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                $fileName = uniqid('rev_') . '_' . $idx . '.' . $fileExt;
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetFile)) {
                    $imagePath = $targetFile;
                }
            }
        }

        try {
            $stmt = $conn->prepare("INSERT INTO tbl_reviews (productID_slug, orderID, customerID, customerName, rating, fit, size, comment, imagePath, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("siissssss", $productSlug, $orderID, $customerID, $customerName, $rating, $fit, $size, $comment, $imagePath);
            
            if ($stmt->execute()) {
                $processedCount++;
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if ($processedCount > 0) {
        echo json_encode(['success' => true, 'message' => "$processedCount reviews posted successfully.", 'errors' => $errors]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No valid reviews were submitted.', 'errors' => $errors]);
    }
} elseif ($action === 'get_reviews') {
    $productID_slug = $_GET['product_id'] ?? '';

    try {
        // Get reviews
        $stmt = $conn->prepare("SELECT * FROM tbl_reviews WHERE productID_slug = ? ORDER BY dateCreated DESC");
        $stmt->bind_param("s", $productID_slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = $result->fetch_all(MYSQLI_ASSOC);

        // Calculate Stats
        $stats = [
            'avg_rating' => 0,
            'total' => count($reviews),
            'fit' => ['Small' => 0, 'True to Size' => 0, 'Large' => 0]
        ];

        if ($stats['total'] > 0) {
            $totalRating = 0;
            foreach ($reviews as $rev) {
                $totalRating += $rev['rating'];
                if (isset($stats['fit'][$rev['fit']])) {
                    $stats['fit'][$rev['fit']]++;
                }
            }
            $stats['avg_rating'] = round($totalRating / $stats['total'], 2);
        }

        echo json_encode(['success' => true, 'reviews' => $reviews, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'like_review') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to like reviews.']);
        exit;
    }
    $reviewID = (int) ($_POST['review_id'] ?? 0);
    if ($reviewID <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
        exit;
    }

    // Anti-Stuffing: track likes per session to prevent repeated likes
    if (!isset($_SESSION['liked_reviews'])) $_SESSION['liked_reviews'] = [];
    if (in_array($reviewID, $_SESSION['liked_reviews'])) {
        echo json_encode(['success' => false, 'message' => 'You have already liked this review.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE tbl_reviews SET likes = likes + 1 WHERE reviewID = ?");
        $stmt->bind_param("i", $reviewID);
        if ($stmt->execute()) {
            $_SESSION['liked_reviews'][] = $reviewID;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>