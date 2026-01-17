<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
  
session_start();
require_once(__DIR__ . '/includes/functions.php');

if (!isset($_SESSION['ad_username'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = __('notifications_title');
$activePage = 'notifications';
include 'api/notifications.php';
// Bildirişləri əldə et
$response = getNotifications();
$notifications = $response['notifications'] ?? [];

require_once('includes/header.php');
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-bell me-2 text-primary"></i>
                    <?php echo __('notifications_title'); ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" id="refreshBtn" class="btn btn-light border" onclick="refreshNotifications()">
                        <i class="fas fa-sync-alt"></i>
                        <span class="ms-1 d-none d-md-inline"><?php echo __('refresh'); ?></span>
                    </button>
                </div>
            </div>

            <!-- Notifications Container -->
            <div class="notifications-wrapper">
                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i>
                        <?php echo __('no_notifications'); ?>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="col">
                                <?= formatNotification($notification) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>


   <link rel="stylesheet" href="temp/css/notifications.css">


<?php require_once('includes/footer.php'); ?>
<script>
function refreshNotifications() {
    const refreshBtn = document.getElementById('refreshBtn');
    
    refreshBtn.disabled = true;
    refreshBtn.querySelector('i').classList.add('fa-spin');
    
    const container = document.querySelector('.notifications-wrapper');
    
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        
        const newNotifications = temp.querySelector('.notifications-wrapper');
        
        if (newNotifications) {
            container.innerHTML = newNotifications.innerHTML;
            
            Swal.fire({
                title: '<?php echo __('updated'); ?>!',
                text: '<?php echo __('notifications_refreshed'); ?>',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        }
    })
    .catch(error => {
        console.error('Refresh error:', error);
        Swal.fire({
            title: '<?php echo __('error'); ?>!',
            text: '<?php echo __('error_refresh_notifications'); ?>',
            icon: 'error',
            timer: 2000,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });
    })
    .finally(() => {
        refreshBtn.disabled = false;
        refreshBtn.querySelector('i').classList.remove('fa-spin');
    });
}

// Tooltips initialize
document.addEventListener('DOMContentLoaded', function() {
    // Mövcud tooltip kodu...
    
    // Refresh button üçün tooltip
    new bootstrap.Tooltip(document.getElementById('refreshBtn'), {
        title: '<?php echo __('refresh_notifications'); ?>',
        placement: 'bottom'
    });
});
</script>