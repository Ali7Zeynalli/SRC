<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
  
session_start();
require_once('includes/functions.php');

if (!isset($_SESSION['ad_username'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = __('gpo_management');
$activePage = 'gpo';

require_once('includes/header.php');
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 bg-light">
            <!-- Page Title -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 text-primary">
                    <i class="fas fa-shield-alt me-2"></i><?php echo __('gpo_management'); ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshGPOBtn">
                            <i class="fas fa-sync-alt"></i> <?php echo __('refresh'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                    <i class="fas fa-cogs text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('total_gpos'); ?></h6>
                                    <h2 class="card-title mb-0" id="totalGPOs">-</h2>
                                </div>
                            </div>
                            <p class="card-text text-muted mt-auto"><?php echo __('all_group_policy_objects'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                                    <i class="fas fa-link text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('linked_ous'); ?></h6>
                                    <h2 class="card-title mb-0" id="linkedOUs">-</h2>
                                </div>
                            </div>
                            <p class="card-text text-muted mt-auto"><?php echo __('ous_with_linked_gpos'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="gpoSearch" placeholder="<?php echo __('search_gpos'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GPO List -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white p-3">
                    <h5 class="card-title mb-0"><?php echo __('group_policy_objects'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="gpoTable">
                            <thead class="bg-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th><?php echo __('gpo_name'); ?></th>
                                    <th width="100"><?php echo __('type'); ?></th>
                                    <th width="100"><?php echo __('status'); ?></th>
                                    <th><?php echo __('linked_ous'); ?></th>
                                    <th width="120"><?php echo __('modified'); ?></th>
                                    <th width="100"><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="gpoList">
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden"><?php echo __('loading'); ?></span>
                                        </div>
                                        <p class="text-muted mt-2"><?php echo __('loading_gpo_data'); ?></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <div class="text-muted small" id="gpoResultCount"><?php echo __('showing_results', ['count' => 0]); ?></div>
                    <nav aria-label="<?php echo __('gpo_pagination'); ?>" id="gpoPagination" class="d-none">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1"><?php echo __('previous'); ?></a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#"><?php echo __('next'); ?></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- GPO Details Modal -->
<div class="modal fade" id="gpoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('gpo_details'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Will be populated by JavaScript -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted"><?php echo __('loading_gpo_details'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
<script>

document.addEventListener('DOMContentLoaded', function() {
    loadGPOs();
    
    // Search event listener
    document.getElementById('gpoSearch').addEventListener('input', debounce(filterGPOs, 300));
    
    // Button event listeners
    document.getElementById('refreshGPOBtn').addEventListener('click', function() {
        loadGPOs(true);
    });
});

let allGPOData = []; // Store all GPO data for filtering

function loadGPOs(showLoader = false) {
    console.log('<?php echo __('loading_gpos'); ?>');
    
    if (showLoader) {
        document.getElementById('gpoList').innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php echo __('loading'); ?></span>
                    </div>
                    <p class="text-muted mt-2"><?php echo __('loading_gpo_data'); ?></p>
                </td>
            </tr>
        `;
    }
    
    fetch('api/gpo.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`<?php echo __('http_error'); ?> ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('<?php echo __('gpo_data_received'); ?>:', data);
            if (data.error) {
                throw new Error(data.error);
            }
            
            allGPOData = data.gpos; // Store all data
            updateGPOTable(data.gpos);
            updateStats(data);
        })
        .catch(error => {
            console.error('<?php echo __('error_loading_gpos'); ?>:', error);
            document.getElementById('gpoList').innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                        <p><?php echo __('error_loading_gpos'); ?>: ${error.message}</p>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadGPOs(true)">
                            <i class="fas fa-sync-alt me-1"></i> <?php echo __('try_again'); ?>
                        </button>
                    </td>
                </tr>
            `;
            
            // Reset stats
            document.getElementById('totalGPOs').textContent = '0';
            document.getElementById('linkedOUs').textContent = '0';
            document.getElementById('gpoResultCount').textContent = '<?php echo __('error_loading_data'); ?>';
        });
}

function updateGPOTable(gpos) {
    const tbody = document.getElementById('gpoList');
    tbody.innerHTML = '';
    
    if (gpos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <p><?php echo __('no_gpos_found'); ?></p>
                </td>
            </tr>
        `;
        document.getElementById('gpoResultCount').textContent = '<?php echo __('no_results_found'); ?>';
        return;
    }
    
    gpos.forEach((gpo, index) => {
        const row = document.createElement('tr');
        
        // Determine status display
        const statusBadge = gpo.status && !gpo.status.enabled 
            ? '<span class="badge bg-danger"><?php echo __('disabled'); ?></span>' 
            : '<span class="badge bg-success"><?php echo __('enabled'); ?></span>';
            
        // Get linked OUs with formatted display
        const linkedOUsHTML = gpo.linkedOUs && gpo.linkedOUs.length 
            ? gpo.linkedOUs.slice(0, 3).map(ou => `
                <span class="badge rounded-pill bg-light text-dark border">
                    <i class="fas fa-sitemap text-muted me-1"></i>${escapeHtml(ou)}
                </span>
            `).join(' ') + (gpo.linkedOUs.length > 3 ? `<span class="badge rounded-pill bg-secondary ms-1">+${gpo.linkedOUs.length - 3} <?php echo __('more'); ?></span>` : '')
            : '<span class="badge bg-secondary"><?php echo __('not_linked'); ?></span>';
        
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-light p-2 me-2">
                        <i class="fas fa-cog text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-medium">${escapeHtml(gpo.name)}</div>
                        <div class="text-muted small">${escapeHtml(gpo.description || '<?php echo __('no_description'); ?>')}</div>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge rounded-pill ${getGPOTypeBadgeClass(gpo.type)}">
                    <i class="fas ${getGPOTypeIcon(gpo.type)} me-1"></i>
                    ${escapeHtml(gpo.type)}
                </span>
            </td>
            <td>
                ${statusBadge}
            </td>
            <td class="linked-ous-cell">
                <div class="linked-ous-list">
                    ${linkedOUsHTML}
                </div>
            </td>
            <td>
                <div class="small text-muted">${escapeHtml(gpo.modified || '<?php echo __('not_available'); ?>')}</div>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewGPODetails('${escapeHtml(gpo.dn)}')">
                    <i class="fas fa-info-circle"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('gpoResultCount').textContent = `<?php echo __('showing_results'); ?> ${gpos.length}`;
}

function getGPOTypeBadgeClass(type) {
    switch(type.toLowerCase()) {
        case 'computer':
            return 'bg-success bg-opacity-10 text-success';
        case 'user':
            return 'bg-info bg-opacity-10 text-info';
        default:
            return 'bg-primary bg-opacity-10 text-primary';
    }
}

function getGPOTypeIcon(type) {
    switch(type.toLowerCase()) {
        case 'computer':
            return 'fa-desktop';
        case 'user':
            return 'fa-user';
        default:
            return 'fa-cogs';
    }
}

function viewGPODetails(dn) {
    console.log('<?php echo __('opening_gpo_details'); ?>:', dn);
    
    const modal = new bootstrap.Modal(document.getElementById('gpoModal'));
    const modalBody = document.querySelector('#gpoModal .modal-body');
    
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted"><?php echo __('loading_gpo_details'); ?></p>
        </div>
    `;
    
    modal.show();

    // Use URL encoding for DN
    const encodedDN = encodeURIComponent(dn);
    console.log('<?php echo __('encoded_dn'); ?>:', encodedDN);

    fetch(`api/gpo-details.php?dn=${encodedDN}`)
        .then(async response => {
            const text = await response.text();
            console.log('<?php echo __('raw_response'); ?>:', text);
            
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('<?php echo __('json_parse_error'); ?>:', e);
                console.error('<?php echo __('response_text'); ?>:', text);
                throw new Error('<?php echo __('invalid_server_response'); ?>');
            }
        })
        .then(gpo => {
            if (!gpo || gpo.error) {
                throw new Error(gpo.error || '<?php echo __('invalid_gpo_data'); ?>');
            }
            
            console.log('<?php echo __('processed_gpo_data'); ?>:', gpo);
            document.querySelector('#gpoModal .modal-title').innerHTML = `
                <i class="fas ${getGPOTypeIcon(gpo.type)} text-primary me-2"></i>
                ${escapeHtml(gpo.name)}
            `;
            
            modalBody.innerHTML = `
                <div class="gpo-details">
                    <div class="alert alert-light border-0 rounded-0 mb-0 py-3">
                        <div class="d-flex gap-3 mb-3">
                            <div class="badge rounded-pill ${getGPOTypeBadgeClass(gpo.type)} fw-normal">
                                <i class="fas ${getGPOTypeIcon(gpo.type)} me-1"></i> ${escapeHtml(gpo.type)}
                            </div>
                            <div class="badge rounded-pill ${gpo.status && !gpo.status.enabled ? 'bg-danger' : 'bg-success'} fw-normal">
                                ${gpo.status && !gpo.status.enabled ? '<?php echo __('disabled'); ?>' : '<?php echo __('enabled'); ?>'}
                            </div>
                            ${gpo.status && gpo.status.enforced ? 
                                `<div class="badge rounded-pill bg-warning text-dark fw-normal">
                                    <i class="fas fa-lock me-1"></i> <?php echo __('enforced'); ?>
                                </div>` : ''}
                        </div>
                        <p class="small text-muted mb-0">${escapeHtml(gpo.description || '<?php echo __('no_description'); ?>')}</p>
                    </div>
                    
                    <ul class="nav nav-tabs nav-fill" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#general">
                                <i class="fas fa-info-circle me-2"></i><?php echo __('general'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#scope">
                                <i class="fas fa-sitemap me-2"></i><?php echo __('scope'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#delegation">
                                <i class="fas fa-users-cog me-2"></i><?php echo __('delegation'); ?>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active p-4" id="general">
                            ${formatGeneralInfo(gpo)}
                        </div>
                        <div class="tab-pane fade p-4" id="scope">
                            ${formatScopeInfo(gpo.scope)}
                        </div>
                        <div class="tab-pane fade p-4" id="delegation">
                            ${formatDelegationInfo(gpo.delegation)}
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('<?php echo __('error'); ?>:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger m-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${escapeHtml(error.message)}
                </div>
            `;
        });
}

function updateStats(data) {
    // Update basic stats
    document.getElementById('totalGPOs').textContent = data.stats.total || '0';
    document.getElementById('linkedOUs').textContent = data.stats.linked_ous || '0';
}

function filterGPOs() {
    const searchText = document.getElementById('gpoSearch').value.toLowerCase();
    
    // Apply text search filter to get filtered data
    const filteredGPOs = allGPOData.filter(gpo => {
        // Text search filter
        const nameMatch = gpo.name.toLowerCase().includes(searchText);
        const descMatch = gpo.description && gpo.description.toLowerCase().includes(searchText);
        return nameMatch || descMatch;
    });
    
    // Update table with filtered data
    updateGPOTable(filteredGPOs);
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatGeneralInfo(gpo) {
    return `
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo __('basic_information'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted"><?php echo __('name'); ?>:</span>
                                <span class="text-end fw-medium">${escapeHtml(gpo.name)}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted"><?php echo __('type'); ?>:</span>
                                <span class="badge rounded-pill ${getGPOTypeBadgeClass(gpo.type)}">
                                    <i class="fas ${getGPOTypeIcon(gpo.type)} me-1"></i>
                                    ${escapeHtml(gpo.type)}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted"><?php echo __('status'); ?>:</span>
                                <span class="badge ${gpo.status && !gpo.status.enabled ? 'bg-danger' : 'bg-success'}">
                                    ${gpo.status && !gpo.status.enabled ? '<?php echo __('disabled'); ?>' : '<?php echo __('enabled'); ?>'}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted"><?php echo __('created'); ?>:</span>
                                <span>${escapeHtml(gpo.created || '<?php echo __('not_available'); ?>')}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted"><?php echo __('modified'); ?>:</span>
                                <span>${escapeHtml(gpo.modified || '<?php echo __('not_available'); ?>')}</span>
                            </li>
                            <li class="list-group-item px-0 py-2">
                                <div class="text-muted mb-1"><?php echo __('storage_path'); ?>:</div>
                                <div class="small text-break bg-light p-2 rounded">
                                    ${escapeHtml(gpo.path || '<?php echo __('not_available'); ?>')}
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-code-branch me-2"></i>
                            <?php echo __('version_information'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-success bg-opacity-10 border-0">
                                    <div class="card-body p-3">
                                        <h3 class="card-title">
                                            <i class="fas fa-desktop me-2 text-success"></i>
                                            <?php echo __('computer'); ?>
                                        </h3>
                                        <h3 class="mb-0 text-success">${gpo.version && gpo.version.computer ? gpo.version.computer.number : '<?php echo __('not_available'); ?>'}</h3>
                                        <p class="text-muted small mb-0 mt-2">
                                            ${gpo.version && gpo.version.computer && gpo.version.computer.last_modified ? 
                                                `<?php echo __('last_modified'); ?>: ${gpo.version.computer.last_modified}` : 
                                                ''}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-info bg-opacity-10 border-0">
                                    <div class="card-body p-3">
                                        <h3 class="card-title">
                                            <i class="fas fa-user me-2 text-info"></i>
                                            <?php echo __('user'); ?>
                                        </h3>
                                        <h3 class="mb-0 text-info">${gpo.version && gpo.version.user ? gpo.version.user.number : '<?php echo __('not_available'); ?>'}</h3>
                                        <p class="text-muted small mb-0 mt-2">
                                            ${gpo.version && gpo.version.user && gpo.version.user.last_modified ? 
                                                `<?php echo __('last_modified'); ?>: ${gpo.version.user.last_modified}` : 
                                                ''}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function formatScopeInfo(scope) {
    if (!scope) {
        return `<div class="alert alert-info"><?php echo __('no_scope_information'); ?></div>`;
    }
    
    const linkedOUs = scope.links && scope.links.length > 0 
        ? scope.links.map(link => `
            <li class="list-group-item d-flex align-items-center px-0 py-2 border-0">
                <i class="fas ${link.enabled ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'} me-2"></i>
                <div class="ms-2">
                    <div class="fw-medium">${escapeHtml(link.ou)}</div>
                    <div class="d-flex gap-2 mt-1">
                        ${link.enabled ? 
                            '<span class="badge bg-success bg-opacity-10 text-success"><?php echo __('enabled'); ?></span>' : 
                            '<span class="badge bg-danger bg-opacity-10 text-danger"><?php echo __('disabled'); ?></span>'}
                        ${link.enforced ? 
                            '<span class="badge bg-warning bg-opacity-10 text-warning"><?php echo __('enforced'); ?></span>' : 
                            ''}
                    </div>
                </div>
            </li>
        `).join('')
        : `<li class="list-group-item px-0 py-3 text-muted text-center"><?php echo __('no_linked_ous'); ?></li>`;
        
    const securityFiltering = formatSecurityFiltering(scope.security_filtering || {});
    
    return `
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-sitemap me-2"></i>
                            <?php echo __('linked_ous'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            ${linkedOUs}
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>
                            <?php echo __('security_filtering'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        ${securityFiltering}
                    </div>
                </div>
                
                ${scope.wmi_filters && scope.wmi_filters.name ? `
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-code me-2"></i>
                            <?php echo __('wmi_filters'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3">
                                <i class="fas fa-filter text-info"></i>
                            </div>
                            <div>
                                <div class="fw-medium">${escapeHtml(scope.wmi_filters.name)}</div>
                                <div class="small text-muted">${escapeHtml(scope.wmi_filters.description || '<?php echo __('no_description'); ?>')}</div>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
        </div>
    `;
}

function formatSecurityFiltering(filtering) {
    if (filtering.error) {
        return `<div class="alert alert-warning mb-0">${filtering.error}</div>`;
    }

    return `
        <div class="security-list">
            <div class="mb-3">
                <label class="form-label text-muted"><?php echo __('applied_to'); ?>:</label>
                <div>
                    ${Object.entries(filtering.apply_to || {}).map(([key, value]) => 
                        value ? `<span class="badge rounded-pill bg-success me-2 mb-2">
                            <i class="fas fa-check-circle me-1"></i>
                            ${escapeHtml(key.replace('_', ' '))}
                        </span>` : ''
                    ).join('')}
                </div>
            </div>
            
            ${filtering.denied_to && filtering.denied_to.length ? `
                <div>
                    <label class="form-label text-muted"><?php echo __('denied_to'); ?>:</label>
                    <div>
                        ${filtering.denied_to.map(item => 
                            `<span class="badge rounded-pill bg-danger me-2 mb-2">
                                <i class="fas fa-times-circle me-1"></i>
                                ${escapeHtml(item)}
                            </span>`
                        ).join('')}
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

function formatDelegationInfo(delegation) {
    if (!delegation || !delegation.permissions) {
        return `<div class="alert alert-info"><?php echo __('no_delegation_info'); ?></div>`;
    }
    
    return `
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-users-cog me-2"></i>
                    <?php echo __('permissions'); ?>
                </h6>
            </div>
            <div class="card-body p-0">
            <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                        <tr>
                            <th><?php echo __('trustee'); ?></th>
                                <th><?php echo __('type'); ?></th>
                                <th><?php echo __('permissions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${delegation.permissions.map(perm => `
                            <tr>
                                <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas ${perm.type === 'User' ? 'fa-user' : 'fa-users'} text-muted me-2"></i>
                                            <div>${escapeHtml(perm.name)}</div>
                                        </div>
                                    </td>
                                    <td>${escapeHtml(perm.type)}</td>
                                    <td>
                                        ${perm.allowed.map(p => 
                                            `<span class="badge bg-success me-1 mb-1">${escapeHtml(p)}</span>`
                                        ).join('')}
                                        ${perm.denied.map(p => 
                                            `<span class="badge bg-danger me-1 mb-1">${escapeHtml(p)}</span>`
                                        ).join('')}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    `;
}

</script>
