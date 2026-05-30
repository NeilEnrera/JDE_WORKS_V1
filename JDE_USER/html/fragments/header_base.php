<?php
/**
 * Global User Header Fragment
 * Contains the <head> section, meta tags, and global CSS links.
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Global CSS -->
    <link rel="stylesheet" type="text/css" href="../css/navbar.css">

    <title>
        <?php echo $pageTitle ?? 'JDE WORK OF OUR HANDS'; ?>
    </title>
</head>

<body>