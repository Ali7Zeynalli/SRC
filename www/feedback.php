<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
  session_start();
require_once(__DIR__ . '/includes/functions.php');

// Config faylını yükləyirik
$config = require(__DIR__ . '/config/config.php');

if (!isset($_SESSION['ad_username'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = __('feedback_form');
$activePage = 'feedback';
require_once('includes/header.php');
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-comment-alt me-2 text-primary"></i>
                    <?php echo __('feedback_form'); ?>
                </h1>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-comments fa-4x text-primary"></i>
                        </div>
                        <h3 class="mb-3">Send Feedback</h3>
                        <p class="text-muted mb-4">
                            We value your feedback! Please send your thoughts, bug reports, or feature requests directly to our email.
                        </p>
                        
                        <div class="d-grid gap-3">
                            <a href="mailto:Ali.Z.Zeynalli@gmail.com" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-envelope me-2"></i> Ali.Z.Zeynalli@gmail.com
                            </a>
                            <a href="https://linkedin.com/in/ali7zeynalli" target="_blank" class="btn btn-primary btn-lg">
                                <i class="fab fa-linkedin me-2"></i> LinkedIn Profile
                            </a>
                            <a href="https://github.com/Ali7Zeynalli" target="_blank" class="btn btn-dark btn-lg">
                                <i class="fab fa-github me-2"></i> GitHub Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>