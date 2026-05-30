<?php
// navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connection.php';

// Get cart count
$cartCount = isset($_SESSION['cart_count']) ? (int) $_SESSION['cart_count'] : 0;

// Get wishlist count
$wishlistCount = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// Get user info if logged in
if (isset($_SESSION['user_id']) && !isset($_SESSION['email'])) {
    $uID = $_SESSION['user_id'];
    $uType = $_SESSION['user_type'] ?? 'Customer';
    $tbl = ($uType === 'Employee') ? 'tbl_employee' : 'tbl_customer';
    $idCol = ($uType === 'Employee') ? 'employeeID' : 'customerID';
    $stmt = $conn->prepare("SELECT email, userName FROM $tbl WHERE $idCol = ?");
    if ($stmt) {
        $stmt->bind_param("i", $uID);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $_SESSION['email'] = $row['email'];
            $_SESSION['username'] = $row['userName'];
        }
        $stmt->close();
    }

    // Check if the current user has been blocked while active
    if ($_SESSION['user_type'] === 'Customer') {
        $checkBlocked = $conn->prepare("SELECT isBlocked FROM tbl_customer WHERE customerID = ?");
        $checkBlocked->bind_param("i", $_SESSION['user_id']);
        $checkBlocked->execute();
        $blockRes = $checkBlocked->get_result();
        if ($blockRow = $blockRes->fetch_assoc()) {
            if ($blockRow['isBlocked'] == 1) {
                // User is blocked - log them out immediately
                session_unset();
                session_destroy();
                header("Location: login.php?status=suspended");
                exit();
            }
        }
        $checkBlocked->close();
    }
}

$displayEmail = $_SESSION['email'] ?? 'account@example.com';
$displayName = $_SESSION['username'] ?? 'Customer';
?>

<!-- Chat Notifications -->
<link rel="stylesheet" href="../css/chat_notifications.css?v=<?php echo time(); ?>">
<script>
    window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    window.currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;
</script>

<nav class="navbar navbar-expand-md bg-white sticky-top shadow-sm">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-brand">
            <a href="index.php"><img src="../assets/img/logojd.png" alt="JDE Works of Our Hands Logo" class="logo"
                    loading="lazy"></a>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a
                        class="nav-link <?php echo (isset($activePage) && $activePage === 'home') ? 'active' : ''; ?>"
                        href="index.php">Home</a></li>
                <li class="nav-item"><a
                        class="nav-link <?php echo (isset($activePage) && $activePage === 'products') ? 'active' : ''; ?>"
                        href="product.php">Products</a></li>
                <li class="nav-item"><a
                        class="nav-link <?php echo (isset($activePage) && $activePage === 'appointment') ? 'active' : ''; ?>"
                        href="appointment.php">Appointment</a></li>
                <li class="nav-item"><a
                        class="nav-link <?php echo (isset($activePage) && $activePage === 'chat') ? 'active' : ''; ?>"
                        href="<?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Employee') ? 'admin_chat.php' : 'chat.php'; ?>">Live
                        Chat</a></li>
            </ul>
        </div>

        <div class="navbar-right button-container d-flex align-items-center gap-3">
            <!-- Cart Icon with Hover Preview -->
            <div class="dropdown dropdown-hover">
                <a href="cart.php" class="position-relative text-decoration-none d-flex align-items-center"
                    id="cartDropdown" aria-label="Cart" style="color: inherit; padding: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h7.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <?php if ($cartCount > 0): ?>
                        <span id="navbarCartBadge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size:10px; margin-top: 5px; margin-left: -5px;">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php else: ?>
                        <span id="navbarCartBadge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                            style="font-size:10px; margin-top: 5px; margin-left: -5px;">
                            0
                        </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end cart-dropdown-menu" id="cartDropdownMenu">
                    <!-- Header with Store Name -->
                    <div class="cart-preview-header">
                        <div class="store-line d-flex align-items-center">
                            <i class="bi bi-shop me-2"></i>
                            <span class="store-name fw-bold">JDE Works of Our Hands</span>
                            <i class="bi bi-chevron-right ms-1" style="font-size: 10px;"></i>
                        </div>
                    </div>

                    <!-- Scrollable Items List -->
                    <div class="cart-items-preview">
                        <!-- Items will be injected by navbar_cart.js -->
                    </div>

                    <!-- Footer -->
                    <div class="cart-footer-mini">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                <span class="all-label fw-semibold">Subtotal</span>
                            </div>
                            <div class="text-end">
                                <span class="cart-total-value fs-5 fw-bold">₱0.00</span>
                            </div>
                        </div>
                        <a href="cart.php" class="btn-view-cart fw-bold">VIEW CART (0)</a>
                    </div>
                </div>
            </div>

            <!-- Wishlist Icon -->
            <a href="wishlist.php" class="position-relative text-decoration-none d-flex align-items-center"
                aria-label="Wishlist" style="color: inherit; padding: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                </svg>
                <?php if ($wishlistCount > 0): ?>
                    <span id="navbarWishlistBadge"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                        style="font-size:10px; margin-top: 5px; margin-left: -5px;">
                        <?php echo $wishlistCount; ?>
                    </span>
                <?php else: ?>
                    <span id="navbarWishlistBadge"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                        style="font-size:10px; margin-top: 5px; margin-left: -5px;">
                        0
                    </span>
                <?php endif; ?>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown dropdown-hover">
                    <a href="profile.php" class="btn btn-light no-caret d-flex align-items-center" id="profileMenuButton"
                        aria-expanded="false" aria-label="Profile menu"
                        style="padding: 0; border: none; background: none; cursor: pointer;">
                        <span class="rounded-circle bg-secondary d-inline-flex justify-content-center align-items-center"
                            style="width:28px;height:28px;color:#fff;">
                            <span style="font-size:14px;line-height:1;">👤</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 220px;">
                        <li class="px-3 py-2">
                            <div class="fw-semibold" style="font-size: 14px;">
                                <?php echo htmlspecialchars($displayName); ?>
                            </div>
                            <div class="text-muted" style="font-size: 12px;">
                                <?php echo htmlspecialchars($displayEmail); ?>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                        <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                        <li><a class="dropdown-item" href="profile.php?pane=appointments">My Appointments</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item logout-link" href="javascript:void(0)" data-bs-toggle="modal"
                                data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="login">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal (Global) -->
<div class="modal fade logout-modal" id="logoutModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
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
                <button type="button" class="btn btn-stay" data-bs-dismiss="modal">Cancel</button>
                <a href="logout.php" id="logoutConfirmBtn" class="btn btn-logout">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Login Required Modal (Global) -->
<div class="modal fade logout-modal login-required-modal" id="loginRequiredModal" tabindex="-1" aria-hidden="true"
    style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="logout-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <h5 class="modal-title">Authentication Required</h5>
            </div>
            <div class="modal-body" id="loginRequiredMessage">
                Please log in to proceed.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-stay" data-bs-dismiss="modal">Cancel</button>
                <a href="login.php" class="btn btn-logout">Login</a>
            </div>
        </div>
    </div>
</div>
<!-- Account Suspended Modal (Global) -->
<div class="modal fade" id="suspendedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-hidden="true" style="z-index: 1200;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="suspended-icon-badge">
                    <i class="bi bi-person-x-fill"></i>
                </div>
                <div class="modal-header-label">Account Status</div>
                <h5 class="modal-header-title">Account Suspended</h5>
            </div>
            <div class="modal-body">
                <p id="suspendedMessage">
                    Your account has been blocked or suspended by an administrator.
                </p>
                <div class="support-hint">
                    <i class="bi bi-envelope-fill"></i>
                    Please contact support for assistance.
                </div>
            </div>
            <div class="modal-footer">
                <a href="logout.php?status=suspended" class="btn-suspended-ok">
                    I Understand
                </a>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'suspended' && !window.location.pathname.includes('login.php')) {
            const modalEl = document.getElementById('suspendedModal');
            const suspendedModal = new bootstrap.Modal(modalEl, { backdrop: false });
            suspendedModal.show();
        }
    });
</script>
<script src="../js/chat_notifications.js?v=<?php echo time(); ?>"></script>