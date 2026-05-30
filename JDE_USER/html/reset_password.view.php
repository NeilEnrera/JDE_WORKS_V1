<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - JDE Works of Our Hands</title>
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

<body class="reset-password-page">
    <div class="background"></div>
    <div class="form-container">
        <div class="logo">
            <a href="index.php"><img src="../assets/img/LOGO.png" alt="Logo"></a>
        </div>

        <h2 style="text-align: center; color: #fff; margin-bottom: 20px;">Reset Password</h2>
        <p style="text-align: center; color: #ccc; font-size: 0.9em; margin-bottom: 25px;">Please enter your new
            password below.</p>

        <?php if (!empty($error_msg)): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="input-group">
                <span class="icon"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="txt_pw" placeholder="New Password" required>
                <span class="toggle-password">
                    <i class="fa-solid fa-eye-slash"></i>
                </span>
            </div>

            <div class="input-group">
                <span class="icon"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="txt_confirm_pw" placeholder="Confirm New Password" required>
                <span class="toggle-password">
                    <i class="fa-solid fa-eye-slash"></i>
                </span>
            </div>

            <button type="submit" class="signup-btn">RESET PASSWORD</button>
        </form>
    </div>

    <!-- Page Specific Scripts -->
    <script src="../js/auth-messages.js"></script>
    <script src="../js/password-toggle.js"></script>
</body>

</html>