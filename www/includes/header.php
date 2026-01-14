<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
  define('SYSTEM_LOAD', true);
require_once __DIR__ . '/init.php';
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'AD Management'; ?></title>

    <!-- Lokal CSS faylları -->
    <link href="temp/assets/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="temp/assets/lib/font-awesome/all.min.css" rel="stylesheet"> <!-- Font Awesome -->
    <link href="temp/assets/lib/animate/animate.min.css" rel="stylesheet"> <!-- Animate.css -->
    <link href="temp/assets/lib/sweetalert2/sweetalert2.min.css" rel="stylesheet"> <!-- SweetAlert2 -->
    <link href="temp/css/dashboard.css" rel="stylesheet">
    
    <!-- Lokal JavaScript faylları -->
    <script src="temp/assets/lib/sweetalert2/sweetalert2.min.js"></script>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand text-primary fw-bold" href="index.php">
                <i class="fas fa-server me-2"></i>S-RCS
            </a>
            <div class="d-flex align-items-center">
                <span class="text-dark me-3">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['ad_username']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i><?php echo __('header_logout'); ?>
                </a>
            </div>
        </div>
    </nav>
</body>
</html>
