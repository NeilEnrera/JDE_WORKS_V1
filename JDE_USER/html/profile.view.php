<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Personal Center - JDE WORK OF OUR HANDS</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Flatpickr (Premium Date Picker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
</head>

<body>
    <?php
    include '../backend/navbar.php';
    ?>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" href="../css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/address-picker.css?v=<?php echo time(); ?>">

    <div class="profile-section">
        <div class="container">
            <h1 class="personal-center-title">Personal Center</h1>

            <?php /* Success message is now shown via modal at the bottom of the file */ ?>



            <div class="row g-4">
                <!-- Sidebar -->
                <div class="col-lg-3">
                    <?php
                    $active_nav = 'overview'; // Default
                    include 'fragments/user_sidebar.php';
                    ?>
                </div>

                <!-- Main Content -->
                <div class="col-lg-9">

                    <!-- Overview Pane (Dashboard) -->
                    <div id="overview" class="profile-pane active">
                        <!-- Welcome Card -->
                        <div class="dashboard-welcome-card">
                            <div class="welcome-info">
                                <h2 class="welcome-text">Hi,
                                    <?php echo htmlspecialchars($user_data['firstName']); ?>
                                </h2>
                                <span class="member-tag">MEMBER <i class="bi bi-star-fill text-warning ms-1"
                                        style="font-size: 0.8rem;"></i></span>
                            </div>
                        </div>

                        <!-- Orders Status -->
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h3>My Orders</h3>
                                <a href="orders.php" class="view-all-link">View All <i
                                        class="bi bi-chevron-right"></i></a>
                            </div>
                            <div class="section-body">
                                <div class="status-grid">
                                    <a href="orders.php?status=pending" class="status-item"
                                        onclick="clearNotification('pending', this)">
                                        <div class="position-relative">
                                            <i class="bi bi-credit-card status-icon"></i>
                                            <?php if ($counts['pending'] > 0): ?>
                                                <span class="count-badge">
                                                    <?php echo $counts['pending']; ?>
                                                </span>
                                                <?php
                                            endif; ?>
                                        </div>
                                        <span class="status-label">Unpaid</span>
                                    </a>
                                    <a href="orders.php?status=paid" class="status-item"
                                        onclick="clearNotification('paid', this)">
                                        <div class="position-relative">
                                            <i class="bi bi-check2-circle status-icon"></i>
                                            <?php if ($counts['paid'] > 0): ?>
                                                <span class="count-badge">
                                                    <?php echo $counts['paid']; ?>
                                                </span>
                                                <?php
                                            endif; ?>
                                        </div>
                                        <span class="status-label">Paid</span>
                                    </a>
                                    <a href="orders.php?status=processing" class="status-item"
                                        onclick="clearNotification('processing', this)">
                                        <div class="position-relative">
                                            <i class="bi bi-box status-icon"></i>
                                            <?php if ($counts['processing'] > 0): ?>
                                                <span class="count-badge">
                                                    <?php echo $counts['processing']; ?>
                                                </span>
                                                <?php
                                            endif; ?>
                                        </div>
                                        <span class="status-label">Processing</span>
                                    </a>
                                    <a href="orders.php?status=out_for_delivery" class="status-item"
                                        onclick="clearNotification('out_for_delivery', this)">
                                        <div class="position-relative">
                                            <i class="bi bi-truck status-icon"></i>
                                            <?php if ($counts['out_for_delivery'] > 0): ?>
                                                <span class="count-badge">
                                                    <?php echo $counts['out_for_delivery']; ?>
                                                </span>
                                                <?php
                                            endif; ?>
                                        </div>
                                        <span class="status-label">Out for Delivery</span>
                                    </a>
                                    <a href="orders.php?status=history" class="status-item"
                                        onclick="clearNotification('history', this)">
                                        <div class="position-relative">
                                            <i class="bi bi-clock-history status-icon"></i>
                                            <?php if ($counts['history'] > 0): ?>
                                                <span class="count-badge">
                                                    <?php echo $counts['history']; ?>
                                                </span>
                                                <?php
                                            endif; ?>
                                        </div>
                                        <span class="status-label">Order History</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="dashboard-section mt-4">
                            <div class="section-header">
                                <h3>Account Information</h3>
                                <a href="javascript:void(0)" onclick="showPane('account', null)"
                                    class="view-all-link">Edit <i class="bi bi-pencil"></i></a>
                            </div>
                            <div class="section-body">
                                <div class="quick-info-grid">
                                    <div class="info-group">
                                        <div class="info-item">
                                            <label>Full Name</label>
                                            <div class="value">
                                                <?php echo htmlspecialchars($user_data['firstName'] . ' ' . $user_data['lastName']); ?>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <label>Phone Number</label>
                                            <div class="value">
                                                <?php echo htmlspecialchars($user_data['phoneNumber'] ?? 'Not specified'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-item">
                                            <label>Email</label>
                                            <div class="value"><?php echo htmlspecialchars($user_data['email']); ?>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <label>Gender</label>
                                            <div class="value">
                                                <?php
                                                if (($user_data['gender'] ?? '') === 'M' || ($user_data['gender'] ?? '') === 'Male')
                                                    echo 'Male';
                                                elseif (($user_data['gender'] ?? '') === 'F' || ($user_data['gender'] ?? '') === 'Female')
                                                    echo 'Female';
                                                else
                                                    echo htmlspecialchars($user_data['gender'] ?? 'Not specified');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <label>Birth Date</label>
                                            <div class="value">
                                                <?php echo htmlspecialchars($user_data['birthday'] ? date('M d, Y', strtotime($user_data['birthday'])) : 'Not specified'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Addresses Pane -->
                    <div id="addresses" class="profile-pane">
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h3 class="pane-title">My Addresses</h3>
                                <button class="add-address-btn" onclick="openAddressModal()">
                                    <i class="bi bi-plus-lg me-1"></i> Add New Address
                                </button>
                            </div>
                            <div class="section-body">
                                <div id="address-list-container">
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Settings Pane -->
                    <div id="account" class="profile-pane">
                        <h3 class="pane-title mb-4">Account Settings</h3>
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h3>Personal Information</h3>
                            </div>
                            <div class="section-body">
                                <form method="POST" id="profileUpdateForm">
                                    <input type="hidden" name="update_profile" value="1">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="profile-form-group">
                                                <label>First Name</label>
                                                <input type="text" name="first_name" class="profile-form-control"
                                                    value="<?php echo htmlspecialchars($user_data['firstName']); ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="profile-form-group">
                                                <label>Last Name</label>
                                                <input type="text" name="last_name" class="profile-form-control"
                                                    value="<?php echo htmlspecialchars($user_data['lastName']); ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="profile-form-group">
                                                <label>Email Address</label>
                                                <input type="email" name="email" class="profile-form-control"
                                                    value="<?php echo htmlspecialchars($user_data['email']); ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="profile-form-group">
                                                <label>Phone Number</label>
                                                <input type="tel" name="phone" class="profile-form-control"
                                                    value="<?php echo htmlspecialchars($user_data['phoneNumber'] ?? ''); ?>"
                                                    placeholder="e.g. 09123456789" maxlength="11"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="profile-form-group">
                                                <label>Gender</label>
                                                <select name="gender" class="profile-form-control">
                                                    <option value="">Select Gender</option>
                                                    <option value="M" <?php echo (($user_data['gender'] ?? '') === 'M' || ($user_data['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>
                                                        Male</option>
                                                    <option value="F" <?php echo (($user_data['gender'] ?? '') === 'F' || ($user_data['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>
                                                        Female</option>
                                                    <option value="Other" <?php echo (($user_data['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="profile-form-group">
                                                <label>Birth Date</label>
                                                <input type="text" name="birthday" id="birthdayPicker"
                                                    placeholder="Select your birth date" readonly
                                                    class="profile-form-control"
                                                    value="<?php echo htmlspecialchars($user_data['birthday'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="save-btn mt-3">Save Changes</button>
                                </form>
                            </div>
                        </div>

                    </div>

                    <!-- Security & Password Pane -->
                    <div id="security" class="profile-pane">
                        <h3 class="pane-title mb-4">Security & Password</h3>
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h3>Change Password</h3>
                            </div>
                            <div class="section-body">
                                <div class="mb-3">
                                    <form method="POST" id="passwordUpdateForm">
                                        <input type="hidden" name="change_password" value="1">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="profile-form-group">
                                                    <label>Current Password</label>
                                                    <div class="password-toggle-wrapper">
                                                        <input type="password" name="current_password"
                                                            id="current_password"
                                                            class="profile-form-control password-toggle-control"
                                                            required>
                                                        <i class="bi bi-eye-slash password-toggle-icon"
                                                            onclick="togglePassword('current_password', this)"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="profile-form-group">
                                                    <label>New Password</label>
                                                    <div class="password-toggle-wrapper">
                                                        <input type="password" name="new_password" id="new_password"
                                                            class="profile-form-control password-toggle-control"
                                                            required>
                                                        <i class="bi bi-eye-slash password-toggle-icon"
                                                            onclick="togglePassword('new_password', this)"></i>
                                                    </div>
                                                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                                        Must be 8+ characters with uppercase, lowercase, numbers &
                                                        symbols.
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="profile-form-group">
                                                    <label>Confirm New Password</label>
                                                    <div class="password-toggle-wrapper">
                                                        <input type="password" name="confirm_password"
                                                            id="confirm_password"
                                                            class="profile-form-control password-toggle-control"
                                                            required>
                                                        <i class="bi bi-eye-slash password-toggle-icon"
                                                            onclick="togglePassword('confirm_password', this)"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="save-btn mt-3">Update Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My Appointments Pane -->
                    <div id="appointments" class="profile-pane">
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h3>My Appointments</h3>
                                <a href="appointment.php" class="add-address-btn text-decoration-none">
                                    <i class="bi bi-calendar-plus me-1"></i> Book New
                                </a>
                            </div>
                            <div class="section-body">

                                <?php if (!empty($has_blocked_conflict)): ?>
                                    <div class="alert shadow-sm border-0 d-flex align-items-center mb-4"
                                        style="background: linear-gradient(135deg, #fff7ed, #ffedd5); border-radius: 16px; padding: 20px;">
                                        <div class="me-3"
                                            style="background: #f59e0b; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1 text-dark">Schedule Update Required</h6>
                                            <p class="mb-0 text-muted small">Some of your previous appointments were
                                                cancelled due to admin-blocked dates. Please book a new slot.</p>
                                        </div>
                                        <a href="appointment.php" class="btn btn-sm fw-bold ms-3"
                                            onclick="syncRescheduleDismissal()"
                                            style="background: #f59e0b; color: white; border-radius: 8px; padding: 8px 16px;">
                                            Reschedule Now
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if (empty($appointments)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-2">No appointments scheduled yet.</p>
                                    </div>
                                    <?php
                                else: ?>
                                    <div class="appointment-list">
                                        <?php
                                        $services = [
                                            'school-uniform' => 'School Uniform',
                                            'corporate-wear' => 'Corporate Wear',
                                            'business-suit' => 'Business Suit',
                                            'custom-tailoring' => 'Custom Tailoring',
                                            'alteration' => 'Alteration Service',
                                            'consultation' => 'General Consultation'
                                        ];
                                        foreach ($appointments as $appt):
                                            $svcLabel = $services[$appt['serviceType']] ?? htmlspecialchars($appt['serviceType']);
                                            $fullName = htmlspecialchars(trim(($user_data['firstName'] ?? '') . ' ' . ($user_data['lastName'] ?? '')));
                                            $apptId = htmlspecialchars($appt['appointmentID']);
                                            $apptDate = htmlspecialchars(date('F j, Y', strtotime($appt['appointmentDate'])));
                                            $apptTime = htmlspecialchars($appt['appointmentTime']);
                                            $apptStatus = htmlspecialchars($appt['appointmentStatus']);
                                            $apptNote = htmlspecialchars($appt['message'] ?? '');
                                            $apptBooked = htmlspecialchars(date('M j, Y g:i A', strtotime($appt['dateCreated'])));
                                            ?>
                                            <div class="appointment-card-item" onclick="openApptModal(
                                                    <?php echo htmlspecialchars(json_encode($apptId), ENT_QUOTES); ?>,
                                                    <?php echo htmlspecialchars(json_encode($fullName), ENT_QUOTES); ?>,
                                                    <?php echo htmlspecialchars(json_encode($svcLabel), ENT_QUOTES); ?>,
                                                    <?php echo htmlspecialchars(json_encode($apptDate), ENT_QUOTES); ?>,
                                                    <?php echo htmlspecialchars(json_encode($apptTime), ENT_QUOTES); ?>,
                                                    <?php echo htmlspecialchars(json_encode($apptStatus), ENT_QUOTES); ?>,
                                                    <?php echo htmlspecialchars(json_encode($apptNote), ENT_QUOTES); ?>,
                                                    <?php echo htmlspecialchars(json_encode($apptBooked), ENT_QUOTES); ?>,
                                                    <?php echo (int) $appt['is_blocked_conflict']; ?>
                                                )" style="cursor: pointer;" title="Click to view details">
                                                <div class="appointment-card-header">
                                                    <div class="d-flex align-items-center">
                                                        <div class="appointment-date-badge">
                                                            <span class="month">
                                                                <?php echo date('M', strtotime($appt['appointmentDate'])); ?>
                                                            </span>
                                                            <span class="day">
                                                                <?php echo date('d', strtotime($appt['appointmentDate'])); ?>
                                                            </span>
                                                        </div>
                                                        <div class="ms-3">
                                                            <div class="appointment-service">
                                                                <?php echo $svcLabel; ?>
                                                            </div>
                                                            <div class="appointment-time">
                                                                <i class="bi bi-clock me-1"></i>
                                                                <?php echo $appt['appointmentTime']; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="appointment-status mt-2 mt-md-0">
                                                        <?php
                                                        $statusClass = '';
                                                        $statusLabel = $appt['appointmentStatus'];
                                                        $status = $appt['appointmentStatus'];

                                                        if ($appt['is_blocked_conflict']) {
                                                            $statusClass = 'status-pending';
                                                            $statusLabel = 'Reschedule Needed';
                                                        } elseif ($status === 'Pending') {
                                                            $statusClass = 'status-pending';
                                                        } elseif ($status === 'Confirmed') {
                                                            $statusClass = 'status-approve';
                                                            $statusLabel = 'Confirmed';
                                                        } elseif ($status === 'Cancelled') {
                                                            $statusClass = 'status-reject';
                                                            $statusLabel = 'Cancelled';
                                                        } elseif ($status === 'Completed') {
                                                            $statusClass = 'status-completed';
                                                        }
                                                        ?>
                                                        <span class="status-badge <?php echo $statusClass; ?>">
                                                            <?php echo $statusLabel; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <?php if (!empty($appt['message'])): ?>
                                                    <div class="appointment-note mt-3">
                                                        <i class="bi bi-chat-left-text me-1"></i>
                                                        <span>Note:
                                                            <?php echo htmlspecialchars($appt['message']); ?>
                                                        </span>
                                                    </div>
                                                    <?php
                                                endif; ?>
                                            </div>
                                            <?php
                                        endforeach; ?>
                                    </div>
                                    <?php
                                endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Modal -->
    <div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="addressModalLabel">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <form id="address-form">
                        <input type="hidden" id="address-id" name="addressID">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Receiver Name</label>
                            <input type="text" class="profile-form-control" id="receiver-name" required
                                placeholder="e.g. John Doe" readonly
                                style="background-color: #f8f9fa; color: #333; opacity: 1;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="tel" class="profile-form-control" id="receiver-phone" required
                                placeholder="e.g. 09123456789" pattern="09[0-9]{9}" maxlength="11"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Street Address</label>
                            <input type="text" class="profile-form-control" id="address-line" required
                                placeholder="Street name, House/Unit #">
                        </div>
                        <div class="address-field-group">
                            <label class="address-field-label">Province / State*</label>
                            <select class="address-field-select" id="province" required>
                                <option value="">-- SELECT PROVINCE --</option>
                            </select>
                        </div>
                        <div class="address-field-group">
                            <label class="address-field-label">City / Municipality*</label>
                            <select class="address-field-select" id="city" required disabled>
                                <option value="">-- SELECT CITY --</option>
                            </select>
                        </div>
                        <div class="address-field-group">
                            <label class="address-field-label">Barangay*</label>
                            <select class="address-field-select" id="barangay" required disabled>
                                <option value="">-- SELECT BARANGAY --</option>
                            </select>
                        </div>
                        <div class="address-field-group">
                            <label class="address-field-label">Postal Code*</label>
                            <input type="text" class="address-field-input" id="zip" required placeholder="ZIP">
                        </div>
                        <button type="submit" class="save-btn w-100 mt-2" id="btn-save-address">Save Address</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Write Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content"
                style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                <div class="modal-header border-0 pb-0" style="padding: 24px 24px 0;">
                    <h5 class="modal-title fw-bold">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <p class="text-muted small mb-4">Share your experience with our product to help other customers!</p>
                    <form id="profileReviewForm" enctype="multipart/form-data">
                        <input type="hidden" name="product_slug" id="reviewProductSlug">

                        <div class="mb-4 text-center">
                            <label class="form-label fw-medium mb-3 d-block">Your Rating</label>
                            <div class="star-rating-input d-flex justify-content-center flex-row-reverse">
                                <input type="radio" id="pstar5" name="rating" value="5" /><label for="pstar5"
                                    title="5 stars"></label>
                                <input type="radio" id="pstar4" name="rating" value="4" /><label for="pstar4"
                                    title="4 stars"></label>
                                <input type="radio" id="pstar3" name="rating" value="3" /><label for="pstar3"
                                    title="3 stars"></label>
                                <input type="radio" id="pstar2" name="rating" value="2" /><label for="pstar2"
                                    title="4 stars"></label>
                                <input type="radio" id="pstar1" name="rating" value="1" /><label for="pstar1"
                                    title="1 stars"></label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium mb-2">How was the fit?</label>
                            <div class="d-flex gap-2 text-center">
                                <input type="radio" class="btn-check" name="fit" id="pfitSmall" value="Small"
                                    autocomplete="off">
                                <label class="btn btn-outline-secondary flex-fill" for="pfitSmall"
                                    style="border-radius: 8px; font-size: 0.9rem;">Small</label>

                                <input type="radio" class="btn-check" name="fit" id="pfitTrue" value="True to Size"
                                    autocomplete="off" checked>
                                <label class="btn btn-outline-secondary flex-fill" for="pfitTrue"
                                    style="border-radius: 8px; font-size: 0.9rem;">True Size</label>

                                <input type="radio" class="btn-check" name="fit" id="pfitLarge" value="Large"
                                    autocomplete="off">
                                <label class="btn btn-outline-secondary flex-fill" for="pfitLarge"
                                    style="border-radius: 8px; font-size: 0.9rem;">Large</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="reviewCommentField" class="form-label fw-medium mb-2">Feedback</label>
                            <textarea id="reviewCommentField" name="comment" class="form-control" rows="4"
                                placeholder="Describe the quality, style, and fit..." required
                                style="border-radius: 12px; border: 1px solid #eee; padding: 12px; resize: none; font-size: 0.95rem;"></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="reviewImageInput" class="form-label fw-medium mb-2">Add Photo <span
                                    class="text-muted fw-normal">(Optional)</span></label>
                            <div class="p-3 border rounded-4 text-center"
                                style="border-style: dashed !important; background: #fafafa; cursor: pointer;"
                                onclick="document.getElementById('reviewImageInput').click()">
                                <input type="file" id="reviewImageInput" name="review_image" accept="image/*"
                                    class="d-none" onchange="updateFileName(this)">
                                <i class="bi bi-camera fs-2 text-muted mb-2 d-block"></i>
                                <span id="fileNameDisplay" class="text-muted small">Tap to upload a photo of your
                                    item</span>
                            </div>
                        </div>

                        <button type="submit" class="btn w-100 py-3 fw-bold shadow-sm text-white"
                            style="background: #d6b25e; border: none; border-radius: 12px; font-size: 1.1rem; transition: all 0.3s;"
                            onmouseover="this.style.backgroundColor='#c49e4a'"
                            onmouseout="this.style.backgroundColor='#d6b25e'">Post
                            Review</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="profileErrorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 justify-content-center mt-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px; background-color: rgba(231, 76, 60, 0.1);">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="modal-body text-center px-4 pt-4 pb-5">
                    <h5 class="fw-bold mb-3">Update Failed</h5>
                    <p class="text-muted mb-4" id="profileErrorModalText"></p>
                    <button type="button" class="btn btn-secondary px-5" data-bs-dismiss="modal"
                        style="border-radius: 8px;">Try Again</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="profileSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 justify-content-center mt-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px; background-color: rgba(39, 174, 96, 0.1);">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="modal-body text-center px-4 pt-4 pb-5">
                    <h5 class="fw-bold mb-3" id="profileSuccessModalTitle">Success</h5>
                    <p class="text-muted mb-4" id="profileSuccessModalText"></p>
                    <button type="button" class="btn btn-primary px-5" data-bs-dismiss="modal"
                        style="border-radius: 8px;">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-3">Delete Address?</h5>
                    <p class="text-muted mb-4 text-sm">Are you sure you want to remove this address? This action cannot
                        be undone.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Detail Modal -->
    <div class="modal fade" id="apptDetailModal" tabindex="-1" aria-labelledby="apptDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header border-0 text-white"
                    style="background: linear-gradient(135deg, #b8860b, #daa520); padding: 20px 24px;">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="apptDetailModalLabel">
                            <i class="bi bi-calendar-check me-2"></i>Appointment Details
                        </h5>
                        <small class="opacity-75" id="apptDetailId">Booking #&mdash;</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div style="background:#f8f9fa; border-radius:12px; padding:14px;">
                                <div class="text-muted"
                                    style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">
                                    Name</div>
                                <div class="fw-semibold mt-1" id="apptDetailName" style="font-size:.95rem;">&mdash;
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background:#f8f9fa; border-radius:12px; padding:14px;">
                                <div class="text-muted"
                                    style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">
                                    Service</div>
                                <div class="fw-semibold mt-1" id="apptDetailService" style="font-size:.95rem;">&mdash;
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background:#f8f9fa; border-radius:12px; padding:14px;">
                                <div class="text-muted"
                                    style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">
                                    Date</div>
                                <div class="fw-semibold mt-1" id="apptDetailDate" style="font-size:.95rem;">&mdash;
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background:#f8f9fa; border-radius:12px; padding:14px;">
                                <div class="text-muted"
                                    style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">
                                    Time</div>
                                <div class="fw-semibold mt-1" id="apptDetailTime" style="font-size:.95rem;">&mdash;
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div style="background:#f8f9fa; border-radius:12px; padding:14px;">
                                <div class="text-muted"
                                    style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">
                                    Status</div>
                                <div class="mt-1" id="apptDetailStatus">&mdash;</div>
                            </div>
                        </div>
                        <div class="col-12" id="apptDetailNoteRow">
                            <div
                                style="background:#fff8e1; border-radius:12px; padding:14px; border-left: 3px solid #daa520;">
                                <div class="text-muted"
                                    style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">
                                    <i class="bi bi-chat-left-text me-1"></i>Note
                                </div>
                                <div class="mt-1" id="apptDetailNote" style="font-size:.9rem;">&mdash;</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="text-center text-muted" style="font-size:0.8rem;">
                                <i class="bi bi-clock-history me-1"></i>Booked on: <span
                                    id="apptDetailBooked">&mdash;</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($reschedule_notice)): ?>
        <!-- Reschedule Warning Modal -->
        <div class="modal fade" id="rescheduleWarningModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-header border-0 text-white"
                        style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 25px;">
                        <div class="d-flex align-items-center gap-3">
                            <div
                                style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <h5 class="modal-title fw-bold mb-0">Schedule Update Required</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
                            onclick="syncRescheduleDismissal()"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        We apologize for the inconvenience &mdash; please reschedule your appointment.
                        <div
                            style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 15px; padding: 20px; margin-bottom: 25px;">
                            <span
                                style="color: #92400e; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Action
                                Recommended</span>
                            <p style="color: #78350f; font-weight: 600; margin-top: 5px; margin-bottom: 0;">Click below to
                                browse available dates and book a new slot.</p>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn flex-grow-1 py-3 fw-bold border" data-bs-dismiss="modal"
                                onclick="syncRescheduleDismissal()"
                                style="border-radius: 12px; font-size: 0.9rem;">Dismiss</button>
                            <a href="appointment.php" class="btn flex-grow-1 py-3 fw-bold"
                                onclick="syncRescheduleDismissal()"
                                style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(217, 119, 6, 0.3); font-size: 0.9rem;">
                                Reschedule Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Flatpickr JS (CDN) — initialized in profile.js -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
    <script src="../js/ph-locations.js?v=<?php echo time(); ?>"></script>
    <script src="../js/profile.js?v=<?php echo time(); ?>"></script>
    <script src="../js/reviews.js?v=<?php echo time(); ?>"></script>
</body>

</html>