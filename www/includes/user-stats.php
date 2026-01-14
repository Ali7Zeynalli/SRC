<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
  ?>
<!-- Stats Rows -->
<!-- First Row - 3 cards -->
<div class="row g-3 mb-3">
    <!-- Total Users Card -->
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm" onclick="filterUsers('total')" data-stat="total">
            <div class="card-body p-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 small"><?php echo __('total_users'); ?></h6>
                        <h5 class="mb-0 text-primary fw-bold stat-value"><?php echo $stats['total']; ?></h5>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-2 rounded">
                        <i class="fas fa-users text-primary small"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Users Card -->
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm" onclick="filterUsers('active')" data-stat="active">
            <div class="card-body p-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 small"><?php echo __('active_users'); ?></h6>
                        <h5 class="mb-0 text-success fw-bold stat-value"><?php echo $stats['active']; ?></h5>
                    </div>
                    <div class="bg-success bg-opacity-10 p-2 rounded">
                        <i class="fas fa-user-check text-success small"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inactive Users Card -->
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm" onclick="filterUsers('inactive')" data-stat="inactive">
            <div class="card-body p-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 small"><?php echo __('inactive_users'); ?></h6>
                        <h5 class="mb-0 text-danger fw-bold stat-value"><?php echo $stats['inactive']; ?></h5>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-2 rounded">
                        <i class="fas fa-user-times text-danger small"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Second Row - 4 cards -->
<div class="row g-3 mb-4">
    <!-- Locked Users Card -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm" onclick="filterUsers('locked')" data-stat="locked">
            <div class="card-body p-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 small"><?php echo __('locked_users'); ?></h6>
                        <h5 class="mb-0 text-warning fw-bold stat-value"><?php echo $stats['locked']; ?></h5>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-2 rounded">
                        <i class="fas fa-user-lock text-warning small"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expired Password Card -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm" onclick="filterUsers('expired')" data-stat="expired">
            <div class="card-body p-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 small"><?php echo __('password_expired'); ?></h6>
                        <h5 class="mb-0 text-danger fw-bold stat-value"><?php echo $stats['expired_password']; ?></h5>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-2 rounded">
                        <i class="fas fa-key text-danger small"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Never Expires Card -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm" onclick="filterUsers('never_expires')" data-stat="never_expires">
            <div class="card-body p-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 small"><?php echo __('never_expires'); ?></h6>
                        <h5 class="mb-0 text-info fw-bold stat-value"><?php echo $stats['never_expires']; ?></h5>
                    </div>
                    <div class="bg-info bg-opacity-10 p-2 rounded">
                        <i class="fas fa-infinity text-info small"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Must Change Card -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm" onclick="filterUsers('must_change')" data-stat="must_change">
            <div class="card-body p-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 small"><?php echo __('must_change'); ?></h6>
                        <h5 class="mb-0 text-secondary fw-bold stat-value"><?php echo $stats['must_change']; ?></h5>
                    </div>
                    <div class="bg-secondary bg-opacity-10 p-2 rounded">
                        <i class="fas fa-key text-secondary small"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update CSS for 7 columns -->
<style>
@media (min-width: 992px) {
    .col-lg-1-7 {
        flex: 0 0 14.285714%;
        max-width: 14.285714%;
    }
}
</style>
