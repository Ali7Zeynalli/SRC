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
    header('Location: login.php');
    exit;
}

$activePage = 'activity-logs';
require_once('includes/header.php');
?>

<!-- CSS -->
<link rel="stylesheet" type="text/css" href="temp/assets/lib/daterangepicker/daterangepicker.css" />
<link href="temp/css/activity-logs.css" rel="stylesheet">

<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-history text-primary me-2"></i><?php echo __('activity_logs_title'); ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshLogs">
                        <i class="fas fa-sync-alt me-1"></i> <?php echo __('refresh'); ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="exportLogs">
                        <i class="fas fa-download me-1"></i> <?php echo __('export'); ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="deleteLogs">
                        <i class="fas fa-trash-alt me-1"></i> <?php echo __('delete_all_logs'); ?>
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-primary bg-opacity-10 me-3">
                                    <i class="fas fa-chart-line text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('total_activities'); ?></h6>
                                    <h3 class="card-title mb-0" id="totalActivities">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-success bg-opacity-10 me-3">
                                    <i class="fas fa-calendar-day text-success"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('todays_activities'); ?></h6>
                                    <h3 class="card-title mb-0" id="todayActivities">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-info bg-opacity-10 me-3">
                                    <i class="fas fa-calendar-week text-info"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('weekly_activities'); ?></h6>
                                    <h3 class="card-title mb-0" id="weeklyActivities">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-warning bg-opacity-10 me-3">
                                    <i class="fas fa-calendar-alt text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('monthly_activities'); ?></h6>
                                    <h3 class="card-title mb-0" id="monthlyActivities">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-0 bg-light" 
                                       id="searchInput" placeholder="<?php echo __('search_logs'); ?>">
                            </div>
                        </div>
                
                        <div class="col-md-4">
                            <input type="text" class="form-control bg-light border-0" 
                                   id="dateRange" placeholder="<?php echo __('select_date_range'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
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
                            <tbody id="logsTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <nav class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted small">
                    <?php echo __('showing'); ?> <span id="currentRange">0-0</span> <?php echo __('of'); ?> <span id="totalLogs">0</span> <?php echo __('logs'); ?>
                </div>
                <ul class="pagination mb-0" id="pagination"></ul>
            </nav>
        </main>
    </div>
</div>

<!-- Templates for log entries -->
<template id="logItemTemplate">
    <tr>
        <td class="log-time"></td>
        <td class="log-action"></td>
        <td class="log-user"></td>
        <td class="log-details"></td>
        <td class="log-status"></td>
    </tr>
</template>

<!-- Scripts -->
<?php require_once('includes/footer.php'); ?>
<!-- jQuery -->
<script src="temp/assets/lib/jquery/jquery-3.6.0.min.js"></script>
<!-- Moment.js (required for daterangepicker) -->
<script src="temp/assets/lib/moment/moment.min.js"></script>
<!-- Daterangepicker -->
<script src="temp/assets/lib/daterangepicker/daterangepicker.min.js"></script>
<!-- Activity Logs JS -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
    initializeDateRangePicker();
    loadLogs();
    setupEventListeners();
});

function setupEventListeners() {
    // Refresh button
    const refreshBtn = document.getElementById('refreshLogs');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            // Clear search input
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Reset action filter
            const actionFilter = document.getElementById('actionFilter');
            if (actionFilter) {
                actionFilter.value = '';
            }
            
            // Set date range to today
            const dateRange = $('#dateRange').data('daterangepicker');
            if (dateRange) {
                dateRange.setStartDate(moment());
                dateRange.setEndDate(moment());
            }
            
            // Reload logs
            loadLogs(1);
            
            // Rotate refresh icon
            const icon = refreshBtn.querySelector('i');
            if (icon) {
                icon.classList.add('fa-spin');
                setTimeout(() => icon.classList.remove('fa-spin'), 1000);
            }
        });
    }
    
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterLogs, 300));
    }
    
    // Action filter
    const actionFilter = document.getElementById('actionFilter');
    if (actionFilter) {
        actionFilter.addEventListener('change', filterLogs);
    }

    // Delete logs button
    const deleteBtn = document.getElementById('deleteLogs');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', confirmDeleteLogs);
    }

    // Export logs button
    const exportBtn = document.getElementById('exportLogs');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportLogs);
    }
}

function initializeDateRangePicker() {
    const dateRange = document.getElementById('dateRange');
    if (!dateRange) return;

    // Get today's date
    const today = moment();

    $(dateRange).daterangepicker({
        startDate: today.clone().startOf('day'),  // Start of day
        endDate: today.clone().endOf('day'),      // End of day
        ranges: {
            '<?php echo __('today'); ?>': [moment().startOf('day'), moment().endOf('day')],
            '<?php echo __('yesterday'); ?>': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
            '<?php echo __('last_7_days'); ?>': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            '<?php echo __('last_30_days'); ?>': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
            '<?php echo __('this_month'); ?>': [moment().startOf('month'), moment().endOf('month')]
        },
        locale: {
            format: 'YYYY-MM-DD'
        }
    }, function(start, end) {
        loadLogs();
    });

    // Display date range value
    dateRange.value = `${today.format('YYYY-MM-DD')} - ${today.format('YYYY-MM-DD')}`;
}

function loadLogs(page = 1) {
    showLoading();
    
    const dateRange = $('#dateRange').data('daterangepicker');
    const actionFilter = document.getElementById('actionFilter');
    const searchInput = document.getElementById('searchInput');

    const params = new URLSearchParams({
        page: page,
        limit: 20,
        start_date: dateRange.startDate.startOf('day').format('YYYY-MM-DD HH:mm:ss'),
        end_date: dateRange.endDate.endOf('day').format('YYYY-MM-DD HH:mm:ss'),
        action: actionFilter ? actionFilter.value : '',
        search: searchInput ? searchInput.value : ''
    });

    fetch(`api/activity-logs.php?${params}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateLogsTable(data.logs);
                updatePagination(data.pagination);
                updateStats(data.stats);
            } else {
                throw new Error(data.error || '<?php echo __('error_load_logs'); ?>');
            }
        })
        .catch(error => {
            console.error('Error loading logs:', error);
            showError('<?php echo __('error_load_logs'); ?>');
        })
        .finally(hideLoading);
}

function updateLogsTable(logs) {
    const tbody = document.getElementById('logsTable');
    if (!tbody) return;
    
    if (!logs || logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i><?php echo __('no_logs_found'); ?>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = logs.map(log => `
        <tr class="activity-log-item action-${log.action.toLowerCase()}">
            <td>
                <div class="small text-muted">${formatDate(log.timestamp)}</div>
                <div class="smaller">${formatTime(log.timestamp)}</div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="activity-icon me-3">
                        ${getActionIcon(log.action)}
                    </div>
                    <div>
                        <div class="fw-medium">${formatAction(log.action)}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="fw-medium">${escapeHtml(log.user_id)}</div>
                <small class="text-muted">${log.ip_address || ''}</small>
            </td>
            <td>
                ${formatDetails(log)}
            </td>
            <td>
                <span class="badge ${getStatusBadgeClass(log.status)}">
                    ${log.status || '<?php echo __('success'); ?>'}
                </span>
            </td>
        </tr>
    `).join('');
}

function formatDetails(log) {
    const action = log.action.toLowerCase();
    
    // Create a standardized format for details with proper structure
    const createDetailHTML = (label, value, additionalDetails = null, statusClass = 'text-primary') => {
                return `
                    <div>
                <span class="fw-medium">${label}</span> 
                <span class="${statusClass}">${escapeHtml(value || '')}</span>
                ${additionalDetails ? `<div class="small text-muted">${escapeHtml(additionalDetails)}</div>` : ''}
                    </div>
                `;
    };
    
    // Authentication actions
    if (action === 'login' || action === 'logout' || action === 'login_failed') {
        let operationType = '';
        let textColorClass = 'text-primary';
        let sectionIcon = '<i class="fas fa-shield-alt me-2"></i>'; // Security/Auth section icon
        let additionalDetails = log.details || '';
        
        if (action === 'login') {
            operationType = 'Successful login';
            textColorClass = 'text-success';
        } else if (action === 'logout') {
            operationType = 'Logged out';
            textColorClass = 'text-secondary';
        } else if (action === 'login_failed') {
            operationType = 'Login failed';
            textColorClass = 'text-warning';
            additionalDetails = additionalDetails || 'Invalid credentials';
        }
        
        return createDetailHTML(`${sectionIcon}${operationType}`, '', additionalDetails, textColorClass);
    }
    
    // User Operations
    if (action.includes('user') || action === 'password_reset' || action === 'unlock_user') {
        let operationType = '';
        let textColorClass = 'text-primary';
        let sectionIcon = '<i class="fas fa-user me-2"></i>'; // User section icon
        
        if (action === 'user_create') {
            operationType = 'New user:';
        } else if (action === 'user_modify') {
            operationType = 'Modified user:';
        } else if (action === 'user_data_change') {
            operationType = 'User data changed:';
        } else if (action === 'user_enable' || action === 'enable_user') {
            operationType = 'User enabled:';
            textColorClass = 'text-success';
        } else if (action === 'user_disable' || action === 'disable_user') {
            operationType = 'User disabled:';
            textColorClass = 'text-warning';
        } else if (action === 'password_reset') {
            operationType = 'Password reset for user:';
            textColorClass = 'text-warning';
        } else if (action === 'unlock_user') {
            operationType = 'User unlocked:';
            textColorClass = 'text-success';
        } else if (action === 'move_user') {
            operationType = 'Moved user:';
            textColorClass = 'text-info';
        } else if (action === 'user_group_membership') {
            operationType = 'Group membership:';
        } else if (action === 'delete_user') {
            operationType = 'Deleted user:';
            textColorClass = 'text-danger';
        }
        
        // Extract user name and any additional details
        let userName = '';
        let additionalDetails = '';
        
        if (log.details) {
            try {
                const detailsObj = JSON.parse(log.details);
                userName = detailsObj.name || detailsObj.user || log.details;
                
                // Collect additional details
                const detailsArray = [];
                if (detailsObj.type) detailsArray.push(`Type: ${detailsObj.type}`);
                if (detailsObj.path) detailsArray.push(`Path: ${detailsObj.path}`);
                if (detailsObj.from && detailsObj.to) {
                    detailsArray.push(`From: ${detailsObj.from} → To: ${detailsObj.to}`);
                }
                
                additionalDetails = detailsArray.join(' | ');
            } catch (e) {
                // Not JSON, use as is
                userName = log.target_user_id || log.details || '';
            }
        } else if (log.target_user_id) {
            userName = log.target_user_id;
        }
        
        // Return with section icon
        return createDetailHTML(`${sectionIcon}${operationType}`, userName, additionalDetails, textColorClass);
    }
    
    // OU Operations
    if (action.includes('_ou')) {
        let operationType = '';
        let textColorClass = 'text-primary';
        let sectionIcon = '<i class="fas fa-folder me-2"></i>'; // OU section icon
        
        if (action === 'create_ou') {
            operationType = 'Created OU:';
        } else if (action === 'update_ou') {
            operationType = 'Updated OU:';
        } else if (action === 'move_ou') {
            operationType = 'Moved OU:';
            textColorClass = 'text-info';
        } else if (action === 'delete_ou') {
            operationType = 'Deleted OU:';
            textColorClass = 'text-danger';
        }

        // Extract OU name from details if available
        let ouName = '';
        let additionalDetails = '';
        
        if (log.details) {
            // Check if details contains JSON or has a specific format
            try {
                const detailsObj = JSON.parse(log.details);
                ouName = detailsObj.name || '';
                
                // Format additional details based on action type
                if (action === 'move_ou') {
                    if (detailsObj.from && detailsObj.to) {
                        const fromPath = detailsObj.from.replace(/^OU=|,DC=.*$/g, '');
                        const toName = detailsObj.destination_name || 'Unknown';
                        additionalDetails = `From: ${fromPath} → To: ${toName}`;
                    }
                } else if (action === 'create_ou') {
                    const parent = detailsObj.parent === 'root' ? 'Root' : detailsObj.parent;
                    if (parent) {
                        additionalDetails = `Parent: ${parent}`;
                    }
                    if (detailsObj.description) {
                        additionalDetails += (additionalDetails ? ' | ' : '') + 
                            `Description: ${detailsObj.description}`;
                    }
                } else if (action === 'update_ou') {
                    if (detailsObj.description) {
                        additionalDetails = `Description: ${detailsObj.description}`;
                    }
                } else if (action === 'delete_ou') {
                    if (detailsObj.dn) {
                        additionalDetails = `DN: ${detailsObj.dn.replace(/,DC=.*$/g, '')}`;
                    }
                }
            } catch (e) {
                // Fallback for non-JSON format
                if (action === 'delete_ou') {
                    if (log.details.includes('Attempting to delete OU')) {
                        const match = log.details.match(/Attempting to delete OU ['"](.*?)['"]/) || 
                                      log.details.match(/Attempting to delete OU (.*?)(?:\s|$)/);
                        if (match && match[1]) {
                            ouName = match[1];
                            additionalDetails = '';
                        } else {
                            const parts = log.details.split('\\');
                            ouName = parts[0].trim();
                            if (parts.length > 1) {
                                additionalDetails = parts.slice(1).join('\\').trim();
                            }
                        }
                    } else {
                        const dnMatch = log.details.match(/OU=([^,]+)/);
                        if (dnMatch && dnMatch[1]) {
                            ouName = dnMatch[1];
                        } else {
                            ouName = log.details.replace(/\\+$/, '').trim();
                        }
                    }
                } else if (action === 'move_ou') {
                    const moveMatch = log.details.match(/Moving OU ['"](.*?)['"] from ['"](.*?)['"] to ['"](.*?)['"]/i);
                    if (moveMatch && moveMatch.length >= 4) {
                        ouName = moveMatch[1];
                        additionalDetails = `From: ${moveMatch[2]} → To: ${moveMatch[3]}`;
                    } else {
                        const nameMatch = log.details.match(/OU ['"](.*?)['"]/i) || 
                                         log.details.match(/OU=([^,]+)/);
                        if (nameMatch && nameMatch[1]) {
                            ouName = nameMatch[1];
                        } else {
                            ouName = log.details.replace(/\\+$/, '').trim();
                        }
                    }
                } else if (action === 'update_ou') {
                    const updateMatch = log.details.match(/Updating OU ['"](.*?)['"]/i);
                    if (updateMatch && updateMatch[1]) {
                        ouName = updateMatch[1];
                    } else {
                        const nameMatch = log.details.match(/OU ['"](.*?)['"]/i) || 
                                         log.details.match(/OU=([^,]+)/);
                        if (nameMatch && nameMatch[1]) {
                            ouName = nameMatch[1];
                        } else {
                            ouName = log.details.replace(/\\+$/, '').trim();
                        }
                    }
                } else if (action === 'create_ou') {
                    const createMatch = log.details.match(/Created OU ['"](.*?)['"]/i) ||
                                       log.details.match(/Creating OU ['"](.*?)['"]/i);
                    if (createMatch && createMatch[1]) {
                        ouName = createMatch[1];
                    } else {
                        const nameMatch = log.details.match(/OU ['"](.*?)['"]/i) || 
                                         log.details.match(/OU=([^,]+)/);
                        if (nameMatch && nameMatch[1]) {
                            ouName = nameMatch[1];
                        } else {
                            ouName = log.details.replace(/\\+$/, '').trim();
                        }
                    }
                } else {
                    ouName = log.details.replace(/\\+$/, '').trim();
                }
            }
        }
        
        return createDetailHTML(`${sectionIcon}${operationType}`, ouName, additionalDetails, textColorClass);
    }
    
    // Group Operations
    if (action.includes('group')) {
        let operationType = '';
        let textColorClass = 'text-primary';
        let sectionIcon = '<i class="fas fa-users me-2"></i>'; // Group section icon
        
        if (action === 'create_group') {
            operationType = 'Created group:';
        } else if (action === 'modify_group') {
            operationType = 'Modified group:';
        } else if (action === 'change_group_type') {
            operationType = 'Changed group type:';
            textColorClass = 'text-info';
        } else if (action === 'move_group') {
            operationType = 'Moved group:';
            textColorClass = 'text-info';
        } else if (action === 'delete_group') {
            operationType = 'Deleted group:';
            textColorClass = 'text-danger';
        }
        
        // Extract group name and any additional details
        let groupName = '';
        let additionalDetails = '';
        
        if (log.details) {
            try {
                const detailsObj = JSON.parse(log.details);
                groupName = detailsObj.name || detailsObj.group || log.details;
                
                // Collect additional details
                const detailsArray = [];
                if (detailsObj.type) detailsArray.push(`Type: ${detailsObj.type}`);
                if (detailsObj.path) detailsArray.push(`Path: ${detailsObj.path}`);
                if (detailsObj.from && detailsObj.to) {
                    detailsArray.push(`From: ${detailsObj.from} → To: ${detailsObj.to}`);
                }
                
                additionalDetails = detailsArray.join(' | ');
            } catch (e) {
                // Not JSON, use as is
                groupName = log.details;
            }
        }
        
        // Return with section icon
        return createDetailHTML(`${sectionIcon}${operationType}`, groupName, additionalDetails, textColorClass);
    }
    
    // Computer Operations
    if (action.includes('computer') || action === 'join_domain' || action === 'pc_reset') {
        let operationType = '';
        let textColorClass = 'text-primary';
        let sectionIcon = '<i class="fas fa-desktop me-2"></i>'; // Computer section icon
        
        if (action === 'move_computer') {
            operationType = 'Moved computer:';
        } else if (action === 'delete_computer') {
            operationType = 'Deleted computer:';
            textColorClass = 'text-danger';
        } else if (action === 'add_computer' || action === 'create_computer') {
            operationType = 'Added computer:';
            textColorClass = 'text-success';
        } else if (action === 'modify_computer' || action === 'update_computer') {
            operationType = 'Modified computer:';
        } else if (action === 'reset_computer' || action === 'pc_reset') {
            operationType = 'Reset computer:';
            textColorClass = 'text-warning';
        } else if (action === 'join_domain' || action === 'pc_join_domain') {
            operationType = 'Computer joined domain:';
            textColorClass = 'text-success';
        } else if (action === 'remove_domain' || action === 'pc_remove_domain') {
            operationType = 'Computer removed from domain:';
            textColorClass = 'text-warning';
        } else {
            // Generic computer operation
            operationType = 'Computer operation:';
        }
        
        // Extract computer name and additional details
        let computerName = '';
        let additionalDetails = '';
        
    if (log.details) {
            try {
                const detailsObj = JSON.parse(log.details);
                computerName = detailsObj.name || detailsObj.computer || detailsObj.hostname || log.details;
                
                // Collect additional details
                const detailsArray = [];
                if (detailsObj.path) detailsArray.push(`Path: ${detailsObj.path}`);
                if (detailsObj.from && detailsObj.to) {
                    detailsArray.push(`From: ${detailsObj.from} → To: ${detailsObj.to}`);
                }
                if (detailsObj.ip) detailsArray.push(`IP: ${detailsObj.ip}`);
                if (detailsObj.domain) detailsArray.push(`Domain: ${detailsObj.domain}`);
                if (detailsObj.os) detailsArray.push(`OS: ${detailsObj.os}`);
                
                additionalDetails = detailsArray.join(' | ');
            } catch (e) {
                // Not JSON, try to parse the text
                const nameMatch = log.details.match(/Computer(?:\s+name)?:\s+(.+?)(?:,|\s+|$)/i) ||
                                 log.details.match(/PC(?:\s+name)?:\s+(.+?)(?:,|\s+|$)/i) ||
                                 log.details.match(/Hostname:\s+(.+?)(?:,|\s+|$)/i);
                
                if (nameMatch && nameMatch[1]) {
                    computerName = nameMatch[1].trim();
                    
                    // Try to extract additional details
                    const ipMatch = log.details.match(/IP:\s+([0-9.]+)/i);
                    const pathMatch = log.details.match(/Path:\s+(.+?)(?:,|\s+|$)/i);
                    const domainMatch = log.details.match(/Domain:\s+(.+?)(?:,|\s+|$)/i);
                    
                    const details = [];
                    if (ipMatch && ipMatch[1]) details.push(`IP: ${ipMatch[1]}`);
                    if (pathMatch && pathMatch[1]) details.push(`Path: ${pathMatch[1]}`);
                    if (domainMatch && domainMatch[1]) details.push(`Domain: ${domainMatch[1]}`);
                    
                    if (details.length > 0) {
                        additionalDetails = details.join(' | ');
                    }
                } else {
                    // If can't extract name, use full details
                    computerName = log.details;
                }
            }
        }
        
        // Return with section icon
        return createDetailHTML(`${sectionIcon}${operationType}`, computerName, additionalDetails, textColorClass);
    }
    
    // Check for system operations (like server maintenance)
    if (action.includes('system') || action.includes('server') || action.includes('service')) {
        let operationType = 'System:';
        let textColorClass = 'text-primary';
        let sectionIcon = '<i class="fas fa-server me-2"></i>'; // System section icon
        
        if (action.includes('backup')) {
            operationType = 'System Backup:';
        } else if (action.includes('restore')) {
            operationType = 'System Restore:';
        } else if (action.includes('update')) {
            operationType = 'System Update:';
        } else if (action.includes('restart')) {
            operationType = 'System Restart:';
            textColorClass = 'text-warning';
        } else if (action.includes('maintenance')) {
            operationType = 'System Maintenance:';
        }
        
        return createDetailHTML(`${sectionIcon}${operationType}`, log.details || '', null, textColorClass);
    }
    
    // Default fallback for other actions
    let genericIcon = '<i class="fas fa-history me-2"></i>'; // Generic action icon
    return createDetailHTML(`${genericIcon}Action:`, log.details || 'No details available', null, 'text-secondary');
}

function exportToCSV() {
    const table = document.querySelector('table');
    const rows = Array.from(table.querySelectorAll('tr'));
    
    const csvContent = rows.map(row => {
        const cells = Array.from(row.querySelectorAll('th, td'));
        return cells.map(cell => {
            let text = cell.textContent.trim();
            if (text.includes(',') || text.includes('"')) {
                text = `"${text.replace(/"/g, '""')}"`;
            }
            return text;
        }).join(',');
    }).join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `activity_${formatDate(new Date())}.csv`;
    link.click();
}

function formatTimestamp(timestamp) {
    return new Date(timestamp).toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function formatDate(timestamp) {
    return new Date(timestamp).toLocaleDateString();
}

function formatTime(timestamp) {
    return new Date(timestamp).toLocaleTimeString();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    const tbody = document.getElementById('logsTable');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>${escapeHtml(message)}
                    </div>
                </td>
            </tr>
        `;
    }
}

function getActionIcon(action) {
    // Group icons by category with consistent colors
    const iconMap = {
        // Authentication - Shield family icons with standard colors
        'login': '<i class="fas fa-sign-in-alt text-success"></i>',
        'logout': '<i class="fas fa-sign-out-alt text-secondary"></i>',
        'login_failed': '<i class="fas fa-exclamation-triangle text-warning"></i>',
            
        // User Management - User family icons
        'user_create': '<i class="fas fa-user-plus text-primary"></i>',
        'user_modify': '<i class="fas fa-user-edit text-primary"></i>',
        'user_data_change': '<i class="fas fa-user-edit text-primary"></i>',
        'user_enable': '<i class="fas fa-user-check text-success"></i>',
        'enable_user': '<i class="fas fa-user-check text-success"></i>',
        'user_disable': '<i class="fas fa-user-slash text-warning"></i>',
        'disable_user': '<i class="fas fa-user-slash text-warning"></i>',
        'password_reset': '<i class="fas fa-key text-warning"></i>',
        'unlock_user': '<i class="fas fa-unlock text-success"></i>',
        'move_user': '<i class="fas fa-people-arrows text-info"></i>',
        'user_group_membership': '<i class="fas fa-users-cog text-purple"></i>',
        'delete_user': '<i class="fas fa-user-times text-danger"></i>',
            
        // OU Operations - Folder family icons
        'create_ou': '<i class="fas fa-folder-plus text-primary"></i>',
        'update_ou': '<i class="fas fa-folder-open text-primary"></i>',
        'move_ou': '<i class="fas fa-exchange-alt text-info"></i>',
        'delete_ou': '<i class="fas fa-folder-minus text-danger"></i>',
            
        // Group Operations - Users/groups family icons
        'create_group': '<i class="fas fa-users text-primary"></i>',
        'modify_group': '<i class="fas fa-user-friends text-primary"></i>',
        'change_group_type': '<i class="fas fa-object-group text-info"></i>',
        'move_group': '<i class="fas fa-arrows-alt text-info"></i>',
        'delete_group': '<i class="fas fa-trash-alt text-danger"></i>',
            
        // Computer Operations - Desktop/Laptop family icons
        'move_computer': '<i class="fas fa-arrows-alt text-info"></i>',
        'delete_computer': '<i class="fas fa-trash-alt text-danger"></i>',
        'add_computer': '<i class="fas fa-laptop-medical text-success"></i>',
        'create_computer': '<i class="fas fa-laptop-medical text-success"></i>',
        'modify_computer': '<i class="fas fa-laptop-code text-primary"></i>',
        'update_computer': '<i class="fas fa-laptop-code text-primary"></i>',
        'reset_computer': '<i class="fas fa-power-off text-warning"></i>',
        'pc_reset': '<i class="fas fa-power-off text-warning"></i>',
        'join_domain': '<i class="fas fa-plug text-success"></i>',
        'pc_join_domain': '<i class="fas fa-plug text-success"></i>',
        'remove_domain': '<i class="fas fa-unlink text-warning"></i>',
        'pc_remove_domain': '<i class="fas fa-unlink text-warning"></i>',
            
        // System Operations - Server/System family icons
        'system_backup': '<i class="fas fa-database text-primary"></i>',
        'system_restore': '<i class="fas fa-undo text-info"></i>',
        'system_update': '<i class="fas fa-sync text-primary"></i>',
        'server_restart': '<i class="fas fa-power-off text-warning"></i>',
        'system_maintenance': '<i class="fas fa-tools text-primary"></i>'
    };
    
    // Get icon from map if exists
    const icon = iconMap[action.toLowerCase()];
    if (icon) return icon;
    
    // Default icons based on action type
    if (action.toLowerCase().includes('user')) {
        return '<i class="fas fa-user text-primary"></i>';
    }
    
    if (action.toLowerCase().includes('computer') || action.toLowerCase().includes('pc_')) {
        return '<i class="fas fa-desktop text-primary"></i>';
    }
    
    if (action.toLowerCase().includes('group')) {
        return '<i class="fas fa-users text-primary"></i>';
    }
    
    if (action.toLowerCase().includes('ou')) {
        return '<i class="fas fa-folder text-primary"></i>';
    }
    
    if (action.toLowerCase().includes('system') || action.toLowerCase().includes('server')) {
        return '<i class="fas fa-server text-primary"></i>';
    }
    
    // Generic default icon
    return '<i class="fas fa-history text-secondary"></i>';
}

function formatAction(action) {
    // Convert from snake_case to readable format
    const actionMap = {
        // Authentication
        'login': 'User Login',
        'logout': 'User Logout',
        'login_failed': 'Login Failed',
        
        // User Management
        'user_create': 'User Created',
        'user_modify': 'User Modified', 
        'user_data_change': 'User Data Changed',
        'user_enable': 'User Enabled',
        'enable_user': 'User Enabled',
        'user_disable': 'User Disabled',
        'disable_user': 'User Disabled',
        'password_reset': 'Password Reset',
        'unlock_user': 'User Unlocked',
        'move_user': 'User Moved',
        'user_group_membership': 'Group Membership Changed',
        'delete_user': 'User Deleted',
        
        // OU Operations
        'create_ou': 'OU Created',
        'update_ou': 'OU Updated',
        'move_ou': 'OU Moved',
        'delete_ou': 'OU Deleted',
        
        // Group Operations
        'create_group': 'Group Created',
        'modify_group': 'Group Modified',
        'change_group_type': 'Group Type Changed',
        'move_group': 'Group Moved',
        'delete_group': 'Group Deleted',
        
        // Computer Operations
        'move_computer': 'Computer Moved',
        'delete_computer': 'Computer Deleted',
        'add_computer': 'Computer Added',
        'create_computer': 'Computer Created',
        'modify_computer': 'Computer Modified',
        'update_computer': 'Computer Updated',
        'reset_computer': 'Computer Reset',
        'pc_reset': 'Computer Reset',
        'join_domain': 'Domain Join',
        'pc_join_domain': 'Domain Join',
        'remove_domain': 'Domain Removal',
        'pc_remove_domain': 'Domain Removal',
        
        // System Operations
        'system_backup': 'System Backup',
        'system_restore': 'System Restore',
        'system_update': 'System Update',
        'server_restart': 'Server Restart',
        'system_maintenance': 'System Maintenance'
    };
    
    const result = actionMap[action.toLowerCase()];
    if (result) return result;
    
    // Handle other operations not explicitly defined
    if (action.toLowerCase().includes('computer') || action.toLowerCase().includes('pc_')) {
        return 'Computer ' + action.split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
    }
    
    if (action.toLowerCase().includes('system') || action.toLowerCase().includes('server')) {
        return 'System ' + action.split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
    }
    
    // Convert snake_case to readable format for any other action type
    return action.split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function getStatusBadgeClass(status) {
    switch(status?.toLowerCase()) {
        case 'success':
            return 'bg-success';
        case 'failed':
            return 'bg-danger';
        case 'warning':
            return 'bg-warning';
        case 'pending':
            return 'bg-info';
        default:
            // Automatic failed status for Login Failed
            return status === 'login_failed' ? 'bg-danger' : 'bg-success';
    }
}

function showLoading() {
    const tbody = document.getElementById('logsTable');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Loading activity logs...</div>
                </td>
            </tr>
        `;
    }
}

function hideLoading() {
    // Loading is automatically hidden when table is updated
}

function updatePagination(pagination) {
    const paginationElement = document.getElementById('pagination');
    if (!paginationElement) return;

    const totalPages = pagination.total_pages;
    const currentPage = pagination.current_page;
    
    let html = '';
    
    // Previous button
    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadLogs(${currentPage - 1}); return false;">
                Previous
            </a>
        </li>
    `;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += `
                <li class="page-item active">
                    <span class="page-link">${i}</span>
                </li>
            `;
        } else {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadLogs(${i}); return false;">${i}</a>
                </li>
            `;
        }
    }
    
    // Next button
    html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadLogs(${currentPage + 1}); return false;">
                Next
            </a>
        </li>
    `;
    
    paginationElement.innerHTML = html;
    
    // Update showing text
    const start = ((currentPage - 1) * pagination.per_page) + 1;
    const end = Math.min(start + pagination.per_page - 1, pagination.total_records);
    
    document.getElementById('currentRange').textContent = `${start}-${end}`;
    document.getElementById('totalLogs').textContent = pagination.total_records;
}

function updateStats(stats) {
    const elements = {
        totalActivities: stats.total || 0,
        todayActivities: stats.today || 0,
        weeklyActivities: stats.weekly || 0,
        monthlyActivities: stats.monthly || 0
    };

    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    });
}

function filterLogs() {
    loadLogs(1); // Reset to first page when filtering
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function confirmDeleteLogs() {
    Swal.fire({
        title: '<?php echo __('delete_logs_confirm_title'); ?>',
        html: `
            <div class="text-start">
                <p class="mb-2"><?php echo __('delete_logs_confirm_text'); ?></p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo __('delete_logs_backup_warning'); ?>
                </div>
                <ul class="text-muted small">
                    <li><?php echo __('delete_logs_warning_1'); ?></li>
                    <li><?php echo __('delete_logs_warning_2'); ?></li>
                    <li><?php echo __('delete_logs_warning_3'); ?></li>
                </ul>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<?php echo __('yes_delete_all'); ?>',
        cancelButtonText: '<?php echo __('cancel'); ?>',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            deleteAllLogs();
        }
    });
}

function deleteAllLogs() {
    showLoading();
    
    fetch('api/delete-logs.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '<?php echo __('logs_deleted'); ?>',
                html: `
                    <div>
                        <p><?php echo __('logs_deleted_success'); ?></p>
                        <div class="alert alert-info">
                            <i class="fas fa-file-archive me-2"></i>
                            <?php echo __('backup_saved_as'); ?>: ${data.backup_file}
                        </div>
                    </div>
                `
            });
            loadLogs(); // Reload logs
        } else {
            throw new Error(data.error || '<?php echo __('error_delete_logs'); ?>');
        }
    })
    .catch(error => {
        console.error('Error deleting logs:', error);
        Swal.fire({
            icon: 'error',
            title: '<?php echo __('error'); ?>',
            text: '<?php echo __('error_delete_logs'); ?>'
        });
    })
    .finally(hideLoading);
}

function exportLogs() {
    showLoading();
    
    fetch('api/export-logs.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.download_url) {
            Swal.fire({
                icon: 'success',
                title: '<?php echo __('logs_exported'); ?>',
                html: `
                    <div>
                        <p><?php echo __('logs_exported_success'); ?></p>
                        <div class="alert alert-info">
                            <i class="fas fa-file-archive me-2"></i>
                            <?php echo __('export_saved_as'); ?>: ${data.export_file}
                        </div>
                        <div class="mt-3">
                            <a href="${data.download_url}" class="btn btn-primary" download>
                                <i class="fas fa-download me-2"></i><?php echo __('download_csv'); ?>
                            </a>
                        </div>
                    </div>
                `
            });
        } else {
            throw new Error(data.error || '<?php echo __('error_export_logs'); ?>');
        }
    })
    .catch(error => {
        console.error('Error exporting logs:', error);
        Swal.fire({
            icon: 'error',
            title: '<?php echo __('error'); ?>',
            text: '<?php echo __('error_export_logs'); ?>'
        });
    })
    .finally(hideLoading);
} 
</script> 