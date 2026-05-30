<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../css/admin-common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/admin-appointments.css?v=<?php echo time(); ?>">
    <style>
        /* Force Flatpickr to appear above modal z-index */
        .flatpickr-calendar.open {
            z-index: 10000 !important;
        }

        /* Fix for grid alignment in the modal */
        #blockedDateWrapper .flatpickr-input-container {
            width: 100%;
        }
    </style>
    <title>
        Appointments Management
    </title>
</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/fragments/sidebar.php'; ?>

        <div class="main-content">



            <!-- Main Container -->
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-info">
                        <h2>Appointments Management</h2>
                        <p>Manage and track all customer service appointments</p>
                    </div>
                </div>

                <!-- Triage Tabs -->
                <div class="appointments-triage">
                    <div class="triage-group-left">
                        <div class="triage-tabs">
                            <button class="triage-tab active" data-filter="all">
                                All <span class="appt-count-badge" id="count-all">0</span>
                            </button>
                            <button class="triage-tab" data-filter="Pending">
                                Pending <span class="appt-count-badge" id="count-pending">0</span>
                            </button>
                            <button class="triage-tab" data-filter="Accepted">
                                Accepted <span class="appt-count-badge" id="count-accepted">0</span>
                            </button>
                            <button class="triage-tab" data-filter="Declined">
                                Declined <span class="appt-count-badge" id="count-declined">0</span>
                            </button>
                            <button class="triage-tab" data-filter="Cancelled">
                                Cancelled <span class="appt-count-badge" id="count-cancelled">0</span>
                            </button>
                            <div class="triage-divider"></div>
                            <button class="triage-tab upcoming-tab-highlight" data-filter="Upcoming">
                                <i class="bi bi-calendar2-check"></i> Upcoming
                                <span class="appt-count-badge" id="count-upcoming">0</span>
                            </button>
                        </div>
                    </div>

                    <div class="triage-group-right">
                        <button class="btn-appt-header-action secondary" onclick="openReminderSettingsModal()">
                            <i class="bi bi-gear-fill"></i> Email Settings
                        </button>
                        <button class="btn-appt-header-action primary" onclick="openBlockedDatesModal()">
                            <i class="bi bi-calendar-x"></i> Block Dates
                        </button>
                    </div>
                </div>

                <!-- Upcoming Appointments Header -->
                <div id="upcomingAppointmentsHeader" style="display: none; margin-bottom: 20px;">
                    <h3
                        style="font-size: 1.2rem; color: var(--primary); font-weight: 800; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-calendar-check" style="color: var(--accent);"></i> Upcoming Appointments
                    </h3>
                </div>

                <!-- Filter Bar -->
                <div class="filters-bar">
                    <div class="search-wrapper">
                        <i class="bi bi-search"></i>
                        <input type="text" id="apptSearchInput" placeholder="Search by name...">
                    </div>
                    <div class="date-filters">
                        <div class="filter-group">
                            <label>From</label>
                            <input type="text" id="apptDateFrom" class="admin-datepicker" placeholder="From">
                        </div>
                        <div class="filter-group">
                            <label>To</label>
                            <input type="text" id="apptDateTo" class="admin-datepicker" placeholder="To">
                        </div>
                        <div class="filter-group">
                            <label>Period</label>
                            <select id="apptPeriodFilter"
                                style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px; background: white; font-size: 0.9rem; color: #475569; min-width: 140px; height: 38px; outline: none; cursor: pointer;">
                                <option value="">All Periods</option>
                                <option value="8:00 AM">8:00 AM - 9:00 AM</option>
                                <option value="9:00 AM">9:00 AM - 10:00 AM</option>
                                <option value="10:00 AM">10:00 AM - 11:00 AM</option>
                                <option value="11:00 AM">11:00 AM - 12:00 PM</option>
                                <option value="1:00 PM">1:00 PM - 2:00 PM</option>
                                <option value="2:00 PM">2:00 PM - 3:00 PM</option>
                                <option value="3:00 PM">3:00 PM - 4:00 PM</option>
                                <option value="4:00 PM">4:00 PM - 5:00 PM</option>
                                <option value="5:00 PM">5:00 PM - 6:00 PM</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="appointments-table-container">
                    <div class="table-wrapper">
                        <table id="appointmentsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date & Time</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointments)): ?>
                                    <tr id="apptEmptyState">
                                        <td colspan="5" style="text-align: center;">No appointments found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($appointments as $appt): ?>
                                        <tr data-status="<?php echo $appt['appointmentStatus'] ?? 'Pending'; ?>"
                                            data-date="<?php echo $appt['appointmentDate']; ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($appt['name'])); ?>"
                                            data-period="<?php echo htmlspecialchars($appt['appointmentTime']); ?>">
                                            <td>#<?php echo str_pad($appt['appointmentID'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <div style="font-weight: 600; color: var(--primary); font-size: 0.95rem;">
                                                    <?php echo $appt['appointmentDate']; ?></div>
                                                <div style="font-weight: 600; color: #475569; font-size: 0.95rem;">
                                                    <?php echo $appt['appointmentTime']; ?></div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($appt['name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($appt['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($appt['serviceType']); ?></td>
                                            <td>
                                                <?php
                                                $status = $appt['appointmentStatus'] ?? 'Pending';
                                                $statusClass = strtolower($status);
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr id="apptEmptyState" style="display: none;">
                                        <td colspan="5" style="text-align: center;">No appointments match your filters</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- --- PAGINATION CONTROLS --- -->
                <?php if ($totalRows > $limit): ?>
                    <div class="pagination-container mt-4">
                        <div class="pagination-info">
                            Showing <?php echo count($appointments); ?> of <?php echo $totalRows; ?> total appointments
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

                <!-- Past Appointments Table -->
                <div id="pastAppointmentsContainer" style="display: none; margin-top: 30px;">
                    <div class="page-header" style="margin-bottom: 15px; padding: 0;">
                        <div class="header-info">
                            <h3 style="font-size: 1.2rem; margin: 0; color: var(--secondary);"><i
                                    class="bi bi-clock-history"></i> Past Appointments</h3>
                        </div>
                    </div>

                    <div class="filters-bar">
                        <div class="search-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" id="pastApptSearchInput"
                                placeholder="Search past appointments by name...">
                        </div>
                    </div>
                    <div class="appointments-table-container">
                        <div class="table-wrapper">
                            <table id="pastAppointmentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date & Time</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($appointments)): ?>
                                        <tr id="pastApptEmptyState">
                                            <td colspan="5" style="text-align: center;">No past appointments found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        $todayDate = date('Y-m-d');
                                        $hasPast = false;
                                        foreach ($appointments as $appt):
                                            // Only accepted past appointments
                                            if (($appt['appointmentStatus'] ?? 'Pending') === 'Accepted' && $appt['appointmentDate'] < $todayDate):
                                                $hasPast = true;
                                                ?>
                                                <tr data-status="<?php echo $appt['appointmentStatus'] ?? 'Pending'; ?>"
                                                    data-date="<?php echo $appt['appointmentDate']; ?>"
                                                    data-name="<?php echo htmlspecialchars(strtolower($appt['name'])); ?>"
                                                    data-period="<?php echo htmlspecialchars($appt['appointmentTime']); ?>"
                                                    style="opacity: 0.85;">
                                                    <td>#<?php echo str_pad($appt['appointmentID'], 3, '0', STR_PAD_LEFT); ?></td>
                                                    <td>
                                                        <div style="font-weight: 600; color: var(--primary); font-size: 0.95rem;">
                                                            <?php echo $appt['appointmentDate']; ?></div>
                                                        <div style="font-weight: 600; color: #475569; font-size: 0.95rem;">
                                                            <?php echo $appt['appointmentTime']; ?></div>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($appt['name']); ?></strong><br>
                                                        <small><?php echo htmlspecialchars($appt['email']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appt['serviceType']); ?></td>
                                                    <td>
                                                        <span class="status-badge accepted">
                                                            Accepted
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php
                                            endif;
                                        endforeach;
                                        ?>
                                        <?php if (!$hasPast): ?>
                                            <tr id="pastApptEmptyState">
                                                <td colspan="5" style="text-align: center;">No past appointments found</td>
                                            </tr>
                                        <?php else: ?>
                                            <tr id="pastApptEmptyState" style="display: none;">
                                                <td colspan="5" style="text-align: center;">No past appointments match your
                                                    filters</td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Appointment Details Modal -->
            <div id="apptDetailsModal" class="modal-overlay">
                <div class="confirm-modal-content" style="max-width: 1100px; padding: 0; overflow: hidden; width: 95%;">
                    <div class="confirm-modal-header"
                        style="background: var(--primary); padding: 30px; display: flex; justify-content: space-between; align-items: center; border-radius: 0;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="confirm-modal-icon"
                                style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white; width: 50px; height: 50px;">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <h3 style="margin: 0; color: white; font-size: 1.4rem;">Appointment Details</h3>
                        </div>
                        <button class="close-modal" onclick="closeApptModal()"
                            style="background: rgba(255,255,255,0.1); border: none; color: white; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">&times;</button>
                    </div>

                    <div class="modal-body" style="padding: 35px;">
                        <!-- Conflict Alert -->
                        <div id="apptConflictAlert" class="alert alert-warning mb-4"
                            style="display: none; border: 1px solid #ffe69c; border-radius: 12px; padding: 18px; background: #fff3cd;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <i class="bi bi-exclamation-triangle-fill"
                                    style="font-size: 1.5rem; color: #856404;"></i>
                                <div>
                                    <strong style="display: block; color: #856404; font-size: 1rem;">Potential Conflict
                                        Detected!</strong>
                                    <span id="conflictMessage" style="color: #856404; font-size: 0.9rem;">There is
                                        already an accepted appointment at this time.</span>
                                </div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                            <!-- Column 1: Customer & Timing -->
                            <div style="display: flex; flex-direction: column; gap: 25px;">
                                <div class="info-group">
                                    <label
                                        style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 1px; margin-bottom: 8px; display: block;">Customer
                                        Name</label>
                                    <p id="modalApptName"
                                        style="font-size: 1.2rem; color: var(--primary); font-weight: 700; margin: 0;">
                                    </p>
                                </div>
                                <div class="info-group">
                                    <label
                                        style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 1px; margin-bottom: 8px; display: block;">Contact
                                        Info</label>
                                    <p id="modalApptEmail" style="margin: 0; color: #64748b; font-size: 0.95rem;"></p>
                                    <p id="modalApptPhone"
                                        style="margin: 5px 0 0 0; color: #64748b; font-size: 0.95rem;"></p>
                                </div>
                                <div class="info-group">
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <label
                                            style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 1px; margin: 0;">Schedule</label>
                                        <span id="modalApptCountdown"
                                            style="font-size: 10px; font-weight: 800; background: #e0e7ff; color: #4338ca; padding: 3px 8px; border-radius: 6px; display: none;"></span>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <p id="modalApptDate"
                                            style="font-size: 1rem; color: var(--primary); font-weight: 700; margin: 0;">
                                        </p>
                                        <p id="modalApptTime"
                                            style="font-size: 1rem; color: var(--primary); font-weight: 700; margin: 0;">
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Service & Notes -->
                            <div style="display: flex; flex-direction: column; gap: 25px;">
                                <div class="info-group">
                                    <label
                                        style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 1px; margin-bottom: 8px; display: block;">Service
                                        Request</label>
                                    <div id="modalApptService" class="status-badge processing" style="margin: 0;"></div>
                                </div>
                                <div class="info-group">
                                    <label
                                        style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 1px; margin-bottom: 8px; display: block;">Customer
                                        Message</label>
                                    <div
                                        style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; min-height: 100px;">
                                        <p id="modalApptMessage"
                                            style="margin: 0; color: #475569; font-size: 0.95rem; line-height: 1.6;">
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="modalApptActions" class="confirm-modal-footer"
                        style="padding: 25px 35px; background: #f8fafc; border-top: 1px solid #e2e8f0; margin-top: 0; justify-content: flex-end;">
                        <div style="display: flex; gap: 12px;" id="modalStatusActions">
                            <!-- Status buttons injected dynamically -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- Blocked Dates Management Modal -->
            <div id="blockedDatesModal" class="modal-overlay">
                <div class="confirm-modal-content"
                    style="max-width: 950px; padding: 0; overflow: visible !important; width: 95%; border-radius: 24px;">
                    <div class="confirm-modal-header"
                        style="background: var(--primary); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; border-radius: 24px 24px 0 0;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="confirm-modal-icon"
                                style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white; width: 45px; height: 45px; font-size: 1.2rem;">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; color: white; font-size: 1.3rem;">Manage Blocked Dates</h3>
                                <p
                                    style="margin: 3px 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem; font-weight: 500;">
                                    Block or unblock specific booking dates</p>
                            </div>
                        </div>
                        <button class="close-modal" onclick="closeBlockedDatesModal()"
                            style="background: rgba(255,255,255,0.1); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: all 0.2s;">&times;</button>
                    </div>

                    <div id="blockedModalBody" class="modal-body" style="padding: 30px; overflow: visible !important;">
                        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 30px; align-items: start;">

                            <!-- Left Side: Add Form -->
                            <div
                                style="background: #f8fafc; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; position: sticky; top: 0;">
                                <h4
                                    style="font-size: 1rem; margin: 0 0 20px 0; color: var(--primary); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-plus-circle" style="color: var(--accent);"></i>
                                    Block a New Date
                                </h4>

                                <div style="display: flex; flex-direction: column; gap: 20px;">
                                    <div class="info-group" style="margin: 0; position: relative;">
                                        <label
                                            style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; margin-bottom: 8px; display: block; letter-spacing: 0.5px;">Select
                                            Date</label>
                                        <div id="blockedDateWrapper" style="position: relative;">
                                            <input type="text" id="newBlockedDate" class="admin-datepicker"
                                                placeholder="Choose Date">
                                        </div>
                                    </div>

                                    <div class="info-group" style="margin: 0;">
                                        <label
                                            style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; margin-bottom: 8px; display: block; letter-spacing: 0.5px;">Reason
                                            (Optional)</label>
                                        <textarea id="blockedReason" placeholder="Holiday, Repair, etc."
                                            class="form-control"
                                            style="padding: 12px 15px; border-radius: 12px; border: 1px solid #cbd5e1; width: 100%; height: 90px; resize: none; font-size: 0.95rem;"></textarea>
                                    </div>

                                    <button onclick="saveBlockedDate()"
                                        style="background: var(--accent); color: white; border: none; padding: 14px; border-radius: 12px; cursor: pointer; font-weight: 800; width: 100%; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(214, 178, 94, 0.2);">
                                        Block Date Now
                                    </button>
                                </div>
                            </div>

                            <!-- Right Side: List -->
                            <div style="flex: 1;">
                                <h4
                                    style="font-size: 0.85rem; margin: 0 0 15px 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-list-check"></i>
                                    Currently Blocked Dates
                                </h4>

                                <div
                                    style="background: white; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02); height: 400px; display: flex; flex-direction: column;">
                                    <div style="flex: 1; overflow-y: auto;">
                                        <table class="table" style="margin: 0; width: 100%; border-collapse: collapse;">
                                            <thead style="background: #f1f5f9; position: sticky; top: 0; z-index: 10;">
                                                <tr>
                                                    <th
                                                        style="padding: 15px 20px; text-align: left; font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 1px;">
                                                        Date</th>
                                                    <th
                                                        style="padding: 15px 20px; text-align: left; font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 1px;">
                                                        Reason</th>
                                                    <th
                                                        style="padding: 15px 20px; text-align: right; font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 1px;">
                                                        Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="blockedDatesList">
                                                <!-- Dynamic rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- Reminder Settings Modal -->
            <div id="reminderSettingsModal" class="modal-overlay">
                <div class="confirm-modal-content" style="max-width: 450px; border-radius: 24px;">
                    <div class="confirm-modal-header"
                        style="background: #f8fafc; color: var(--primary); padding: 25px 30px; display: flex; justify-content: space-between; align-items: center; border-radius: 24px 24px 0 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="confirm-modal-icon"
                                style="background: #012b4311; border-color: #012b4322; color: var(--primary); width: 40px; height: 40px;">
                                <i class="bi bi-alarm"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; color: var(--primary); font-size: 1.1rem; font-weight: 800;">Email
                                    Preferences</h3>
                                <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.75rem; font-weight: 500;">Set
                                    automated reminder timing</p>
                            </div>
                        </div>
                        <button class="close-modal" onclick="closeReminderSettingsModal()"
                            style="background: #f1f5f9; border: none; color: #64748b; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;">&times;</button>
                    </div>

                    <div class="modal-body" style="padding: 30px;">
                        <div style="background: #fff; border-radius: 16px; margin-bottom: 0;">
                            <div class="info-group" style="margin: 0; position: relative;">
                                <label
                                    style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; margin-bottom: 10px; display: block; letter-spacing: 0.5px;">Preferred
                                    reminder time</label>
                                <div style="position: relative;">
                                    <input type="time" id="reminderTimeInput" class="form-control"
                                        style="padding: 15px; border-radius: 12px; border: 1px solid #cbd5e1; width: 100%; font-size: 1.1rem; font-weight: 700; color: var(--primary); text-align: center;">
                                </div>
                                <p style="margin: 15px 0 0 0; color: #64748b; font-size: 0.85rem; line-height: 1.4;">
                                    <i class="bi bi-info-circle"></i> This time will be used to trigger automated email
                                    reminders for all upcoming appointments scheduled for the following day.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="confirm-modal-footer"
                        style="padding: 20px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 24px 24px;">
                        <button onclick="saveReminderSettings()"
                            style="background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 10px; cursor: pointer; font-weight: 700; width: 100%; transition: all 0.3s; box-shadow: 0 4px 12px rgba(1, 43, 67, 0.2);">
                            Save Preferences
                        </button>
                    </div>
                </div>
            </div>
        </div> <!-- main-content -->
    </div> <!-- admin-layout -->
    <!-- Premium Status/Confirmation Modal — styled like reminderSettingsModal -->
    <div id="statusModal" class="modal-overlay">
        <div class="confirm-modal-content"
            style="max-width: 450px; border-radius: 24px; padding: 0; overflow: hidden; width: 100%;">
            <!-- Header — matches reminderSettingsModal header -->
            <div
                style="background: #f8fafc; padding: 22px 28px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="confirm-modal-icon" id="statusIcon" style="width: 40px; height: 40px; flex-shrink: 0;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h3 id="statusTitle"
                            style="margin: 0; color: var(--primary); font-size: 1.05rem; font-weight: 800; letter-spacing: -0.3px;">
                        </h3>
                        <p id="statusSubtitle"
                            style="margin: 2px 0 0; color: #64748b; font-size: 0.75rem; font-weight: 500;"></p>
                    </div>
                </div>
                <button id="statusModalClose" onclick="closeStatusModal()"
                    style="background: #f1f5f9; border: none; color: #64748b; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
            </div>
            <!-- Body -->
            <div style="padding: 24px 28px; background: #fff;">
                <p id="statusMessage" style="margin: 0; color: #475569; font-size: 0.9rem; line-height: 1.65;"></p>
                <!-- Countdown progress bar (shown on warn/success auto-dismiss) -->
                <div id="statusProgressWrap" style="display: none; margin-top: 16px;">
                    <div style="height: 3px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                        <div id="statusProgressBar"
                            style="height:100%; border-radius:4px; width:100%; transform-origin:left; transition: none;">
                        </div>
                    </div>
                    <p style="font-size:0.72rem; color:#94a3b8; margin:5px 0 0; text-align:right;">Auto-closing in <span
                            id="statusCountdown">8</span>s</p>
                </div>
            </div>
            <!-- Footer — matches reminderSettingsModal footer -->
            <div id="statusActions"
                style="padding: 16px 28px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 10px;">
                <!-- Buttons injected via appointments.js -->
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/fragments/scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../js/appointments.js?v=<?php echo time(); ?>"></script>
</body>

</html>