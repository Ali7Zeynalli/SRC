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

$pageTitle = __('dashboard_title');
$activePage = 'dashboard';

try {
    $ldap_conn = getLDAPConnection();
    $stats = [
        'users_total' => 0,
        'users_active' => 0,
        'users_locked' => 0,
        'groups_total' => 0,
        'computers_total' => 0,
        'gpo_count' => 0
    ];
    
    require_once('includes/header.php');
?>


<div class="container-fluid">
    <div class="main-wrapper">
        <?php require_once('includes/sidebar.php'); ?>
        
        <main>
            <div class="dashboard-container">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo __('dashboard_title'); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="tasks.php" class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-plus me-1"></i> <?php echo __('new_ticket'); ?>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshStats">
                            <i class="fas fa-sync-alt me-1"></i> <?php echo __('refresh'); ?>
                        </button>
                    </div>
                </div>

                <!-- Helpdesk Stats Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase small font-weight-bold mb-1"><?php echo __('total_tickets'); ?></h6>
                                        <h3 class="font-weight-bold mb-0 text-primary" id="taskTotal">-</h3>
                                    </div>
                                    <div class="text-primary opacity-50"><i class="fas fa-ticket-alt fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase small font-weight-bold mb-1"><?php echo __('open_unresolved'); ?></h6>
                                        <h3 class="font-weight-bold mb-0 text-danger" id="taskOpen">-</h3>
                                    </div>
                                    <div class="text-danger opacity-50"><i class="fas fa-exclamation-circle fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase small font-weight-bold mb-1"><?php echo __('unassigned'); ?></h6>
                                        <h3 class="font-weight-bold mb-0 text-warning" id="taskUnassigned">-</h3>
                                    </div>
                                    <div class="text-warning opacity-50"><i class="fas fa-user-clock fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards Row -->
                <div class="row g-3 mb-4">
                    <!-- Users Card -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon bg-primary bg-opacity-10 rounded p-3 me-3">
                                            <i class="fas fa-users text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="card-subtitle text-muted mb-1"><?php echo __('users'); ?></h6>
                                            <h3 class="card-title mb-0" id="totalUsers">-</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small>
                                        <i class="fas fa-user-check text-success me-1"></i>
                                        <span id="activeUsers">0</span> <?php echo __('active'); ?>
                                    </small>
                                    <small>
                                        <i class="fas fa-user-times text-danger me-1"></i>
                                        <span id="inactiveUsers">0</span> <?php echo __('inactive'); ?>
                                    </small>
                                    <small>
                                        <i class="fas fa-user-lock text-warning me-1"></i>
                                        <span id="lockedUsers">0</span> <?php echo __('locked'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Groups Card -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon bg-success bg-opacity-10 rounded p-3 me-3">
                                            <i class="fas fa-layer-group text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="card-subtitle text-muted mb-1"><?php echo __('groups'); ?></h6>
                                            <h3 class="card-title mb-0" id="totalGroups">-</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small>
                                        <i class="fas fa-shield-alt text-primary me-1"></i>
                                        <span id="securityGroups">0</span>&nbsp;<?php echo __('security_groups'); ?>
                                    </small>
                                    <small>
                                        <i class="fas fa-share-alt text-info me-1"></i>
                                        <span id="distributionGroups">0</span>&nbsp;<?php echo __('distribution_groups'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Computers Card -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon bg-info bg-opacity-10 rounded p-3 me-3">
                                            <i class="fas fa-desktop text-info"></i>
                                        </div>
                                        <div>
                                            <h6 class="card-subtitle text-muted mb-1"><?php echo __('computers'); ?></h6>
                                            <h3 class="card-title mb-0" id="dashboardTotalComputers">-</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small>
                                        <i class="fas fa-server text-info me-1"></i>
                                        <span id="dashboardServerComputers">0</span>&nbsp;<?php echo __('servers'); ?>
                                    </small>
                                    <small>
                                        <i class="fas fa-laptop text-success me-1"></i>
                                        <span id="dashboardWorkstationComputers">0</span>&nbsp;<?php echo __('workstations'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon bg-warning bg-opacity-10 rounded p-3 me-3">
                                            <i class="fas fa-bolt text-warning"></i>
                                        </div>
                                        <div>
                                            <h6 class="card-subtitle text-muted mb-1"><?php echo __('quick_actions'); ?></h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="quick-actions d-flex flex-wrap gap-2">
                                    <a href="notifications.php" class="quick-action-btn" data-bs-toggle="tooltip" title="<?php echo __('notifications'); ?>">
                                        <i class="fas fa-bell text-primary"></i>
                                    </a>
                                    <a href="activity-logs.php" class="quick-action-btn" data-bs-toggle="tooltip" title="<?php echo __('activity_logs'); ?>">
                                        <i class="fas fa-history text-info"></i>
                                    </a>
                                    <a href="security.php" class="quick-action-btn" data-bs-toggle="tooltip" title="<?php echo __('system_config'); ?>">
                                        <i class="fas fa-shield-alt text-danger"></i>
                                    </a>
                                    <a href="contact.php" class="quick-action-btn" data-bs-toggle="tooltip" title="<?php echo __('support'); ?>">
                                        <i class="fas fa-headset text-success"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-3 mb-4">
                    <!-- Users Distribution Chart -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-3"><?php echo __('users_distribution'); ?></h6>
                                <div style="height: 250px">
                                    <canvas id="usersChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Groups Distribution Chart -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-3"><?php echo __('groups_distribution'); ?></h6>
                                <div style="height: 250px">
                                    <canvas id="groupsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password Status Chart -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-3"><?php echo __('password_status'); ?></h6>
                                <div style="height: 250px">
                                    <canvas id="passwordStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Info Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-network-wired text-primary me-2"></i>
                            <?php echo __('system_health'); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- AD Server Status -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body" id="adServerCard">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-server fa-2x text-muted"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?php echo __('ad_server'); ?></h6>
                                                <div class="small text-muted"><?php echo __('loading'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hosting Server Status -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body" id="hostingDetails">
                                        <div class="loading-placeholder"><?php echo __('loading_hosting_details'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history text-primary me-2"></i>
                                <?php echo __('recent_activities'); ?>
                            </h5>
                            <a href="activity-logs.php" class="btn btn-sm btn-outline-primary ms-auto">
                                <?php echo __('view_all'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="activities-container" id="recentActivities">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden"><?php echo __('loading'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Required Scripts -->
<script src="temp/assets/lib/chart/chart.min.js"></script>
<?php require_once('includes/footer.php'); ?>
<script>
// Qlobal chart obyektlərini saxlamaq üçün
const charts = {
    usersChart: null,
    groupsChart: null
};

// DOM yüklənəndə işə düşəcək funksiyalar
document.addEventListener('DOMContentLoaded', function() {
    // İlkin yükləmələr
    loadInitialData();
    setupEventListeners();
    setupSidebar();
    updateComputerStats();
    
    // Periodik yeniləmələr
    setInterval(loadInitialData, 10000); // Hər 10 saniyədə
});

// İlkin məlumatların yüklənməsi
async function loadInitialData() {
    try {
        await Promise.all([
            loadStats(),
            loadSystemHealth(),
            loadRecentActivities()
        ]);
    } catch (error) {
        console.error('Data loading error:', error);
        showError('<?php echo __('error_loading_data'); ?>');
    }
}

// Sidebar funksionallığının qurulması
function setupSidebar() {
    // Sidebar toggle elementini əlavə et
    const body = document.body;
    const sidebarToggle = document.createElement('div');
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    body.appendChild(sidebarToggle);

    // Sidebar yığılması/açılması üçün event handler
    sidebarToggle.addEventListener('click', function() {
        if (window.innerWidth < 992) {
            // Mobil görünüşdə
            body.classList.toggle('sidebar-open');
        } else {
            // Desktop görünüşdə
            body.classList.toggle('sidebar-collapsed');
        }
    });

    // Klik hadisəsi üçün dinləyici əlavə et
    document.addEventListener('click', function(event) {
        // Sidebar xaricində klik olunduqda və sidebar açıqdırsa
        if (window.innerWidth < 992 && 
            body.classList.contains('sidebar-open') && 
            !event.target.closest('.sidebar') && 
            !event.target.closest('.sidebar-toggle')) {
            body.classList.remove('sidebar-open');
        }
    });

    // Ekran ölçüsü dəyişdikdə
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            body.classList.remove('sidebar-open');
        } else {
            body.classList.remove('sidebar-collapsed');
        }
    });

    // Sidebar bağlantılarına klik olunduqda mobil görünüşdə sidebarı bağla
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                body.classList.remove('sidebar-open');
            }
        });
    });
}

// Event listener-lərin qurulması
function setupEventListeners() {
    // Refresh düyməsi
    const refreshBtn = document.getElementById('refreshStats');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', async function() {
            try {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?php echo __('refreshing'); ?>';
                
                await loadInitialData();
            } catch (error) {
                console.error('Refresh error:', error);
                showError('<?php echo __('error_refreshing_data'); ?>');
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync-alt me-1"></i> <?php echo __('refresh'); ?>';
            }
        });
    }

    // Statistika kartlarına klik
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function() {
            const type = this.dataset.type;
            if (type) {
                window.location.href = `${type}.php`;
            }
        });
    });
}

// Statistikaların yüklənməsi
async function loadStats() {
    try {
        const response = await fetch('api/stats.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        
        if (data.success) {
            updateStatCards(data.stats);
            updateCharts(data.stats);
        } else {
            // Clear session and reload if there's an error
            await fetch('api/clear-session.php');
            throw new Error(data.error || '<?php echo __('error_loading_stats'); ?>');
        }
    } catch (error) {
        console.error('Stats loading error:', error);
        // Clear session and reload on error
        await fetch('api/clear-session.php');
        throw error;
    }
}

// Statistika kartlarının yenilənməsi
function updateStatCards(stats) {
    if (!stats?.users) return;

    const userStats = stats.users;
    
    // Update user statistics
    updateElement('totalUsers', userStats.total || 0);
    updateElement('activeUsers', userStats.active || 0);
    updateElement('inactiveUsers', userStats.inactive || 0);
    updateElement('lockedUsers', userStats.locked || 0);
    
    // Update progress bar
    const total = userStats.total || 0;
    if (total > 0) {
        const activePercent = Math.round((userStats.active || 0) / total * 100);
        const inactivePercent = Math.round((userStats.inactive || 0) / total * 100);
        const lockedPercent = Math.round((userStats.locked || 0) / total * 100);
        
        // Add tooltip data
        document.getElementById('activeUsers').title = `${activePercent}%`;
        document.getElementById('inactiveUsers').title = `${inactivePercent}%`;
        document.getElementById('lockedUsers').title = `${lockedPercent}%`;
    }

    // Groups statistics
    const groupStats = stats.groups || {};
    updateElement('totalGroups', groupStats.total || 0);
    updateElement('securityGroups', groupStats.security || 0);
    updateElement('distributionGroups', groupStats.distribution || 0);

    // Computers statistics
    const computerStats = stats.computers || {};
    updateElement('totalComputers', computerStats.total || 0);
    updateElement('serverComputers', computerStats.servers || 0);
    updateElement('workstationComputers', computerStats.workstations || 0);

    // Task Statistics
    const taskStats = stats.tasks || {};
    updateElement('taskTotal', taskStats.total || 0);
    updateElement('taskOpen', taskStats.open || 0);
    updateElement('taskUnassigned', taskStats.unassigned || 0);

    // Update charts
    updateCharts(stats);
}

// Qrafiklərin yenilənməsi
function updateCharts(stats) {
    if (!stats?.users) return;

    const userStats = stats.users;
    const groupStats = stats.groups || {};
    const passwordStats = userStats.password_status || {};

    // Users Distribution Chart
    updateChart('usersChart', {
        type: 'doughnut',
        data: {
            labels: [
                '<?php echo __('active'); ?>',
                '<?php echo __('inactive'); ?>',
                '<?php echo __('locked'); ?>'
            ],
            datasets: [{
                data: [
                    userStats.active || 0,
                    userStats.inactive || 0,
                    userStats.locked || 0
                ],
                backgroundColor: [
                    '#28a745', // Active - Green
                    '#dc3545', // Inactive - Red
                    '#ffc107'  // Locked - Yellow
                ],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        boxWidth: 12,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const total = data.datasets[0].data.reduce((acc, val) => acc + val, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    
                                    return {
                                        text: `${label} (${percentage}%)`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: data.datasets[0].borderColor,
                                        lineWidth: data.datasets[0].borderWidth,
                                        hidden: isNaN(data.datasets[0].data[i]) || data.datasets[0].data[i] === 0,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = userStats.total || 0;
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });

    // Groups Distribution Chart
    updateChart('groupsChart', {
        type: 'doughnut',
        data: {
            labels: [
                '<?php echo __('security_groups'); ?>',
                '<?php echo __('distribution_groups'); ?>'
            ],
            datasets: [{
                data: [
                    groupStats.security || 0,
                    groupStats.distribution || 0
                ],
                backgroundColor: [
                    '#007bff', // Security - Blue
                    '#17a2b8'  // Distribution - Cyan
                ],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        boxWidth: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = (groupStats.security || 0) + (groupStats.distribution || 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });

    // Password Status Chart
    updateChart('passwordStatusChart', {
        type: 'doughnut',
        data: {
            labels: [
                '<?php echo __('expired'); ?>',
                '<?php echo __('never_expires'); ?>',
                '<?php echo __('must_change'); ?>'
            ],
            datasets: [{
                data: [
                    passwordStats.expired || 0,
                    passwordStats.never_expires || 0,
                    passwordStats.must_change || 0
                ],
                backgroundColor: [
                    '#dc3545', // Expired
                    '#0dcaf0', // Never Expires 
                    '#6c757d'  // Must Change 
                ],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        boxWidth: 12,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const total = data.datasets[0].data.reduce((acc, val) => acc + val, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    
                                    return {
                                        text: `${label} (${percentage}%)`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: data.datasets[0].borderColor,
                                        lineWidth: data.datasets[0].borderWidth,
                                        hidden: isNaN(data.datasets[0].data[i]) || data.datasets[0].data[i] === 0,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = Object.values(passwordStats).reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
}

// Chart yeniləmə funksiyası
function updateChart(canvasId, config) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    
    // Əvvəlki chart-ı təmizlə
    if (charts[canvasId]) {
        charts[canvasId].destroy();
    }

    // Yeni chart yarat
    charts[canvasId] = new Chart(ctx, config);
}

// Sistem sağlamlığının yüklənməsi
async function loadSystemHealth() {
    try {
        const response = await fetch('api/system-health.php');
        if (!response.ok) {
            throw new Error(`<?php echo __('http_error'); ?>: ${response.status}`);
        }
        
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || '<?php echo __('error_loading_system_health'); ?>');
        }

        updateADServerInfo(data);
        updateHostingDetails(data.hosting_server);
    } catch (error) {
        console.error('<?php echo __('system_health_error'); ?>:', error);
        
        // Xəta mesajlarını UI-da göstər
        const adServerCard = document.getElementById('adServerCard');
        if (adServerCard) {
            adServerCard.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo __('error_loading_ad_server'); ?>
                </div>
            `;
        }
        
        const hostingDetails = document.getElementById('hostingDetails');
        if (hostingDetails) {
            hostingDetails.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo __('error_loading_hosting_details'); ?>
                </div>
            `;
        }
    }
}

function updateHostingDetails(hosting) {
    const interfacesHtml = hosting.network.interfaces.map(iface => `
        <div class="network-interface mb-2">
            <div class="d-flex justify-content-between">
                <small class="text-muted">${iface.name}:</small>
                <small class="fw-medium">${iface.ip}</small>
            </div>
            <div class="d-flex justify-content-between">
                <small class="text-muted">MAC:</small>
                <small class="fw-medium">${iface.mac}</small>
            </div>
        </div>
    `).join('');

    const html = `
        <div class="d-flex flex-column gap-2">
            <div class="d-flex justify-content-between">
                <span class="text-muted"><?php echo __('hostname'); ?>:</span>
                <span class="fw-medium">${hosting.server.hostname}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted"><?php echo __('operating_system'); ?>:</span>
                <span class="fw-medium">${hosting.server.os}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted"><?php echo __('web_server'); ?>:</span>
                <span class="fw-medium">${hosting.server.server_software}</span>
            </div>
            <div class="network-interfaces mt-2">
                <span class="text-muted d-block mb-2"><?php echo __('network_interfaces'); ?>:</span>
                ${interfacesHtml}
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted"><?php echo __('database'); ?>:</span>
                <span class="badge bg-${hosting.database.status === 'Connected' ? 'success' : 'danger'} rounded-pill">
                    ${hosting.database.status}
                </span>
            </div>
        </div>
    `;
    document.getElementById('hostingDetails').innerHTML = html;
}

// AD Server məlumatlarını göstərmək funksiyasını yeniləyirik
function updateADServerInfo(data) {
    const adServerCard = document.getElementById('adServerCard');
    if (!adServerCard) return;

    const serverInfo = data.ad_server.network_info;
    const networkInfo = serverInfo.network;
    
    adServerCard.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-server fa-2x ${data.ad_server.status ? 'text-success' : 'text-danger'}"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="mb-1"><?php echo __('ad_server'); ?></h6>
                
                <div class="small mb-2 ${data.ad_server.status ? 'text-success' : 'text-danger'}">
                    <i class="fas fa-circle me-1"></i>
                    ${data.ad_server.status ? '<?php echo __('connected'); ?>' : '<?php echo __('disconnected'); ?>'}
                    <span class="text-muted ms-2">
                        (<?php echo __('response_time'); ?>: ${data.ad_server.response_time}ms)
                    </span>
                </div>

                <div class="server-details small">
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><?php echo __('domain'); ?>:</span>
                                <span class="fw-medium">${serverInfo.domain}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><?php echo __('server_name'); ?>:</span>
                                <span class="fw-medium">${serverInfo.hostname}</span>
                            </div>
                        </div>
                        
                        <!-- Network Information -->
                        <div class="col-12 mt-2">
                            <div class="network-info border-top pt-2">
                                <div class="text-muted mb-2"><?php echo __('network_configuration'); ?>:</div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo __('ip_address'); ?>:</span>
                                    <span class="fw-medium">${networkInfo.ip_address}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo __('mac_address'); ?>:</span>
                                    <span class="fw-medium">${networkInfo.mac_address}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo __('gateway'); ?>:</span>
                                    <span class="fw-medium">${networkInfo.default_gateway}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo __('subnet_mask'); ?>:</span>
                                    <span class="fw-medium">${networkInfo.subnet_mask}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo __('dns_servers'); ?>:</span>
                                    <span class="fw-medium text-end">
                                        ${networkInfo.dns_servers.length > 0 
                                            ? networkInfo.dns_servers.map(dns => 
                                                `<div>${dns}</div>`
                                              ).join('')
                                            : '<?php echo __('no_dns_servers'); ?>'
                                        }
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Domain Information -->
                        <div class="col-12 mt-2">
                            <div class="domain-info border-top pt-2">
                                <div class="text-muted mb-2"><?php echo __('domain_configuration'); ?>:</div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo __('domain_level'); ?>:</span>
                                    <span class="fw-medium">${serverInfo.domain_functionality}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo __('forest_level'); ?>:</span>
                                    <span class="fw-medium">${serverInfo.forest_functionality}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted"><?php echo __('dc_level'); ?>:</span>
                                    <span class="fw-medium">${serverInfo.dc_functionality}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Utility funksiyaları
function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value || '0';
    }
}

function updateMetric(elementId, value, color) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const percentage = Math.min(100, Math.max(0, value));
    element.style.background = `conic-gradient(
        ${color} ${percentage * 3.6}deg,
        #f0f0f0 ${percentage * 3.6}deg
    )`;
    
    element.innerHTML = `<div class="metric-value">${Math.round(percentage)}%</div>`;
}

function showError(message) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-danger border-0';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.appendChild(toast);
    document.body.appendChild(container);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        container.remove();
    });
}

function loadRecentActivities() {
    fetch('api/recent-activities.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateRecentActivities(data.activities);
            } else {
                throw new Error(data.error || '<?php echo __('error_loading_activities'); ?>');
            }
        })
        .catch(error => {
            console.error('<?php echo __('error'); ?>:', error);
            const container = document.getElementById('recentActivities');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-danger m-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo __('error_loading_activities'); ?>
                    </div>
                `;
            }
        });
}

function updateRecentActivities(activities) {
    const container = document.getElementById('recentActivities');
    if (!container) return;

    if (!activities || activities.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <div class="text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo __('no_recent_activities'); ?>
                </div>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th><?php echo __('time'); ?></th>
                        <th><?php echo __('action'); ?></th>
                        <th><?php echo __('user'); ?></th>
                        <th><?php echo __('details'); ?></th>
                        <th><?php echo __('status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    ${activities.map(activity => `
                        <tr>
                            <td>
                                <div class="small">${formatTimeAgo(activity.timestamp)}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="activity-icon me-2">
                                        ${getActionIcon(activity.action)}
                                    </div>
                                    <div class="fw-medium">
                                        ${formatAction(activity.action)}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">${escapeHtml(activity.user_id)}</div>
                                <small class="text-muted">${activity.ip_address || ''}</small>
                            </td>
                            <td>${formatActivityDetails(activity)}</td>
                            <td>
                                <span class="badge bg-${getActivityColor(activity.action)}">
                                    ${activity.status || '<?php echo __('success'); ?>'}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function getActivityColor(action) {
    const colors = {
        'login': 'success',
        'login_failed': 'danger',
        'logout': 'warning',
        'user_data_change': 'info',
        'unlock_user': 'success',
        'delete_user': 'danger',
        'enable_user': 'success',
        'disable_user': 'warning'
    };
    
    return colors[action.toLowerCase()] || 'secondary';
}

function formatActivityDetails(activity) {
    if (activity.target_user_id) {
        const details = {
            'user_data_change': {
                text: '<?php echo __('modified_user'); ?>:',
                class: 'text-primary'
            },
            'unlock_user': {
                text: '<?php echo __('unlocked_user'); ?>:',
                class: 'text-success'
            },
            'delete_user': {
                text: '<?php echo __('deleted_user'); ?>:',
                class: 'text-danger'
            },
            'enable_user': {
                text: '<?php echo __('enabled_user'); ?>:',
                class: 'text-success'
            },
            'disable_user': {
                text: '<?php echo __('disabled_user'); ?>:',
                class: 'text-warning'
            },
            'login_failed': {
                text: '<?php echo __('failed_login_attempt'); ?>:',
                class: 'text-danger'
            }
        };

        const detail = details[activity.action.toLowerCase()] || {
            text: '<?php echo __('user'); ?>:',
            class: 'text-secondary'
        };

        // Ignore specific details text
        const ignoreDetails = [
            '<?php echo __('cleared_displayname'); ?>',
            '<?php echo __('updated_mail'); ?>',
            '<?php echo __('updated_title'); ?>',
            '<?php echo __('updated_telephone'); ?>',
            '<?php echo __('updated_mobile'); ?>',
            '<?php echo __('updated_department'); ?>',
            '<?php echo __('updated_description'); ?>'
        ];

        let detailsText = '';
        if (activity.details && !ignoreDetails.some(text => activity.details.includes(text))) {
            detailsText = `
                <span class="mx-2">•</span>
                <span>${escapeHtml(activity.details)}</span>
            `;
        }

        return `
            <div class="d-flex align-items-center">
                <span class="me-2">${detail.text}</span>
                <span class="${detail.class} fw-medium">
                    ${escapeHtml(activity.target_user_id)}
                </span>
                ${detailsText}
            </div>
        `;
    }

    return activity.details || '<?php echo __('no_additional_details'); ?>';
}

function formatTimeAgo(timestamp) {
    const date = new Date(timestamp);
    
    // Format date and time in English
    const formatDateTime = (date) => {
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        
        return `${month}/${day}/${year} ${hours}:${minutes}`;
    };

    return formatDateTime(date);
}

function formatAction(action) {
    return action.split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Kompüter statistikalarını yeniləmək üçün funksiya
function updateComputerStats() {
    fetch('api/computers.php')
        .then(response => response.json())
        .then(data => {
            if (data && data.stats) {
                const stats = data.stats;
                // Ümumi kompüter sayı
                document.getElementById('dashboardTotalComputers').textContent = stats.total || 0;
                // Server sayı
                document.getElementById('dashboardServerComputers').textContent = stats.servers || 0;
                // Workstation sayı
                document.getElementById('dashboardWorkstationComputers').textContent = stats.workstations || 0;
            }
        })
        .catch(error => {
            console.error('<?php echo __('error_loading_computer_stats'); ?>:', error);
            // Xəta halında 0 göstər
            document.getElementById('dashboardTotalComputers').textContent = '0';
            document.getElementById('dashboardServerComputers').textContent = '0';
            document.getElementById('dashboardWorkstationComputers').textContent = '0';
        });
}

function getActionIcon(action) {
    const icons = {
        'login': '<i class="fas fa-sign-in-alt text-success"></i>',
        'login_failed': '<i class="fas fa-exclamation-triangle text-danger"></i>',
        'logout': '<i class="fas fa-sign-out-alt text-danger"></i>',
        'user_data_change': '<i class="fas fa-user-edit text-info"></i>',
        'unlock_user': '<i class="fas fa-unlock text-success"></i>',
        'delete_user': '<i class="fas fa-user-times text-danger"></i>',
        'enable_user': '<i class="fas fa-user-check text-success"></i>',
        'disable_user': '<i class="fas fa-user-slash text-warning"></i>',
        'password_reset': '<i class="fas fa-key text-warning"></i>',
        'reset_password': '<i class="fas fa-key text-warning"></i>'
    };
    
    return icons[action.toLowerCase()] || '<i class="fas fa-info-circle text-secondary"></i>';
}


</script>



<?php
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    header('Location: error.php?code=500&message=' . urlencode($e->getMessage()));
    exit;
}
?>
