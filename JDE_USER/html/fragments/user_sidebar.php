<?php
/**
 * user_sidebar.php — Shared sidebar fragment for user account pages.
 * Handles active state highlighting and cross-page navigation logic.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$active_nav = $active_nav ?? '';

// Determine active state for order filters based on status query param
$currentStatus = $_GET['status'] ?? '';
?>

<!-- Account Sidebar -->
<div class="sidebar-category">
    <div class="sidebar-category-name">My Account</div>
    <ul class="personal-nav-list">
        <li class="personal-nav-item">
            <a href="<?php echo $currentPage === 'profile.php' ? 'javascript:void(0)' : 'profile.php?pane=overview'; ?>"
                class="personal-nav-link <?php echo $active_nav === 'overview' ? 'active' : ''; ?>" id="nav-overview"
                <?php echo $currentPage === 'profile.php' ? 'onclick="showPane(\'overview\', this)"' : ''; ?>>
                <i class="bi bi-person"></i> My Profile
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="<?php echo $currentPage === 'profile.php' ? 'javascript:void(0)' : 'profile.php?pane=addresses'; ?>"
                class="personal-nav-link <?php echo $active_nav === 'addresses' ? 'active' : ''; ?>" id="nav-addresses"
                <?php echo $currentPage === 'profile.php' ? 'onclick="showPane(\'addresses\', this)"' : ''; ?>>
                <i class="bi bi-journal-text"></i> Address Book
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="<?php echo $currentPage === 'profile.php' ? 'javascript:void(0)' : 'profile.php?pane=security'; ?>"
                class="personal-nav-link <?php echo $active_nav === 'security' ? 'active' : ''; ?>" id="nav-security"
                <?php echo $currentPage === 'profile.php' ? 'onclick="showPane(\'security\', this)"' : ''; ?>>
                <i class="bi bi-shield-lock"></i> Security &amp; Password
            </a>
        </li>
    </ul>
</div>

<div class="sidebar-category">
    <div class="sidebar-category-name">My Orders</div>
    <ul class="personal-nav-list">
        <li class="personal-nav-item">
            <a href="orders.php"
                class="personal-nav-link <?php echo ($currentPage === 'orders.php' && $currentStatus === '') ? 'active' : ''; ?>">
                <i class="bi bi-list-task"></i> All Orders
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="orders.php?status=pending"
                class="personal-nav-link <?php echo ($currentStatus === 'pending') ? 'active' : ''; ?>">
                <i class="bi bi-wallet2"></i> Unpaid Orders
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="orders.php?status=paid"
                class="personal-nav-link <?php echo ($currentStatus === 'paid') ? 'active' : ''; ?>">
                <i class="bi bi-check2-circle"></i> Paid Orders
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="orders.php?status=processing"
                class="personal-nav-link <?php echo ($currentStatus === 'processing') ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i> Processing Orders
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="orders.php?status=out_for_delivery"
                class="personal-nav-link <?php echo ($currentStatus === 'out_for_delivery') ? 'active' : ''; ?>">
                <i class="bi bi-truck"></i> Out for Delivery
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="orders.php?status=history"
                class="personal-nav-link <?php echo ($currentStatus === 'history') ? 'active' : ''; ?>">
                <i class="bi bi-clock-history"></i> Order History
            </a>
        </li>
    </ul>
</div>

<div class="sidebar-category">
    <div class="sidebar-category-name">Other Services</div>
    <ul class="personal-nav-list">
        <li class="personal-nav-item">
            <a href="chat.php" class="personal-nav-link <?php echo $currentPage === 'chat.php' ? 'active' : ''; ?>">
                <i class="bi bi-chat-dots"></i> My Message
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="<?php echo $currentPage === 'profile.php' ? 'javascript:void(0)' : 'profile.php?pane=appointments'; ?>"
                class="personal-nav-link <?php echo $active_nav === 'appointments' ? 'active' : ''; ?>"
                id="nav-appointments" <?php echo $currentPage === 'profile.php' ? 'onclick="showPane(\'appointments\', this)"' : ''; ?>>
                <i class="bi bi-calendar-event"></i> My Appointments
            </a>
        </li>
        <li class="personal-nav-item">
            <a href="javascript:void(0)" class="personal-nav-link logout-link text-danger" data-bs-toggle="modal"
                data-bs-target="#logoutModal">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </li>
    </ul>
</div>