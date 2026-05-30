<?php
/**
 * verify.view.php — Email Verification UI
 * Clean HTML structure separated from logic, aligned with JDE brand.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - JDE Works of Our Hands</title>
    <meta name="description" content="Email verification result for your JDE Works account.">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Page Styles -->
    <link rel="stylesheet" href="../css/verify.css">
</head>
<body>

    <?php if ($status === 'success'): ?>
        <canvas id="confetti-canvas"></canvas>
    <?php endif; ?>

    <div class="verify-card">

        <!-- Logo: white box with gold border, mirrors signin -->
        <div class="logo-wrap">
            <img src="../assets/img/LOGO.png" alt="JDE Works of Our Hands">
        </div>

        <!-- Status Icon -->
        <div class="icon-bubble <?php echo $status === 'success' ? 'icon-success' : 'icon-error'; ?>">
            <i class="fas <?php echo $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        </div>

        <!-- Headline -->
        <h1 class="verify-title">
            <?php echo $status === 'success' ? 'Email Verified!' : 'Verification Failed'; ?>
        </h1>
        <p class="verify-msg"><?php echo htmlspecialchars($message); ?></p>

        <div class="verify-divider"></div>

        <?php if ($status === 'success'): ?>
            <!-- Countdown indicator -->
            <div class="timer-pill">
                <i class="fas fa-clock"></i>
                Redirecting in <span id="timer-count">5</span> seconds
            </div>
        <?php endif; ?>

        <!-- Action Button -->
        <a href="login.php" class="btn-verify" id="cta-btn">
            <?php if ($status === 'success'): ?>
                <i class="fas fa-right-to-bracket"></i> Continue to Login
            <?php else: ?>
                <i class="fas fa-arrow-left"></i> Back to Login
            <?php endif; ?>
        </a>

        <!-- Footer Note -->
        <p class="already-note">
            <?php if ($status === 'success'): ?>
                Welcome aboard. Your account is now fully active.
            <?php else: ?>
                Need help? Contact <strong>jdeworks0@gmail.com</strong>
            <?php endif; ?>
        </p>

    </div>

    <!-- Page Scripts -->
    <script src="../js/verify.js"></script>
</body>
</html>
