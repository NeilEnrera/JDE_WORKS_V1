<?php
/**
 * Global Admin Header Fragment
 * Contains the <head> section, meta tags, and global CSS links.
 */
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/JDE_ADMIN/') !== false;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?php echo $isAdminDir ? '../css/admin-common.css' : '../../JDE_ADMIN/css/admin-common.css'; ?>">
    <!-- Page Specific Title -->
    <title>
        <?php echo $pageTitle ?? 'JDE Admin'; ?>
    </title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $isAdminDir ? '../assets/img/logo.png' : '../../JDE_ADMIN/assets/img/logo.png'; ?>">
</head>

<body class="<?php echo $bodyClass ?? ''; ?>">
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">