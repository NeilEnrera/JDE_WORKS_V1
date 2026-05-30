<?php
// Detect successful registration redirect
$registered = isset($_GET['registered']) && $_GET['registered'] === '1';
$error_msg = $error_msg ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - JDE Works of Our Hands</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/signin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body class="registration-page">
    <div class="background"></div>

    <div class="registration-wrapper">
        <div class="brand-section">
            <div class="brand-content text-center">
                <div class="logo-wrapper mb-4">
                    <a href="../backend/index.php"><img src="../assets/img/LOGO.png" alt="Logo" class="brand-logo"></a>
                </div>
                <h1 class="brand-title">JDE</h1>
                <h2 class="brand-subtitle">Works of Our Hands</h2>
                <div class="brand-divider"></div>
                <p class="brand-tagline"></p>
            </div>
        </div>

        <div class="form-section">
            <div id="error-container" style="display: <?php echo !empty($error_msg) ? 'block' : 'none'; ?>">
                <div class="error-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="error-text"><?php echo $error_msg; ?></span>
                </div>
            </div>

            <form action="../backend/signin.php" method="POST" id="regForm" novalidate>
                <h3 class="form-header">Create Your Account</h3>

                <div class="form-group">
                    <label class="input-label">First Name <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="txt_givenName" placeholder="Enter first name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Middle Name</label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="txt_middleName" placeholder="Enter middle name">
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Last Name <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="txt_surname" placeholder="Enter last name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Gender <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-venus-mars"></i></span>
                        <select name="txt_gender" required>
                            <option value="" disabled selected>Select Gender</option>
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Birthdate <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
                        <input type="text" id="birthday" name="txt_birthday" placeholder="Select Date" required
                            readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Email Address <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="txt_email" placeholder="email@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Username <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-user-tag"></i></span>
                        <input type="text" name="txt_uname" placeholder="Choose a username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Phone Number <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-phone"></i></span>
                        <input type="text" id="phoneInput" name="txt_phone" placeholder="09123456789" maxlength="11"
                            inputmode="numeric" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Password <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="txt_pw" id="passwordInput" placeholder="Enter Password" required>
                        <span class="toggle-password"><i class="fa-solid fa-eye-slash"></i></span>
                    </div>
                    <div id="password-strength-indicator" class="password-strength"></div>
                </div>

                <div class="form-group">
                    <label class="input-label">Confirm Password <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="txt_confirm_pw" id="confirmPasswordInput"
                            placeholder="Confirm password" required>
                        <span class="toggle-password"><i class="fa-solid fa-eye-slash"></i></span>
                    </div>
                </div>

                <!-- TERMS -->
                <!-- Terms Modal -->
                <div class="modal-overlay" id="termsModal">
                    <div class="modal">
                        <div class="modal-header">
                            <h3>Terms & Conditions & Privacy Policy</h3>
                            <button class="close-modal" id="closeTerms">&times;</button>
                        </div>

                        <div class="modal-body">
                            <p><strong>Terms & Conditions</strong></p>
                            <p>
                                By creating an account, you agree to comply with the policies and
                                guidelines of JDE Works of Our Hands regarding custom tailoring orders
                                and site usage.
                            </p><br>

                            <p><strong>Privacy Policy</strong></p>
                            <p>
                                Your information (name, email, and birthdate) will be used solely for
                                personalization, tailoring services, and essential communication.
                                We guarantee that we do not sell or share your data with
                                third-party marketing agencies.
                            </p><br>

                            <p><strong>Account Security</strong></p>
                            <p>
                                You are responsible for maintaining the confidentiality of your
                                account credentials and for all activities that occur under your account.
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="modal-btn" id="acceptTerms">
                                I Understand
                            </button>
                        </div>
                    </div>
                </div>

                <div class="terms-row">
                    <input type="checkbox" id="terms" disabled required>

                    <label for="terms">
                        I agree to the
                        <a href="javascript:void(0)" id="openTerms" class="highlight-terms">Terms & Conditions and
                            Privacy Policy</a>
                        <span class="required">*</span>
                    </label>

                </div>

                <button type="submit" class="signup-btn">CREATE ACCOUNT</button>

                <div class="divider">or</div>
                <div class="login-link">
                    Already have an account? <a href="../backend/login.php">LOGIN</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($registered): ?>
        <!-- Registration Success Modal -->
        <div id="regSuccessOverlay" style="
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,0.55); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
    ">
            <div style="
            background: #fff; border-radius: 20px;
            padding: 40px 36px; max-width: 420px; width: 90%;
            text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            animation: popIn .35s cubic-bezier(.34,1.56,.64,1) forwards;
        ">
                <!-- Animated checkmark -->
                <div style="margin-bottom: 20px;">
                    <svg width="72" height="72" viewBox="0 0 52 52" style="display:block;margin:0 auto;">
                        <circle cx="26" cy="26" r="25" fill="none" stroke="#4CAF50" stroke-width="2" stroke-dasharray="157"
                            stroke-dashoffset="157" style="animation: drawCircle .6s ease forwards .1s; fill:none;" />
                        <path fill="none" stroke="#4CAF50" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                            d="M14 27l8 8 16-16" stroke-dasharray="35" stroke-dashoffset="35"
                            style="animation: drawCheck .4s ease forwards .7s;" />
                    </svg>
                </div>
                <h2 style="font-family:Poppins,sans-serif;font-size:1.5rem;font-weight:700;color:#222;margin-bottom:8px;">
                    Account Awaiting Verification!</h2>
                <div
                    style="background: #FFF9C4; border-radius: 12px; padding: 15px; margin-bottom: 25px; border: 1px solid #FFF176;">
                    <p style="font-family:Poppins,sans-serif;color:#FBC02D;font-size:.85rem;margin:0;font-weight:600;">
                        <i class="fas fa-envelope-open-text me-2"></i>Please check your email to verify your account before
                        logging in.
                    </p>
                </div>
                <p style="font-family:Poppins,sans-serif;color:#999;font-size:.85rem;margin-bottom:28px;">
                    Redirecting to login in <strong id="regCountdown">10</strong> second(s)...
                </p>
                <a href="../backend/login.php" id="regLoginBtn" style="
                display: inline-block; padding: 12px 36px;
                background: linear-gradient(135deg,#b8860b,#daa520);
                color: #fff; border-radius: 50px; text-decoration: none;
                font-family: Poppins,sans-serif; font-weight: 600; font-size: .95rem;
                box-shadow: 0 4px 15px rgba(184,134,11,.35);
                transition: transform .2s, box-shadow .2s;
            " onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(184,134,11,.45)'"
                    onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(184,134,11,.35)'">
                    Go to Login
                </a>
            </div>
        </div>
        <style>
            @keyframes popIn {
                from {
                    opacity: 0;
                    transform: scale(.8)
                }

                to {
                    opacity: 1;
                    transform: scale(1)
                }
            }

            @keyframes drawCircle {
                to {
                    stroke-dashoffset: 0;
                }
            }

            @keyframes drawCheck {
                to {
                    stroke-dashoffset: 0;
                }
            }
        </style>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../js/login-validation.js"></script>
    <script src="../js/password-toggle.js"></script>
    <?php if ($registered): ?>

        <script>
            (function () {
                var seconds = 10;
                var countdownEl = document.getElementById('regCountdown');
                var timer = setInterval(function () {
                    seconds--;
                    if (countdownEl) countdownEl.textContent = seconds;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        window.location.href = '../backend/login.php';
                    }
                }, 1000);

                // If user clicks the button, cancel auto-redirect
                var btn = document.getElementById('regLoginBtn');
                if (btn) {
                    btn.addEventListener('click', function () {
                        clearInterval(timer);
                    });
                }
            })();
        </script>
    <?php endif; ?>
</body>

</html>