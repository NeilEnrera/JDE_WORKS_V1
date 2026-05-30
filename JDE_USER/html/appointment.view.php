<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book an Appointment - JDE WORK OF OUR HANDS</title>

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
    include '../backend/navbar.php';
    ?>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" href="../css/appointment.css">

    <!-- Hero Section -->
    <div class="appointment-hero">
        <div class="appointment-hero-content">
            <h1>Book an Appointment</h1>
            <p>Schedule a consultation with our expert tailors. We're here to bring your vision to life.</p>
        </div>
    </div>

    <!-- Appointment Form Section -->
    <div class="appointment-section">
        <div class="container appointment-container">
            <div class="appointment-card">
                <div class="row g-0">
                    <!-- Left Column: Visual Calendar -->
                    <div class="col-lg-6 pe-lg-5 mb-4 mb-lg-0">
                        <div class="calendar-wrapper">
                            <div class="calendar-header">
                                <button id="prevMonth" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M15 18l-6-6 6-6" />
                                    </svg>
                                </button>
                                <h3 id="currentMonth"></h3>
                                <button id="nextMonth" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M9 18l6-6-6-6" />
                                    </svg>
                                </button>
                            </div>
                            <div class="calendar-body">
                                <div class="calendar-weekdays">
                                    <div>Sun</div>
                                    <div>Mon</div>
                                    <div>Tue</div>
                                    <div>Wed</div>
                                    <div>Thu</div>
                                    <div>Fri</div>
                                    <div>Sat</div>
                                </div>
                                <div class="calendar-days" id="calendarDays">
                                    <!-- Days will be rendered here by JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Form -->
                    <div class="col-lg-6 ps-lg-5 border-start-lg">
                        <h2>Schedule Your Visit</h2>
                        <p class="subtitle mb-3">Fill out the form below and we'll get back to you to confirm your
                            appointment.</p>
                            
                        <div id="importantReminderBox" class="alert py-2 px-3 mb-4 text-start" style="display: none; background-color: #fff3cd; border: 1px solid #ffe69c; color: #664d03; border-radius: 6px;">
                            <strong style="font-size: 0.9rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Important Reminder:</strong>
                            <p class="mb-0 mt-1" style="font-size: 0.85rem;">Please arrive at your scheduled time with all necessary requirements. Please be advised that missed appointments will be prioritized as our schedule allows, which may result in extended wait times.</p>
                        </div>

                        <?php if (!empty($reschedule_notice)): ?>
                        <!-- Reschedule Warning Modal -->
                        <div class="modal fade" id="rescheduleWarningModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow" style="border-radius: 20px; overflow: hidden;">
                                    <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 25px;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                            </div>
                                            <h5 class="modal-title fw-bold mb-0">Schedule Update Required</h5>
                                        </div>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="syncRescheduleDismissal()"></button>
                                    </div>
                                    <div class="modal-body p-4 text-center">
                                        <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.6;">
                                            One or more of your upcoming appointments were cancelled because the admin has blocked those dates. 
                                            We apologize for the inconvenience — please reschedule your appointment below.
                                        </p>
                                        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 15px; padding: 20px; margin-bottom: 25px;">
                                            <span style="color: #92400e; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Action Recommended</span>
                                            <p style="color: #78350f; font-weight: 600; margin-top: 5px; margin-bottom: 0;">Please select a new available date from the calendar below.</p>
                                        </div>
                                        <button type="button" class="btn w-100 py-3 fw-bold" data-bs-dismiss="modal" onclick="syncRescheduleDismissal()"
                                            style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(217, 119, 6, 0.3);">
                                            Understood & Continue
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <?php endif; ?>

                        <?php if (isset($success_message)): ?>
                            <!-- Success Modal -->
                            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content success-modal-content">
                                        <div class="success-modal-particles">
                                            <div class="particle" style="top: 10%; left: 10%; width: 10px; height: 10px;">
                                            </div>
                                            <div class="particle"
                                                style="top: 20%; right: 15%; width: 15px; height: 15px; animation-delay: 1s;">
                                            </div>
                                            <div class="particle"
                                                style="bottom: 15%; left: 20%; width: 12px; height: 12px; animation-delay: 2s;">
                                            </div>
                                            <div class="particle"
                                                style="bottom: 10%; right: 10%; width: 8px; height: 8px; animation-delay: 3.5s;">
                                            </div>
                                        </div>
                                        <button type="button" class="btn-modal-close-top" data-bs-dismiss="modal"
                                            aria-label="Close">
                                            <i class="bi bi-x"></i>
                                        </button>
                                        <div class="modal-body text-center p-4">
                                            <div class="success-icon-container">
                                                <svg class="animated-checkmark" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 52 52">
                                                    <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" />
                                                    <path class="checkmark-check" fill="none"
                                                        d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                                                </svg>
                                            </div>
                                            <h2 class="modal-title mb-2" id="successModalLabel">Booking Successful!</h2>
                                            <p class="mb-0 text-muted small">Your appointment has been booked successfully.
                                            </p>
                                            <p class="mb-4 text-muted small">We'll contact you soon to confirm.</p>

                                            <div class="booking-details-card">
                                                <div class="detail-item">
                                                    <span class="detail-label">Booking ID</span>
                                                    <span class="detail-value fw-bold">#<?php echo htmlspecialchars($appointment_id ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Date</span>
                                                    <span class="detail-value">
                                                        <?php echo date('M d', strtotime($appointment_date)); ?>
                                                    </span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Service</span>
                                                    <span class="detail-value text-truncate w-100"
                                                        title="<?php echo ucwords(str_replace('-', ' ', htmlspecialchars($service_type))); ?>">
                                                        <?php
                                                        $svc = ucwords(str_replace('-', ' ', htmlspecialchars($service_type)));
                                                        echo $svc;
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Period</span>
                                                    <span class="detail-value">
                                                        <?php echo $appointment_time; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="mb-4">
                                                <p class="text-muted small mb-1">
                                                    <i class="bi bi-camera-fill me-1"></i> Please take a screenshot of this just in case and be on time.
                                                </p>
                                            </div>

                                            <div class="modal-action-buttons">
                                                <a href="profile.php?pane=appointments" class="btn-success-primary">
                                                    <i class="bi bi-calendar-check-fill"></i> View My Appointments
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <strong>Error!</strong>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="appointment.php">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name"
                                            placeholder="Full Name" value="<?php echo htmlspecialchars($user_name); ?>"
                                            <?php echo isset($_SESSION['user_id']) ? 'readonly style="background-color: #f8f9fa; color: #333; opacity: 1;"' : ''; ?> required>
                                        <label for="name">Full Name <span class="required">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="Email Address" value="<?php echo htmlspecialchars($user_email); ?>" 
                                            <?php echo isset($_SESSION['user_id']) ? 'readonly style="background-color: #f8f9fa; color: #333; opacity: 1;"' : ''; ?> required>
                                        <label for="email">Email Address <span class="required">*</span></label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            placeholder="Phone Number" pattern="09[0-9]{9}" maxlength="11"
                                            value="<?php echo htmlspecialchars($user_phone); ?>" required>
                                        <label for="phone">Phone Number <span class="required">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="service_type" name="service_type" required>
                                            <option value="" selected disabled>Select a service</option>
                                            <option value="fitting">Fitting Appointment</option>
                                            <option value="school-uniform">School Uniform</option>
                                            <option value="custom-tailoring">Custom Tailoring</option>
                                            <option value="alteration">Alteration Service</option>
                                            <option value="consultation">General Consultation</option>
                                        </select>
                                        <label for="service_type">Service Type <span class="required">*</span></label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="appointment_date"
                                            name="appointment_date" placeholder="Selected Date"
                                            value="<?php echo htmlspecialchars($appointment_date); ?>"
                                            min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required readonly
                                            style="background-color: #fff; pointer-events: none;">
                                        <label for="appointment_date">Selected Date <span
                                                class="required">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="appointment_time" name="appointment_time"
                                            required>
                                            <option value="" <?php echo empty($appointment_time) ? 'selected' : ''; ?> disabled>Select a period</option>
                                            <option value="8:00 AM" <?php echo $appointment_time == '8:00 AM' ? 'selected' : ''; ?>>8:00 AM</option>
                                            <option value="9:00 AM" <?php echo $appointment_time == '9:00 AM' ? 'selected' : ''; ?>>9:00 AM</option>
                                            <option value="10:00 AM" <?php echo $appointment_time == '10:00 AM' ? 'selected' : ''; ?>>10:00 AM</option>
                                            <option value="11:00 AM" <?php echo $appointment_time == '11:00 AM' ? 'selected' : ''; ?>>11:00 AM</option>
                                            <option value="12:00 PM" <?php echo $appointment_time == '12:00 PM' ? 'selected' : ''; ?>>12:00 PM</option>
                                            <option value="1:00 PM" <?php echo $appointment_time == '1:00 PM' ? 'selected' : ''; ?>>1:00 PM</option>
                                            <option value="2:00 PM" <?php echo $appointment_time == '2:00 PM' ? 'selected' : ''; ?>>2:00 PM</option>
                                            <option value="3:00 PM" <?php echo $appointment_time == '3:00 PM' ? 'selected' : ''; ?>>3:00 PM</option>
                                            <option value="4:00 PM" <?php echo $appointment_time == '4:00 PM' ? 'selected' : ''; ?>>4:00 PM</option>
                                            <option value="5:00 PM" <?php echo $appointment_time == '5:00 PM' ? 'selected' : ''; ?>>5:00 PM</option>
                                        </select>
                                        <label for="appointment_time">Preferred Period <span
                                                class="required">*</span></label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="message" name="message"
                                            placeholder="Additional Notes" style="height: 120px"></textarea>
                                        <label for="message">Additional Notes (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="submit-btn">
                                        <span>Book Appointment</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-arrow-right ms-2">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-card-icon">📍</div>
                    <h3>Visit Our Shop</h3>
                    <p>Hilltop Branch, 11 Esperanza, Novaliches, Quezon City</p>
                </div>
                <div class="info-card">
                    <div class="info-card-icon">⏰</div>
                    <h3>Business Hours</h3>
                    <p>Mon-Fri: 8:00 AM - 6:00 PM<br>Sat: 8:00 AM - 5:00 PM</p>
                </div>
                <div class="info-card">
                    <div class="info-card-icon">📞</div>
                    <h3>Call Us</h3>
                    <p>0933-598-7864<br>0997-892-7142</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Site Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> JDE Works of Our Hands. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
    <script src="../js/appointment.js?v=<?php echo time(); ?>"></script>
</body>

</html>