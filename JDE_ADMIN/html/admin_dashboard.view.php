<?php
$pageTitle = "Admin Dashboard";
include __DIR__ . '/fragments/header.php';
?>

<style>
    /* Direct Layout Injection – Centering Fixes */
    .inv-card-info {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 100% !important;
    }
    .inv-summary-card h4, .status-count-premium, .stock-move-card h4 {
        margin: 0 !important;
        line-height: 1 !important;
    }
    .inv-card-label, .status-name-premium, .stock-move-card span {
        margin: 0 0 2px 0 !important;
        line-height: 1.2 !important;
    }
    .inv-summary-card, .status-row-premium {
        display: flex !important;
        align-items: center !important;
    }
</style>

<!-- Page Specific Assets -->
<link rel="stylesheet" href="../css/admin-dashboard.css">

<div class="container pt-5 pb-5">

    <!-- Header Section -->
    <div class="dashboard-header-premium">
        <div class="welcome-msg">
            <h2>Welcome Back, <span><?php echo htmlspecialchars($_SESSION['fname'] ?? 'Admin'); ?>!</span></h2>
            <p>Your store's real-time overview at a glance.</p>
        </div>
        <div class="header-right-side">
            <div class="clock-widget">
                <div class="clock-icon-box"><i class="bi bi-clock-fill"></i></div>
                <div class="clock-text">
                    <span class="time" id="liveClock">--:--:-- --</span>
                    <span class="date" id="liveDate">--- --, ----</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Row (3 Cards) -->
    <div class="revenue-hero">
        <div class="rev-hero-content">
            <div class="rev-main-info">
                <div class="rev-hero-icon"><i class="bi bi-wallet2"></i></div>
                <div class="rev-text">
                    <div class="rev-label-premium" id="revenueLabel">Today's Revenue</div>
                    <h1 class="rev-value-premium">₱<span id="revenueValueHolder"><?php echo number_format($dailyRevenue, 2); ?></span></h1>
                </div>
            </div>
            <div class="rev-controls">
                <button class="rev-btn active" data-amt="<?php echo number_format($dailyRevenue, 2); ?>" data-lbl="Today's Revenue">Daily</button>
                <button class="rev-btn" data-amt="<?php echo number_format($monthlyRevenue, 2); ?>" data-lbl="Monthly Revenue">Monthly</button>
                <button class="rev-btn" data-amt="<?php echo number_format($yearlyRevenue, 2); ?>" data-lbl="Yearly Revenue">Yearly</button>
            </div>
        </div>
    </div>

    <!-- Metric Row -->
    <div class="metrics-grid-premium">
        <a href="orders.php" class="metric-card-premium orders">
            <div class="metric-icon-premium"><i class="bi bi-cart3"></i></div>
            <span class="metric-count-premium"><?php echo $totalOrders; ?></span>
            <span class="metric-label-premium">Orders</span>
        </a>
        <a href="orders.php" class="metric-card-premium pending">
            <div class="metric-icon-premium"><i class="bi bi-hourglass-split"></i></div>
            <span class="metric-count-premium"><?php echo $pendingOrders; ?></span>
            <span class="metric-label-premium">Pending</span>
        </a>
        <a href="appointments.php" class="metric-card-premium appts">
            <div class="metric-icon-premium"><i class="bi bi-calendar-check"></i></div>
            <span class="metric-count-premium"><?php echo $todayAppointments; ?></span>
            <span class="metric-label-premium">Today's Appts</span>
        </a>
        <a href="../../JDE_ADMIN/backend/admin_chat.php" class="metric-card-premium chats">
            <div class="metric-icon-premium"><i class="bi bi-chat-dots"></i></div>
            <span class="metric-count-premium"><?php echo ($unreadInquiries + $unreadChatUsers); ?></span>
            <span class="metric-label-premium">Inquiries</span>
        </a>
        <a href="users.php" class="metric-card-premium users">
            <div class="metric-icon-premium"><i class="bi bi-people"></i></div>
            <span class="metric-count-premium"><?php echo $totalUsers; ?></span>
            <span class="metric-label-premium">Users</span>
        </a>
    </div>

    <!-- 1. Inventory Summary Tier (Immediately under metrics) -->
    <div class="column-card-premium section-spacer">
        <div class="column-title-premium">
            <h3><i class="bi bi-box-seam-fill"></i> Inventory Summary</h3>
        </div>
        <div class="inventory-summary-grid" id="inventorySummaryCards">
            <div class="inv-summary-card in-stock">
                <div class="inv-card-icon"><i class="bi bi-box"></i></div>
                <div class="inv-card-info">
                    <span class="inv-card-label">Total Products</span>
                    <h4 id="sumTotalProducts"><?php echo (int) $inventorySummary['totalProducts']; ?></h4>
                </div>
            </div>
            <div class="inv-summary-card in-stock">
                <div class="inv-card-icon"><i class="bi bi-stack"></i></div>
                <div class="inv-card-info">
                    <span class="inv-card-label">Total Stock</span>
                    <h4 id="sumTotalStockQty"><?php echo (int) $inventorySummary['totalStockQty']; ?></h4>
                </div>
            </div>
            <div class="inv-summary-card low-stock">
                <div class="inv-card-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="inv-card-info">
                    <span class="inv-card-label">Low Stock</span>
                    <h4 id="sumLowStockItems"><?php echo (int) $inventorySummary['lowStockItems']; ?></h4>
                </div>
            </div>
            <div class="inv-summary-card out-stock">
                <div class="inv-card-icon"><i class="bi bi-x-octagon-fill"></i></div>
                <div class="inv-card-info">
                    <span class="inv-card-label">Out of Stock</span>
                    <h4 id="sumOutOfStockItems"><?php echo (int) $inventorySummary['outOfStockItems']; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Operational & Movement Tier -->
    <div class="dashboard-columns-grid">
        <!-- Order Status Pipeline -->
        <div class="column-card-premium">
            <div class="column-title-premium">
                <h3><i class="bi bi-funnel-fill"></i> Order Progress</h3>
                <a href="orders.php" class="view-all-link">Manage</a>
            </div>
            <div class="status-list-premium">
                <?php
                $osList = [
                    ['name' => 'Pending', 'icon' => 'bi-clock-history', 'color' => '#f39c12'],
                    ['name' => 'Paid', 'icon' => 'bi-check-circle-fill', 'color' => '#2ecc71'],
                    ['name' => 'Processing', 'icon' => 'bi-gear-fill', 'color' => '#3498db'],
                    ['name' => 'Awaiting Balance', 'icon' => 'bi-wallet2', 'color' => '#e67e22'],
                    ['name' => 'Out for Delivery', 'icon' => 'bi-truck', 'color' => '#9b59b6'],
                    ['name' => 'Completed', 'icon' => 'bi-patch-check-fill', 'color' => '#27ae60']
                ];
                foreach ($osList as $os):
                    $count = $statusBreakdown[$os['name']] ?? 0;
                ?>
                    <div class="status-row-premium">
                        <div class="status-icon-premium" style="background:<?php echo $os['color']; ?>; color:white;">
                            <i class="bi <?php echo $os['icon']; ?>"></i>
                        </div>
                        <div class="inv-card-info">
                            <span class="status-name-premium"><?php echo $os['name']; ?></span>
                            <span class="status-count-premium"><?php echo $count; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Low Stock Products (Moved beside Order Progress) -->
        <div class="column-card-premium">
            <div class="column-title-premium">
                <h3><i class="bi bi-exclamation-octagon-fill"></i> Low Stock Products</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle inventory-table-sm">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Size</th>
                            <th>Current Stock</th>
                            <th>Stock Threshold</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="lowStockTableBody">
                        <?php if (empty($lowStockItems)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No low or out-of-stock products.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['productName']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($item['size'] ?? 'All'); ?></span></td>
                                    <td><?php echo (int) $item['stocks']; ?></td>
                                    <td><?php echo (int) $item['stockThreshold']; ?></td>
                                    <td><span class="stock-status-pill <?php echo htmlspecialchars($item['statusClass']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                    <td class="text-end"><a href="products.php?search=<?php echo urlencode($item['productName']); ?>" class="btn-restock">Restock</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>



</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    // Clock
    const updateTime = () => {
        const now = new Date();
        document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', { hour12: true });
        document.getElementById('liveDate').textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    };
    setInterval(updateTime, 1000);
    updateTime();

    // Revenue Switcher
    const revHolder = document.getElementById('revenueValueHolder');
    const revLabel = document.getElementById('revenueLabel');
    document.querySelectorAll('.rev-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.rev-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            revHolder.textContent = btn.dataset.amt;
            revLabel.textContent = btn.dataset.lbl;
        });
    });



    function renderLowStockTable(items) {
        const tbody = document.getElementById('lowStockTableBody');
        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No low or out-of-stock products.</td></tr>';
            return;
        }
        tbody.innerHTML = items.map(item => `
            <tr>
                <td>${item.productName}</td>
                <td><span class="badge bg-light text-dark border">${item.size || 'All'}</span></td>
                <td>${item.stocks}</td>
                <td>${item.stockThreshold}</td>
                <td><span class="stock-status-pill ${item.statusClass}">${item.status}</span></td>
                <td class="text-end"><a href="products.php?search=${encodeURIComponent(item.productName)}" class="btn-restock">Restock</a></td>
            </tr>
        `).join('');
    }
;



    const fetchInventoryData = async () => {
        try {
            const res = await fetch('../backend/admin_dashboard.php?inventory_data=1', { cache: 'no-store' });
            const payload = await res.json();
            if (!payload.success) return;

            document.getElementById('sumTotalProducts').textContent = payload.inventorySummary.totalProducts;
            document.getElementById('sumTotalStockQty').textContent = payload.inventorySummary.totalStockQty;
            document.getElementById('sumLowStockItems').textContent = payload.inventorySummary.lowStockItems;
            document.getElementById('sumOutOfStockItems').textContent = payload.inventorySummary.outOfStockItems;

            renderLowStockTable(payload.lowStockItems || []);
        } catch (err) {
            // Silently ignore transient refresh errors.
        }
    };

    setInterval(fetchInventoryData, 30000);
});
</script>

<?php include __DIR__ . '/fragments/footer.php'; ?>