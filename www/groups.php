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

$pageTitle = __('group_management');
$activePage = 'groups';

require_once('includes/header.php');
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>
        <link rel="stylesheet" href="temp/css/groups.css">

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-users-cog me-2"></i><?php echo __('group_management'); ?></h1>
                <div class="btn-toolbar">
                    <button id="createGroupBtn" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> <?php echo __('create_group'); ?>
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-primary bg-opacity-10 rounded p-3 me-3">
                                        <i class="fas fa-layer-group text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1"><?php echo __('total_groups'); ?></h6>
                                        <h3 class="card-title mb-0" id="totalGroups">-</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-info bg-opacity-10 rounded p-3 me-3">
                                        <i class="fas fa-shield-alt text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1"><?php echo __('security_groups'); ?></h6>
                                        <h3 class="card-title mb-0" id="securityGroups">-</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-success bg-opacity-10 rounded p-3 me-3">
                                        <i class="fas fa-share-alt text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1"><?php echo __('distribution_groups'); ?></h6>
                                        <h3 class="card-title mb-0" id="distributionGroups">-</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Groups Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo __('groups_list'); ?></h5>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" id="groupSearch" placeholder="<?php echo __('search_groups'); ?>">
                            <button class="btn btn-primary btn-sm" id="refreshGroups">
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
                                    <th><?php echo __('group_name'); ?></th>
                                    <th><?php echo __('type'); ?></th>
                                    <th><?php echo __('members'); ?></th>
                                    <th><?php echo __('ou'); ?></th>
                                    <th><?php echo __('created'); ?></th>
                                    <th><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="groupsTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Group Details Modal -->
<div class="modal fade" id="groupDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('group_details'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createGroupForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i><?php echo __('create_new_group'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="groupName" class="form-label required"><?php echo __('group_name'); ?></label>
                        <input type="text" class="form-control" id="groupName" required>
                        <small class="form-text text-muted"><?php echo __('enter_unique_group_name'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label"><?php echo __('description'); ?></label>
                        <textarea class="form-control" id="groupDescription" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupEmail" class="form-label"><?php echo __('email_address'); ?></label>
                        <input type="email" class="form-control" id="groupEmail">
                        <small class="form-text text-muted"><?php echo __('optional_group_email'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupNotes" class="form-label"><?php echo __('notes'); ?></label>
                        <textarea class="form-control" id="groupNotes" rows="2"></textarea>
                        <small class="form-text text-muted"><?php echo __('additional_group_notes'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupType" class="form-label required"><?php echo __('group_type'); ?></label>
                        <select class="form-select" id="groupType" required>
                            <option value="Security"><?php echo __('security'); ?></option>
                            <option value="Distribution"><?php echo __('distribution'); ?></option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo __('group_type_description'); ?>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupScope" class="form-label required"><?php echo __('group_scope'); ?></label>
                        <select class="form-select" id="groupScope" required>
                            <option value="Global"><?php echo __('global'); ?></option>
                            <option value="DomainLocal"><?php echo __('domain_local'); ?></option>
                            <option value="Universal"><?php echo __('universal'); ?></option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo __('group_scope_description'); ?>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="groupOU" class="form-label required"><?php echo __('organizational_unit'); ?></label>
                        <select class="form-select" id="groupOU" required>
                            <option value=""><?php echo __('select_ou'); ?></option>
                        </select>
                        <small class="form-text text-muted"><?php echo __('select_ou_description'); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary" id="submitGroupBtn" disabled><?php echo __('create_group'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Group Modal -->
<div class="modal fade" id="editGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editGroupForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i><?php echo __('edit_group'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editGroupDN">
                    
                    <div class="mb-3">
                        <label for="editGroupName" class="form-label"><?php echo __('group_name'); ?></label>
                        <input type="text" class="form-control" id="editGroupName">
                        <small class="form-text text-muted"><?php echo __('enter_new_group_name'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editGroupDescription" class="form-label"><?php echo __('description'); ?></label>
                        <textarea class="form-control" id="editGroupDescription" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editGroupEmail" class="form-label"><?php echo __('email_address'); ?></label>
                        <input type="email" class="form-control" id="editGroupEmail">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editGroupNotes" class="form-label"><?php echo __('notes'); ?></label>
                        <textarea class="form-control" id="editGroupNotes" rows="2"></textarea>
                        <small class="form-text text-muted"><?php echo __('additional_group_notes'); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-success"><?php echo __('save_changes'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Move Group to OU Modal -->
<div class="modal fade" id="moveGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="moveGroupForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i><?php echo __('move_group'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="moveGroupDN">
                    
                    <div class="mb-3">
                        <label for="moveGroupName" class="form-label"><?php echo __('group_name'); ?></label>
                        <input type="text" class="form-control" id="moveGroupName" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="currentGroupOU" class="form-label"><?php echo __('current_ou'); ?></label>
                        <input type="text" class="form-control" id="currentGroupOU" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moveGroupOU" class="form-label required"><?php echo __('new_organizational_unit'); ?></label>
                        <select class="form-select" id="moveGroupOU" required>
                            <option value="">   <?php echo __('select_ou'); ?></option>
                        </select>
                        <small class="form-text text-muted"><?php echo __('select_ou_description'); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-exchange-alt me-1"></i><?php echo __('move_group'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Members to Group Modal -->
<div class="modal fade" id="addMembersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addMembersForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i><?php echo __('add_members'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="targetGroupDN">
                    
                    <div class="mb-3">
                        <label for="targetGroupName" class="form-label"><?php echo __('group_name'); ?></label>
                        <input type="text" class="form-control" id="targetGroupName" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="userSearch" class="form-label"><?php echo __('search_users'); ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="userSearch" placeholder="<?php echo __('search_by_name_username_email'); ?>">
                            <button class="btn btn-outline-secondary" type="button" id="searchUsersBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted"><?php echo __('type_at_least_2_characters_to_search_for_users'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('available_users'); ?></label>
                        <div class="users-container border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                            <div id="availableUsers" class="list-group">
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-search me-2"></i><?php echo __('search_for_users_to_add'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('selected_users'); ?></label>
                        <div class="users-container border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                            <div id="selectedUsers" class="list-group">
                                <div class="text-center text-muted py-3 no-selected-users">
                                    <i class="fas fa-users-slash me-2"></i><?php echo __('no_users_selected'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-success" id="addMembersBtn" disabled>
                        <i class="fas fa-user-plus me-1"></i><?php echo __('add_members'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
<script src="temp/assets/lib/SweetAlert2/sweetalert2.min.js"></script>
<script>

document.addEventListener('DOMContentLoaded', function() {
    loadGroups();
    
    // Add event listeners
    const refreshBtn = document.getElementById('refreshGroups');
    const searchInput = document.getElementById('groupSearch');
    const createGroupBtn = document.getElementById('createGroupBtn');
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            // Clear search input
            if (searchInput) {
                searchInput.value = '';
            }
            // Reload groups
            loadGroups();
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterGroups, 300));
    }
    
    if (createGroupBtn) {
        createGroupBtn.addEventListener('click', showCreateGroupModal);
    }
    
    // Event listener for OU select change
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'groupOU') {
            validateGroupForm();
        }
    });
    
    // Event listener for group form validation
    document.addEventListener('input', function(e) {
        if (e.target && (e.target.id === 'groupName' || e.target.id === 'groupType')) {
            validateGroupForm();
        }
    });
});

function loadGroups() {
    fetch('api/groups.php')
        .then(response => response.json())
        .then(data => {
            updateGroupsTable(data.groups);
            updateStats(data.stats);
        })
        .catch(error => console.error('Error loading groups:', error));
}

function updateGroupsTable(groups) {
    const tbody = document.getElementById('groupsTable');
    tbody.innerHTML = '';
    
    if (!groups || groups.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No groups found</td></tr>';
        return;
    }
    
    groups.forEach((group, index) => {
        const row = document.createElement('tr');
        row.className = 'align-middle';
        row.innerHTML = `
            <td class="text-muted">${index + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="group-icon me-2">
                        <i class="${getGroupIcon(group.type)} fa-lg"></i>
                    </div>
                    <div>
                        <div class="fw-medium">${escapeHtml(group.name)}</div>
                        <small class="text-muted">${escapeHtml(group.description || '')}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge ${getGroupTypeBadgeClass(group.type)}">${escapeHtml(group.type)}</span>
                <span class="badge badge-group-default">${escapeHtml(group.scope || 'Unknown')}</span>
            </td>
            <td>
                <button class="btn btn-sm btn-light" onclick="viewGroupMembers('${escapeHtml(group.dn)}')">
                    <i class="fas fa-users me-1"></i>${group.memberCount} members
                </button>
            </td>
            <td><small class="text-muted">${escapeHtml(group.ou)}</small></td>
            <td><small class="text-muted">${escapeHtml(group.created || '')}</small></td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewGroupDetails('${escapeHtml(group.dn)}')">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="showEditGroupModal('${escapeHtml(group.dn)}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="prepareDirectMoveToOU('${escapeHtml(group.dn)}', '${escapeHtml(group.name)}', '${escapeHtml(group.ou)}')">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="showAddMembersModal('${escapeHtml(group.dn)}', '${escapeHtml(group.name)}')">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteGroup('${escapeHtml(group.dn)}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getGroupTypeBadgeClass(type) {
    switch(type.toLowerCase()) {
        case 'security': return 'badge-group-security';
        case 'distribution': return 'badge-group-distribution';
        default: return 'badge-group-default';
    }
}

function filterGroups() {
    const searchText = document.getElementById('groupSearch').value.toLowerCase();
    const rows = document.getElementById('groupsTable').getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        if (row.cells && row.cells.length > 1) {
            const groupName = row.cells[1].textContent.toLowerCase();
            const groupType = row.cells[2].textContent.toLowerCase();
            const groupOU = row.cells[4].textContent.toLowerCase();
            
            // Enhanced search - by name, type or OU
            const isVisible = groupName.includes(searchText) || 
                               groupType.includes(searchText) || 
                               groupOU.includes(searchText);
                               
            row.classList.toggle('d-none', !isVisible);
        }
    });
}

function updateStats(stats) {
    // Set default values
    const defaultStats = {
        total: 0,
        types: {
            Security: 0,
            Distribution: 0
        }
    };

    // Merge incoming stats with default values
    const finalStats = {
        total: stats.total || defaultStats.total,
        types: { ...defaultStats.types, ...stats.types }
    };

    // Update statistics
    document.getElementById('totalGroups').textContent = finalStats.total;
    document.getElementById('securityGroups').textContent = finalStats.types.Security || 0;
    document.getElementById('distributionGroups').textContent = finalStats.types.Distribution || 0;
}

function getGroupIcon(type) {
    const baseClass = 'fas';
    switch(type.toLowerCase()) {
        case 'security':
            return `${baseClass} fa-shield-alt group-icon-security`;
        case 'distribution':
            return `${baseClass} fa-share-alt group-icon-distribution`;
        default:
            return `${baseClass} fa-layer-group group-icon-default`;
    }
}

function viewGroupDetails(dn) {
    const modal = new bootstrap.Modal(document.getElementById('groupDetailsModal'));
    const modalBody = document.querySelector('#groupDetailsModal .modal-body');
    const modalTitle = document.querySelector('#groupDetailsModal .modal-title');
    
    modalBody.innerHTML = `
        <div class="loading-state">
            <div class="spinner-border"></div>
            <p class="loading-text"><?php echo __('loading_group_details'); ?></p>
        </div>
    `;
    
    modal.show();
    
    fetch(`api/group-details.php?dn=${encodeURIComponent(dn)}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(group => {
            modalTitle.innerHTML = `<i class="fas fa-users-cog me-2"></i>${escapeHtml(group.name)}`;
            modalBody.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="group-details-card">
                            <div class="group-details-header">
                                <h6 class="mb-0"><?php echo __('group_information'); ?></h6>
                            </div>
                            <div class="group-details-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4"><?php echo __('type'); ?></dt>
                                    <dd class="col-sm-8">
                                        <span class="badge ${getGroupTypeBadgeClass(group.type)}">
                                            ${escapeHtml(group.type)}
                                        </span>
                                    </dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('scope'); ?></dt>
                                    <dd class="col-sm-8">
                                        <span class="badge badge-group-default">
                                            ${escapeHtml(group.scope || 'Unknown')}
                                        </span>
                                    </dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('description'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(group.description || '-')}</dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('email_address'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(group.email || '-')}</dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('notes'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(group.notes || '-')}</dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('created'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(group.created)}</dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('location'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(group.ou)}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="group-details-card">
                            <div class="group-details-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo __('members'); ?> (${group.memberCount})</h6>
                            </div>
                            <div class="group-details-body members-container">
                                ${group.members.length > 0 ? `
                                    <div class="list-group list-group-flush" id="membersContainer">
                                        ${group.members.map(member => `
                                            <div class="group-member-item">
                                                <div class="group-member-name">
                                                    <i class="fas fa-user group-icon-default group-member-icon"></i>
                                                    ${escapeHtml(member)}
                                                </div>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="removeMember('${escapeHtml(group.dn)}', '${escapeHtml(member)}')">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </div>
                                        `).join('')}
                                    </div>
                                ` : `
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <?php echo __('no_members'); ?>
                                    </div>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Detailed members API call
            fetch(`api/group-action.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get_members_detailed',
                    dn: group.dn
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.members.length > 0) {
                    const membersContainer = document.getElementById('membersContainer');
                    if (membersContainer) {
                        membersContainer.innerHTML = data.members.map(member => `
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas ${member.type === 'user' ? 'fa-user' : 'fa-users'} 
                                         ${member.enabled ? 'text-primary' : 'text-muted'} me-2"></i>
                                    <span class="${!member.enabled ? 'text-muted' : ''}">${escapeHtml(member.name)}</span>
                                    ${member.type === 'user' ? `<small class="text-muted ms-2">(${escapeHtml(member.username)})</small>` : ''}
                                    ${!member.enabled ? '<span class="badge bg-warning ms-2"> <?php echo __('disabled'); ?></span>' : ''}
                                </div>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="removeMember('${escapeHtml(group.dn)}', '${escapeHtml(member.dn)}')">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </div>
                        `).join('');
                    }
                }
            })
            .catch(error => console.error('<?php echo __('error_loading_detailed_members'); ?>:', error));
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo __('error_loading_group_details'); ?>: ${escapeHtml(error.message)}
                </div>
            `;
        });
}

function viewGroupMembers(dn) {
    // First show group details
    viewGroupDetails(dn);
    
    // Focus on members after group details modal is fully opened
    setTimeout(() => {
        const membersContainer = document.querySelector('#groupDetailsModal .card-body[style*="overflow-y: auto"]');
        if (membersContainer) {
            membersContainer.scrollIntoView({ behavior: 'smooth' });
            
            // Add search input
            const cardHeader = membersContainer.previousElementSibling;
            if (cardHeader && !cardHeader.querySelector('.member-search-input')) {
                const searchDiv = document.createElement('div');
                searchDiv.className = 'mt-2';
                searchDiv.innerHTML = `
                    <input type="text" class="form-control form-control-sm member-search-input" 
                           placeholder="Search members..." id="memberSearchInput">
                `;
                cardHeader.appendChild(searchDiv);
                
                // Event listener for search input
                document.getElementById('memberSearchInput').addEventListener('input', function() {
                    const searchText = this.value.toLowerCase();
                    const memberItems = membersContainer.querySelectorAll('.list-group-item');
                    
                    memberItems.forEach(item => {
                        const memberName = item.textContent.toLowerCase();
                        item.classList.toggle('d-none', !memberName.includes(searchText));
                    });
                });
            }
        }
    }, 500);
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

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show create group modal
function showCreateGroupModal() {
    // Load OUs
    loadOUs();
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('createGroupModal'));
    
    // Form handlers
    document.getElementById('groupName').value = '';
    document.getElementById('groupDescription').value = '';
    document.getElementById('groupEmail').value = '';
    document.getElementById('groupType').value = '<?php echo __('security'); ?>';
    document.getElementById('groupScope').value = '<?php echo __('global'); ?>';
    
    // Submit event listener
    const form = document.getElementById('createGroupForm');
    if (form) {
        form.onsubmit = function(e) {
            e.preventDefault();
            createGroup();
        };
    }
    
    modal.show();
}

// Load OUs
function loadOUs(selector = 'groupOU') {
    const ouSelect = document.getElementById(selector);
    if (!ouSelect) {
        console.error(`Element with ID '${selector}' not found`);
        return;
    }
    
    ouSelect.innerHTML = '<option value=""> <?php echo __('loading'); ?>...</option>';
    
    fetch('api/get-ous.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                ouSelect.innerHTML = '<option value=""> <?php echo __('select_ou'); ?>...</option>';
                
                // Load OUs
                if (Array.isArray(data.ous) && data.ous.length > 0) {
                    data.ous.forEach(ou => {
                        const option = document.createElement('option');
                        option.value = ou.dn;
                        option.textContent = ou.path;
                        ouSelect.appendChild(option);
                    });
                    
                    // Special event for successful OU load
                    ouSelect.dispatchEvent(new CustomEvent('ous-loaded'));
                } else {
                    console.warn('OU list is empty');
                    ouSelect.innerHTML = '<option value=""> <?php echo __('no_ous_found'); ?></option>';
                }
            } else {
                console.error('Error loading OUs:', data.error);
                ouSelect.innerHTML = '<option value=""> <?php echo __('ous_not_loaded'); ?>: ' + (data.error || ' <?php echo __('error'); ?>') + '</option>';
            }
        })
        .catch(error => {
            console.error('Error loading OUs:', error);
            ouSelect.innerHTML = '<option value=""> <?php echo __('ous_not_loaded'); ?>: ' + error.message + '</option>';
        });
}

// Validate group form
function validateGroupForm() {
    const groupName = document.getElementById('groupName').value.trim();
    const groupOU = document.getElementById('groupOU').value;
    const groupEmail = document.getElementById('groupEmail').value.trim();
    const submitBtn = document.querySelector('#createGroupForm button[type="submit"], #editGroupForm button[type="submit"]');
    
    let isValid = true;
    let errorMessages = [];
    
    // Check required fields
    if (groupName.length === 0) {
        isValid = false;
        errorMessages.push(' <?php echo __('group_name_required'); ?>');
        document.getElementById('groupName').classList.add('is-invalid');
    } else {
        document.getElementById('groupName').classList.remove('is-invalid');
    }
    
    if (!groupOU) {
        isValid = false;
        errorMessages.push(' <?php echo __('ou_must_be_selected'); ?>');
        document.getElementById('groupOU').classList.add('is-invalid');
    } else {
        document.getElementById('groupOU').classList.remove('is-invalid');
    }
    
    // Check email format
    if (groupEmail && !isValidEmail(groupEmail)) {
        isValid = false;
        errorMessages.push(' <?php echo __('email_format_is_invalid'); ?>');
        document.getElementById('groupEmail').classList.add('is-invalid');
    } else {
        document.getElementById('groupEmail').classList.remove('is-invalid');
    }
    
    // Enable/disable submit button
    if (submitBtn) {
        submitBtn.disabled = !isValid;
    }
    
    if (!isValid && errorMessages.length > 0) {
        Swal.fire({
            title: ' <?php echo __('attention'); ?>!',
            html: '<ul class="text-start"><li>' + errorMessages.join('</li><li>') + '</li></ul>',
            icon: 'warning',
            confirmButtonText: ' <?php echo __('close'); ?>',
            confirmButtonColor: '#ffc107'
        });
    }
    
    return isValid;
}

// Email format validator
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Create new group
async function createGroup() {
    // Get create button
    const createBtn = document.getElementById('submitGroupBtn');
    
    // Get form values
    const name = document.getElementById('groupName').value.trim();
    const parentOU = document.getElementById('groupOU').value.trim();
    const type = document.getElementById('groupType').value.trim();
    const scope = document.getElementById('groupScope').value.trim();
    const description = document.getElementById('groupDescription').value.trim();
    const email = document.getElementById('groupEmail').value.trim();
    const notes = document.getElementById('groupNotes').value.trim();
    
    // Validate inputs
    if (!name) {
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('group_name_required'); ?>',
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    if (!parentOU) {
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('parent_ou_is_required'); ?>',
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    if (!type) {
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('group_type_is_required'); ?>',
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    // Update button state - show spinner
    if (createBtn) {
        createBtn.setAttribute('disabled', 'disabled');
        createBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo __('creating'); ?>...';
    }
    
    // Prepare group data
    const groupData = {
        action: 'create_group',
        name: name,
        ou: parentOU,
        type: type,
        scope: scope,
        description: description,
        email: email,
        notes: notes
    };
    
    try {
        // Send API request
        const response = await fetch('api/group-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(groupData)
        });
        
        // Check if response is okay (HTTP 200-299)
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        // Check the content type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Get text response for error details (without logging)
            const textResponse = await response.text();
            throw new Error(`Expected JSON response but got: ${contentType}`);
        }
        
        // Process JSON response
        const result = await response.json();
        
        // Reset button
        if (createBtn) {
            createBtn.removeAttribute('disabled');
            createBtn.innerHTML = 'Create Group';
        }
        
        // Handle success case
        if (result.success) {
            // Hide the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createGroupModal'));
            if (modal) {
                modal.hide();
            }
            
            // Reset form
            document.getElementById('createGroupForm').reset();
            
            // Reload groups
            loadGroups();
            
            // Show success message
            Swal.fire({
                title: ' <?php echo __('success'); ?>!',
                text: ' <?php echo __('group_created_successfully'); ?>',
                icon: 'success',
                confirmButtonText: 'Close',
                confirmButtonColor: '#198754',
                timer: 2000,
                timerProgressBar: true
            });
        } else {
            // API error handling
            Swal.fire({
                title: ' <?php echo __('error'); ?>!',
                text: result.error || ' <?php echo __('failed_to_create_group'); ?>',
                icon: 'error',
                confirmButtonText: 'Close',
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (error) {
        // Minimal error logging
        console.error(' <?php echo __('error_creating_group'); ?>: ' + error.name);
        
        // Reset button
        if (createBtn) {
            createBtn.removeAttribute('disabled');
            createBtn.innerHTML = ' <?php echo __('create_group'); ?>';
        }
        
        // Show error message
        Swal.fire({
            title: ' <?php echo __('system_error'); ?>!',
            text: ' <?php echo __('a_system_error_occurred'); ?>: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
    }
}

// Show alert (using SweetAlert2)
function showAlert(title, message, type = 'info') {
    Swal.fire({
        title: title,
        text: message,
        icon: getAlertIcon(type),
        confirmButtonText: 'Close',
        confirmButtonColor: '#0d6efd',
        timer: 3000,
        timerProgressBar: true
    });
}

// Get SweetAlert2 icon based on alert type
function getAlertIcon(type) {
    switch (type) {
        case 'success': return 'success';
        case 'warning': return 'warning';
        case 'danger': return 'error';
        case 'info': 
        default: return 'info';
    }
}

// Group delete confirmation dialog
function confirmDeleteGroup(dn) {
    Swal.fire({
        title: ' <?php echo __('warning'); ?>!',
        text: ' <?php echo __('are_you_sure_you_want_to_delete_this_group'); ?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: ' <?php echo __('yes_delete_group'); ?>',
        cancelButtonText: ' <?php echo __('cancel'); ?>'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteGroup(dn);
        }
    });
}

// Delete group
function deleteGroup(dn) {
    fetch('api/group-action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete_group',
            dn: dn
        })
    })
    .then(response => {
        if (!response.ok) {
            // If response status is not 200-299, there's an error
            console.error('Server error:', response.status, response.statusText);
            throw new Error(`Server error: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Hide modal if it's open
            const groupModal = document.getElementById('groupDetailsModal');
            if (groupModal) {
                const groupModalInstance = bootstrap.Modal.getInstance(groupModal);
                if (groupModalInstance) {
                    groupModalInstance.hide();
                }
            }
            
            // Show success operation notification to user
            Swal.fire({
                title: ' <?php echo __('success'); ?>!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'Close',
                confirmButtonColor: '#198754',
                timer: 2000,
                timerProgressBar: true
            });
            
            // Reload groups
            loadGroups();
        } else {
            Swal.fire({
                title: ' <?php echo __('error'); ?>!',
                text: data.error,
                icon: 'error',
                confirmButtonText: 'Close',
                confirmButtonColor: '#dc3545'
            });
        }
    })
    .catch(error => {
        console.error('Error deleting group:', error);
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('a_server_error_occurred_while_deleting_the_group'); ?>',
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
    });
}

// Show edit group modal
function showEditGroupModal(dn) {
    // Load group details
    fetch(`api/group-details.php?dn=${encodeURIComponent(dn)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            return response.json();
        })
        .then(group => {
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editGroupModal'));
            
            // Get edit form
            const editForm = document.getElementById('editGroupForm');
            
            // Fill form
            document.getElementById('editGroupDN').value = group.dn;
            document.getElementById('editGroupName').value = group.name;
            document.getElementById('editGroupDescription').value = group.description || '';
            document.getElementById('editGroupEmail').value = group.email || '';
            document.getElementById('editGroupNotes').value = group.notes || '';
            
            // Add: data-dn attribute to form
            editForm.setAttribute('data-dn', group.dn);
            
            // Submit event listener
            if (editForm) {
                editForm.onsubmit = function(e) {
                    e.preventDefault();
                    updateGroup();
                };
            }
            
            modal.show();
        })
        .catch(error => {
            console.error('Error loading group details:', error);
            Swal.fire({
                title: ' <?php echo __('error'); ?>!',
                text: ' <?php echo __('an_error_occurred_while_loading_group_details'); ?>: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Close',
                confirmButtonColor: '#dc3545'
            });
        });
}

/**
 * Group update function
 */
async function updateGroup() {
    // Get form values
    const dn = document.getElementById('editGroupDN').value.trim();
    const name = document.getElementById('editGroupName').value.trim();
    const description = document.getElementById('editGroupDescription').value.trim();
    const email = document.getElementById('editGroupEmail').value.trim();
    const notes = document.getElementById('editGroupNotes').value.trim();
    
    // Validate inputs
    if (!dn) {
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('group_dn_is_missing'); ?>',
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    if (!name) {
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('group_name_is_required'); ?>',
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    // Update button state - show spinner
    const updateBtn = document.getElementById('editGroupSubmitBtn');
    if (updateBtn) {
        updateBtn.setAttribute('disabled', 'disabled');
        updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo __('updating'); ?>...';
    }
    
    // Build data
    const data = {
        action: 'update_group',
        dn: dn,
        name: name,
        description: description,
        email: email,
        notes: notes
    };
    
    try {
        // Send API request
        const response = await fetch('api/group-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        // Check if response is okay (HTTP 200-299)
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        // Check the content type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Get text response for error details (without logging)
            const textResponse = await response.text();
            throw new Error(`Expected JSON response but got: ${contentType}`);
        }
        
        // Process JSON response
        const result = await response.json();
        
        // Hide spinner
        if (updateBtn) {
            updateBtn.removeAttribute('disabled');
            updateBtn.innerHTML = 'Save';
        }
        
        if (result.success) {
            // Success
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editGroupModal'));
            if (modal) {
                modal.hide();
            }
            
            // Reload groups
            loadGroups();
            
            // Show notification
            Swal.fire({
                title: ' <?php echo __('success'); ?>!',
                text: ' <?php echo __('group_successfully_updated'); ?>',
                icon: 'success',
                confirmButtonText: 'Close',
                confirmButtonColor: '#198754',
                timer: 2000,
                timerProgressBar: true
            });
        } else {
            // API error
            Swal.fire({
                title: ' <?php echo __('error'); ?>!',
                text: result.error || ' <?php echo __('an_error_occurred_during_group_update'); ?>',
                icon: 'error',
                confirmButtonText: 'Close',
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (error) {
        // Minimal error logging
        console.error(' <?php echo __('group_update_error'); ?>: ' + error.name);
        
        // Hide spinner
        if (updateBtn) {
            updateBtn.removeAttribute('disabled');
            updateBtn.innerHTML = 'Save';
        }
        
        Swal.fire({
            title: ' <?php echo __('system_error'); ?>!',
            text: ' <?php echo __('a_system_error_occurred'); ?>: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
    }
}

// Show move group modal
function showMoveGroupModal() {
    // First get current group details from editGroupModal
    const dn = document.getElementById('editGroupDN').value;
    const groupName = document.getElementById('editGroupName').value;
    
    // Get modal elements
    const moveModal = document.getElementById('moveGroupModal');
    const moveForm = document.getElementById('moveGroupForm');
    
    if (!moveForm) {
        console.error("Move Group form not found in showMoveGroupModal");
        return;
    }
    
    // Add group details to form
    moveForm.setAttribute('data-dn', dn);
    
    // Set modal title
    document.querySelector('#moveGroupModal .modal-title').innerHTML = `<i class="fas fa-exchange-alt me-2"></i>Move group "${groupName}"`;
    
    // Load group details
    fetch(`api/group-details.php?dn=${encodeURIComponent(dn)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            return response.json();
        })
        .then(group => {
            // Show current OU and group name
            document.getElementById('currentGroupOU').value = group.ou || '';
            document.getElementById('moveGroupName').value = groupName;
            document.getElementById('moveGroupDN').value = dn;
            
            // Load OUs
            loadOUs('moveGroupOU');
            
            // Hide edit modal and show move group modal
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editGroupModal'));
            if (editModal) {
                editModal.hide();
            }
            
            // Submit event listener
            moveForm.onsubmit = function(e) {
                e.preventDefault();
                moveGroupToOU();
                return false;
            };
            
            // Show modal
            const bsModal = new bootstrap.Modal(moveModal);
            bsModal.show();
        })
        .catch(error => {
            console.error('Error loading group details:', error);
            
            Swal.fire({
                title: ' <?php echo __('error'); ?>!',
                text: ' <?php echo __('could_not_load_group_details'); ?>',
                icon: 'error',
                confirmButtonText: 'Close',
                confirmButtonColor: '#dc3545'
            });
        });
}

/**
 * Group OU move function
 */
async function moveGroupToOU() {
    const form = document.getElementById('moveGroupForm');
    if (!form) {
        return;
    }
    
    // Get form data
    const groupDN = form.getAttribute('data-dn');
    const newOU = document.getElementById('moveGroupOU').value;
    
    // Validate required fields
    if (!groupDN) {
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('group_dn_is_missing'); ?>',
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    if (!newOU) {
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('new_ou_is_required'); ?>',
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
        return;
    }
    
    // Prepare move button
    const moveBtn = document.getElementById('moveToOUBtn');
    if (moveBtn) {
        moveBtn.setAttribute('disabled', 'disabled');
        moveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo __('moving'); ?>...';
    }
    
    // Prepare data for the request
    const moveData = {
        action: 'move_group_to_ou',
        dn: groupDN,
        new_ou: newOU
    };
    
    try {
        // Send request to the API
        const response = await fetch('api/group-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(moveData)
        });
        
        // Check HTTP status
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
        }
        
        const data = await response.json();
        
        // Reset button state
        if (moveBtn) {
            moveBtn.removeAttribute('disabled');
            moveBtn.innerHTML = 'Move Group';
        }
        
        // Process response
        if (data.success) {
            // Hide modal
            const moveModal = bootstrap.Modal.getInstance(document.getElementById('moveGroupModal'));
            if (moveModal) {
                moveModal.hide();
            }
            
            // Show success message
            Swal.fire({
                title: ' <?php echo __('success'); ?>!',
                text: ' <?php echo __('group_moved_successfully'); ?>',
                icon: 'success',
                confirmButtonText: 'Close',
                confirmButtonColor: '#198754',
                timer: 2000,
                timerProgressBar: true
            });
            
            // Reload groups
            loadGroups();
        } else {
            // Show error message
            Swal.fire({
                title: ' <?php echo __('error'); ?>!',
                text: data.error || ' <?php echo __('failed_to_move_group'); ?>',
                icon: 'error',
                confirmButtonText: 'Close',
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (error) {
        // Minimal error logging
        console.error(' <?php echo __('error_moving_group_to_new_ou'); ?>: ' + error.name);
        
        // Reset button state
        if (moveBtn) {
            moveBtn.removeAttribute('disabled');
            moveBtn.innerHTML = 'Move Group';
        }
        
        // Show error message
        Swal.fire({
            title: ' <?php echo __('error'); ?>!',
            text: ' <?php echo __('failed_to_move_group'); ?>: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Close',
            confirmButtonColor: '#dc3545'
        });
    }
}

// Remove member from group
function removeMember(groupDN, memberDN) {
    Swal.fire({
        title: ' <?php echo __('remove_members'); ?>',
        text: ' <?php echo __('are_you_sure_you_want_to_remove_this_member_from_the_group'); ?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: ' <?php echo __('yes_remove'); ?>',
        cancelButtonText: ' <?php echo __('cancel'); ?>'
    }).then((result) => {
        if (result.isConfirmed) {
            // Remove member
            fetch('api/group-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'remove_members',
                    dn: groupDN,
                    members: [memberDN]
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: ' <?php echo __('success'); ?>!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Close',
                        confirmButtonColor: '#198754',
                        timer: 2000,
                        timerProgressBar: true
                    });
                    
                    // Update group details
                    viewGroupDetails(groupDN);
                    
                    // Reload groups (member count may change)
                    loadGroups();
                } else {
                    Swal.fire({
                        title: ' <?php echo __('error'); ?>!',
                        text: data.error,
                        icon: 'error',
                        confirmButtonText: 'Close',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error removing member:', error);
                showAlert('Error', ' <?php echo __('an_error_occurred_while_removing_the_member_from_the_group'); ?>', 'danger');
            });
        }
    });
}

// Move group from group details modal to move group modal
function prepareDirectMoveToOU(dn, groupName, currentOU) {
    // Prepare modal
    const moveModal = document.getElementById('moveGroupModal');
    const moveForm = document.getElementById('moveGroupForm');
    
    if (!moveForm) {
        console.error(" <?php echo __('move_group_form_not_found_in_prepare_direct_move_to_ou'); ?>");
        return;
    }
    
    // Add group details to form
    moveForm.setAttribute('data-dn', dn);
    
    // Set modal title and current OU details
    document.querySelector('#moveGroupModal .modal-title').innerHTML = `<i class="fas fa-exchange-alt me-2"></i> <?php echo __('move_group'); ?> "${groupName}"`;
    document.getElementById('currentGroupOU').value = currentOU;
    document.getElementById('moveGroupName').value = groupName;
    document.getElementById('moveGroupDN').value = dn;
    
    // Load OUs
    loadOUs('moveGroupOU');
    
    // Set modal submit event
    moveForm.onsubmit = function(e) {
        e.preventDefault();
        moveGroupToOU();
        return false;
    };
    
    // Show modal
    const bsModal = new bootstrap.Modal(moveModal);
    bsModal.show();
}

/**
 * Shows the modal to add members to a group
 * @param {string} groupDN The distinguished name of the group
 * @param {string} groupName The name of the group
 */
function showAddMembersModal(groupDN, groupName) {
    // Clear previous state
    const searchInput = document.getElementById('userSearch');
    const availableUsersContainer = document.getElementById('availableUsers');
    const selectedUsersContainer = document.getElementById('selectedUsers');
    
    searchInput.value = '';
    availableUsersContainer.innerHTML = `
        <div class="text-center text-muted py-3">
            <i class="fas fa-search me-2"></i> <?php echo __('search_for_users_to_add'); ?>
        </div>
    `;
    selectedUsersContainer.innerHTML = `
        <div class="text-center text-muted py-3 no-selected-users">
            <i class="fas fa-users-slash me-2"></i> <?php echo __('no_users_selected'); ?>
        </div>
    `;
    
    // Set group info
    document.getElementById('targetGroupDN').value = groupDN;
    document.getElementById('targetGroupName').value = groupName;
    
    // Disable add members button initially
    document.getElementById('addMembersBtn').disabled = true;
    
    // Setup search button event 
    document.getElementById('searchUsersBtn').onclick = searchUsers;
    
    // Also search when pressing enter in the search field
    searchInput.onkeydown = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchUsers();
        }
    };
    
    // Setup form submission
    const addMembersForm = document.getElementById('addMembersForm');
    addMembersForm.onsubmit = function(e) {
        e.preventDefault();
        addMembersToGroup();
    };
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('addMembersModal'));
    modal.show();
}

/**
 * Searches for users to add to the group
 */
function searchUsers() {
    const searchQuery = document.getElementById('userSearch').value.trim();
    const availableUsersContainer = document.getElementById('availableUsers');
    const groupDN = document.getElementById('targetGroupDN').value;
    
    // Validate search query
    if (searchQuery.length < 2) {
        Swal.fire({
            icon: 'warning',
            title: ' <?php echo __('search_query_too_short'); ?>',
            text: ' <?php echo __('please_enter_at_least_2_characters_to_search_for_users'); ?>',
            confirmButtonText: ' <?php echo __('ok'); ?>'
        });
        return;
    }
    
    // Show loading state
    availableUsersContainer.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden"> <?php echo __('loading'); ?>...</span>
            </div>
            <span class="ms-2"> <?php echo __('searching_for_users'); ?>...</span>
        </div>
    `;
    
    // Search for users via API
    fetch(`api/search-users.php?query=${encodeURIComponent(searchQuery)}&group_dn=${encodeURIComponent(groupDN)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to search for users');
            }
            
            if (data.users.length === 0) {
                availableUsersContainer.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-user-slash me-2"></i> <?php echo __('no_users_found_matching'); ?> '${escapeHtml(searchQuery)}'
                    </div>
                `;
                return;
            }
            
            // Display available users
            availableUsersContainer.innerHTML = '';
            data.users.forEach(user => {
                const userElement = document.createElement('a');
                userElement.href = '#';
                userElement.className = 'list-group-item list-group-item-action user-item';
                userElement.dataset.dn = user.dn;
                userElement.dataset.name = user.displayName || user.username;
                userElement.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user me-2 text-muted"></i>
                            <span class="fw-medium">${escapeHtml(user.displayName || user.username)}</span>
                                <small class="text-muted">(${escapeHtml(user.username)})</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary select-user-btn">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                `;
                availableUsersContainer.appendChild(userElement);
                
                // Add click event to select user
                userElement.querySelector('.select-user-btn').onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectUser(user.dn, user.displayName || user.username);
                };
            });
        })
        .catch(error => {
            console.error('Error searching for users:', error);
            availableUsersContainer.innerHTML = `
                <div class="text-center text-danger py-3">
                    <i class="fas fa-exclamation-circle me-2"></i>${escapeHtml(error.message)}
                </div>
            `;
        });
}

/**
 * Selects a user to add to the group
 * @param {string} userDN The distinguished name of the user
 * @param {string} userName The display name of the user
 */
function selectUser(userDN, userName) {
    const selectedUsersContainer = document.getElementById('selectedUsers');
    const noSelectedMsg = selectedUsersContainer.querySelector('.no-selected-users');
    
    // Remove the "no selected users" message if it exists
    if (noSelectedMsg) {
        noSelectedMsg.remove();
    }
    
    // Check if user is already selected
    if (selectedUsersContainer.querySelector(`[data-dn="${userDN}"]`)) {
        // User already selected - highlight briefly
        const existingItem = selectedUsersContainer.querySelector(`[data-dn="${userDN}"]`);
        existingItem.classList.add('bg-warning-subtle');
        setTimeout(() => {
            existingItem.classList.remove('bg-warning-subtle');
        }, 1000);
        return;
    }
    
    // Create user element
    const userElement = document.createElement('div');
    userElement.className = 'list-group-item d-flex justify-content-between align-items-center user-item';
    userElement.dataset.dn = userDN;
    userElement.dataset.name = userName;
    userElement.innerHTML = `
        <div>
            <i class="fas fa-user me-2 text-muted"></i>
            <span class="fw-medium">${escapeHtml(userName)}</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-user-btn">
            <i class="fas fa-times"></i>
        </button>
        <input type="hidden" name="selected_users[]" value="${escapeHtml(userDN)}">
    `;
    selectedUsersContainer.appendChild(userElement);
    
    // Add click event to remove user
    userElement.querySelector('.remove-user-btn').onclick = function() {
        userElement.remove();
        // If no users left, show the "no selected users" message
        if (selectedUsersContainer.querySelectorAll('.user-item').length === 0) {
            selectedUsersContainer.innerHTML = `
                <div class="text-center text-muted py-3 no-selected-users">
                    <i class="fas fa-users-slash me-2"></i> <?php echo __('no_users_selected'); ?>
                </div>
            `;
        }
        // Update add members button state
        updateAddMembersButtonState();
    };
    
    // Update add members button state
    updateAddMembersButtonState();
}

/**
 * Updates the state of the Add Members button based on user selection
 */
function updateAddMembersButtonState() {
    const selectedUsersContainer = document.getElementById('selectedUsers');
    const addMembersBtn = document.getElementById('addMembersBtn');
    
    // Enable the button if at least one user is selected
    addMembersBtn.disabled = selectedUsersContainer.querySelectorAll('.user-item').length === 0;
}

/**
 * Adds selected members to the group
 */
function addMembersToGroup() {
    const groupDN = document.getElementById('targetGroupDN').value;
    const groupName = document.getElementById('targetGroupName').value;
    const selectedUsersContainer = document.getElementById('selectedUsers');
    const selectedUsers = Array.from(selectedUsersContainer.querySelectorAll('.user-item')).map(el => ({
        dn: el.dataset.dn,
        name: el.dataset.name
    }));
    
    if (selectedUsers.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: ' <?php echo __('no_users_selected'); ?>',
            text: ' <?php echo __('please_select_at_least_one_user_to_add_to_the_group'); ?>',
            confirmButtonText: ' <?php echo __('ok'); ?>'
        });
        return;
    }
    
    // Show confirmation dialog
    Swal.fire({
        title: ' <?php echo __('add_members_to_group'); ?>?',
        html: `
            <p> <?php echo __('are_you_sure_you_want_to_add'); ?> ${selectedUsers.length} <?php echo __('user(s)'); ?> <?php echo __('to_the_group'); ?> <strong>${escapeHtml(groupName)}</strong>?</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: ' <?php echo __('add_members'); ?>',
        cancelButtonText: ' <?php echo __('cancel'); ?>',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: ' <?php echo __('adding_members'); ?>...',
                html: ' <?php echo __('please_wait_while_the_members_are_being_added_to_the_group'); ?>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send API request to add members
            fetch('api/group-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'add_members',
                    group_dn: groupDN,
                    member_dns: selectedUsers.map(user => user.dn)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || ' <?php echo __('failed_to_add_members_to_group'); ?>');
                }
                
                // Close the modal
                bootstrap.Modal.getInstance(document.getElementById('addMembersModal')).hide();
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: ' <?php echo __('members_added'); ?>',
                    html: `
                        <p> <?php echo __('selected_users_have_been_added_to_the_group'); ?> <strong>${escapeHtml(groupName)}</strong>.</p>
                    `,
                    confirmButtonText: ' <?php echo __('ok'); ?>'
                });
                
                // Refresh the groups list to update member count
                loadGroups();
            })
            .catch(error => {
                console.error('Error adding members to group:', error);
                Swal.fire({
                    icon: 'error',
                    title: ' <?php echo __('error'); ?>',
                    text: error.message || ' <?php echo __('failed_to_add_members_to_group'); ?>',
                    confirmButtonText: ' <?php echo __('ok'); ?>'
                });
            });
        }
    });
}


</script>
