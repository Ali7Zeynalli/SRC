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

$pageTitle = __('ou_management');
$activePage = 'ous';

require_once('includes/header.php');
?>
<link href="temp/css/ous.css" rel="stylesheet">

<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-sitemap me-2"></i><?php echo __('ou_management'); ?></h1>
                <div class="btn-toolbar">
                    <button class="btn btn-sm btn-success me-2" id="newOUBtn">
                        <i class="fas fa-plus me-1"></i><?php echo __('new_ou'); ?>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary me-2" id="toggleView">
                        <i class="fas fa-exchange-alt me-1"></i><?php echo __('toggle_view'); ?>
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Tree View -->
                <div class="col-md-4" id="ouTreeView">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><?php echo __('ou_structure'); ?></h5>
                        </div>
                        <div class="card-body">
                            <div id="ouTree" class="ou-tree"></div>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div class="col-md-8" id="ouTableView">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo __('organizational_units'); ?></h5>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control" id="ouSearch" placeholder="<?php echo __('search_ous'); ?>">
                                    <button class="btn btn-primary btn-sm" id="refreshOUs">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th><?php echo __('name'); ?></th>
                                            <th><?php echo __('type'); ?></th>
                                            <th><?php echo __('members'); ?></th>
                                            <th><?php echo __('parent_ou'); ?></th>
                                            <th><?php echo __('path'); ?></th>
                                            <th><?php echo __('created'); ?></th>
                                            <th><?php echo __('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="ou-table-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>



<?php require_once('includes/footer.php'); ?>
<script>

document.addEventListener('DOMContentLoaded', function() {
    loadOUs();
    
    document.getElementById('refreshOUs').addEventListener('click', loadOUs);
    document.getElementById('ouSearch').addEventListener('input', filterOUs);
    document.getElementById('toggleView').addEventListener('click', toggleView);
    document.getElementById('newOUBtn').addEventListener('click', createNewOU);
});

let currentView = 'combined'; // or 'tree' or 'table'

function toggleView() {
    const treeView = document.getElementById('ouTreeView');
    const tableView = document.getElementById('ouTableView');
    
    switch(currentView) {
        case 'combined':
            currentView = 'tree';
            treeView.className = 'col-md-12';
            tableView.style.display = 'none';
            break;
        case 'tree':
            currentView = 'table';
            treeView.style.display = 'none';
            tableView.className = 'col-md-12';
            tableView.style.display = 'block';
            break;
        case 'table':
            currentView = 'combined';
            treeView.className = 'col-md-4';
            treeView.style.display = 'block';
            tableView.className = 'col-md-8';
            tableView.style.display = 'block';
            break;
    }
}

// Loading state HTML-i
const loadingHTML = `
    <div class="loading-indicator">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden"><?php echo __('loading'); ?></span>
        </div>
        <p class="loading-text"><?php echo __('loading_ous'); ?></p>
    </div>
`;

// Table loading HTML-i
const tableLoadingHTML = `
    <tr>
        <td colspan="7" class="table-loading">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="loading-text"><?php echo __('loading_ous'); ?></p>
        </td>
    </tr>
`;

// Empty state HTML-i
const emptyStateHTML = `
    <div class="empty-state text-center">
        <i class="fas fa-folder-open empty-state-icon"></i>
        <p class="text-muted"><?php echo __('no_items_in_ou'); ?></p>
    </div>
`;

// Warning message HTML-i
const warningMessageHTML = `
    <div class="warning-message">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong><?php echo __('warning'); ?></strong> <?php echo __('ou_delete_warning'); ?>
        </div>
    </div>
`;

function loadOUs() {
    const treeContainer = document.getElementById('ouTree');
    const tableBody = document.getElementById('ou-table-body');
    
    if (treeContainer) {
        treeContainer.innerHTML = loadingHTML;
    }
    
    if (tableBody) {
        tableBody.innerHTML = tableLoadingHTML;
    }
    
    // Fetch OUs from API
    fetch('api/ous.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load OUs');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Store OUs in global variable for filtering
            window.allOUs = data.ous || [];
            
            // Update stats if available
            if (data.stats) {
                updateOUStats(data.stats);
            }
            
            // Update table view
            updateOUTable(window.allOUs);
            
            // Update tree view
            if (treeContainer) {
                const treeHTML = buildTreeHTML(window.allOUs);
                treeContainer.innerHTML = treeHTML;
                
                // Add event listeners to tree nodes
                document.querySelectorAll('.ou-node').forEach(node => {
                    node.addEventListener('click', function() {
                        const dn = this.getAttribute('data-dn');
                        viewOUDetails(dn);
                    });
                });
            }
        })
        .catch(error => {
            console.error('Error loading OUs:', error);
            
            // Show error message
            if (treeContainer) {
                treeContainer.innerHTML = `<div class="alert alert-danger">Failed to load OUs: ${error.message}</div>`;
            }
            
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center"><div class="alert alert-danger">Failed to load OUs: ${error.message}</div></td></tr>`;
            }
        });
}

function updateOUTable(ous) {
    const tableBody = document.getElementById('ou-table-body');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (ous.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="6" class="text-center"><?php echo __('no_items_in_ou'); ?></td>`;
        tableBody.appendChild(row);
        return;
    }
    
    ous.forEach(ou => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <span class="me-2">${getContainerIcon(ou.type, ou.isContainer)}</span>
                    <a href="#" class="ou-name" data-dn="${ou.dn}">${ou.name}</a>
                </div>
            </td>
            <td>${ou.type}</td>
            <td>${ou.memberCount}</td>
            <td>${ou.parentOU || '<?php echo __('root'); ?>'}</td>
            <td>${ou.path || 'N/A'}</td>
            <td>${ou.created}</td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary view-ou-btn" data-dn="${ou.dn}" title="<?php echo __('button_view_details'); ?>">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
    
    // Add event listeners to OU names
    document.querySelectorAll('.ou-name').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const dn = this.getAttribute('data-dn');
            viewOUDetails(dn);
        });
    });
    
    // Add event listeners to view buttons
    document.querySelectorAll('.view-ou-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const dn = this.getAttribute('data-dn');
            viewOUDetails(dn);
        });
    });
    
    // Add event listeners to edit buttons
    document.querySelectorAll('.edit-ou-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const dn = this.getAttribute('data-dn');
            editOU(dn);
        });
    });
}

function filterOUs() {
    const searchText = document.getElementById('ouSearch').value.toLowerCase();
    const rows = document.getElementById('ouTable').getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        const ouName = row.cells[1].textContent.toLowerCase();
        row.style.display = ouName.includes(searchText) ? '' : 'none';
    });
}

function renderOUTree(hierarchy) {
    const treeContainer = document.getElementById('ouTree');
    treeContainer.innerHTML = buildTreeHTML(hierarchy);
    
    // Add click handlers for expand/collapse and content loading
    document.querySelectorAll('.ou-node').forEach(node => {
        node.addEventListener('click', async (e) => {
            e.stopPropagation();
            
            // Remove active class from all nodes
            document.querySelectorAll('.ou-node').forEach(n => n.classList.remove('active'));
            node.classList.add('active');
            
            const dn = node.dataset.dn;
            const contentsDiv = node.parentElement.querySelector('.ou-contents');
            const childrenDiv = node.parentElement.querySelector('ul');
            
            // Toggle children if they exist
            if (childrenDiv) {
                const isExpanded = childrenDiv.style.display !== 'none';
                childrenDiv.style.display = isExpanded ? 'none' : 'block';
                node.querySelector('.ou-toggle').innerHTML = isExpanded ? '▸' : '▾';
            }
            
            // Load and show contents
            await loadOUContents(dn, contentsDiv);
            showOUContents(dn, node.dataset.name);
        });
    });
}

async function loadOUContents(dn, contentsDiv) {
    if (!contentsDiv) return;
    
    // Toggle visibility
    if (contentsDiv.classList.contains('d-none')) {
        contentsDiv.classList.remove('d-none');
        
        // Show loading state
        contentsDiv.innerHTML = `
            <div class="loading-indicator ps-4 py-2">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden"><?php echo __('loading'); ?></span>
                </div>
                <span class="ms-2 small"><?php echo __('loading_ou_details'); ?></span>
            </div>
        `;
        
        try {
            const response = await fetch(`api/ou-contents.php?dn=${encodeURIComponent(dn)}`);
            const data = await response.json();
            
            if (data.items.length === 0) {
                contentsDiv.innerHTML = emptyStateHTML;
            } else {
                // Group items by type
                const groupedItems = data.items.reduce((acc, item) => {
                    if (!acc[item.type]) acc[item.type] = [];
                    acc[item.type].push(item);
                    return acc;
                }, {});
                
                // Render grouped items
                contentsDiv.innerHTML = `
                    <div class="ps-4 py-2">
                        ${Object.entries(groupedItems).map(([type, items]) => `
                            <div class="mb-2">
                                <div class="text-muted small mb-1">${type}s (${items.length})</div>
                                ${items.map(item => `
                                    <div class="ou-item py-1" data-dn="${escapeHtml(item.dn)}">
                                        <i class="fas ${getItemIcon(item.type)} me-2"></i>
                                        ${escapeHtml(item.name)}
                                        <span class="badge ${getItemBadgeClass(item.type)} ms-1">
                                            ${escapeHtml(item.type)}
                                        </span>
                                    </div>
                                `).join('')}
                            </div>
                        `).join('')}
                    </div>
                `;
                
                // Add click handlers for sub-OU items
                contentsDiv.querySelectorAll('.ou-item[data-dn]').forEach(item => {
                    if (item.querySelector('.badge').textContent.trim() === 'OU') {
                        item.style.cursor = 'pointer';
                        item.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const subDn = item.dataset.dn;
                            showOUContents(subDn, item.textContent.trim());
                        });
                    }
                });
            }
        } catch (error) {
            contentsDiv.innerHTML = `
                <div class="ps-4 py-2 text-danger small">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    <?php echo __('error_loading_ous'); ?>
                </div>
            `;
        }
    } else {
        contentsDiv.classList.add('d-none');
    }
}

function buildTreeHTML(ous) {
    // First, organize OUs into a hierarchy
    const ouMap = {};
    const rootOUs = [];
    
    // Create a map of all OUs by DN
    ous.forEach(ou => {
        ouMap[ou.dn] = { ...ou, children: [] };
    });
    
    // Build the hierarchy
    ous.forEach(ou => {
        const parentDN = getParentDN(ou.dn);
        
        if (parentDN && ouMap[parentDN]) {
            ouMap[parentDN].children.push(ouMap[ou.dn]);
        } else {
            rootOUs.push(ouMap[ou.dn]);
        }
    });
    
    // Sort root OUs
    rootOUs.sort((a, b) => a.name.localeCompare(b.name));
    
    // Build the HTML
    let html = '<ul class="ou-tree">';
    rootOUs.forEach(ou => {
        html += buildOUNode(ou);
    });
    html += '</ul>';
    
    return html;
}

function buildOUNode(ou) {
    // Sort children
    if (ou.children && ou.children.length > 0) {
        ou.children.sort((a, b) => a.name.localeCompare(b.name));
    }
    
    let html = `
        <li>
            <div class="ou-node" data-dn="${ou.dn}">
                <span class="ou-icon">${getContainerIcon(ou.type, ou.isContainer)}</span>
                <span class="ou-name">${ou.name}</span>
                <span class="ou-count">(${ou.memberCount})</span>
                <div class="ou-actions">
                    <button class="btn btn-sm btn-outline-primary view-ou-btn" data-dn="${ou.dn}">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
            </div>
    `;
    
    if (ou.children && ou.children.length > 0) {
        html += '<ul>';
        ou.children.forEach(child => {
            html += buildOUNode(child);
        });
        html += '</ul>';
    }
    
    html += '</li>';
    return html;
}

function getParentDN(dn) {
    const parts = dn.split(',');
    if (parts.length <= 1) return null;
    
    return parts.slice(1).join(',');
}

function toggleOUNode(node) {
    const children = node.querySelector('ul');
    const toggle = node.querySelector('.ou-toggle');
    if (children) {
        const isExpanded = children.style.display !== 'none';
        children.style.display = isExpanded ? 'none' : 'block';
        toggle.innerHTML = isExpanded ? '▸' : '▾';
        
        // If expanding, recursively expand all child OUs that have children
        if (!isExpanded) {
            children.querySelectorAll('li').forEach(child => {
                if (child.querySelector('ul')) {
                    toggleOUNode(child);
                }
            });
        }
    }
}

function showOUContents(dn, ouName) {
    const tableView = document.getElementById('ouTableView');
    tableView.querySelector('.card-header h5').textContent = `<?php echo __('contents'); ?>: ${ouName}`;
    
    // Show loading state
    document.getElementById('ouTable').innerHTML = tableLoadingHTML;
    
    fetch(`api/ou-contents.php?dn=${encodeURIComponent(dn)}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('ouTable');
            tbody.innerHTML = '';
            
            if (data.items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-folder-open text-muted me-2"></i>
                            <?php echo __('empty_ou_message'); ?>
                        </td>
                    </tr>
                `;
                return;
            }
            
            data.items.forEach((item, index) => {
                tbody.appendChild(createOUContentRow(item, index + 1));
            });
        })
        .catch(error => {
            console.error('<?php echo __('error_loading_ous'); ?>:', error);
            document.getElementById('ouTable').innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo __('error_loading_ous'); ?>
                    </td>
                </tr>
            `;
        });
}

function createOUContentRow(item, index) {
    const icon = getItemIcon(item.type);
    const badgeClass = getItemBadgeClass(item.type);
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${index}</td>
        <td>
            <div class="d-flex align-items-center">
                <i class="fas ${icon} me-2"></i>
                ${escapeHtml(item.name)}
            </div>
        </td>
        <td>
            <span class="badge ${badgeClass}">
                ${escapeHtml(item.type)}
            </span>
        </td>
        <td>${item.memberCount || '-'}</td>
        <td>${escapeHtml(item.parentOU || '-')}</td>
        <td>${escapeHtml(item.created || '-')}</td>
    `;
    return row;
}

function getItemIcon(type) {
    switch(type.toLowerCase()) {
        case 'user':
            return 'fa-user-circle text-primary';
        case 'group':
            return 'fa-users text-success';
        case 'computer':
            return 'fa-desktop text-info';
        case 'organizational unit':
            return 'fa-sitemap text-warning';
        case 'container':
            return 'fa-folder text-secondary';
        case 'security group':
            return 'fa-shield-alt text-success';
        case 'distribution group':
            return 'fa-envelope text-info';
        case 'builtin':
            return 'fa-cog text-secondary';
        case 'printer':
            return 'fa-print text-dark';
        case 'contact':
            return 'fa-address-card text-info';
        default:
            return 'fa-folder text-secondary';
    }
}

function getItemBadgeClass(type) {
    switch(type.toLowerCase()) {
        case 'user':
            return 'bg-primary';
        case 'group':
        case 'security group':
            return 'bg-success';
        case 'computer':
            return 'bg-info';
        case 'organizational unit':
            return 'bg-warning';
        case 'container':
            return 'bg-secondary';
        case 'distribution group':
            return 'bg-info';
        case 'builtin':
            return 'bg-dark';
        case 'printer':
            return 'bg-dark';
        case 'contact':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

function viewOUDetails(dn) {
    // Show loading
    Swal.fire({
        title: '<?php echo __('loading_ou_details'); ?>',
        html: '<div class="modal-loading"><div class="spinner-border text-primary" role="status"></div></div>',
        showConfirmButton: false,
        allowOutsideClick: false
    });
    
    // Fetch OU contents
    fetch(`api/ou-contents.php?dn=${encodeURIComponent(dn)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('<?php echo __('error_loading_ous'); ?>');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Find the current OU in our data
            const currentOU = data.ou;
            const contents = data.contents || [];
            
            // Create content for the modal with tabs
            let contentHtml = `
                <div class="modal-tabs mb-3">
                    <ul class="nav nav-tabs nav-fill" id="ouDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" 
                                data-bs-target="#info-content" type="button" role="tab" aria-selected="true">
                                <i class="fas fa-info-circle me-2"></i><?php echo __('tab_information'); ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contents-tab" data-bs-toggle="tab" 
                                data-bs-target="#contents-content" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-folder-open me-2"></i><?php echo __('tab_contents'); ?> <span class="badge bg-primary ms-1">${contents.length}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="actions-tab" data-bs-toggle="tab" 
                                data-bs-target="#actions-content" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-cogs me-2"></i><?php echo __('tab_actions'); ?>
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="tab-content" id="ouDetailsTabContent">
                    <!-- Information Tab -->
                    <div class="tab-pane fade show active" id="info-content" role="tabpanel" aria-labelledby="info-tab">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="ou-icon-large me-3">
                                        ${currentOU.isContainer ? 
                                            '<i class="fas fa-folder text-warning"></i>' : 
                                            '<i class="fas fa-sitemap text-primary"></i>'}
                                    </div>
                                    <div>
                                        <h4 class="mb-1">${currentOU.name}</h4>
                                        <p class="text-muted mb-0">${currentOU.type}</p>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-group">
                                            <label class="text-muted small"><?php echo __('label_description'); ?></label>
                                            <p class="mb-0">${currentOU.description || '<span class="text-muted fst-italic"><?php echo __('no_description'); ?></span>'}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-group">
                                            <label class="text-muted small"><?php echo __('label_created'); ?></label>
                                            <p class="mb-0">${currentOU.created}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-group">
                                            <label class="text-muted small"><?php echo __('label_parent_ou'); ?></label>
                                            <p class="mb-0">${currentOU.parentOU || '<?php echo __('root'); ?>'}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-group">
                                            <label class="text-muted small"><?php echo __('label_member_count'); ?></label>
                                            <p class="mb-0">${currentOU.memberCount} <?php echo __('objects'); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-group">
                                            <label class="text-muted small"><?php echo __('label_path'); ?></label>
                                            <p class="mb-0">${currentOU.path || 'N/A'}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-group">
                                            <label class="text-muted small"><?php echo __('label_distinguished_name'); ?></label>
                                            <div class="bg-light p-2 rounded small text-break">
                                                <code>${currentOU.dn}</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contents Tab -->
                    <div class="tab-pane fade" id="contents-content" role="tabpanel" aria-labelledby="contents-tab">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-0">
                                ${contents.length > 0 ? `
                                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                        <div class="d-flex align-items-center">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-0">
                                                    <i class="fas fa-search text-muted"></i>
                                                </span>
                                                <input type="text" class="form-control border-0 bg-light" 
                                                    id="ouContentsSearch" placeholder="<?php echo __('search_contents_placeholder'); ?>">
                                            </div>
                                        </div>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-secondary" id="refreshContents">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" id="ouContentsTable">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="border-0"><?php echo __('table_name'); ?></th>
                                                    <th class="border-0"><?php echo __('table_type'); ?></th>
                                                    <th class="border-0"><?php echo __('table_description'); ?></th>
                                                    <th class="border-0"><?php echo __('table_created'); ?></th>
                                                    <th class="border-0 text-end"><?php echo __('table_actions'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${contents.map(item => {
                                                    let icon = '';
                                                    let badgeClass = '';
                                                    
                                                    if (item.type === 'user') {
                                                        icon = '<i class="fas fa-user text-primary"></i>';
                                                        badgeClass = 'bg-primary';
                                                    } else if (item.type === 'group') {
                                                        icon = '<i class="fas fa-users text-success"></i>';
                                                        badgeClass = 'bg-success';
                                                    } else if (item.type === 'computer') {
                                                        icon = '<i class="fas fa-desktop text-info"></i>';
                                                        badgeClass = 'bg-info';
                                                    } else {
                                                        icon = '<i class="fas fa-cube text-secondary"></i>';
                                                        badgeClass = 'bg-secondary';
                                                    }
                                                    
                                                    return `
                                                        <tr class="ou-content-item" data-type="${item.type}" data-name="${item.name.toLowerCase()}">
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="icon-container me-2">
                                                                        ${icon}
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-medium">${item.name}</div>
                                                                        ${item.memberCount ? `<small class="text-muted"><?php echo __('members_count'); ?> ${item.memberCount}</small>` : ''}
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="badge ${badgeClass}">${item.type.charAt(0).toUpperCase() + item.type.slice(1)}</span>
                                                            </td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 200px;">
                                                                    ${item.description || '<span class="text-muted fst-italic"><?php echo __('no_description'); ?></span>'}
                                                                </div>
                                                            </td>
                                                            <td>${item.created}</td>
                                                            <td class="text-end">
                                                                <div class="btn-group">
                                                                    <button class="btn btn-sm btn-outline-primary view-item-btn" 
                                                                            data-type="${item.type}" 
                                                                            data-dn="${item.dn}"
                                                                            title="<?php echo __('button_view_details'); ?>">
                                                                        <i class="fas fa-info-circle"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    `;
                                                }).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                ` : `
                                    <div class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                                            <h5><?php echo __('empty_ou_message'); ?></h5>
                                            <p class="text-muted"><?php echo __('empty_ou_description'); ?></p>
                                        </div>
                                    </div>
                                `}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions Tab -->
                    <div class="tab-pane fade" id="actions-content" role="tabpanel" aria-labelledby="actions-tab">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row g-4">
                                    <!-- Edit Properties Card -->
                                    <div class="col-md-4">
                                        <div class="card action-card h-100 border-0 shadow-sm">
                                            <div class="card-body text-center p-4">
                                                <div class="icon-container mx-auto mb-3" style="width: 60px; height: 60px; background-color: rgba(13, 110, 253, 0.1); border-radius: 50%;">
                                                    <i class="fas fa-edit text-primary" style="font-size: 1.8rem; line-height: 60px;"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo __('edit'); ?></h5>
                                                <p class="card-text text-muted mb-4"><?php echo __('edit_ou_desc'); ?></p>
                                                <button class="btn btn-primary w-100" id="editOUBtn" data-dn="${currentOU.dn}">
                                                    <i class="fas fa-pencil-alt me-2"></i> <?php echo __('button_edit_properties'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Move OU Card -->
                                    <div class="col-md-4">
                                        <div class="card action-card h-100 border-0 shadow-sm">
                                            <div class="card-body text-center p-4">
                                                <div class="icon-container mx-auto mb-3" style="width: 60px; height: 60px; background-color: rgba(255, 193, 7, 0.1); border-radius: 50%;">
                                                    <i class="fas fa-arrows-alt text-warning" style="font-size: 1.8rem; line-height: 60px;"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo __('move'); ?></h5>
                                                <p class="card-text text-muted mb-4"><?php echo __('move_ou_desc'); ?></p>
                                                <button class="btn btn-outline-warning w-100" id="moveOUBtn" data-dn="${currentOU.dn}">
                                                    <i class="fas fa-exchange-alt me-2"></i> <?php echo __('button_move_ou'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete OU Card -->
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="text-center mb-4">
                                                    <div class="mb-3">
                                                        <i class="fas fa-trash-alt text-danger" style="font-size: 1.8rem; line-height: 60px;"></i>
                                                    </div>
                                                    <h5 class="card-title"><?php echo __('delete'); ?></h5>
                                                    <p class="card-text text-muted mb-4"><?php echo __('delete_ou_desc'); ?></p>
                                                    <button class="btn btn-danger w-100" id="deleteOUBtn" data-dn="${currentOU.dn}">
                                                        <i class="fas fa-trash-alt me-2"></i> <?php echo __('delete_ou'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Warning Message -->
                                    <div class="col-12 mt-3">
                                        <div class="alert alert-warning border-0 d-flex align-items-center">
                                            <i class="fas fa-exclamation-triangle text-warning me-3" style="font-size: 1.5rem;"></i>
                                            <div>
                                                <strong><?php echo __('warning'); ?></strong> <?php echo __('ou_delete_warning'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Show the modal with OU details
            Swal.fire({
                title: `<div class="d-flex align-items-center">
                            ${currentOU.isContainer ? 
                                '<i class="fas fa-folder text-warning me-3"></i>' : 
                                '<i class="fas fa-sitemap text-primary me-3"></i>'}
                            <span>${currentOU.name}</span>
                        </div>`,
                html: contentHtml,
                width: '900px',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    container: 'ou-details-modal',
                    popup: 'swal-wide',
                    title: 'text-start'
                }
            });
            
            // Initialize search functionality for contents
            const searchInput = document.getElementById('ouContentsSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    document.querySelectorAll('.ou-content-item').forEach(item => {
                        const name = item.getAttribute('data-name');
                        const type = item.getAttribute('data-type');
                        const visible = name.includes(searchTerm) || type.includes(searchTerm);
                        item.style.display = visible ? '' : 'none';
                    });
                });
            }
            
            // Add event listeners to view buttons
            document.querySelectorAll('.view-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const type = this.getAttribute('data-type');
                    const dn = this.getAttribute('data-dn');
                    
                    // Close the current modal
                    Swal.close();
                    
                    // View the item based on its type
                    if (type === 'user') {
                        viewUserDetails(dn);
                    } else if (type === 'group') {
                        viewGroupDetails(dn);
                    } else if (type === 'computer') {
                        viewComputerDetails(dn);
                    }
                });
            });
            
            // Add event listeners for action buttons
            document.getElementById('editOUBtn')?.addEventListener('click', function() {
                editOU(this.getAttribute('data-dn'));
            });
            
            document.getElementById('deleteOUBtn')?.addEventListener('click', function() {
                deleteOU(this.getAttribute('data-dn'), currentOU.name);
            });
            
            document.getElementById('moveOUBtn')?.addEventListener('click', function() {
                moveOU(this.getAttribute('data-dn'), currentOU.name);
            });
            
            document.getElementById('createNewObject')?.addEventListener('click', function() {
                Swal.close();
                showCreateObjectDialog(currentOU.dn);
            });
            
            document.getElementById('refreshContents')?.addEventListener('click', function() {
                Swal.close();
                viewOUDetails(currentOU.dn);
            });
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: '<?php echo __('error'); ?>',
                text: error.message || '<?php echo __('error_loading_ous'); ?>'
            });
        });
}

function editOU(dn) {
    // Show loading
    Swal.fire({
        title: '<?php echo __('loading_ou_details'); ?>',
        html: '<div class="modal-loading"><div class="spinner-border text-primary" role="status"></div></div>',
        showConfirmButton: false,
        allowOutsideClick: false
    });
    
    // Fetch current OU details first
    fetch(`api/ou-contents.php?dn=${encodeURIComponent(dn)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            const currentOU = data.ou;
            
            Swal.fire({
                title: '<?php echo __('edit_ou_title'); ?>',
                html: `
                    <form id="editOUForm" class="text-start">
                    <div class="mb-3">
                            <label for="ouName" class="form-label"><?php echo __('ou_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ouName" value="${currentOU.name}" required>
                    </div>
                    <div class="mb-3">
                        <label for="ouDescription" class="form-label"><?php echo __('description'); ?></label>
                            <textarea class="form-control" id="ouDescription" rows="2">${currentOU.description || ''}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('distinguished_name'); ?></label>
                            <div class="form-control bg-light text-muted" style="height: auto;">
                                <small>${currentOU.dn}</small>
                            </div>
                    </div>
                </form>
                `,
                showCancelButton: true,
                confirmButtonText: '<?php echo __('save_changes'); ?>',
                cancelButtonText: '<?php echo __('cancel'); ?>',
                focusConfirm: false,
                preConfirm: () => {
                    const ouName = document.getElementById('ouName').value;
                    const ouDescription = document.getElementById('ouDescription').value;
                    
                    if (!ouName) {
                        Swal.showValidationMessage('<?php echo __('ou_name_required'); ?>');
                        return false;
                    }
                    
                    return { ouName, ouDescription };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: '<?php echo __('saving_changes'); ?>',
                        html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden"><?php echo __('loading'); ?></span></div>',
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });
                    
                    const formData = new FormData();
                    formData.append('action', 'update');
                    formData.append('dn', dn);
                    formData.append('name', result.value.ouName);
                    formData.append('description', result.value.ouDescription);
                    
                    fetch('api/ou-action.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                throw new Error(text || '<?php echo __('server_error'); ?>');
                            }
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '<?php echo __('ou_updated'); ?>',
                                text: data.message || `<?php echo __('organizational_unit'); ?> "${result.value.ouName}" <?php echo __('updated_successfully'); ?>.`,
                                confirmButtonText: '<?php echo __('ok'); ?>'
                            }).then(() => {
                                loadOUs();
                            });
                        } else {
                            throw new Error(data.error || '<?php echo __('error_updating_ou'); ?>');
                        }
                    })
                    .catch(error => {
                        console.error('Error updating OU:', error);
                        Swal.fire({
                            icon: 'error',
                            title: '<?php echo __('error'); ?>',
                            text: error.message && error.message.includes('<br') ? 
                                '<?php echo __('server_error'); ?>' : 
                                (error.message || '<?php echo __('error_updating_ou'); ?>')
                        });
                    });
                }
            });
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: '<?php echo __('error'); ?>',
                text: error.message || '<?php echo __('error_loading_ous'); ?>'
            });
        });
}

function deleteOU(dn, name) {
    Swal.fire({
        title: '<?php echo __('delete_ou'); ?>?',
        html: `<?php echo __('delete_ou_desc'); ?> <strong>${name}</strong>?<br><br>
               <div class="alert alert-danger">
                   <i class="fas fa-exclamation-triangle me-2"></i>
                   <?php echo __('ou_delete_warning'); ?>
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<?php echo __('yes_delete'); ?>',
        cancelButtonText: '<?php echo __('no_cancel'); ?>',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`api/ou-action.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&dn=${encodeURIComponent(dn)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || '<?php echo __('error'); ?>');
                }
                return data;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: '<?php echo __('ou_deleted'); ?>!',
                text: `<?php echo __('ou_deleted'); ?> "${name}"`,
                confirmButtonText: 'OK'
            }).then(() => {
                loadOUs();
            });
        }
    }).catch(error => {
        Swal.fire({
            icon: 'error',
            title: '<?php echo __('error'); ?>',
            text: error.message || '<?php echo __('error'); ?>'
        });
    });
}

function moveOU(dn, name) {
    Swal.fire({
        title: '<?php echo __('loading'); ?>...',
        html: '<div class="modal-loading"><div class="spinner-border text-primary" role="status"></div></div>',
        showConfirmButton: false,
        allowOutsideClick: false
    });
    
    // First, fetch all OUs to populate the destination dropdown
    fetch('api/ous.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            const ous = data.ous || [];
            
            // Filter out the current OU and its children to prevent circular references
            const filteredOUs = ous.filter(ou => !ou.dn.includes(dn) && ou.dn !== dn);
            
            Swal.fire({
                title: '<?php echo __('move_ou'); ?>',
                html: `
                    <form id="moveOUForm" class="text-start">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('ou_to_move'); ?></label>
                            <div class="form-control bg-light text-muted" style="height: auto;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-sitemap text-primary me-2"></i>
                                    <strong>${name}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="destinationOU" class="form-label"><?php echo __('destination'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="destinationOU" required>
                                <option value="root"><?php echo __('root'); ?></option>
                                ${filteredOUs.map(ou => `<option value="${ou.dn}">${ou.name}${ou.path ? ` (${ou.path})` : ''}</option>`).join('')}
                            </select>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo __('moving_ou_warning'); ?>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '<?php echo __('move'); ?>',
                cancelButtonText: '<?php echo __('cancel'); ?>',
                focusConfirm: false,
                preConfirm: () => {
                    const destinationOU = document.getElementById('destinationOU').value;
                    
                    if (!destinationOU) {
                        Swal.showValidationMessage('<?php echo __('destination_required'); ?>');
                        return false;
                    }
                    
                    return { destinationOU };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: '<?php echo __('moving_ou'); ?>',
                        html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden"><?php echo __('loading'); ?></span></div>',
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });
                    
                    const formData = new FormData();
                    formData.append('action', 'move');
                    formData.append('dn', dn);
                    formData.append('destination', result.value.destinationOU);
                    
                    fetch('api/ou-action.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '<?php echo __('ou_moved'); ?>',
                                text: data.message || `<?php echo __('organizational_unit'); ?> "${name}" <?php echo __('moved_successfully'); ?>.`,
                                confirmButtonText: '<?php echo __('ok'); ?>'
                            }).then(() => {
                                loadOUs();
                            });
                        } else {
                            throw new Error(data.error || '<?php echo __('error_moving_ou'); ?>');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: '<?php echo __('error'); ?>',
                            text: error.message || '<?php echo __('error_moving_ou'); ?>'
                        });
                    });
                }
            });
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: '<?php echo __('error'); ?>',
                text: error.message || '<?php echo __('error_loading_ous'); ?>'
            });
        });
}

function manageOUSecurity(dn, name) {
    // Implementation for managing OU security
    console.log("Manage OU Security:", dn);
    alert("Security management functionality will be implemented soon");
}

function manageGPOLinks(dn, name) {
    // Implementation for managing GPO links
    console.log("Manage GPO Links:", dn);
    alert("GPO links management functionality will be implemented soon");
}

function createNewObject(type, parentDN) {
    // Implementation for creating new objects
    console.log("Create new", type, "in", parentDN);
    alert(`Create new ${type} functionality will be implemented soon`);
}

function showCreateObjectDialog(parentDN) {
    Swal.fire({
        title: '<?php echo __('create_new_object'); ?>',
        html: `
            <div class="mb-3">
                <label class="form-label"><?php echo __('object_type'); ?></label>
                <select class="form-select" id="objectTypeSelect">
                    <option value="user"><?php echo __('user'); ?></option>
                    <option value="group"><?php echo __('group'); ?></option>
                    <option value="computer"><?php echo __('computer'); ?></option>
                    <option value="ou"><?php echo __('organizational_unit'); ?></option>
                </select>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<?php echo __('continue'); ?>',
        cancelButtonText: '<?php echo __('cancel'); ?>',
        focusConfirm: false,
        preConfirm: () => {
            const objectType = document.getElementById('objectTypeSelect').value;
            return { objectType };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            createNewObject(result.value.objectType, parentDN);
        }
    });
}

// Helper functions for viewing other object types
function viewUserDetails(dn) {
    Swal.fire({
        title: '<?php echo __('loading_user_details'); ?>',
        html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden"><?php echo __('loading'); ?></span></div>',
        showConfirmButton: false,
        allowOutsideClick: false,
        timer: 1000,
        willClose: () => {
            window.location.href = `users.php?action=view&dn=${encodeURIComponent(dn)}`;
        }
    });
}

function viewGroupDetails(dn) {
    Swal.fire({
        title: '<?php echo __('loading_group_details'); ?>',
        html: '<div class="spinner-border text-success" role="status"><span class="visually-hidden"><?php echo __('loading'); ?></span></div>',
        showConfirmButton: false,
        allowOutsideClick: false,
        timer: 1000,
        willClose: () => {
            window.location.href = `groups.php?action=view&dn=${encodeURIComponent(dn)}`;
        }
    });
}

function viewComputerDetails(dn) {
    Swal.fire({
        title: '<?php echo __('loading_computer_details'); ?>',
        html: '<div class="spinner-border text-info" role="status"><span class="visually-hidden"><?php echo __('loading'); ?></span></div>',
        showConfirmButton: false,
        allowOutsideClick: false,
        timer: 1000,
        willClose: () => {
            window.location.href = `computers.php?action=view&dn=${encodeURIComponent(dn)}`;
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getContainerIcon(type, isContainer) {
    if (type === 'Organizational Unit') {
        return '<i class="fas fa-sitemap text-primary"></i>';
    } else if (isContainer) {
        if (type.includes('User') || type.includes('Users')) {
            return '<i class="fas fa-users text-info"></i>';
        } else if (type.includes('Computer') || type.includes('Computers')) {
            return '<i class="fas fa-desktop text-secondary"></i>';
        } else {
            return '<i class="fas fa-folder text-warning"></i>';
        }
    } else {
        return '<i class="fas fa-folder text-warning"></i>';
    }
}

function updateOUStats(stats) {
    // Update OU statistics if elements exist
    const totalOUsElement = document.getElementById('totalOUs');
    const ouTypesElement = document.getElementById('ouTypes');
    
    if (totalOUsElement) {
        totalOUsElement.textContent = stats.total || 0;
    }
    
    if (ouTypesElement && stats.types) {
        let typesHTML = '';
        for (const type in stats.types) {
            typesHTML += `
                <div class="col-6 mb-2">
                    <div class="d-flex justify-content-between">
                        <span>${type}:</span>
                        <span class="fw-bold">${stats.types[type]}</span>
                    </div>
                </div>
            `;
        }
        ouTypesElement.innerHTML = typesHTML;
    }
}

// Function to create a new OU
function createNewOU() {
    Swal.fire({
        title: '<?php echo __('create_new_ou'); ?>',
        html: `
            <form id="createOUForm" class="text-start">
                <div class="mb-3">
                    <label for="ouName" class="form-label"><?php echo __('ou_name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ouName" required>
                </div>
                <div class="mb-3">
                    <label for="ouDescription" class="form-label"><?php echo __('description'); ?></label>
                    <textarea class="form-control" id="ouDescription" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label for="parentOU" class="form-label"><?php echo __('parent_ou'); ?></label>
                    <select class="form-select" id="parentOU">
                        <option value="root"><?php echo __('root'); ?></option>
                    </select>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: '<?php echo __('create'); ?>',
        cancelButtonText: '<?php echo __('cancel'); ?>',
        focusConfirm: false,
        didOpen: () => {
            const parentSelect = document.getElementById('parentOU');
            parentSelect.innerHTML = '<option value=""><?php echo __('loading'); ?>...</option>';
            parentSelect.disabled = true;
            
            fetch('api/ous.php')
                .then(response => response.json())
                .then(data => {
                    parentSelect.innerHTML = `<option value="root"><?php echo __('root'); ?></option>`;
                    
                    if (data.ous && data.ous.length > 0) {
                        data.ous.forEach(ou => {
                            const option = document.createElement('option');
                            option.value = ou.dn;
                            option.textContent = ou.name + (ou.path ? ` (${ou.path})` : '');
                            parentSelect.appendChild(option);
                        });
                    }
                    
                    parentSelect.disabled = false;
                })
                .catch(error => {
                    console.error('<?php echo __('error_loading_ous'); ?>:', error);
                    parentSelect.innerHTML = `<option value="root"><?php echo __('root'); ?></option>`;
                    parentSelect.disabled = false;
                });
        },
        preConfirm: () => {
            const ouName = document.getElementById('ouName').value;
            const ouDescription = document.getElementById('ouDescription').value;
            const parentOU = document.getElementById('parentOU').value;
            
            if (!ouName) {
                Swal.showValidationMessage('<?php echo __('ou_name_required'); ?>');
                return false;
            }
            
            return { ouName, ouDescription, parentOU };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: '<?php echo __('creating_ou'); ?>...',
                html: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden"><?php echo __('loading'); ?></span></div>',
                showConfirmButton: false,
                allowOutsideClick: false
            });
            
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('name', result.value.ouName);
            formData.append('description', result.value.ouDescription);
            formData.append('parent', result.value.parentOU);
            
            fetch('api/ou-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '<?php echo __('ou_created'); ?>',
                        text: data.message || `<?php echo __('ou_created_success'); ?> "${result.value.ouName}"`,
                        confirmButtonText: '<?php echo __('ok'); ?>'
                    }).then(() => {
                        loadOUs();
                    });
                } else {
                    throw new Error(data.error || '<?php echo __('error_creating_ou'); ?>');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo __('error'); ?>',
                    text: error.message || '<?php echo __('error_creating_ou'); ?>'
                });
            });
        }
    });
}


</script>