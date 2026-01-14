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

$pageTitle = __('computer_management');
$activePage = 'computers';

require_once('includes/header.php');
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-desktop me-2"></i><?php echo __('computer_management'); ?></h1>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stats-icon bg-primary bg-opacity-10 rounded p-3 me-3">
                                    <i class="fas fa-desktop text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('total_computers'); ?></h6>
                                    <h3 class="card-title mb-0" id="totalComputers">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stats-icon bg-info bg-opacity-10 rounded p-3 me-3">
                                    <i class="fas fa-server text-info"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('servers'); ?></h6>
                                    <h3 class="card-title mb-0" id="serverComputers">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stats-icon bg-success bg-opacity-10 rounded p-3 me-3">
                                    <i class="fas fa-laptop text-success"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('workstations'); ?></h6>
                                    <h3 class="card-title mb-0" id="workstationComputers">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stats-icon bg-primary bg-opacity-10 rounded p-3 me-3">
                                    <i class="fab fa-windows text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('windows'); ?></h6>
                                    <h3 class="card-title mb-0" id="windowsComputers">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stats-icon bg-danger bg-opacity-10 rounded p-3 me-3">
                                    <i class="fab fa-linux text-danger"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('linux'); ?></h6>
                                    <h3 class="card-title mb-0" id="linuxComputers">-</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Computers Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo __('computers_list'); ?></h5>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="computerSearch" placeholder="<?php echo __('search_computers'); ?>">
                            <button class="btn btn-primary btn-sm" id="refreshComputers">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th><?php echo __('computer_name'); ?></th>
                                    <th><?php echo __('type'); ?></th>
                                    <th><?php echo __('os_version'); ?></th>
                                    <th><?php echo __('device_name'); ?></th>
                                    <th><?php echo __('ou_location'); ?></th>
                                    <th><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="computersTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Computer Detail Modal -->
<div class="modal fade" id="computerDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('computer_details'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4" id="computerDetailContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden"><?php echo __('loading'); ?></span>
                        </div>
                            <p class="mt-2"><?php echo __('loading_computer_details'); ?></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Move Computer Modal -->
<div class="modal fade" id="moveComputerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('move_computer_to_ou'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="moveComputerDN">
                <input type="hidden" id="moveComputerName">
                
                <div class="mb-3">
                    <p><?php echo __('moving_computer'); ?>: <strong id="moveComputerNameDisplay"></strong></p>
                    <p><?php echo __('current_location'); ?>: <span id="moveComputerCurrentOU" class="text-muted"></span></p>
                </div>
                
                <div class="mb-3">
                    <label for="ouSelect" class="form-label"><?php echo __('select_destination_ou'); ?>:</label>
                    <select class="form-select" id="ouSelect" required>
                        <option value=""><?php echo __('loading_ous'); ?>...</option>
                    </select>
                    <div class="form-text"><?php echo __('select_ou_description'); ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="moveComputerBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="moveComputerSpinner"></span>
                    <?php echo __('move_computer'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Computer Confirmation Modal -->
<div class="modal fade" id="deleteComputerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('confirm_delete'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="deleteComputerDN">
                <p><?php echo __('confirm_delete_computer'); ?> <strong id="deleteComputerName"></strong>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> <?php echo __('delete_action_warning'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                <button type="button" class="btn btn-danger" id="confirmDeleteComputerBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="deleteComputerSpinner"></span>
                    <?php echo __('delete_computer'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadComputers();
    
    const refreshBtn = document.getElementById('refreshComputers');
    const searchInput = document.getElementById('computerSearch');
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
            }
            loadComputers();
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterComputers, 300));
    }
    
    // Köçürmə modal düyməsi
    const moveComputerBtn = document.getElementById('moveComputerBtn');
    if (moveComputerBtn) {
        moveComputerBtn.addEventListener('click', moveComputer);
    }
    
    // Silmə təsdiq düyməsi
    const confirmDeleteBtn = document.getElementById('confirmDeleteComputerBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', deleteComputer);
    }
});

function loadComputers() {
    fetch('api/computers.php')
        .then(response => response.json())
        .then(data => {
            if (data && data.computers && Array.isArray(data.computers)) {
                updateComputersTable(data.computers);
                updateStats(data.stats);
            } else {
                console.error('Invalid data format:', data);
                document.getElementById('computersTable').innerHTML = '<tr><td colspan="7" class="text-center"><?php echo __('no_data_found'); ?></td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading computers:', error);
            document.getElementById('computersTable').innerHTML = '<tr><td colspan="7" class="text-center text-danger"><?php echo __('error_occurred'); ?></td></tr>';
            showErrorAlert('<?php echo __('error_loading_computers'); ?>');
        });
}

function updateComputersTable(computers) {
    const tbody = document.getElementById('computersTable');
    tbody.innerHTML = '';
    
    if (!computers || computers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center"><?php echo __('no_computers_found'); ?></td></tr>';
        return;
    }
    
    computers.forEach((computer, index) => {
        const row = document.createElement('tr');
        row.className = 'align-middle';
        row.innerHTML = `
            <td class="text-muted">${index + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="computer-icon me-2">
                        <i class="fas ${getComputerIcon(computer)} fa-lg"></i>
                    </div>
                    <div>
                        <div class="fw-medium">${escapeHtml(computer.name)}</div>
                        <small class="text-muted">${escapeHtml(computer.description || '')}</small>
                    </div>
                </div>
            </td>
            <td><span class="badge ${getTypeBadgeClass(computer.type)}">${escapeHtml(computer.type)}</span></td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="os-icon me-2">
                        <i class="${getOSIcon(computer.osType)} fa-lg"></i>
                    </div>
                    <div class="small">
                        ${escapeHtml(computer.os)}
                        <small class="text-muted">${escapeHtml(computer.osVersion)}</small>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(computer.deviceName)}</td>
            <td><small class="text-muted">${escapeHtml(computer.ou)}</small></td>
            <td>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewComputerDetails('${escapeAttr(computer.dn)}')">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showMoveComputerModal('${escapeAttr(computer.dn)}', '${escapeAttr(computer.name)}', '${escapeAttr(computer.ou)}')">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteComputer('${escapeAttr(computer.dn)}', '${escapeAttr(computer.name)}')">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getOSIcon(osType) {
    switch(osType) {
        case 'Windows':
            return 'fab fa-windows text-primary';
        case 'Linux':
            return 'fab fa-linux text-danger';
        default:
            return 'fas fa-question-circle text-secondary';
    }
}

function getComputerIcon(computer) {
    let iconClass = '';
    
    // Əvvəlcə tipi yoxla
    if (computer.type === 'Server') {
        iconClass = 'fa-server text-info';
    } else {
        // Workstation ikonu
        iconClass = 'fa-desktop text-success';
    }
    
    // Kompüter aktiv deyilsə, rəngi solğunlaşdır
    if (!computer.enabled) {
        return `${iconClass} opacity-50`;
    }
    
    return iconClass;
}

function getTypeBadgeClass(type) {
    switch(type) {
        case 'Server': 
            return 'bg-info text-dark';
        case 'Workstation': 
            return 'bg-success';
        default: 
            return 'bg-secondary';
    }
}

function updateStats(stats) {
    const defaultStats = {
        total: 0,
        servers: 0,
        workstations: 0,
        windows: 0,
        linux: 0
    };

    const finalStats = { ...defaultStats, ...(stats || {}) };

    document.getElementById('totalComputers').textContent = finalStats.total;
    document.getElementById('serverComputers').textContent = finalStats.servers;
    document.getElementById('workstationComputers').textContent = finalStats.workstations;
    document.getElementById('windowsComputers').textContent = finalStats.windows;
    document.getElementById('linuxComputers').textContent = finalStats.linux;
}

function filterComputers() {
    const searchText = document.getElementById('computerSearch').value.toLowerCase();
    const rows = document.getElementById('computersTable').getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        const computerName = row.cells[1]?.textContent.toLowerCase() || '';
        row.style.display = computerName.includes(searchText) ? '' : 'none';
    });
}

// Kompüter haqqında ətraflı məlumat
function viewComputerDetails(dn) {
    const modal = new bootstrap.Modal(document.getElementById('computerDetailModal'));
    modal.show();
    
    const contentArea = document.getElementById('computerDetailContent');
    contentArea.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"><?php echo __('loading'); ?></span>
            </div>
            <p class="mt-2"><?php echo __('loading_computer_details'); ?></p>
        </div>
    `;
    
    // Kompüter məlumatlarını API-dən əldə et
    const formData = new FormData();
    formData.append('action', 'get_computer_details');
    formData.append('dn', dn);
    
    fetch('api/computer-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const computer = data;
            
            // İşlənmiş məlumatları göstərmək üçün HTML hazırla
            contentArea.innerHTML = `
                <div class="col-md-6">
                    <h5 class="mb-3"><?php echo __('basic_information'); ?></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%"><?php echo __('computer_name'); ?></th>
                            <td>${escapeHtml(computer.name)}</td>
                        </tr>
                        <tr>
                            <th><?php echo __('description'); ?></th>
                            <td>${escapeHtml(computer.description || 'N/A')}</td>
                        </tr>
                        <tr>
                            <th><?php echo __('type'); ?></th>
                            <td><span class="badge ${getTypeBadgeClass(computer.type)}">${escapeHtml(computer.type)}</span></td>
                        </tr>
                        <tr>
                            <th><?php echo __('status'); ?></th>
                            <td>
                                <span class="badge ${computer.enabled ? 'bg-success' : 'bg-danger'}">
                                    ${computer.enabled ? 'Enabled' : 'Disabled'}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3"><?php echo __('system_information'); ?></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%"><?php echo __('operating_system'); ?></th>
                            <td>${escapeHtml(computer.os || 'Unknown')}</td>
                        </tr>
                        <tr>
                            <th><?php echo __('os_version'); ?></th>
                            <td>${escapeHtml(computer.osVersion || 'N/A')}</td>
                        </tr>
                        <tr>
                            <th><?php echo __('device_name'); ?></th>
                            <td>${escapeHtml(computer.deviceName || computer.name)}</td>
                        </tr>
                        <tr>
                            <th><?php echo __('distinguished_name'); ?></th>
                            <td><small class="text-muted">${escapeHtml(computer.dn)}</small></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 mt-3">
                    <h5 class="mb-3"><?php echo __('location'); ?></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%"><?php echo __('ou_path'); ?></th>
                            <td>${escapeHtml(computer.ou)}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 mt-3">
                    <h5 class="mb-3"><?php echo __('activity'); ?></h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%"><?php echo __('last_logon'); ?></th>
                            <td>${escapeHtml(computer.lastLogon || 'Never')}</td>
                        </tr>
                        <tr>
                            <th><?php echo __('logon_count'); ?></th>
                            <td>${escapeHtml(computer.logonCount || '0')}</td>
                        </tr>
                        <tr>
                            <th><?php echo __('created_date'); ?></th>
                            <td>${escapeHtml(computer.created || 'Unknown')}</td>
                        </tr>
                    </table>
                </div>
            `;
        } else {
            contentArea.innerHTML = `
                <div class="col-12 text-center text-danger">
                    <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                    <h5><?php echo __('error_loading_computer_details'); ?></h5>
                    <p class="text-muted"><?php echo __('unknown_error_occurred'); ?></p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error fetching computer details:', error);
        contentArea.innerHTML = `
            <div class="col-12 text-center text-danger">
                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                <h5><?php echo __('error_loading_computer_details'); ?></h5>
                <p class="text-muted"><?php echo __('server_connection_error'); ?></p>
            </div>
        `;
    });
}

// Kompüter silmə funksiyaları
function confirmDeleteComputer(dn, name) {
    const modal = new bootstrap.Modal(document.getElementById('deleteComputerModal'));
    document.getElementById('deleteComputerDN').value = dn;
    document.getElementById('deleteComputerName').textContent = name;
    modal.show();
}

function deleteComputer() {
    const dn = document.getElementById('deleteComputerDN').value;
    const spinner = document.getElementById('deleteComputerSpinner');
    const btn = document.getElementById('confirmDeleteComputerBtn');
    
    // Spinner-i göstər və düyməni deaktiv et
    spinner.classList.remove('d-none');
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'delete_computer');
    formData.append('dn', dn);
    
    fetch('api/computer-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Modalı gizlət
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteComputerModal'));
        modal.hide();
        
        // Spinner-i gizlət və düyməni aktiv et
        spinner.classList.add('d-none');
        btn.disabled = false;
        
        if (data.success) {
            // Uğurlu bildiriş göstər
            showSuccessAlert('<?php echo __('computer_deleted_success'); ?>', '<?php echo __('computer_removed_permanently'); ?>');
            // Cədvəli yenilə
            loadComputers();
        } else {
            // Xəta bildirişi göstər
            showErrorAlert('<?php echo __('error_deleting_computer'); ?>', data.error || '<?php echo __('unknown_error_occurred'); ?>');
        }
    })
    .catch(error => {
        console.error('Error deleting computer:', error);
        
        // Spinner-i gizlət və düyməni aktiv et
        spinner.classList.add('d-none');
        btn.disabled = false;
        
        // Xəta bildirişi göstər
        showErrorAlert('<?php echo __('error_deleting_computer'); ?>', '<?php echo __('server_connection_error'); ?>');
    });
}

// Kompüter köçürmə funksiyaları
function showMoveComputerModal(dn, name, currentOU) {
    // Modal məlumatlarını doldur
    document.getElementById('moveComputerDN').value = dn;
    document.getElementById('moveComputerName').value = name;
    document.getElementById('moveComputerNameDisplay').textContent = name;
    document.getElementById('moveComputerCurrentOU').textContent = currentOU;
    
    // OU-ları yüklə
    loadOUs('ouSelect');
    
    // Modalı göstər
    const modal = new bootstrap.Modal(document.getElementById('moveComputerModal'));
    modal.show();
}

function loadOUs(selectId) {
    const ouSelect = document.getElementById(selectId);
    ouSelect.innerHTML = '<option value="">Loading OUs...</option>';
    
    fetch('api/get-ous.php')
        .then(response => response.json())
        .then(data => {
            if (data && data.success && Array.isArray(data.ous)) {
                ouSelect.innerHTML = '<option value="">Select OU...</option>';
                
                if (data.ous.length === 0) {
                    console.warn('OU list is empty');
                    ouSelect.innerHTML = '<option value="">No OUs found</option>';
                    return;
                }
                
                data.ous.forEach(ou => {
                    const option = document.createElement('option');
                    option.value = ou.dn;
                    option.textContent = ou.path;
                    ouSelect.appendChild(option);
                });
            } else {
                console.error('Error loading OUs:', data);
                ouSelect.innerHTML = '<option value="">Failed to load OUs: ' + (data.error || 'Error') + '</option>';
            }
        })
        .catch(error => {
            console.error('Exception loading OUs:', error);
            ouSelect.innerHTML = '<option value="">Failed to load OUs: Server error</option>';
        });
}

function moveComputer() {
    const dn = document.getElementById('moveComputerDN').value;
    const name = document.getElementById('moveComputerName').value;
    const newOuDN = document.getElementById('ouSelect').value;
    const spinner = document.getElementById('moveComputerSpinner');
    const btn = document.getElementById('moveComputerBtn');
    
    // OU seçilməyibsə bildiriş göstər
    if (!newOuDN) {
        showErrorAlert('<?php echo __('destination_ou_required'); ?>', '<?php echo __('select_destination_ou_message'); ?>');
        return;
    }
    
    // Spinner-i göstər və düyməni deaktiv et
    spinner.classList.remove('d-none');
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'move_computer');
    formData.append('dn', dn);
    formData.append('new_ou_dn', newOuDN);
    
    fetch('api/computer-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Modalı gizlət
        const modal = bootstrap.Modal.getInstance(document.getElementById('moveComputerModal'));
        modal.hide();
        
        // Spinner-i gizlət və düyməni aktiv et
        spinner.classList.add('d-none');
        btn.disabled = false;
        
        if (data.success) {
            // Uğurlu bildiriş göstər
            showSuccessAlert('<?php echo __('computer_moved_success'); ?>', `${name} <?php echo __('computer_moved_to_ou'); ?>`);
            // Cədvəli yenilə
            loadComputers();
        } else {
            // Xəta bildirişi göstər
            showErrorAlert('<?php echo __('error_moving_computer'); ?>', data.error || '<?php echo __('unknown_error_occurred'); ?>');
        }
    })
    .catch(error => {
        console.error('Error moving computer:', error);
        
        // Spinner-i gizlət və düyməni aktiv et
        spinner.classList.add('d-none');
        btn.disabled = false;
        
        // Xəta bildirişi göstər
            showErrorAlert('<?php echo __('error_moving_computer'); ?>', '<?php echo __('server_connection_error'); ?>');
    });
}

// SweetAlert2 bildiriş funksiyaları
function showSuccessAlert(title, message) {
    Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        confirmButtonColor: '#28a745'
    });
}

function showErrorAlert(title, message) {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonColor: '#dc3545'
    });
}

function showWarningAlert(title, message) {
    Swal.fire({
        icon: 'warning',
        title: title,
        text: message,
        confirmButtonColor: '#ffc107'
    });
}

function showInfoAlert(title, message) {
    Swal.fire({
        icon: 'info',
        title: title,
        text: message,
        confirmButtonColor: '#17a2b8'
    });
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeAttr(text) {
    if (text === null || text === undefined) return '';
    return text.replace(/"/g, '&quot;').replace(/'/g, '&apos;');
}


</script>

<!-- SweetAlert2 kitabxanası -->
<script src="temp/assets/lib/SweetAlert2/sweetalert2.min.js"></script>

<!-- Əlavə CSS stillərini header-ə və ya CSS faylına əlavə edin -->
<link rel="stylesheet" href="temp/css/computer.css">
