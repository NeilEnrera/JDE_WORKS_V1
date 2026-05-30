<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - JDE Works of Our Hands</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/navbar.css">
</head>

<body>
    <?php
    $activePage = 'orders';
    include '../backend/navbar.php';
    ?>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" href="../css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/orders.css?v=<?php echo time(); ?>">

    <div class="orders-section">
        <div class="container">
            <h1 class="personal-center-title">Personal Center</h1>

            <div class="row g-4">
                <div class="col-lg-3">
                    <?php include 'fragments/user_sidebar.php'; ?>
                </div>

                <div class="col-lg-9">
                    <div class="order-tabs">
                        <a href="orders.php"
                            class="tab-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All
                            Orders</a>
                        <a href="orders.php?status=pending"
                            class="tab-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Unpaid</a>
                        <a href="orders.php?status=paid"
                            class="tab-link <?php echo $status_filter === 'paid' ? 'active' : ''; ?>">Paid</a>
                        <a href="orders.php?status=processing"
                            class="tab-link <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">Processing</a>
                        <a href="orders.php?status=out_for_delivery"
                            class="tab-link <?php echo $status_filter === 'out_for_delivery' ? 'active' : ''; ?>">Out for Delivery</a>
                        <a href="orders.php?status=history"
                            class="tab-link <?php echo $status_filter === 'history' ? 'active' : ''; ?>">Order
                            History</a>
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="empty-orders">
                            <div class="empty-icon"><i class="bi bi-bag-x"></i></div>
                            <h3>No orders found</h3>
                            <p class="text-muted mb-4">You haven't placed any orders yet or no orders match this filter.</p>
                            <a href="product.php" class="btn btn-primary px-4 py-2"
                                style="background: var(--primary-gold); border: none; border-radius: 50px;">Start
                                Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list-container">
                            <div class="orders-list">
                                <?php foreach ($orders as $order): ?>
                                    <div class="order-card">
                                        <div class="order-card-header">
                                            <div>
                                                <span class="order-id">Order #<?php echo $order['orderID']; ?></span>
                                                <span class="ms-2 order-date"><?php echo date('M d, Y', strtotime($order['orderDate'])); ?></span>
                                            </div>
                                            <span class="status-badge <?php echo getStatusClass($order['orderStatus']); ?>">
                                                <?php echo $order['orderStatus']; ?>
                                            </span>
                                        </div>
                                        <div class="order-body">
                                            <?php
                                            $stmt_items = $conn->prepare("SELECT ci.*, p.productName, p.productImage, p.slug FROM tbl_cartItem ci JOIN tbl_product p ON ci.productID = p.productID WHERE ci.cartID = ? LIMIT 3");
                                            $stmt_items->bind_param("i", $order['cartID']);
                                            $stmt_items->execute();
                                            $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
                                            $stmt_items->close();
                                            foreach ($items as $item): ?>
                                                <div class="item-preview">
                                                    <img src="<?php echo resolveProductPath($item['productImage']); ?>" class="item-img" alt="">
                                                    <span class="item-name"><?php echo htmlspecialchars($item['productName']); ?></span>
                                                    <span class="ms-auto text-muted small">x<?php echo $item['quantity']; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="order-footer">
                                            <div class="total-price">
                                                <span class="text-muted small">Total:</span>
                                                <span class="fw-bold fs-5">₱<?php echo number_format($order['totalPrice'], 2); ?></span>
                                            </div>
                                            <div class="actions d-flex gap-2">
                                                <?php
                                                $s_low = strtolower($order['orderStatus']);
                                                $d_low = strtolower($order['distributionMethod'] ?? '');
                                                $is_c = (int) ($order['customCount'] ?? 0) > 0;
                                                $can_c = false;
                                                if ($is_c) { $can_c = in_array($s_low, ['pending', 'unpaid', '']); } 
                                                else {
                                                    if (strpos($d_low, 'pick') !== false) { $can_c = !in_array($s_low, ['completed', 'delivered', 'cancelled']); } 
                                                    else {
                                                        $forb_del = ['out for delivery', 'on the way', 'delivered', 'completed', 'cancelled'];
                                                        $can_c = !in_array($s_low, $forb_del);
                                                    }
                                                }
                                                ?>
                                                <?php if ($can_c): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger px-3 rounded-pill" onclick="confirmCancel(<?php echo $order['orderID']; ?>, <?php echo $is_c ? 'true' : 'false'; ?>)">Cancel Order</button>
                                                <?php endif; ?>
                                                <a href="track-order.php?order_id=<?php echo $order['orderID']; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill" style="border-color: var(--primary-gold); color: var(--primary-gold);">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination Controls -->
                            <?php if ($total_pages > 1): ?>
                                <div class="pagination-container">
                                    <a href="?status=<?php echo $status_filter; ?>&page=<?php echo max(1, $current_page - 1); ?>" 
                                       class="pagination-btn prev-next <?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                    
                                    <?php
                                    $start_p = max(1, $current_page - 2);
                                    $end_p = min($total_pages, $start_p + 4);
                                    if ($end_p - $start_p < 4) $start_p = max(1, $end_p - 4);
                                    
                                    if ($start_p > 1): ?>
                                        <a href="?status=<?php echo $status_filter; ?>&page=1" class="pagination-btn">1</a>
                                        <?php if ($start_p > 2): ?><span class="pagination-ellipsis px-2">...</span><?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_p; $i <= $end_p; $i++): ?>
                                        <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" 
                                           class="pagination-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($end_p < $total_pages): ?>
                                        <?php if ($end_p < $total_pages - 1): ?><span class="pagination-ellipsis px-2">...</span><?php endif; ?>
                                        <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $total_pages; ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
                                    <?php endif; ?>

                                    <a href="?status=<?php echo $status_filter; ?>&page=<?php echo min($total_pages, $current_page + 1); ?>" 
                                       class="pagination-btn prev-next <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="feedback-icon mb-3">
                        <i id="feedbackIcon" class="bi bi-check-circle-fill"
                            style="font-size: 3.5rem; color: #198754;"></i>
                    </div>
                    <h4 id="feedbackTitle" class="fw-bold mb-2">Success!</h4>
                    <p id="feedbackMessage" class="text-muted small">Action completed successfully.</p>
                    <button type="button" class="btn w-100 py-2 mt-3 text-white fw-bold shadow-sm"
                        data-bs-dismiss="modal" style="border-radius: 12px; background-color: #052b47; border: none;">GREAT</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimalist Cancellation Confirmation Modal -->
    <div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-sm" style="border-radius: 16px;">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-circle text-danger" style="font-size: 3.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #2d3436;">Cancel Order?</h4>
                    <p id="cancelModalMessage" class="text-secondary mb-4 small" style="line-height: 1.6;">Are you sure you want to cancel this
                        order? This action cannot be undone once confirmed.</p>
                    <div id="cancelPolicyNote" class="alert alert-warning border-0 small text-start d-none" style="border-radius: 12px; font-size: 0.82rem;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <span id="cancelPolicyText"></span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary w-100 py-2 fw-semibold"
                            data-bs-dismiss="modal" style="border-radius: 8px; font-size: 0.9rem; color: #636e72;">NO,
                            KEEP IT</button>
                        <button type="button" id="confirmCancelBtn" class="btn btn-danger w-100 py-2 fw-semibold"
                            style="border-radius: 8px; font-size: 0.9rem; background-color: #eb4d4b; border: none;">YES,
                            CANCEL</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/orders_center.js?v=<?php echo time(); ?>"></script>
</body>

</html>