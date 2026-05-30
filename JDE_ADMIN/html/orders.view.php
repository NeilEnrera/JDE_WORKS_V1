<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../css/admin-common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/admin-management.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/orders.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title><?php echo $pageTitle ?? 'Management Hub'; ?></title>
</head>

<div class="admin-layout">
    <?php include __DIR__ . '/fragments/sidebar.php'; ?>

    <div class="main-content">


        <div class="container">
            <div class="page-header">
                <div class="header-info">
                    <h2>Management Hub</h2>
                    <p>Centralized control for customer orders</p>
                </div>
                <div class="header-actions" style="display: flex; gap: 12px;">
                    <button class="btn-bulk-discount" onclick="openDiscountAuthModal()">
                        <i class="bi bi-plus-circle"></i> Apply Discount
                    </button>
                </div>
            </div>

            <!-- =====================================================================
                 DISCOUNT OVERRIDE MODALS
            ===================================================================== -->
            <!-- Step 1: Authorization Modal (Manual Password) -->
            <div id="discountAuthModal" class="modal-overlay">
                <div class="modal small" style="border-radius: 15px; overflow: hidden; max-width: 450px;">
                    <div class="modal-header auth-header" style="background: #0b2e46; color: #fff; padding: 20px 25px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div
                                style="background: rgba(245, 158, 11, 0.2); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-percent" style="color: #f59e0b; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem; color: #f59e0b;">Authorize Discount</h3>
                                <p style="margin: 0; font-size: 0.75rem; opacity: 0.7;">Step 1: Identity Verification
                                </p>
                            </div>
                        </div>
                        <button class="modal-close" onclick="closeDiscountAuthModal()"
                            style="color: #fff; opacity: 0.5;">&times;</button>
                    </div>
                    <div class="modal-body" style="padding: 25px;">
                        <label
                            style="text-transform: uppercase; font-size: 0.7rem; font-weight: 800; color: #64748b; letter-spacing: 1px; margin-bottom: 12px; display: block;">Admin
                            Password Confirmation</label>

                        <div style="position: relative; margin-bottom: 20px;">
                            <i class="bi bi-shield-lock"
                                style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #f59e0b; font-size: 1.1rem;"></i>
                            <input type="password" id="jdeAuthPass" placeholder="Enter your password"
                                autocomplete="new-password"
                                style="width: 100%; padding: 12px 45px; border-radius: 12px; border: 2px solid #f1f5f9; font-size: 0.95rem; font-weight: 600;">
                            <button onclick="toggleDiscountPassword()"
                                style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 5px;">
                                <i id="passToggleIcon" class="bi bi-eye"></i>
                            </button>
                        </div>

                        <!-- Inline Error Message -->
                        <div id="jdeAuthError"
                            style="display: none; color: #ef4444; font-size: 0.8rem; font-weight: 700; margin-top: -10px; margin-bottom: 15px; display: flex; align-items: center; gap: 5px;">
                            <!-- Populated dynamically by JS -->
                        </div>

                        <div
                            style="background: #f8fafc; border-radius: 12px; padding: 15px; display: flex; gap: 12px; align-items: flex-start; border: 1px solid #e2e8f0;">
                            <i class="bi bi-info-circle"
                                style="color: #3b82f6; font-size: 1.1rem; margin-top: 2px;"></i>
                            <p style="margin: 0; font-size: 0.8rem; color: #475569; line-height: 1.5;">Verify your
                                authority to adjust the discount rules by entering your administrator password.</p>
                        </div>

                        <div style="margin-top: 25px; display: flex; gap: 12px;">
                            <button onclick="closeDiscountAuthModal()"
                                style="flex: 1; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-weight: 700; font-size: 0.85rem; cursor: pointer;">CANCEL</button>
                            <button onclick="jdeVerifyAccess()"
                                style="flex: 2; padding: 12px; border-radius: 12px; border: none; background: #0b2e46; color: #f59e0b; font-weight: 700; font-size: 0.85rem; cursor: pointer; box-shadow: 0 4px 12px rgba(11, 46, 70, 0.15);">VERIFY
                                & PROCEED</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Edit Discount Panel Modal -->
            <div id="discountPanelModal" class="modal-overlay" style="z-index: 4001;">
                <div class="modal large" style="max-width: 950px; border-radius: 15px; overflow: hidden;">
                    <div class="modal-header" style="background: #0b2e46; color: #fff; padding: 15px 25px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div
                                style="background: #f59e0b; width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-percent" style="color: #0b2e46; font-size: 1.1rem;"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem;">Discount Panel</h3>
                                <p style="margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.6);">Configure manual
                                    product overrides</p>
                            </div>
                        </div>
                        <button class="modal-close" onclick="closeDiscountPanelModal()"
                            style="color: #fff; opacity: 0.5;">&times;</button>
                    </div>
                    <div class="modal-body" style="padding: 0;">
                        <div style="display: flex; height: 520px;">
                            <!-- Sidebar: Product Selection -->
                            <div class="discount-sidebar-container" style="width: 320px;">
                                <div style="padding: 15px; background: #fff; border-bottom: 1px solid #eee;">
                                    <div class="discount-sidebar-search" style="padding: 6px 12px 6px 35px;">
                                        <i class="bi bi-search"></i>
                                        <input type="text" id="discountProdSearch" placeholder="Search product..."
                                            style="font-size: 0.8rem;">
                                    </div>
                                </div>

                                <!-- Category Scroller -->
                                <div class="category-scroller" id="discountCatScroller">
                                    <div class="cat-chip active" data-cat="all">All Items</div>
                                    <div class="cat-chip" data-cat="Men">Men's</div>
                                    <div class="cat-chip" data-cat="Women">Women's</div>
                                    <div class="cat-chip" data-cat="Bespoke">Custom</div>
                                </div>

                                <div id="discountProdList" style="flex: 1; overflow-y: auto; padding: 15px;">
                                    <!-- Products will be listed here -->
                                </div>
                            </div>

                            <!-- Main: Discount Config -->
                            <div id="discountConfigArea"
                                style="flex: 1; padding: 30px 40px; display: none; background: #fff;">
                                <div
                                    style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                                    <img id="discountProdImg" src=""
                                        style="width: 70px; height: 70px; border-radius: 12px; object-fit: cover; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                                    <div>
                                        <h4 id="discountProdName"
                                            style="margin: 0; font-size: 1.25rem; color: #0b2e46; font-weight: 800;">
                                            Product Name</h4>
                                        <p id="discountProdID"
                                            style="margin: 3px 0 0 0; font-size: 0.8rem; color: #64748b; font-weight: 600;">
                                        </p>
                                        <div id="discountProdBasePrice"
                                            style="margin-top: 5px; font-size: 0.85rem; font-weight: 700; color: #64748b;">
                                        </div>
                                    </div>
                                </div>

                                <div
                                    style="background: rgba(59, 130, 246, 0.05); border-left: 4px solid #3b82f6; padding: 12px 15px; border-radius: 0 8px 8px 0; margin-bottom: 25px; display: flex; gap: 10px; align-items: flex-start;">
                                    <i class="bi bi-info-circle-fill"
                                        style="color: #3b82f6; font-size: 1.1rem; margin-top: 1px;"></i>
                                    <p style="margin: 0; font-size: 0.8rem; color: #475569; line-height: 1.5;">
                                        <strong>How it works:</strong> The custom discount will only be applied if the
                                        customer orders the <strong>Minimum Quantity</strong> or more of this specific
                                        product.
                                    </p>
                                </div>

                                <div class="admin-form-grid"
                                    style="grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label
                                            style="text-transform: uppercase; font-size: 0.7rem; font-weight: 800; color: #64748b; letter-spacing: 1px; margin-bottom: 8px; display: block;">Discount
                                            (%)</label>
                                        <div style="position: relative;">
                                            <input type="number" id="customDiscountPct" min="0" max="100" step="0.5"
                                                placeholder="e.g. 15.00" oninput="updateDiscountPreview()"
                                                style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 2px solid #f1f5f9; font-size: 0.95rem; font-weight: 600;">
                                            <span
                                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #94a3b8; font-size: 0.85rem;">%</span>
                                        </div>
                                        <div class="quick-pct-chips" style="margin-top: 10px; margin-bottom: 15px;">
                                            <div class="pct-chip" onclick="setQuickPct(5)">5%</div>
                                            <div class="pct-chip" onclick="setQuickPct(10)">10%</div>
                                            <div class="pct-chip" onclick="setQuickPct(15)">15%</div>
                                            <div class="pct-chip" onclick="setQuickPct(20)">20%</div>
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label
                                            style="text-transform: uppercase; font-size: 0.7rem; font-weight: 800; color: #64748b; letter-spacing: 1px; margin-bottom: 8px; display: block;">Quantity</label>
                                        <input type="number" id="customMinQty" min="1" placeholder="e.g. 5"
                                            style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 2px solid #f1f5f9; font-size: 0.95rem; font-weight: 600;">
                                    </div>
                                </div>

                                <!-- Live Preview Card -->
                                <div class="discount-preview-card">
                                    <h5
                                        style="margin: 0 0 10px 0; font-size: 0.7rem; color: #0b2e46; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                                        Live Price Preview</h5>
                                    <div class="preview-stat">
                                        <span class="preview-label">Base Price</span>
                                        <span class="preview-value" id="prevBasePrice">₱0.00</span>
                                    </div>
                                    <div class="preview-stat">
                                        <span class="preview-label">Savings</span>
                                        <span class="preview-value" id="prevSavings"
                                            style="color: #ef4444;">-₱0.00</span>
                                    </div>
                                    <div style="height: 1px; background: #e2e8f0; margin: 10px 0;"></div>
                                    <div class="preview-stat" style="margin-bottom: 0;">
                                        <span class="preview-label" style="color: #0b2e46;">New Price</span>
                                        <span class="preview-value discounted" id="prevNewPrice">₱0.00</span>
                                    </div>
                                </div>

                                <div
                                    style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 12px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                                    <button class="btn-cancel" onclick="resetDiscountRule()"
                                        style="background: #fff; color: #ef4444; border: 1px solid #fee2e2; padding: 10px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; transition: all 0.2s; cursor: pointer;">
                                        <i class="bi bi-trash"></i> Reset
                                    </button>
                                    <button class="btn-save" onclick="saveDiscountOverride()"
                                        style="background: #0b2e46; color: #f59e0b; padding: 10px 30px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; border: none; box-shadow: 0 4px 15px rgba(11, 46, 70, 0.15); transition: all 0.2s; cursor: pointer;">
                                        <i class="bi bi-check-all"></i> Save Rule
                                    </button>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div id="discountEmptyState"
                                style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; text-align: center; padding: 40px;">
                                <div
                                    style="width: 120px; height: 120px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 25px;">
                                    <i class="bi bi-box-seam" style="font-size: 3.5rem; color: #cbd5e1;"></i>
                                </div>
                                <h4 style="color: #0b2e46; margin: 0 0 10px 0; font-weight: 800;">No Product Selected
                                </h4>
                                <p style="color: #94a3b8; max-width: 250px; margin: 0; line-height: 1.6;">Choose a
                                    product from the list to start configuring its manual discount rules.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTENT SECTIONS -->

            <!-- Orders Section -->
            <div id="ordersSection" class="tab-content active">
                <!-- Status Triage Tabs -->
                <div class="status-triage-tabs" id="orderTriageTabs">
                    <button class="triage-tab active" data-filter="all">
                        All Orders <span class="tab-count-badge" id="count-all">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Unpaid">
                        Unpaid <span class="tab-count-badge" id="count-unpaid">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Paid">
                        Paid <span class="tab-count-badge" id="count-paid">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Processing">
                        Processing <span class="tab-count-badge" id="count-processing">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Awaiting Balance">
                        Awaiting Balance <span class="tab-count-badge" id="count-awaiting-balance">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Out for delivery">
                        Out for Delivery <span class="tab-count-badge" id="count-out-for-delivery">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Ready to Deliver">
                        Ready <span class="tab-count-badge" id="count-ready-to-deliver">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Completed">
                        Completed <span class="tab-count-badge" id="count-completed">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Return Items">
                        Return <span class="tab-count-badge" id="count-return-items">0</span>
                    </button>
                    <button class="triage-tab" data-filter="Cancelled">
                        Cancelled <span class="tab-count-badge" id="count-cancelled">0</span>
                    </button>
                    <button class="triage-tab archived-tab" id="toggleOrderArchiveBtn" onclick="toggleOrderArchives()">
                        <i class="bi bi-archive"></i> View Archive
                    </button>
                </div>

                <div class="filters-bar">
                    <div class="search-wrapper">
                        <i class="bi bi-search"></i>
                        <input type="text" id="orderSearchInput" placeholder="Search by name, service, or ID...">
                    </div>
                    <div class="date-filters">
                        <div class="filter-group">
                            <label>From</label>
                            <input type="text" id="orderDateFrom" class="admin-datepicker" placeholder="From">
                        </div>
                        <div class="filter-group">
                            <label>To</label>
                            <input type="text" id="orderDateTo" class="admin-datepicker" placeholder="To">
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-wrapper">
                        <table id="ordersTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-col="0">Order ID <span class="sort-icon">↕</span></th>
                                    <th class="text-center-cell">Image</th>
                                    <th>Product Name</th>
                                    <th class="text-center-cell">Gender</th>
                                    <th data-col="2">Customer</th>
                                    <th data-col="3">Date</th>
                                    <th class="text-center-cell sortable" data-col="4">Total <span
                                            class="sort-icon">↕</span></th>
                                    <th class="text-center-cell">Payment</th>
                                    <th class="text-center-cell">Proof</th>
                                    <th class="text-center-cell sortable" data-col="5">Status <span
                                            class="sort-icon">↕</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr data-row-id="<?php echo $order['orderID']; ?>"
                                        data-status="<?php echo $order['orderStatus']; ?>"
                                        data-method="<?php echo $order['distributionMethod']; ?>"
                                        data-active="<?php echo $order['isActive']; ?>"
                                        data-date="<?php echo $order['orderDate'] ? date('Y-m-d', strtotime($order['orderDate'])) : ''; ?>"
                                        class="clickable-row <?php echo (int) $order['isActive'] === 0 ? 'archived-row' : ''; ?>"
                                        onclick="viewOrder(<?php echo $order['orderID']; ?>)">
                                        <td>#ORD-<?php echo str_pad($order['orderID'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td class="text-center-cell">
                                            <div class="prod-img-preview">
                                                <img src="<?php echo !empty($order['productImage']) ? '../../JDE_USER/' . str_replace('../', '', $order['productImage']) : '../../JDE_USER/assets/img/logojd.png'; ?>"
                                                    alt="Order Product" loading="lazy"
                                                    onerror="this.src='../../JDE_USER/assets/img/logojd.png'">
                                            </div>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($order['productName'] ?? 'No Items/Custom'); ?></strong>
                                        </td>
                                        <td class="text-center-cell">
                                            <?php echo $order['genderLabel']; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['customerName']); ?></td>
                                        <td><?php echo $order['orderDate'] ? date('M j, Y', strtotime($order['orderDate'])) : 'N/A'; ?>
                                        </td>
                                        <td class="text-center-cell">₱<?php echo number_format($order['totalPrice'], 2); ?>
                                        </td>
                                        <td class="text-center-cell">
                                            <?php if (!empty($order['proofOfPayment'])): ?>
                                                <div class="payment-method-badge gcash"
                                                    onclick="event.stopPropagation(); viewOrder(<?php echo $order['orderID']; ?>)">
                                                    <i class="bi bi-image"></i> Proof of Payment
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">Standard</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center-cell">
                                            <?php
                                                $podVal = trim($order['proofOfDelivery'] ?? '');
                                                $podDist = strtolower($order['distributionMethod'] ?? '');
                                                $podStatus = strtolower($order['orderStatus'] ?? '');
                                                $podIsDelivery = ($podDist === 'delivery');
                                                $podIsCompleted = in_array($podStatus, ['completed', 'order completed', 'delivered']);
                                                $podHasValidPath = $podVal !== '' 
                                                    && strtolower($podVal) !== 'null' 
                                                    && strtolower($podVal) !== 'undefined'
                                                    && strpos($podVal, 'assets/img/proofs/') !== false
                                                    && strlen($podVal) > strlen('assets/img/proofs/')
                                                    && strpos($podVal, 'default') === false
                                                    && strpos($podVal, 'placeholder') === false;
                                            ?>
                                            <?php if ($podIsDelivery && $podIsCompleted && $podHasValidPath): ?>
                                                <div class="payment-method-badge proof-delivery"
                                                    onclick="event.stopPropagation(); viewOrder(<?php echo $order['orderID']; ?>)">
                                                    <i class="bi bi-camera"></i> Proof
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center-cell">
                                            <?php 
                                                $rawStatus = strtolower($order['orderStatus']);
                                                $sClass = str_replace(' ', '-', $rawStatus);
                                                $displayText = $order['orderStatus'];

                                                if ($rawStatus === 'awaiting balance') {
                                                    $method = strtolower($order['methodName'] ?? '');
                                                    $isCOD = strpos($method, 'cash on delivery') !== false;
                                                    $isCOP = strpos($method, 'cash on pickup') !== false || strpos($method, 'cash on pick up') !== false;
                                                    $isPickUp = strpos(strtolower($order['distributionMethod'] ?? ''), 'pickup') !== false;

                                                    if ($isCOD || $isCOP) {
                                                        $sClass .= ' cod-awaiting';
                                                        $displayText = $isPickUp ? 'To be paid upon Pickup' : 'To be paid upon Delivery';
                                                    }
                                                }
                                            ?>
                                            <span
                                                class="status-badge clickable <?php echo $sClass; ?>"
                                                onclick="event.stopPropagation(); updateOrderStatus(<?php echo $order['orderID']; ?>, '<?php echo addslashes($order['orderStatus']); ?>', '<?php echo addslashes($order['balancePaymentStatus'] ?? ''); ?>')">
                                                <?php echo $displayText; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="orderEmptyState" class="empty-state-row" style="display:none">
                                    <td colspan="10"><i class="bi bi-inbox"></i> No orders match your filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- --- PAGINATION CONTROLS --- -->
                <?php if ($totalRows > $limit): ?>
                    <div class="pagination-container mt-4">
                        <div class="pagination-info">
                            Showing <?php echo count($orders); ?> of <?php echo $totalRows; ?> total orders
                        </div>
                        <div class="pagination-controls">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>" class="pagination-btn">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </a>
                            <?php else: ?>
                                <button class="pagination-btn disabled" disabled>
                                    <i class="bi bi-chevron-left"></i> Previous
                                </button>
                            <?php endif; ?>

                            <div class="pagination-numbers">
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                if ($startPage > 1): ?>
                                    <a href="?page=1&limit=<?php echo $limit; ?>" class="pagination-number">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span class="pagination-ellipsis">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>"
                                        class="pagination-number <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <span class="pagination-ellipsis">...</span>
                                    <?php endif; ?>
                                    <a href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>"
                                        class="pagination-number"><?php echo $totalPages; ?></a>
                                <?php endif; ?>
                            </div>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>" class="pagination-btn">
                                    Next <i class="bi bi-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <button class="pagination-btn disabled" disabled>
                                    Next <i class="bi bi-chevron-right"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- MODALS -->



        <!-- View Order Modal -->
        <div id="viewOrderModal" class="modal-overlay">
            <div class="modal large">
                <div class="modal-header">
                    <h3>Order Details <span id="viewOrderID"></span> <span id="viewPreOrderBadge" class="status-badge" style="display:none; background-color:#ff9800; color:#fff;">PRE-ORDER</span> <span id="viewOrderStatusBadge"
                            class="status-badge"></span></h3>
                    <button class="modal-close" onclick="closeViewOrderModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Progress Tracker (Track My Order Style) -->
                    <div class="progress-track" id="orderTimeline">
                        <div class="progress-line" id="orderProgressLine" style="width: 0%;"></div>
                        <!-- Steps will be dynamically injected or modified by orders.js based on distributionMethod -->
                        <div id="timelineStepsContainer" style="display: contents;">
                            <div class="step-item active" id="step-1">
                                <div class="step-icon"><i class="bi bi-clock-history"></i></div>
                                <div class="step-label">Order<br>Placed</div>
                            </div>
                            <div class="step-item" id="step-2">
                                <div class="step-icon"><i class="bi bi-cash"></i></div>
                                <div class="step-label">Down<br>Payment</div>
                            </div>
                            <div class="step-item" id="step-3">
                                <div class="step-icon"><i class="bi bi-gear"></i></div>
                                <div class="step-label">Order<br>Processing</div>
                                <div class="step-time" id="time-processing"></div>
                            </div>
                            <div class="step-item" id="step-4">
                                <div class="step-icon"><i class="bi bi-wallet2"></i></div>
                                <div class="step-label">Awaiting<br>Balance</div>
                                <div class="step-time" id="time-remaining-balance"></div>
                            </div>
                            <div class="step-item" id="step-5">
                                <div class="step-icon" id="icon-step-5"><i class="bi bi-geo-fill"></i></div>
                                <div class="step-label" id="label-step-5">Out for<br>delivery</div>
                                <div class="step-time" id="time-out-for-delivery"></div>
                            </div>
                            <div class="step-item" id="step-6">
                                <div class="step-icon"><i class="bi bi-patch-check-fill"></i></div>
                                <div class="step-label">Order<br>Completed</div>
                                <div class="step-time" id="time-completed"></div>
                            </div>
                        </div>
                    </div>

                    <div class="order-info-grid">
                        <div class="info-group">
                            <label>Customer Name</label>
                            <p id="viewCustomerName"></p>
                        </div>
                        <div class="info-group">
                            <label>Order Date</label>
                            <p id="viewOrderDate"></p>
                        </div>
                        <div class="info-group">
                            <label>Address</label>
                            <p id="viewShippingAddress"></p>
                        </div>
                        <div class="info-group">
                            <label>Subtotal</label>
                            <p id="viewSubtotal" class="fw-bold"></p>
                        </div>
                        <div class="info-group">
                            <label>Shipping Fee</label>
                            <p id="viewShippingFee" class="fw-bold"></p>
                        </div>
                        <div class="info-group">
                            <label>Total Price</label>
                            <p id="viewTotalPrice" class="price-highlight"></p>
                        </div>
                        <div class="info-group">
                            <label>Payment Option</label>
                            <p id="viewPaymentOption" class="fw-bold"></p>
                        </div>
                        <div class="info-group" id="adminDiscountGroup" style="display:none;">
                            <label>Bulk Discount</label>
                            <p id="viewDiscountAmount" class="fw-bold"></p>
                        </div>
                        <div class="info-group" id="adminDownpaymentGroup" style="display:none;">
                            <label>Required Downpayment (50%)</label>
                            <p id="viewDownpayment" class="fw-bold"></p>
                        </div>
                        <div class="info-group" id="adminBalanceGroup" style="display:none;">
                            <label>Balance</label>
                            <p id="viewBalance" class="fw-bold text-danger"></p>
                        </div>
                    </div>

                    <!-- Proof of Delivery Section (Shown when Delivered/Completed) -->
                    <div id="proofOfDeliveryArea" class="payment-info-section mt-4"
                        style="display:none; border-top: 1px solid #eee; padding-top: 20px;">
                        <div class="payment-section-header">
                            <h4><i class="bi bi-truck"></i> Proof of Delivery</h4>
                        </div>
                        <div class="payment-details-grid text-center">
                            <img id="viewProofImage" src="" alt="Proof of Delivery"
                                style="max-width: 100%; max-height: 400px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); cursor: pointer; border: 1px solid #eee;"
                                onclick="window.open(this.src, '_blank')">
                            <p class="text-muted mt-2 small">Click image to enlarge</p>
                        </div>
                    </div>

                    <!-- Return Information Section (Shown when Return Items) -->
                    <div id="returnInfoArea" class="payment-info-section mt-4"
                        style="display:none; border-top: 1px solid #eee; padding-top: 20px;">
                        <div class="payment-section-header">
                            <h4><i class="bi bi-arrow-counterclockwise"></i> Return Information</h4>
                        </div>
                        <div class="payment-details-grid">
                            <div class="payment-detail-row">
                                <span class="pdr-label">Reason</span>
                                <span id="viewReturnReason" class="pdr-value fw-bold text-primary"></span>
                            </div>
                            <div class="payment-detail-row">
                                <span class="pdr-label">Remarks</span>
                                <span id="viewReturnRemarks" class="pdr-value"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Information Section (Shown when Cancelled) -->
                    <div id="cancelInfoArea" class="payment-info-section mt-4"
                        style="display:none; border-top: 1px solid #eee; padding-top: 20px;">
                        <div class="payment-section-header">
                            <h4><i class="bi bi-x-circle"></i> Cancellation Information</h4>
                        </div>
                        <div class="payment-details-grid">
                            <div class="payment-detail-row">
                                <span class="pdr-label">Reason</span>
                                <span id="viewCancelReason" class="pdr-value fw-bold text-danger"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information Section -->
                    <div class="payment-info-section" id="paymentDetailsArea">
                        <div class="payment-section-header">
                            <h4><i class="bi bi-credit-card"></i> Payment Information</h4>
                            <span id="paymentStatusBadge" class="payment-status-badge" style="display:none;"></span>
                        </div>
                        <div class="payment-details-grid">
                            <div class="payment-detail-row">
                                <span class="pdr-label">Method</span>
                                <span id="viewPaymentMethod" class="pdr-value fw-bold"></span>
                            </div>
                            <div class="payment-detail-row" id="gcashInfoGroup" style="display:none;">
                                <span class="pdr-label">GCash Ref #</span>
                                <span class="pdr-value ref-row">
                                    <span id="viewReferenceNumber" class="ref-number"></span>
                                    <button class="btn-copy-ref" title="Copy reference number"
                                        onclick="copyReferenceNumber()">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </span>
                            </div>
                            <div class="payment-detail-row">
                                <span class="pdr-label">Status</span>
                                <span id="viewPaymentStatus" class="pdr-value"></span>
                            </div>
                            <!-- Receipt thumbnail — always visible for GCash with a receipt -->
                            <div class="payment-detail-row receipt-thumb-row" id="orderReceiptThumbGroup"
                                style="display:none;">
                                <span class="pdr-label">Proof of Payment</span>
                                <span class="pdr-value">
                                    <div class="order-receipt-thumb" onclick="openPaymentVerifyModal()">
                                        <img id="orderReceiptThumb" src="" alt="Proof of Payment">
                                        <div class="order-receipt-thumb-overlay">
                                            <i class="bi bi-zoom-in"></i>
                                        </div>
                                    </div>
                                </span>
                            </div>
                            <!-- Verify Payment CTA — shown only for unverified GCash -->
                            <div class="payment-detail-row" id="verifyActionsGroup" style="display:none;">
                                <span class="pdr-label"></span>
                                <span class="pdr-value">
                                    <button class="btn-verify-payment-cta" onclick="openPaymentVerifyModal()">
                                        <i class="bi bi-shield-check"></i> Verify Payment
                                        <i class="bi bi-arrow-right-short"></i>
                                    </button>
                                </span>
                            </div>

                            <div class="payment-detail-row" id="orderBalanceRefGroup" style="display:none;">
                                <span class="pdr-label">Balance Ref #</span>
                                <span class="pdr-value ref-row">
                                    <span id="viewBalanceReferenceNumber" class="ref-number"></span>
                                </span>
                            </div>

                            <!-- Remaining Balance Receipt Thumbnail -->
                            <div class="payment-detail-row receipt-thumb-row" id="orderBalanceReceiptThumbGroup"
                                style="display:none;">
                                <span class="pdr-label">Balance Proof</span>
                                <span class="pdr-value">
                                    <div class="order-receipt-thumb" onclick="openBalanceVerifyModal()">
                                        <img id="orderBalanceReceiptThumb" src="" alt="Balance Proof">
                                        <div class="order-receipt-thumb-overlay">
                                            <i class="bi bi-zoom-in"></i>
                                        </div>
                                    </div>
                                </span>
                            </div>

                            <!-- Verify Balance CTA -->
                            <div class="payment-detail-row" id="verifyBalanceActionsGroup" style="display:none;">
                                <span class="pdr-label"></span>
                                <span class="pdr-value">
                                    <button class="btn-verify-payment-cta" style="background: #e67e22;"
                                        onclick="openBalanceVerifyModal()">
                                        <i class="bi bi-shield-lock"></i> Verify Balance
                                        <i class="bi bi-arrow-right-short"></i>
                                    </button>
                                </span>
                            </div>

                        </div>
                    </div>

                    <div class="order-items-list">
                        <h4 id="orderedItemsHeader">Ordered Items</h4>
                        <div class="table-container" style="max-height: 350px; overflow-y: auto; border-radius: 8px;">
                            <table id="viewOrderItemsTable">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Size</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="modal-actions-left">
                        <button class="btn-row-action danger btn-delete-order-modal" id="modalDeleteBtn">
                            <i class="bi bi-archive"></i> Archive Order
                        </button>
                    </div>
                    <div class="modal-actions-center">
                        <!-- Left empty or for future central actions -->
                    </div>
                    <div class="modal-actions-right">

                        <button class="btn-row-action primary btn-update-order-modal" id="modalUpdateBtn">
                            <i class="bi bi-pencil-square"></i> Update Status
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Modal -->
        <div id="orderStatusModal" class="modal-overlay" style="z-index: 2500;">
            <div class="modal">
                <div class="modal-header">
                    <h4>Update Order Status</h4>
                    <button class="modal-close" onclick="closeOrderStatusModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>New Status</label>
                        <select id="newStatus" class="form-control">
                            <!-- Populated by JS for correct flow -->
                        </select>
                    </div>
                    <div id="cancelReasonGroup" class="form-group" style="display:none; margin-top:15px;">
                        <label>Reason for Cancellation</label>
                        <textarea id="cancelReasonInput" class="form-control" rows="3"
                            placeholder="Why is this order being cancelled?"></textarea>
                    </div>

                    <div id="proofOfDeliveryGroup" class="form-group" style="display:none; margin-top:15px;">
                        <label>Proof of Delivery (Image)</label>
                        <input type="file" id="proofOfDeliveryInput" class="form-control" accept="image/*">
                        <small class="text-muted">Required when marking as Delivered.</small>
                    </div>

                    <!-- Return Items Specific Fields -->
                    <div id="returnItemsGroup" class="form-group" style="display:none; margin-top:15px;">
                        <div class="mb-3">
                            <label class="fw-bold">Reason for Return <span class="text-danger">*</span></label>
                            <input type="text" id="returnReasonInput" class="form-control"
                                placeholder="e.g. Wrong Size, Defective, etc.">
                        </div>
                        <div class="mb-0">
                            <label class="fw-bold">Remarks / Details <span class="text-danger">*</span></label>
                            <textarea id="returnRemarksInput" class="form-control" rows="3"
                                placeholder="Provide more details about the return..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: flex-end;">
                    <button type="button" class="btn-save" id="saveStatusBtn">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Confirmation Modal -->
        <div id="confirmModal" class="modal-overlay">
            <div class="confirm-modal-content"
                style="max-width: 450px; border-radius: 24px; padding: 0; overflow: hidden; width: 100%;">
                <div
                    style="background: #f8fafc; padding: 22px 28px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="confirm-modal-icon"
                            style="width: 40px; height: 40px; flex-shrink: 0; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-exclamation-triangle" style="color:#f59e0b; font-size: 1.2rem;"></i>
                        </div>
                        <div>
                            <h4 id="confirmTitle"
                                style="margin: 0; color: var(--primary); font-size: 1.05rem; font-weight: 800; letter-spacing: -0.3px;">
                                Confirm Action</h4>
                            <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.75rem; font-weight: 500;">Please
                                confirm your action below</p>
                        </div>
                    </div>
                </div>
                <div style="padding: 24px 28px; background: #fff;">
                    <p id="confirmMessage" style="margin: 0; color: #475569; font-size: 0.9rem; line-height: 1.65;"></p>
                </div>
                <div class="confirm-modal-footer"
                    style="padding: 16px 28px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 10px;">
                    <button id="confirmCancelBtn" class="btn-confirm-cancel"
                        style="flex:1; border-radius:10px; padding:11px 0; font-weight:700; font-size:0.88rem; text-transform:uppercase; letter-spacing:0.5px; border:none; cursor:pointer; transition:all 0.2s; background:#f1f5f9; color:#475569;">CANCEL</button>
                    <button id="confirmOkBtn" class="btn-confirm-ok"
                        style="flex:1; border-radius:10px; padding:11px 0; font-weight:700; font-size:0.88rem; text-transform:uppercase; letter-spacing:0.5px; border:none; cursor:pointer; transition:all 0.2s; background:var(--primary); color:var(--accent);">YES,
                        PROCEED</button>
                </div>
            </div>
        </div>

        <!-- Payment Verification Modal -->
        <div id="paymentVerifyModal" class="modal-overlay" style="z-index: 3000;">
            <div class="modal payment-verify-modal">
                <div class="modal-header payment-verify-header">
                    <div class="pvm-header-info">
                        <i class="bi bi-shield-check pvm-icon"></i>
                        <div>
                            <h3>Payment Verification</h3>
                            <span id="pvmOrderLabel" class="pvm-order-label"></span>
                        </div>
                    </div>
                    <button class="modal-close" onclick="closePaymentVerifyModal()">&times;</button>
                </div>
                <div class="modal-body pvm-body">
                    <!-- Left: Receipt -->
                    <div class="pvm-receipt-side">
                        <label class="pvm-section-label"><i class="bi bi-image"></i> Proof of Payment</label>
                        <div class="pvm-receipt-frame" onclick="openReceiptLightbox()">
                            <img id="pvmReceiptImage" src="" alt="Proof of Payment">
                            <div class="pvm-receipt-zoom">
                                <i class="bi bi-zoom-in"></i>
                                <span>Click to Enlarge</span>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Details + Action -->
                    <div class="pvm-details-side">
                        <label class="pvm-section-label"><i class="bi bi-info-circle"></i> Payment Details</label>
                        <div class="pvm-detail-list">
                            <div class="pvm-detail-item">
                                <span class="pvm-dl-label">Customer</span>
                                <span id="pvmCustomerName" class="pvm-dl-value"></span>
                            </div>
                            <div class="pvm-detail-item">
                                <span class="pvm-dl-label">Amount</span>
                                <span id="pvmAmount" class="pvm-dl-value pvm-amount"></span>
                            </div>
                            <div class="pvm-detail-item">
                                <span class="pvm-dl-label">GCash Ref #</span>
                                <span id="pvmRefNumber" class="pvm-dl-value pvm-ref"></span>
                            </div>
                            <div class="pvm-detail-item">
                                <span class="pvm-dl-label">Method</span>
                                <span id="pvmMethod" class="pvm-dl-value"></span>
                            </div>
                            <div class="pvm-detail-item">
                                <span class="pvm-dl-label">Uploaded</span>
                                <span id="pvmUploadedDate" class="pvm-dl-value pvm-uploaded-date"></span>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label class="pvm-section-label"
                                style="font-size: 0.75rem; color: #64748b; margin-bottom: 8px; display: block;">Admin
                                Internal Note (Optional)</label>
                            <textarea id="pvmAdminNote" class="form-control" rows="2"
                                placeholder="Private note about this payment verification..."
                                style="font-size: 0.85rem; border-radius: 8px; border: 1px solid #e2e8f0; resize: none;"></textarea>
                        </div>


                        <div class="pvm-action-row">
                            <button class="pvm-btn-reject" onclick="verifyPayment('rejected')">
                                <i class="bi bi-x-circle-fill"></i> Reject
                            </button>
                            <button class="pvm-btn-approve" onclick="verifyPayment('approved')">
                                <i class="bi bi-check-circle-fill"></i> Approve Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Lightbox Overlay (Consolidated) -->
        <div id="receiptLightbox" class="modal-overlay" style="z-index: 5000;" onclick="closeReceiptLightbox()">
            <div class="lightbox-inner" onclick="event.stopPropagation()">
                <button class="lightbox-close" onclick="closeReceiptLightbox()">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="lightbox-image-container">
                    <img id="lightboxReceiptImg" src="" alt="Proof of Payment Full View">
                </div>
                <div class="lightbox-footer">
                    <a id="lightboxDownload" href="" download="gcash_receipt" class="lightbox-download" target="_blank">
                        <i class="bi bi-download"></i> Download Receipt
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Update Success Modal -->
        <div id="statusSuccessModal" class="modal-overlay high-z">
            <div class="modal" style="max-width: 400px; text-align: center;">
                <div class="modal-body" style="padding: 30px 20px;">
                    <i class="bi bi-check-circle-fill text-success"
                        style="font-size: 4rem; display: block; margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 10px; font-weight: 600;">Status Updated</h3>
                    <p style="color: #6c757d; margin-bottom: 25px;">The order status has been successfully updated.</p>
                    <button class="btn-save" style="width: 100%; justify-content: center;"
                        onclick="location.reload()">OK</button>
                </div>
            </div>
        </div>



        <!-- Production Card / Measurement Sheet Modal -->
        <div id="measurementModal" class="modal-overlay" style="z-index: 3500;">
            <div class="modal measurement-card">
                <div class="modal-header production-header">
                    <div class="pvm-header-info">
                        <i class="bi bi-rulers pvm-icon"></i>
                        <div>
                            <h3>Production Card</h3>
                            <span id="mcItemName" class="pvm-order-label">Custom Tailoring Item</span>
                        </div>
                    </div>
                    <div class="header-actions-right">
                        <button class="btn-print-specs" onclick="window.print()"><i class="bi bi-printer"></i>
                            Print</button>
                        <button class="modal-close" onclick="closeMeasurementModal()">&times;</button>
                    </div>
                </div>
                <div class="modal-body spec-body">
                    <div class="spec-sheet-container">
                        <div class="spec-customer-info">
                            <div class="info-item">
                                <label>Date:</label>
                                <span id="mcDate"><?php echo date('M j, Y'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Customer:</label>
                                <span id="mcCustomer" class="fw-bold">---</span>
                            </div>
                            <div class="info-item">
                                <label>Order ID:</label>
                                <span id="mcOrderID" class="fw-bold">---</span>
                            </div>
                        </div>

                        <div class="spec-grid" id="mcGrid">
                            <!-- Populated by JS -->
                        </div>

                        <div class="spec-footer">
                            <div class="spec-notes">
                                <label>Special Instructions / Notes:</label>
                                <p id="mcNotes">None provided.</p>
                            </div>
                            <div class="spec-signature">
                                <div class="sig-line"></div>
                                <span>Production Supervisor Signature</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <?php include __DIR__ . '/fragments/scripts.php'; ?>

        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="../js/orders.js?v=<?php echo time(); ?>"></script>
    </div> <!-- main-content -->
</div> <!-- admin-layout -->

<!-- Balance Verification Modal -->
<div id="balanceVerifyModal" class="modal-overlay mid-z">
    <div class="modal payment-verify-modal">
        <div class="modal-header payment-verify-header">
            <div class="pvm-header-info">
                <i class="bi bi-shield-lock pvm-icon"></i>
                <div>
                    <h3>Balance Verification</h3>
                    <span id="bvmOrderLabel" class="pvm-order-label"></span>
                </div>
            </div>
            <button class="modal-close" onclick="closeBalanceVerifyModal()">&times;</button>
        </div>
        <div class="modal-body pvm-body">
            <!-- Left: Receipt -->
            <div class="pvm-receipt-side">
                <label class="pvm-section-label"><i class="bi bi-image"></i> Balance Receipt</label>
                <div class="pvm-receipt-frame" onclick="openBalanceReceiptLightbox()">
                    <img id="bvmReceiptImage" src="" alt="Balance Receipt">
                </div>
            </div>
            <!-- Right: Details -->
            <div class="pvm-details-side">
                <label class="pvm-section-label"><i class="bi bi-info-circle"></i> Payment Details</label>
                <div class="pvm-detail-list">
                    <div class="pvm-detail-item">
                        <span class="pvm-dl-label">Customer</span>
                        <span id="bvmCustomerName" class="pvm-dl-value"></span>
                    </div>
                    <div class="pvm-detail-item">
                        <span class="pvm-dl-label">Amount Due</span>
                        <span id="bvmAmount" class="pvm-dl-value pvm-amount" style="color: #e67e22;"></span>
                    </div>
                    <div class="pvm-detail-item">
                        <span class="pvm-dl-label">GCash Ref #</span>
                        <span id="bvmRefNumber" class="pvm-dl-value pvm-ref"></span>
                    </div>
                    <div class="pvm-detail-item">
                        <span class="pvm-dl-label">Method</span>
                        <span id="bvmMethod" class="pvm-dl-value"></span>
                    </div>
                    <div class="pvm-detail-item">
                        <span class="pvm-dl-label">Uploaded</span>
                        <span id="bvmUploadedDate" class="pvm-dl-value pvm-uploaded-date"></span>
                    </div>
                </div>
                <div class="pvm-action-row">
                    <button class="pvm-btn-reject" onclick="verifyBalancePayment('rejected')">
                        <i class="bi bi-x-circle-fill"></i> Reject
                    </button>
                    <button class="pvm-btn-approve" onclick="verifyBalancePayment('approved')">
                        <i class="bi bi-check-circle-fill"></i> Approve Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize global cache with server-rendered data for immediate JS interaction
    window.allOrdersCache = <?php echo json_encode($orders); ?>;
</script>
</body>

</html>