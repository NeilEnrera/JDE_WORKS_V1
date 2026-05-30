<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - JDE Works of Our Hands</title>
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

<body class="login-page">
    <div class="background"></div>
    <div class="form-container">
        <div class="logo">
            <a href="../backend/index.php"><img src="../assets/img/LOGO.png" alt="Logo"></a>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="success-message">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label class="input-label">Username or Email</label>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="txt_uname" placeholder="Username or Email" required>
                </div>
            </div>
            <div class="form-group">
                <label class="input-label">Password</label>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="txt_pw" placeholder="Password" required>
                    <span class="toggle-password">
                        <i class="fa-solid fa-eye-slash"></i>
                    </span>
                </div>
            </div>
            <button type="submit" class="signup-btn">LOGIN</button>
            <div class="forgot-password-link" style="text-align: right; margin-top: -5px; margin-bottom: 10px;">
                <a href="forgot_password.php"
                    style="color: #D6A347; text-decoration: none; font-size: 0.85em; font-weight: 600;">Forgot
                    Password?</a>
            </div>
            <div class="divider">or</div>
            <div class="login-link">
                <p>Don't have an account? <a href="signin.php">SIGN UP</a></p>
            </div>
        </form>
    </div>



    <!-- Page Specific Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/auth-messages.js"></script>
    <script src="../js/password-toggle.js"></script>

</body>

</html>