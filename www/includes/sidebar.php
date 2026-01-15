<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
?>
<nav id="sidebar" class="sidebar">
    <div class="sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span><?php echo __('dashboard_title'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'users' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    <span><?php echo __('users'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'ous' ? 'active' : ''; ?>" href="ous.php">
                    <i class="fas fa-sitemap"></i>
                    <span><?php echo __('ous'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'groups' ? 'active' : ''; ?>" href="groups.php">
                    <i class="fas fa-layer-group"></i>
                    <span><?php echo __('groups'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'computers' ? 'active' : ''; ?>" href="computers.php">
                    <i class="fas fa-desktop"></i>
                    <span><?php echo __('computers'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'tasks' ? 'active' : ''; ?>" href="tasks.php">
                    <i class="fas fa-ticket-alt"></i>
                    <span><?php echo __('helpdesk_tasks'); ?></span>
                </a>
            </li>
        </ul>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'gpo' ? 'active' : ''; ?>" href="gpo.php">
                    <i class="fas fa-shield-alt"></i>
                    <span><?php echo __('group_policy'); ?></span>
                </a>
            </li>
        </ul>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'reports' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span><?php echo __('reports'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'activity-logs' ? 'active' : ''; ?>" href="activity-logs.php">
                    <i class="fas fa-history"></i>
                    <span><?php echo __('activity_logs'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'notifications' ? 'active' : ''; ?>" href="notifications.php">
                    <i class="fas fa-bell"></i>
                    <span><?php echo __('notifications'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage === 'security' ? 'active' : ''; ?>" href="security.php">
                    <i class="fas fa-cog"></i>
                    <span><?php echo __('system_config'); ?></span>
                </a>
            </li>
        </ul>
    </div>
</nav>