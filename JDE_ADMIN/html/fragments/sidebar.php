<?php
$currentPage = basename($_SERVER['PHP_SELF']);
// Robust pathing for JDE_ADMIN notifications
$requestUri = $_SERVER['REQUEST_URI'];
$isAdminDir = strpos($requestUri, '/JDE_ADMIN/') !== false;
$isInBackend = strpos($requestUri, '/backend/') !== false;

if ($isAdminDir) {
    // If we are already in JDE_ADMIN, the API is in ./backend/ or ../backend/
    $adminBase = $isInBackend ? '' : 'backend/';
} else {
    // If we are in JDE_USER or elsewhere
    $adminBase = '../../JDE_ADMIN/backend/';
}
$userBase = $isAdminDir ? ($isInBackend ? '../../JDE_USER/backend/' : '../JDE_USER/backend/') : '../backend/';
?>
<script>
    // Synchronously apply collapsed state to prevent FOUC (Flash of Unstyled Content)
    (function() {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            const layout = document.querySelector('.admin-layout');
            if (layout) layout.classList.add('sidebar-collapsed');
        }
    })();
</script>
<div class="sidebar">
    <div class="sidebar-header">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <img src="<?php echo $isAdminDir ? '../assets/img/logo.png' : '../../JDE_ADMIN/assets/img/logo.png'; ?>"
            alt="JDE Logo" class="sidebar-logo-img">
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php if ($_SESSION['access_level'] == 1): ?>
                <li class="<?php echo ($currentPage == 'admin_dashboard.php') ? 'active' : ''; ?>">
                    <a href="<?php echo $adminBase; ?>admin_dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['access_level'], [1, 3])): ?>
                <li class="<?php echo ($currentPage == 'products.php') ? 'active' : ''; ?>">
                    <a href="<?php echo $adminBase; ?>products.php">
                        <i class="bi bi-box-seam"></i>
                        <span>Products</span>
                        <span id="sidebarLowStockBadge" class="sidebar-badge" style="display: none;"></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['access_level'], [1, 3])): ?>
                <li class="<?php echo ($currentPage == 'orders.php') ? 'active' : ''; ?>">
                    <a href="<?php echo $adminBase; ?>orders.php">
                        <i class="bi bi-cart-check"></i>
                        <span>Orders</span>
                        <span id="sidebarOrdersBadge" class="sidebar-badge" style="display: none;"></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['access_level'], [1, 2])): ?>
                <li class="<?php echo ($currentPage == 'appointments.php') ? 'active' : ''; ?>">
                    <a href="<?php echo $adminBase; ?>appointments.php">
                        <i class="bi bi-calendar-event"></i>
                        <span>Appointments</span>
                        <span id="sidebarAppointmentsBadge" class="sidebar-badge" style="display: none;"></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['access_level'] == 1): ?>
                <li class="<?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
                    <a href="<?php echo $adminBase; ?>users.php">
                        <i class="bi bi-people"></i>
                        <span>Roles</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['access_level'] == 1): ?>
                <li class="<?php echo ($currentPage == 'sales_report.php') ? 'active' : ''; ?>">
                    <a href="<?php echo $adminBase; ?>sales_report.php">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Sales Report</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['access_level'], [1, 2])): ?>
                <li
                    class="<?php echo ($currentPage == 'admin_chat.php' || $currentPage == 'inquiries.php') ? 'active' : ''; ?>">
                    <a href="<?php echo $adminBase; ?>admin_chat.php">
                        <i class="bi bi-chat-dots"></i>
                        <span>Messages</span>
                        <span id="sidebarUnreadBadge" class="sidebar-badge" style="display: none;"></span>
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="javascript:void(0)" class="logout-btn logout-link">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Global Toast Styles -->
<style>
    .sidebar-toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .sidebar-toast {
        background: #ffffff;
        border-left: 5px solid #012b43;
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 15px;
        transform: translateX(120%);
        transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        min-width: 300px;
    }
    .sidebar-toast.show {
        transform: translateX(0);
    }
    .sidebar-toast-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .toast-info .sidebar-toast-icon { background: rgba(1, 43, 67, 0.1); color: #012b43; }
    .toast-success .sidebar-toast-icon { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
    .toast-warning .sidebar-toast-icon { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
    
    .sidebar-toast-content { flex: 1; }
    .sidebar-toast-title { font-weight: 700; color: #0f172a; font-size: 14px; margin-bottom: 2px; }
    .sidebar-toast-msg { color: #64748b; font-size: 13px; }
</style>

<div id="sidebarToastContainer" class="sidebar-toast-container"></div>

<!-- Dynamic Sidebar Badge Logic -->
<script>
    (function() {
        /**
         * Real-time Sidebar Notifications
         * Periodically fetches unread counts and triggers global toasts for new activity.
         */
        const sidebarBase = "<?php echo $adminBase; ?>";
        const currentPage = "<?php echo $currentPage; ?>";
        let lastOrderCount = -1;
        let lastApptCount = -1;
        let lastMsgCount = -1;

        const showGlobalToast = (title, msg, type = 'info', icon = 'bi-bell', link = null, orderID = null) => {
            const container = document.getElementById('sidebarToastContainer');
            const toast = document.createElement('div');
            toast.className = `sidebar-toast toast-${type}`;
            if (link) {
                toast.style.cursor = 'pointer';
                toast.onclick = async () => {
                    if (orderID) {
                        try {
                            const fd = new FormData();
                            fd.append('orderID', orderID);
                            await fetch(`${sidebarBase}admin_api.php?action=mark_order_seen`, { method: 'POST', body: fd });
                        } catch(e) {}
                    }
                    window.location.href = link;
                };
            }
            toast.innerHTML = `
                <div class="sidebar-toast-icon"><i class="bi ${icon}"></i></div>
                <div class="sidebar-toast-content">
                    <div class="sidebar-toast-title">${title}</div>
                    <div class="sidebar-toast-msg">${msg}</div>
                </div>
            `;
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 6000); // Slightly longer for readable links
        };

        const updateSidebarBadges = async () => {
            try {
                // Get last seen IDs from localStorage to filter backend SQL (for Orders & Appointments)
                const lastOrder = localStorage.getItem('jde_admin_last_order') || 0;
                const lastAppt = localStorage.getItem('jde_admin_last_appt') || 0;

                // Ensure fresh data
                const response = await fetch(`${sidebarBase}admin_api.php?action=get_unread_counts&last_order=${lastOrder}&last_appt=${lastAppt}&t=${Date.now()}`, {
                    cache: 'no-store'
                });
                const data = await response.json();
                
                if (data.success && data.counts) {
                    const counts = data.counts;
                    
                    // --- Client-Side Read State Management ---
                    
                    // Low Stock Products
                    let seenLowStock = parseInt(localStorage.getItem('jde_admin_seen_low_stock') || '0', 10);
                    if (currentPage === 'products.php') {
                        seenLowStock = counts.lowStock;
                        localStorage.setItem('jde_admin_seen_low_stock', seenLowStock);
                    }
                    let displayLowStock = Math.max(0, counts.lowStock - seenLowStock);
                    if (counts.lowStock < seenLowStock) { 
                        localStorage.setItem('jde_admin_seen_low_stock', counts.lowStock);
                        displayLowStock = 0;
                    }

                    // Messages / Chat
                    let seenMessages = parseInt(localStorage.getItem('jde_admin_seen_messages') || '0', 10);
                    if (currentPage === 'admin_chat.php' || currentPage === 'inquiries.php') {
                        seenMessages = counts.messages;
                        localStorage.setItem('jde_admin_seen_messages', seenMessages);
                    }
                    let displayMessages = Math.max(0, counts.messages - seenMessages);
                    if (counts.messages < seenMessages) {
                        localStorage.setItem('jde_admin_seen_messages', counts.messages);
                        displayMessages = 0;
                    }

                    // For Orders and Appointments, the Backend already filters by lastOrder/lastAppt IDs
                    if (currentPage === 'orders.php' && data.counts.maxOrderID) {
                        localStorage.setItem('jde_admin_last_order', data.counts.maxOrderID);
                        // Hide badge for orders while on the orders page
                        counts.orders = 0;
                    }
                    if (currentPage === 'appointments.php' && data.counts.maxApptID) {
                        localStorage.setItem('jde_admin_last_appt', data.counts.maxApptID);
                    }
                    
                    // --- Trigger Real-time Global Toasts ---
                    
                    // New Orders
                    if (lastOrderCount !== -1 && (counts.orders - counts.cancellations) > lastOrderCount && currentPage !== 'orders.php') {
                        showGlobalToast('New Order!', `A new pending order has been placed.`, 'success', 'bi-cart-plus', `${sidebarBase}orders.php`);
                    }

                    // Cancellations
                    if (counts.cancelledDetails && counts.cancelledDetails.length > 0) {
                        counts.cancelledDetails.forEach(order => {
                            // Only toast if not already toasted this session/cycle
                            const toastKey = `toasted_cancel_${order.orderID}`;
                            if (!sessionStorage.getItem(toastKey)) {
                                showGlobalToast('Order Cancelled', `Order #ORD-${String(order.orderID).padStart(3, '0')} was cancelled by ${order.customerName}.`, 'warning', 'bi-x-circle', `${sidebarBase}orders.php?orderID=${order.orderID}`, order.orderID);
                                sessionStorage.setItem(toastKey, 'true');
                            }
                        });
                    }

                    if (lastApptCount !== -1 && counts.appointments > lastApptCount && currentPage !== 'appointments.php') {
                        showGlobalToast('Appointment Booked!', `A new customer has scheduled an appointment.`, 'info', 'bi-calendar-event', `${sidebarBase}appointments.php`);
                    }
                    if (lastMsgCount !== -1 && displayMessages > lastMsgCount && currentPage !== 'admin_chat.php' && currentPage !== 'inquiries.php') {
                        showGlobalToast('New Message', `A customer has sent a new inquiry or message.`, 'info', 'bi-chat-dots', `${sidebarBase}admin_chat.php`);
                    }

                    // Store global state for next poll
                    lastOrderCount = counts.orders - counts.cancellations;
                    lastApptCount = counts.appointments;
                    lastMsgCount = displayMessages;

                    // --- Update UI Badges ---
                    const orderBadge = document.getElementById('sidebarOrdersBadge');
                    if (orderBadge) {
                        orderBadge.textContent = counts.orders;
                        orderBadge.style.display = counts.orders > 0 ? 'inline-block' : 'none';
                        // Add red status if there are cancellations
                        if (counts.cancellations > 0) {
                            orderBadge.style.background = '#e74c3c';
                        } else {
                            orderBadge.style.background = ''; // default from CSS
                        }
                    }
                    
                    const apptBadge = document.getElementById('sidebarAppointmentsBadge');
                    if (apptBadge) {
                        apptBadge.textContent = counts.appointments;
                        apptBadge.style.display = counts.appointments > 0 ? 'inline-block' : 'none';
                    }
                    
                    const msgBadge = document.getElementById('sidebarUnreadBadge');
                    if (msgBadge) {
                        msgBadge.textContent = displayMessages;
                        msgBadge.style.display = displayMessages > 0 ? 'inline-block' : 'none';
                    }

                    const stockBadge = document.getElementById('sidebarLowStockBadge');
                    if (stockBadge) {
                        stockBadge.textContent = displayLowStock;
                        stockBadge.style.display = displayLowStock > 0 ? 'inline-block' : 'none';
                    }

                    // Redundant logic removed
                }
            } catch (error) {
                console.error('Sidebar Polling Critical Failure:', error);
            }
        };

        // Update every 3 seconds for a truly "Instant" feel
        setInterval(updateSidebarBadges, 3000);
        
        // Initial execution
        updateSidebarBadges();
    })();
</script>

<!-- Logout Confirmation Modal (Standardized) -->
<div id="logoutModal" class="modal-overlay logout-modal">
    <div class="modal logout-modal-content">
        <div class="modal-header">
            <div class="logout-icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <h5 class="modal-title">Sign Out</h5>
        </div>
        <div class="modal-body">
            Are you sure you want to log out? Any unsaved changes might be lost.
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-stay" onclick="closeLogoutModal()">Cancel</button>
            <a href="<?php echo $userBase; ?>logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</div>