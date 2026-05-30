<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - JDE Works of Our Hands</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/signin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="verify-code-page">
    <div class="background"></div>
    <div class="form-container">
        <div class="logo">
            <a href="index.php"><img src="../assets/img/LOGO.png" alt="Logo"></a>
        </div>

        <h2 style="text-align: center; color: #fff; margin-bottom: 10px;">Verification Code</h2>
        <p style="text-align: center; color: #ccc; font-size: 0.9em; margin-bottom: 10px;">Please enter the 6-digit code sent to your email.</p>
        
        <div id="expiry-timer" style="text-align: center; color: #D6A347; font-size: 0.85rem; margin-bottom: 25px; font-weight: 500;">
            Code expires in: <span id="countdown">--:--</span>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="input-group">
                <span class="icon"><i class="fa-solid fa-key"></i></span>
                <input type="text" name="txt_code" placeholder="6-Digit Code" maxlength="6" required
                    style="text-align: center; letter-spacing: 5px; font-weight: bold; font-size: 1.2em;">
            </div>

            <button type="submit" class="signup-btn">VERIFY CODE</button>
            <div class="login-link">
                Didn't receive the code? <a href="forgot_password.php">Resend Code</a>
            </div>
        </form>
    </div>

    <!-- Page Specific Scripts -->
    <script src="../js/auth-messages.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let secondsLeft = <?php echo (int)$seconds_left; ?>;
            const countdownEl = document.getElementById('countdown');
            const timerContainer = document.getElementById('expiry-timer');

            function updateTimer() {
                if (secondsLeft <= 0) {
                    countdownEl.innerHTML = "EXPIRED";
                    countdownEl.style.color = "#ff4d4d";
                    return;
                }

                const minutes = Math.floor(secondsLeft / 60);
                const seconds = secondsLeft % 60;

                countdownEl.innerHTML = (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                secondsLeft--;
            }

            if (secondsLeft > 0) {
                updateTimer();
                setInterval(updateTimer, 1000);
            } else if (secondsLeft === 0) {
                countdownEl.innerHTML = "EXPIRED";
                countdownEl.style.color = "#ff4d4d";
            } else {
                timerContainer.style.display = 'none';
            }
        });
    </script>
</body>

</html>