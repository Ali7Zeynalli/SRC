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

$pageTitle =  __('user_management');
$activePage = 'users';

try {
    $ldap_conn = getLDAPConnection();
    $users = getAllUsers($ldap_conn);
    $lockedUsers = getLockedUsers($ldap_conn);
    
    $stats = [
        'total' => count($users) - 1,
        'active' => 0,
        'inactive' => 0,
        'expired_password' => 0,
        'locked' => count($lockedUsers),
        'never_expires' => 0,
        'must_change' => 0
    ];
    
    // Loop through users to calculate other stats
    for ($i = 0; $i < $stats['total']; $i++) {
        $user = $users[$i];
        $uac = isset($user['useraccountcontrol'][0]) ? $user['useraccountcontrol'][0] : 0;
        $pwdLastSet = isset($user['pwdlastset'][0]) ? $user['pwdlastset'][0] : 0;
        
        // Check if account is enabled
        if (($uac & 2) !== 2) {
            $stats['active']++;
        } else {
            $stats['inactive']++;
        }
        
        // Check password expiry and never expires status
        $pwdStatus = getPasswordExpiryStatus($pwdLastSet, $ldap_conn, $uac);
        if ($pwdStatus['status'] === 'Expired') {
            $stats['expired_password']++;
        } else if ($pwdStatus['status'] === 'Never Expires') {
            $stats['never_expires']++;
        } else if ($pwdStatus['status'] === 'Must Change') {
            $stats['must_change']++;
        }
    }
    
    $_SESSION['user_stats'] = [
        'total' => $stats['total'],
        'active' => $stats['active'],
        'inactive' => $stats['inactive'],
        'locked' => $stats['locked'],
        'password_status' => [
            'expired' => $stats['expired_password'],
            'never_expires' => $stats['never_expires'],
            'must_change' => $stats['must_change']
        ]
    ];
    
} catch (Exception $e) {
    session_destroy();
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}

require_once('includes/header.php');
?>

<head>
    <!-- ... other head elements ... -->
    <link href="temp/css/user.css" rel="stylesheet">
</head>

<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 bg-light">
            <!-- Page Title -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h5 class="mb-0 text-primary page-title">
                    <i class="fas fa-users me-2"></i><?php echo __('all_users'); ?>
                </h5>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" onclick="showNewUserModal()">
                        <i class="fas fa-user-plus me-2"></i><?php echo __('add_new_user'); ?>
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <?php include('includes/user-stats.php'); ?>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <!-- Search Section -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <!-- Search Input Group -->
                                <div class="flex-grow-1">
                                    <div class="input-group">
                                        <input type="text" id="searchInput" 
                                               class="form-control border-0 bg-light" 
                                               placeholder="Search...">
                                        <select id="searchType" class="form-select border-0 bg-light" style="max-width: 150px;">
                                            <option value="username"><?php echo __('username'); ?></option>
                                            <option value="displayName"><?php echo __('display_name'); ?></option>
                                            <option value="department"><?php echo __('department'); ?></option>
                                            <option value="ou"><?php echo __('ou'); ?></option>
                                            <option value="groups"><?php echo __('groups'); ?></option>
                                        </select>
                                    </div>
                                    <div id="searchResults" class="small text-muted mt-1"></div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-flex gap-2">
                                    <button id="refreshBtn" class="btn btn-primary px-3 position-relative" 
                                            title="Click to refresh data from server" 
                                            data-bs-toggle="tooltip">
                                        <i class="fas fa-sync-alt me-2"></i><?php echo __('refresh'); ?>
                                        <span class="position-absolute top-100 start-50 translate-middle-x badge bg-info text-white" 
                                              style="font-size: 0.65rem; white-space: nowrap;">
                                            <?php echo __('refresh_to_see_new_changes'); ?>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">#</th>
                                    <th class="border-0"><?php echo __('username'); ?></th>
                                    <th class="border-0"><?php echo __('ou'); ?></th>
                                    <th class="border-0"><?php echo __('groups'); ?></th>
                                    <th class="border-0"><?php echo __('status'); ?></th>
                                    <th class="border-0"><?php echo __('password_status'); ?></th>
                                    <th class="border-0"><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="usersTable">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">
                                <span id="paginationInfo"></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <label for="pageSize" class="form-label me-2 mb-0 small"><?php echo __('items_per_page'); ?>:</label>
                                    <select id="pageSize" class="form-select form-select-sm">
                                        <?php 
                                        $config = require(__DIR__ . '/config/config.php');
                                        $pageSizeOptions = $config['pagination_settings']['page_size_options'] ?? [5, 10, 15, 25, 50, 100, -1];
                                        $defaultPageSize = $config['pagination_settings']['default_page_size'] ?? 15;
                                        
                                        foreach ($pageSizeOptions as $size) {
                                            $selected = ($size == $defaultPageSize) ? 'selected' : '';
                                            if ($size == -1) {
                                                echo "<option value=\"-1\" $selected>" . __('show_all') . "</option>";
                                            } else {
                                                echo "<option value=\"$size\" $selected>$size</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <nav aria-label="User pagination">
                                    <ul class="pagination pagination-sm mb-0" id="usersPagination">
                                        <!-- Will be populated by JavaScript -->
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<!-- Action Modal for OU and Groups -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Will be populated dynamically -->
        </div>
    </div>
</div>

<!-- New User Modal -->
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('add_new_user'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newUserForm" class="needs-validation" novalidate>
                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="new_firstname" class="form-label"><?php echo __('first_name'); ?>*</label>
                            <input type="text" class="form-control" id="new_firstname" required>
                        </div>
                        <div class="col-md-4">
                            <label for="new_lastname" class="form-label"><?php echo __('last_name'); ?>*</label>
                            <input type="text" class="form-control" id="new_lastname" required>
                        </div>
                        <div class="col-md-4">
                            <label for="new_displayname" class="form-label"><?php echo __('display_name'); ?></label>
                            <input type="text" class="form-control" id="new_displayname">
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="new_username" class="form-label"><?php echo __('username'); ?>*</label>
                            <input type="text" class="form-control" id="new_username" required>
                            <div class="form-text"><?php echo __('will_be_used_for_login'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label"><?php echo __('password'); ?>*</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="generatePassword()">
                                    <i class="fas fa-random"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="generated_password_info" class="form-text text-success" style="display: none;">
                                <?php echo __('generated_password_copied_to_clipboard'); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="new_confirm_password" class="form-label"><?php echo __('confirm_password'); ?>*</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information and OU -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_email" class="form-label"><?php echo __('email'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="new_email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="new_ou" class="form-label"><?php echo __('organizational_unit'); ?>*</label>
                            <i class="fas fa-info-circle ms-1" 
                               data-bs-toggle="tooltip" 
                               title="<?php echo __('select_where_in_the_active_directory_structure_this_user_should_be_created'); ?>"></i>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-sitemap"></i>
                                </span>
                                <select class="form-select" id="new_ou" required>
                                    <!-- Will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                <?php echo __('shows_the_hierarchical_structure_in_active_directory'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <?php echo __('account_settings'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" id="new_must_change_password">
                                        <label class="form-check-label" for="new_must_change_password">
                                            <?php echo __('user_must_change_password_at_next_logon'); ?>
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" id="new_password_never_expires">
                                        <label class="form-check-label" for="new_password_never_expires">
                                            <?php echo __('password_never_expires'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                <button type="button" class="btn btn-primary" onclick="createNewUser()">
                    <i class="fas fa-user-plus me-2"></i><?php echo __('create_user'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('edit_user'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="edit_username">
                    
                    <!-- Two column layout -->
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('display_name'); ?></label>
                                <input type="text" class="form-control" id="edit_displayname">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('title'); ?></label>
                                <input type="text" class="form-control" id="edit_title">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('phone'); ?></label>
                                <input type="text" class="form-control" id="edit_phone">
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('email'); ?></label>
                                <input type="email" class="form-control" id="edit_email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('department'); ?></label>
                                <input type="text" class="form-control" id="edit_department">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('mobile'); ?></label>
                                <input type="text" class="form-control" id="edit_mobile">
                            </div>
                        </div>
                    </div>

                    <!-- Description - Full Width -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description'); ?></label>
                        <textarea class="form-control" id="edit_description" rows="3"></textarea>
                    </div>

                    <!-- Password Options Card -->
                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-key me-2"></i><?php echo __('password_options'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="fw-bold"><?php echo __('password_never_expires'); ?></span>
                                    <small class="d-block text-muted"><?php echo __('user_can_keep_the_same_password_indefinitely'); ?></small>
                                </div>
                                <button type="button" id="btnNeverExpires" onclick="togglePasswordOption('never_expires')" 
                                        class="btn btn-outline-primary">
                                    <i class="fas fa-clock me-1"></i><span><?php echo __('loading'); ?>...</span>
                                </button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold"><?php echo __('must_change_password'); ?></span>
                                    <small class="d-block text-muted"><?php echo __('user_must_change_password_at_next_logon'); ?></small>
                                </div>
                                <button type="button" id="btnMustChange" onclick="togglePasswordOption('must_change')" 
                                        class="btn btn-outline-primary">
                                    <i class="fas fa-key me-1"></i><span><?php echo __('loading'); ?>...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i><?php echo __('cancel'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveUserEdit()">
                    <i class="fas fa-save me-2"></i><?php echo __('save_changes'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
<script>

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchType = document.getElementById('searchType');
    const refreshBtn = document.getElementById('refreshBtn');
    
    // Load users on page load
    loadUsers(1, true, true);
    
    // Səhifə ölçüsü seçimi
    const pageSizeSelect = document.getElementById('pageSize');
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
            PAGE_SIZE = parseInt(this.value);
            currentPage = 1; // İlk səhifəyə qayıt
            
            // Səhifə ölçüsünü sessionStorage-də saxla
            sessionStorage.setItem('userPageSize', PAGE_SIZE);
            
            // Cədvəli yenilə
            if (filteredUsers.length > 0) {
                updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
                updatePagination(filteredUsers.length);
            } else {
                updateUsersTable(paginateUsers(allUsers, currentPage, PAGE_SIZE));
                updatePagination(allUsers.length);
            }
        });
        
        // Əvvəlki seçilmiş səhifə ölçüsünü yüklə
        const savedPageSize = sessionStorage.getItem('userPageSize');
        if (savedPageSize) {
            PAGE_SIZE = parseInt(savedPageSize);
            pageSizeSelect.value = savedPageSize;
        }
    }
    
    // Search functionality - debounce müddətini artırırıq
    searchInput.addEventListener('input', debounce(function(e) {
        // Axtarış göstəricisini əlavə edirik
        const searchResults = document.getElementById('searchResults');
        if (searchResults) {
            searchResults.innerHTML = `
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                    <span class="visually-hidden"><?php echo __('searching'); ?>...</span>
                </div>
                <span><?php echo __('searching'); ?>...</span>
            `;
        }
        
        handleSearch(e.target.value);
    }, 500)); // 300ms əvəzinə 500ms

    // Search type change event
    searchType.addEventListener('change', function() {
        handleSearch(searchInput.value);
        // Placeholder-i yeniləyirik
        searchInput.placeholder = `Search by ${searchType.options[searchType.selectedIndex].text}...`;
    });

    // Clear search on ESC key
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            handleSearch('');
            this.blur();
        }
    });
    
    // Global changePage funksiyasını əlavə edirik
    window.changePage = function(page) {
        currentPage = page;
        
        // Əgər filtrlənmiş məlumatlar varsa, onları göstəririk
        if (filteredUsers.length > 0) {
            updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
        } else {
            updateUsersTable(paginateUsers(allUsers, currentPage, PAGE_SIZE));
        }
        
        // Səhifələmə məlumatlarını yeniləyirik
        updatePagination(filteredUsers.length > 0 ? filteredUsers.length : allUsers.length);
        
        // Scroll to top of the table
        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer) {
            tableContainer.scrollTop = 0;
        }
    };
    
    // Refresh button
    refreshBtn.addEventListener('click', async function() {
        try {
            // Reset search
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');
            
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Əgər searchResults elementi varsa, təmizləyirik
            if (searchResults) {
                searchResults.textContent = '';
            }

            // Reset filter highlights
            document.querySelectorAll('.card[data-stat]').forEach(card => {
                card.classList.remove('border-primary');
            });
            
            // Add active class to Total Users card
            const totalCard = document.querySelector('.card[data-stat="total"]');
            if (totalCard) {
                totalCard.classList.add('border-primary');
            }

            // Update page title
            const listTitle = document.querySelector('.page-title');
            if (listTitle) {
                listTitle.innerHTML = '<i class="fas fa-users me-2"></i><?php echo __('all_users'); ?>';
                listTitle.className = 'mb-0 text-primary page-title';
            }

            // Show loading state
            this.disabled = true;
            this.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <span><?php echo __('refreshing'); ?>...</span>
                </div>
            `;
            
            // Reset filter and search variables
            currentFilterType = 'total';
            currentSearchText = '';
            filteredUsers = [];
            currentPage = 1;
            
            // Clear cache and reload data
            await clearCache();
            await loadUsers(1, true, true);
            await loadStats();
            
            // Success notification
            showToast('success', '<?php echo __('data_refreshed_successfully'); ?>');
            
        } catch (error) {
            console.error('Refresh error:', error);
            showToast('error', '<?php echo __('failed_to_refresh_data'); ?>');
        } finally {
            // Reset button state
            this.disabled = false;
            this.innerHTML = `
                <i class="fas fa-sync-alt me-2"></i><?php echo __('refresh'); ?>
                <span class="position-absolute top-100 start-50 translate-middle-x badge bg-info text-white" 
                      style="font-size: 0.65rem; white-space: nowrap;">
                    <?php echo __('refresh_to_see_new_changes'); ?>
                </span>
            `;
        }
    });
    
    // Load saved stats immediately
    loadSavedStats();
    
    // Then load fresh stats
    loadStats();
    
    // Refresh stats every 30 seconds
    setInterval(loadStats, 30000);

    // Add SweetAlert2 CSS
    const sweetalertCSS = document.createElement('link');
    sweetalertCSS.rel = 'stylesheet';
    sweetalertCSS.href = 'temp/assets/lib/bootstrap/bootstrap-4.min.css';
    document.head.appendChild(sweetalertCSS);

    // Add SweetAlert2 JS
    const sweetalertJS = document.createElement('script');
    sweetalertJS.src = 'temp/assets/lib/sweetalert2/sweetalert2.min.js';
    document.body.appendChild(sweetalertJS);

    // Statistika kartlarına klik hadisəsini əlavə edirik
    document.querySelectorAll('.card[data-stat]').forEach(card => {
        card.addEventListener('click', function() {
            const statType = this.getAttribute('data-stat');
            filterUsers(statType);
        });
    });

    // Auto-generate full name
    const newFirstname = document.getElementById('new_firstname');
    const newLastname = document.getElementById('new_lastname');
    
    if (newFirstname) {
        newFirstname.addEventListener('input', updateFullName);
    }
    
    if (newLastname) {
        newLastname.addEventListener('input', updateFullName);
    }

    // Initialize tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function updateFullName() {
    const firstName = document.getElementById('new_firstname');
    const lastName = document.getElementById('new_lastname');
    const displayName = document.getElementById('new_displayname');
    
    // Check if all elements exist (they might not if we're not in the new user form)
    if (!firstName || !lastName || !displayName) {
        console.warn('<?php echo __('one_or_more_elements_for_updating_full_name_not_found'); ?>');
        return;
    }
    
    const firstNameValue = firstName.value ? firstName.value.trim() : '';
    const lastNameValue = lastName.value ? lastName.value.trim() : '';
    
    if (firstNameValue && lastNameValue) {
        displayName.value = `${firstNameValue} ${lastNameValue}`;
    } else if (firstNameValue) {
        displayName.value = firstNameValue;
    } else if (lastNameValue) {
        displayName.value = lastNameValue;
    }
}

// Keşləmə üçün dəyişənlər
let cachedUsers = null;
let lastFetchTime = 0;
const CACHE_DURATION = 10 * 60 * 1000; // 10 dəqiqə (əvvəlki 5 dəqiqə əvəzinə)
let isLoadingUsers = false;

// Config-dən səhifələmə ayarlarını oxuyuruq
const config = <?php echo json_encode(require(__DIR__ . '/config/config.php')); ?>;
let PAGE_SIZE = config.pagination_settings.default_page_size || 15; // Bir səhifədə göstəriləcək istifadəçi sayı
const PAGE_SIZE_OPTIONS = config.pagination_settings.page_size_options || [10, 15, 25, 50, 100];

let currentPage = 1;
let allUsers = []; // Bütün istifadəçiləri saxlamaq üçün
let filteredUsers = []; // Filtrlənmiş istifadəçiləri saxlamaq üçün
let currentFilterType = 'total'; // Cari filter tipini saxlamaq üçün
let currentSearchText = ''; // Cari axtarış mətnini saxlamaq üçün

// Optimallaşdırılmış loadUsers funksiyası
async function loadUsers(page = 1, showLoadingIndicator = true, resetFilters = false) {
    try {
        if (isLoadingUsers) return; // Əgər yükləmə prosesi davam edirsə, yeni sorğu göndərmirik
        isLoadingUsers = true;
        currentPage = page;
        
        // Əvvəlki seçilmiş səhifə ölçüsünü yüklə (ilk dəfə yüklənirsə)
        if (page === 1 && showLoadingIndicator && resetFilters) {
            const savedPageSize = sessionStorage.getItem('userPageSize');
            if (savedPageSize) {
                PAGE_SIZE = parseInt(savedPageSize);
                const pageSizeSelect = document.getElementById('pageSize');
                if (pageSizeSelect) {
                    pageSizeSelect.value = savedPageSize;
                }
            }
        }
        
        // Əgər resetFilters true-dursa, filtrlənmiş məlumatları sıfırlayırıq
        if (resetFilters) {
            currentFilterType = 'total';
            currentSearchText = '';
            filteredUsers = [];
        }
        
        const now = Date.now();
        const tbody = document.getElementById('usersTable');
        
        // Yükləmə göstəricisini əlavə edirik
        if (showLoadingIndicator) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="spinner-border text-primary me-3" role="status">
                                <span class="visually-hidden"><?php echo __('loading'); ?>...</span>
                            </div>
                            <span><?php echo __('loading_users_data'); ?>...</span>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        // Keşdə istifadə
        if (cachedUsers && (now - lastFetchTime < CACHE_DURATION)) {
            allUsers = cachedUsers.users;
            
            // Əgər filtrlənmiş məlumatlar varsa və resetFilters false-dursa, onları göstəririk
            if (filteredUsers.length > 0 && !resetFilters) {
                updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
                updatePagination(filteredUsers.length);
            } else {
                updateUsersTable(paginateUsers(allUsers, currentPage, PAGE_SIZE));
                updatePagination(allUsers.length);
            }
            
            updateStats(cachedUsers.stats);
            isLoadingUsers = false;
            return;
        }

        // IndexedDB-dən keşlənmiş məlumatları yoxlayırıq
        const cachedData = await getCachedDataFromIndexedDB('users_data');
        if (cachedData && (now - cachedData.timestamp < CACHE_DURATION)) {
            cachedUsers = cachedData.data;
            lastFetchTime = cachedData.timestamp;
            allUsers = cachedUsers.users;
            
            // Əgər filtrlənmiş məlumatlar varsa və resetFilters false-dursa, onları göstəririk
            if (filteredUsers.length > 0 && !resetFilters) {
                updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
                updatePagination(filteredUsers.length);
            } else {
                updateUsersTable(paginateUsers(allUsers, currentPage, PAGE_SIZE));
                updatePagination(allUsers.length);
            }
            
            updateStats(cachedUsers.stats);
            
            // Arxa planda yeni məlumatları yükləyirik
            fetchFreshData();
            isLoadingUsers = false;
            return;
        }

        // Keşdə məlumat yoxdursa, serverdən yükləyirik
        await fetchFreshData();
        
    } catch (error) {
        console.error('<?php echo __('error_loading_users'); ?>:', error);
        showToast('error', `<?php echo __('failed_to_load_users'); ?>: ${error.message}`);
        isLoadingUsers = false;
    }
}

// Serverdə təzə məlumatları yükləyən funksiya
async function fetchFreshData() {
    try {
        const response = await fetch('api/users.php');
        if (!response.ok) {
            throw new Error('<?php echo __('network_response_was_not_ok'); ?>');
        }

        const data = await response.json();
        
        // Keşə yazırıq
        cachedUsers = data;
        lastFetchTime = Date.now();
        allUsers = data.users;
        
        // IndexedDB-yə keşləyirik
        saveCacheToIndexedDB('users_data', {
            data: data,
            timestamp: lastFetchTime
        });
        
        // Əgər filtrlənmiş məlumatlar varsa, onları yeniləyirik
        if (currentFilterType !== 'total' || currentSearchText) {
            // Əvvəlcə filter tətbiq edirik
            if (currentFilterType !== 'total') {
                filterCachedUsers(currentFilterType);
            } else {
                filteredUsers = [...allUsers];
                
                // Sonra axtarış filtri tətbiq edirik
                if (currentSearchText) {
                    applySearchFilter(currentSearchText);
                }
            }
            
            updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
            updatePagination(filteredUsers.length);
        } else {
            updateUsersTable(paginateUsers(allUsers, currentPage, PAGE_SIZE));
            updatePagination(allUsers.length);
        }
        
        updateStats(data.stats);
        
        isLoadingUsers = false;
    } catch (error) {
        console.error('<?php echo __('error_fetching_fresh_data'); ?>:', error);
        showToast('error', `<?php echo __('failed_to_fetch_data'); ?>: ${error.message}`);
        isLoadingUsers = false;
    }
}

// IndexedDB ilə işləmək üçün funksiyalar
function saveCacheToIndexedDB(key, value) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('UsersAppCache', 1);
        
        request.onupgradeneeded = function(event) {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('cache')) {
                db.createObjectStore('cache');
            }
        };
        
        request.onsuccess = function(event) {
            const db = event.target.result;
            const transaction = db.transaction(['cache'], 'readwrite');
            const store = transaction.objectStore('cache');
            
            store.put(value, key);
            resolve();
        };
        
        request.onerror = function(event) {
            reject(event.target.error);
        };
    });
}

function getCachedDataFromIndexedDB(key) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('UsersAppCache', 1);
        
        request.onupgradeneeded = function(event) {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('cache')) {
                db.createObjectStore('cache');
            }
        };
        
        request.onsuccess = function(event) {
            const db = event.target.result;
            const transaction = db.transaction(['cache'], 'readonly');
            const store = transaction.objectStore('cache');
            
            const getRequest = store.get(key);
            
            getRequest.onsuccess = function() {
                resolve(getRequest.result);
            };
            
            getRequest.onerror = function(event) {
                reject(event.target.error);
            };
        };
        
        request.onerror = function(event) {
            reject(event.target.error);
        };
    });
}

// Səhifələmə üçün funksiyalar
function paginateUsers(users, page, pageSize) {
    // Əgər pageSize -1-dirsə (Hamısını göstər), bütün istifadəçiləri qaytar
    if (pageSize === -1) {
        return users;
    }
    
    const startIndex = (page - 1) * pageSize;
    return users.slice(startIndex, startIndex + pageSize);
}

function updatePagination(totalUsers) {
    // "Hamısını göstər" (-1) seçildiyi halda səhifələməni gizlət
    if (PAGE_SIZE === -1) {
        const paginationContainer = document.getElementById('usersPagination');
        const paginationInfo = document.getElementById('paginationInfo');
        
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }
        
        if (paginationInfo) {
            paginationInfo.innerHTML = `<?php echo __('showing_all'); ?> ${totalUsers} <?php echo __('users'); ?>`;
        }
        return;
    }

    const totalPages = Math.ceil(totalUsers / PAGE_SIZE);
    const paginationContainer = document.getElementById('usersPagination');
    const paginationInfo = document.getElementById('paginationInfo');
    
    if (!paginationContainer) return;
    
    // Səhifələmə məlumatlarını göstəririk
    if (paginationInfo) {
        const startItem = totalUsers === 0 ? 0 : (currentPage - 1) * PAGE_SIZE + 1;
        const endItem = Math.min(currentPage * PAGE_SIZE, totalUsers);
        paginationInfo.innerHTML = `Showing ${startItem} to ${endItem} of ${totalUsers} users`;
    }
    
    let paginationHTML = '';
    
    // Əvvəlki səhifə düyməsi
    paginationHTML += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Səhifə nömrələri
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(1); return false;">1</a>
            </li>
        `;
        
        if (startPage > 2) {
            paginationHTML += `
                <li class="page-item disabled">
                    <a class="page-link" href="#">...</a>
                </li>
            `;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
            </li>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `
                <li class="page-item disabled">
                    <a class="page-link" href="#">...</a>
                </li>
            `;
        }
        
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a>
            </li>
        `;
    }
    
    // Sonrakı səhifə düyməsi
    paginationHTML += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    paginationContainer.innerHTML = paginationHTML;
}

// Səhifə dəyişdirmə funksiyası - filtrlənmiş məlumatları saxlayır
function changePage(page) {
    currentPage = page;
    
    // Əgər filtrlənmiş məlumatlar varsa, onları göstəririk
    if (filteredUsers.length > 0) {
        updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
    } else {
        updateUsersTable(paginateUsers(allUsers, currentPage, PAGE_SIZE));
    }
    
    // Səhifələmə məlumatlarını yeniləyirik
    updatePagination(filteredUsers.length > 0 ? filteredUsers.length : allUsers.length);
    
    // Scroll to top of the table
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.scrollTop = 0;
    }
}

// Cədvəl yeniləməsini optimallaşdırırıq
function updateUsersTable(users) {
    const tbody = document.getElementById('usersTable');
    
    if (!tbody) return;
    
    // DocumentFragment istifadə edərək DOM əməliyyatlarını optimallaşdırırıq
    const fragment = document.createDocumentFragment();
    
    if (users.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td colspan="7" class="text-center py-4">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo __('no_users_found'); ?>. <?php echo __('try_changing_your_search_criteria'); ?>
                </div>
            </td>
        `;
        fragment.appendChild(tr);
    } else {
    users.forEach((user, index) => {
        const tr = document.createElement('tr');
        tr.className = 'align-middle';
        
        // Template literal əvəzinə string concatenation istifadə edirik
        const statusBadge = user.locked ? 
            '<span class="badge bg-warning text-dark"><?php echo __('locked'); ?></span>' :
            (user.enabled ? 
                '<span class="badge bg-success"><?php echo __('active'); ?></span>' : 
                '<span class="badge bg-danger"><?php echo __('inactive'); ?></span>');
        
            // Cədvəl sətirini yaradırıq
        tr.innerHTML = [
                '<td class="fw-bold">' + ((currentPage - 1) * PAGE_SIZE + index + 1) + '</td>',
            '<td>' + escapeHtml(user.username) + '</td>',
            '<td class="text-truncate" style="max-width: 150px;" title="' + escapeHtml(user.ou) + '">' +
                escapeHtml(user.ou || '') + '</td>',
            '<td class="text-truncate" style="max-width: 150px;" title="' + escapeHtml(user.groups) + '">' +
                escapeHtml(user.groups || '') + '</td>',
            '<td>' + statusBadge + '</td>',
            '<td><span class="badge ' + getPasswordStatusBadgeClass(user.passwordStatus) + 
                ' text-white fw-normal px-3 py-2"><i class="fas ' + 
                getPasswordStatusIcon(user.passwordStatus) + ' me-1"></i>' + 
                escapeHtml(user.passwordStatus) + '</span></td>',
            '<td><div class="btn-group btn-group-sm">' +
                '<button onclick="showUserDetails(\'' + user.username + '\')" ' +
                'class="btn btn-outline-primary" title="<?php echo __('view_details'); ?>">' +
                '<i class="fas fa-info-circle"></i></button></div></td>'
        ].join('');
        
        fragment.appendChild(tr);
    });
    }
    
    // Cədvəli təmizləyib yeni məlumatları əlavə edirik
    tbody.innerHTML = '';
    tbody.appendChild(fragment);
}

// Password status helpers
function getPasswordStatusBadgeClass(status) {
    switch(true) {
        case /never expires/i.test(status): return 'bg-info';
        case /expired/i.test(status): return 'bg-danger';
        case /must change/i.test(status): return 'bg-secondary';
        case /(\d+) days left/.test(status):
            const days = parseInt(status);
            return days <= 5 ? 'bg-warning' : 'bg-success';
        default: return 'bg-secondary';
    }
}

function getPasswordStatusIcon(status) {
    switch(true) {
        case /never expires/i.test(status): return 'fa-infinity';
        case /expired/i.test(status): return 'fa-exclamation-circle';
        case /must change/i.test(status): return 'fa-key';
        case /(\d+) days left/.test(status):
            const days = parseInt(status);
            return days <= 5 ? 'fa-clock' : 'fa-check-circle';
        default: return 'fa-question-circle';
    }
}

// Stats and filtering
function updateStats(stats) {
    if (!stats) return;
    
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"] .stat-value`);
        if (element) {
            // Animasiya ilə yeniləyirik
            const currentValue = parseInt(element.textContent) || 0;
            const newValue = stats[key];
            
            if (currentValue !== newValue) {
                animateValue(element, currentValue, newValue, 500);
            }
        }
    });
}

// Rəqəmləri animasiya ilə yeniləmək üçün funksiya
function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        element.textContent = value;
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            element.textContent = end;
        }
    };
    window.requestAnimationFrame(step);
}

// Add function to load saved stats
function loadSavedStats() {
    const savedStats = localStorage.getItem('userStats');
    if (savedStats) {
        updateStats(JSON.parse(savedStats));
    }
}

function filterUsers(type) {
    // Get the title element
    const listTitle = document.querySelector('.page-title');
    
    // Update title based on filter type
    let titleText = '<?php echo __('users_list'); ?>';
    let titleIcon = 'fa-users';
    let titleColor = 'text-primary';
    
    switch(type) {
        case 'locked':
            titleText = '<?php echo __('locked_users'); ?>';
            titleIcon = 'fa-user-lock';
            titleColor = 'text-warning';
            break;
        case 'active':
            titleText = '<?php echo __('active_users'); ?>';
            titleIcon = 'fa-user-check';
            titleColor = 'text-success';
            break;
        case 'inactive':
            titleText = '<?php echo __('inactive_users'); ?>';
            titleIcon = 'fa-user-times';
            titleColor = 'text-danger';
            break;
        case 'expired':
            titleText = '<?php echo __('password_expired_users'); ?>';
            titleIcon = 'fa-key';
            titleColor = 'text-danger';
            break;
        case 'never_expires':
            titleText = '<?php echo __('never_expires_users'); ?>';
            titleIcon = 'fa-infinity';
            titleColor = 'text-info';
            break;
        case 'must_change':
            titleText = '<?php echo __('must_change_password_users'); ?>';
            titleIcon = 'fa-key';
            titleColor = 'text-secondary';
            break;
        default:
            titleText = '<?php echo __('all_users'); ?>';
            titleIcon = 'fa-users';
            titleColor = 'text-primary';
    }
    
    // Update the title HTML
    if (listTitle) {
        listTitle.innerHTML = `<i class="fas ${titleIcon} me-2"></i>${titleText}`;
        listTitle.className = `mb-0 ${titleColor} page-title`;
    }

    // Yükləmə göstəricisini əlavə edirik
    const tbody = document.getElementById('usersTable');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden"><?php echo __('loading'); ?>...</span>
                        </div>
                        <span><?php echo __('filtering_users'); ?></span>
                    </div>
                </td>
            </tr>
        `;
    }

    // Əgər keşdə məlumat varsa, birbaşa filtrlə
    if (cachedUsers && cachedUsers.users && cachedUsers.users.length > 0) {
        filterCachedUsers(type);
        return;
    }

    // Keşdə məlumat yoxdursa, serverdən yüklə
    fetch('api/users.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('<?php echo __('network_response_was_not_ok'); ?>');
            }
            return response.json();
        })
        .then(data => {
            // Keşə yazırıq
            cachedUsers = data;
            lastFetchTime = Date.now();
            allUsers = data.users;
            
            filterCachedUsers(type);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', '<?php echo __('failed_to_filter_users'); ?>');
            
            // Xəta halında cədvəli təmizləyirik
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo __('error_loading_users'); ?>: ${error.message}
                            </div>
                        </td>
                    </tr>
                `;
            }
        });
}

// Keşdəki məlumatları filtrlə
function filterCachedUsers(type) {
    currentFilterType = type;
            
            switch(type) {
                case 'locked':
            filteredUsers = allUsers.filter(user => user.locked);
                    break;
                case 'active':
            filteredUsers = allUsers.filter(user => user.enabled && !user.locked);
                    break;
                case 'inactive':
            filteredUsers = allUsers.filter(user => !user.enabled);
                    break;
                case 'expired':
            filteredUsers = allUsers.filter(user => user.passwordStatus === 'Expired');
                    break;
                case 'never_expires':
            filteredUsers = allUsers.filter(user => user.passwordStatus === 'Never Expires');
                    break;
                case 'must_change':
            filteredUsers = allUsers.filter(user => user.passwordStatus === 'Must Change');
                    break;
        default:
                // 'total' halında bütün istifadəçiləri göstəririk
            filteredUsers = [...allUsers];
    }
    
    // Əgər axtarış mətni varsa, filtrlənmiş məlumatları daha da filtrlə
    if (currentSearchText) {
        applySearchFilter(currentSearchText);
    }
    
    // Səhifələmə üçün ilk səhifəyə qayıdırıq
    currentPage = 1;
    
    // Cədvəli yeniləyirik
    updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
    updatePagination(filteredUsers.length);
            
            // Aktiv filtri vizual olaraq göstəririk
            document.querySelectorAll('.card[data-stat]').forEach(card => {
                card.classList.remove('border-primary');
            });
    
    const activeFilter = document.querySelector(`.card[data-stat="${type}"]`);
    if (activeFilter) {
        activeFilter.classList.add('border-primary');
    }
    
    // Axtarış nəticələrini göstəririk
    updateSearchResults(filteredUsers.length, allUsers.length);
}

// Search functionality - optimallaşdırılmış
function handleSearch(searchText) {
    currentSearchText = searchText.toLowerCase();
    
    // Əgər axtarış mətni boşdursa və filter yoxdursa, bütün istifadəçiləri göstər
    if (!currentSearchText && currentFilterType === 'total') {
        filteredUsers = [...allUsers];
        currentPage = 1;
        updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
        updatePagination(filteredUsers.length);
        updateSearchResults(filteredUsers.length, allUsers.length);
        return;
    }
    
    // Əvvəlcə filter tətbiq edirik (əgər varsa)
    if (currentFilterType !== 'total') {
        filterCachedUsers(currentFilterType);
    } else {
        filteredUsers = [...allUsers];
    }
    
    // Sonra axtarış filtri tətbiq edirik
    if (currentSearchText) {
        applySearchFilter(currentSearchText);
    }
    
    // Nəticələri göstər
    currentPage = 1; // Axtarış zamanı ilk səhifəyə qayıdırıq
    updateUsersTable(paginateUsers(filteredUsers, currentPage, PAGE_SIZE));
    updatePagination(filteredUsers.length);
    updateSearchResults(filteredUsers.length, allUsers.length);
}

// Axtarış filtrini tətbiq etmək üçün ayrı funksiya
function applySearchFilter(searchText) {
    const searchType = document.getElementById('searchType').value;

    filteredUsers = filteredUsers.filter(user => {
        let found = false;

        switch(searchType) {
            case 'username':
                found = user.username.toLowerCase().includes(searchText);
                break;
            case 'displayName':
                found = (user.displayName || '').toLowerCase().includes(searchText);
                break;
            case 'department':
                found = (user.department || '').toLowerCase().includes(searchText);
                break;
            case 'ou':
                found = (user.ou || '').toLowerCase().includes(searchText);
                break;
            case 'groups':
                found = (user.groups || '').toLowerCase().includes(searchText);
                break;
            default:
                // Bütün sahələrdə axtarış
                found = user.username.toLowerCase().includes(searchText) ||
                       (user.displayName || '').toLowerCase().includes(searchText) ||
                       (user.department || '').toLowerCase().includes(searchText) ||
                       (user.ou || '').toLowerCase().includes(searchText) ||
                       (user.groups || '').toLowerCase().includes(searchText);
        }
        
        return found;
    });
}

// Helper functions
function updateFilterVisuals(filterType) {
    document.querySelectorAll('[data-stat]').forEach(card => {
        card.classList.toggle('bg-gray-700', card.getAttribute('data-stat') === filterType);
    });
}

function updateFilterResults(rows) {
    const visibleCount = Array.from(rows).filter(row => row.style.display !== 'none').length;
    updateSearchResults(visibleCount, rows.length);
}

function updateSearchResults(visibleCount, totalCount) {
    const resultsDiv = document.getElementById('searchResults');
    if (visibleCount === totalCount) {
        resultsDiv.textContent = '';
    } else if (visibleCount === 0) {
        resultsDiv.innerHTML = `
            <div class="alert alert-info py-2 d-flex align-items-center" role="alert">
                <i class="fas fa-search me-2"></i>
                <span><?php echo __('no_results_found'); ?>. <?php echo __('try_changing_your_search_parameters'); ?></span>
            </div>`;
    } else {
        resultsDiv.innerHTML = `
            <div class="badge bg-primary text-white p-2">
                <i class="fas fa-check-circle me-1"></i> <?php echo __('found'); ?> ${visibleCount} <?php echo __('results'); ?>
            </div>`;
    }
}

function escapeHtml(unsafe) {
    return unsafe
        ? unsafe.replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[char])
        : '';
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function loadStats() {
    // Əvvəlcə keşdə statistikaları yükləyirik
    if (cachedUsers && cachedUsers.stats) {
        updateStats(cachedUsers.stats);
        return;
    }
    
    // Keşdə yoxdursa, serverdən yükləyirik
    fetch('api/users.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('<?php echo __('network_response_was_not_ok'); ?>');
            }
            return response.json();
        })
        .then(data => {
            if (data.stats) {
                updateStats(data.stats);
                // Save stats to localStorage for faster initial load
                localStorage.setItem('userStats', JSON.stringify(data.stats));
                
                // Keşə yazırıq
                if (!cachedUsers) {
                    cachedUsers = { users: [], stats: data.stats };
                } else {
                    cachedUsers.stats = data.stats;
                }
            }
        })
        .catch(error => {
            console.error('<?php echo __('error_loading_stats'); ?>:', error);
            
            // Xəta halında localStorage-dən yükləyirik
            const savedStats = localStorage.getItem('userStats');
            if (savedStats) {
                updateStats(JSON.parse(savedStats));
            }
        });
}

// Add new user-specific functions
function createNewUser() {
    if (!validateNewUserForm()) {
        return;
    }

    const userData = {
        firstname: document.getElementById('new_firstname').value,
        lastname: document.getElementById('new_lastname').value,
        username: document.getElementById('new_username').value,
        password: document.getElementById('new_password').value,
        email: document.getElementById('new_email').value,
        displayname: document.getElementById('new_displayname').value,
        ou: document.getElementById('new_ou').value,
        account_options: {
            must_change_password: document.getElementById('new_must_change_password').checked,
            password_never_expires: document.getElementById('new_password_never_expires').checked
        },
        groups: [] // Initialize empty groups array
    };

    // Get selected groups if the groups select element exists
    const groupsSelect = document.getElementById('new_groups');
    if (groupsSelect) {
        Array.from(groupsSelect.selectedOptions).forEach(option => {
            userData.groups.push(option.value);
        });
    }

    // Show loading state
    const createButton = document.querySelector('#newUserModal .modal-footer .btn-primary');
    const originalContent = createButton.innerHTML;
    createButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?php echo __('creating'); ?>...';
    createButton.disabled = true;

    // Show confirmation dialog
    showConfirmDialog(
        '<?php echo __('create_new_user'); ?>',
        `<?php echo __('are_you_sure_you_want_to_create_user'); ?> "${userData.username}"?`,
        {
            steps: [
                {icon: 'fa-user-plus', text: '<?php echo __('create_new_user_account'); ?>'},
                {icon: 'fa-key', text: '<?php echo __('set_initial_password'); ?>'},
                {icon: 'fa-users', text: userData.groups && userData.groups.length ? '<?php echo __('add_to_selected_groups'); ?>' : '<?php echo __('skip_group_assignment'); ?>'},
                {icon: 'fa-check', text: '<?php echo __('apply_account_settings'); ?>'}
            ]
        },
        'Create User',
        'fa-user-plus'
    ).then((result) => {
        if (result.isConfirmed) {
            // Send to API directly without showing loading modal
            fetch('api/user-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create_user',
                    ...userData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', '<?php echo __('user_created_successfully'); ?>');
                    bootstrap.Modal.getInstance(document.getElementById('newUserModal')).hide();
                    loadUsers();
                } else {
                    throw new Error(data.error || '<?php echo __('failed_to_create_user'); ?>');
                }
            })
            .catch(error => {
                showToast('error', error.message);
            });
        }
    });
}

function showUserDetails(username) {
    // First check if modal exists
    let userModal = document.getElementById('userModal');
    if (!userModal) {
        console.error('<?php echo __('modal_element_not_found'); ?>');
        return;
    }

    // Get modal components
    const modal = new bootstrap.Modal(userModal);
    const modalBody = userModal.querySelector('.modal-body');
    const modalTitle = userModal.querySelector('.modal-title');

    // Show loading state
    modalBody.innerHTML = `
        <div class="d-flex justify-content-center align-items-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"><?php echo __('loading'); ?>...</span>
            </div>
            <span class="ms-3"><?php echo __('loading_user_details'); ?>...</span>
        </div>
    `;

    // Show modal
    modal.show();

    // Fetch user details
    fetch('api/user-details.php?username=' + encodeURIComponent(username))
        .then(response => response.json())
        .then(data => {
            modalTitle.innerHTML = `
                <i class="fas fa-user-circle me-2"></i>
                <?php echo __('user_details'); ?> - ${escapeHtml(data.displayName || username)}
            `;
            
            // Action buttons array - dinamik olaraq düymələri əlavə edirik
            const actionButtons = [
                `<button class="btn btn-primary btn-sm" onclick="showEditModal('${escapeHtml(username)}')">
                    <i class="fas fa-edit me-1"></i> <?php echo __('edit'); ?>
                </button>`
            ];

            // Kilidlənmiş istifadəçi üçün kiliddən çıxartma düyməsi
            if (data.locked) {
                actionButtons.push(`
                    <button class="btn btn-warning btn-sm" onclick="unlockUser('${escapeHtml(username)}')">
                        <i class="fas fa-unlock me-1"></i> <?php echo __('unlock'); ?>
                    </button>
                `);
            }

            // Aktivləşdirmə/Deaktivləşdirmə düyməsi - enabled statusuna görə
            if (data.enabled) {
                actionButtons.push(`
                    <button class="btn btn-danger btn-sm" onclick="deactivateUser('${escapeHtml(username)}')">
                        <i class="fas fa-user-times me-1"></i> <?php echo __('deactivate'); ?>
                    </button>
                `);
            } else {
                actionButtons.push(`
                    <button class="btn btn-success btn-sm" onclick="activateUser('${escapeHtml(username)}')">
                        <i class="fas fa-user-check me-1"></i> <?php echo __('activate'); ?>
                    </button>
                `);
            }

            // Digər standart düymələr
            actionButtons.push(`
         
                    <button class="btn btn-info btn-sm text-white" onclick="resetPassword('${escapeHtml(username)}')">
                    <i class="fas fa-key me-1"></i> <?php echo __('reset_password'); ?>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="changeOU('${escapeHtml(username)}')">
                    <i class="fas fa-sitemap me-1"></i> <?php echo __('change_ou'); ?>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="manageGroups('${escapeHtml(username)}')">
                    <i class="fas fa-users me-1"></i> <?php echo __('manage_groups'); ?>
                </button>
               
                <button class="btn btn-danger btn-sm" onclick="deleteUser('${escapeHtml(username)}')">
                    <i class="fas fa-trash-alt me-1"></i> <?php echo __('delete'); ?>
                </button>
            `);
            
            modalBody.innerHTML = `
                <div class="row g-3">
                    <!-- Action Buttons -->
                    <div class="col-12 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex flex-wrap gap-2">
                                ${actionButtons.join('')}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('basic_information'); ?></h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4"><?php echo __('username'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(data.username)}</dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('display_name'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(data.displayName || '-')}</dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('department'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(data.department || '-')}</dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('title'); ?></dt>
                                    <dd class="col-sm-8">${escapeHtml(data.title || '-')}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Status -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('account_status'); ?></h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4"><?php echo __('status'); ?></dt>
                                    <dd class="col-sm-8">
                                        ${data.locked ? 
                                                '<span class="badge bg-warning text-dark"> <?php echo __('locked'); ?></span>' :
                                            `<span class="badge ${data.enabled ? 'bg-success' : 'bg-danger'}">
                                                ${data.enabled ? 'Active' : 'Inactive'}
                                            </span>`
                                        }
                                    
                                    </dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('password'); ?></dt>
                                    <dd class="col-sm-8">
                                        <span class="badge ${getPasswordStatusBadgeClass(data.passwordStatus)}">
                                            <i class="fas ${getPasswordStatusIcon(data.passwordStatus)} me-1"></i>
                                            ${escapeHtml(data.passwordStatus)}
                                        </span>
                                    </dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('last_logon'); ?></dt>
                                    <dd class="col-sm-8 text-dark">${escapeHtml(data.lastLogon)}</dd>
                                    
                                    <dt class="col-sm-4"><?php echo __('created_date'); ?></dt>
                                    <dd class="col-sm-8 text-dark">${escapeHtml(data.created)}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Organization -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0"><i class="fas fa-sitemap me-2"></i>Organization</h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">OU Path</dt>
                                    <dd class="col-sm-8">${escapeHtml(data.ou)}</dd>
                                    
                                    <dt class="col-sm-4">Groups</dt>
                                    <dd class="col-sm-8">
                                        <div class="d-flex flex-wrap gap-1">
                                            ${data.groups ? data.groups.split(',').map(group => 
                                                `<span class="badge bg-info">${escapeHtml(group.trim())}</span>`
                                            ).join('') : '-'}
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0"><i class="fas fa-phone-alt me-2"></i>Contact</h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Email</dt>
                                    <dd class="col-sm-8">
                                        ${data.email ? `<a href="mailto:${escapeHtml(data.email)}" class="text-primary">
                                            <i class="fas fa-envelope me-1"></i>${escapeHtml(data.email)}</a>` : '-'}
                                    </dd>
                                    
                                    <dt class="col-sm-4">Phone</dt>
                                    <dd class="col-sm-8">
                                        ${data.phone ? `<a href="tel:${escapeHtml(data.phone)}" class="text-primary">
                                            <i class="fas fa-phone me-1"></i>${escapeHtml(data.phone)}</a>` : '-'}
                                    </dd>
                                    
                                    <dt class="col-sm-4">Mobile</dt>
                                    <dd class="col-sm-8">
                                        ${data.mobile ? `<a href="tel:${escapeHtml(data.mobile)}" class="text-primary">
                                            <i class="fas fa-mobile-alt me-1"></i>${escapeHtml(data.mobile)}</a>` : '-'}
                                    </dd>

                                    <dt class="col-sm-4">Description</dt>
                                    <dd class="col-sm-8">
                                        ${data.description ? `<div class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <small>${escapeHtml(data.description)}</small>
                                        </div>` : '-'}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading user details: ${escapeHtml(error.message)}
                </div>
            `;
            console.error('Error:', error);
        });
}

// Password options state
let passwordOptions = {
    never_expires: false,
    must_change: false
};

function showEditModal(username) {
    // Close details modal if open
    const userModal = document.getElementById('userModal');
    if (userModal) {
        const bsUserModal = bootstrap.Modal.getInstance(userModal);
        if (bsUserModal) {
            bsUserModal.hide();
        }
    }
    
    // First get password status
    fetch('api/user-password-status.php?username=' + encodeURIComponent(username))
        .then(response => response.json())
        .then(statusData => {
            if (statusData.success) {
                // Update password options state
                passwordOptions.never_expires = statusData.password_status.never_expires;
                passwordOptions.must_change = statusData.password_status.must_change;
            }
            
            // Then get user details
            return fetch('api/user-details.php?username=' + encodeURIComponent(username));
        })
        .then(response => response.json())
        .then(user => {
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            const form = document.getElementById('editUserForm');
            
            // Fill form with current values
            form.querySelector('#edit_username').value = user.username || '';
            form.querySelector('#edit_displayname').value = user.displayName || '';
            form.querySelector('#edit_email').value = user.email || '';
            form.querySelector('#edit_title').value = user.title || '';
            form.querySelector('#edit_phone').value = user.phone || '';
            form.querySelector('#edit_mobile').value = user.mobile || '';
            form.querySelector('#edit_department').value = user.department || '';
            form.querySelector('#edit_description').value = user.description || '';
            
            // Update password option buttons
            updatePasswordOptionButtons();
            
            modal.show();
        })
        .catch(error => {
            console.error('Error loading user details:', error);
            showToast('error', 'Failed to load user details: ' + error.message);
        });
}

function updatePasswordOptionButtons() {
    const neverExpiresBtn = document.getElementById('btnNeverExpires');
    const mustChangeBtn = document.getElementById('btnMustChange');
    
    if (neverExpiresBtn) {
        neverExpiresBtn.innerHTML = `
            <i class="fas fa-clock me-1"></i>
            ${passwordOptions.never_expires ? 'Disable' : 'Enable'}
        `;
    }
    
    if (mustChangeBtn) {
        mustChangeBtn.innerHTML = `
            <i class="fas fa-key me-1"></i>
            ${passwordOptions.must_change ? 'Disable' : 'Enable'}
        `;
    }
}

function togglePasswordOption(option) {
    const username = document.getElementById('edit_username').value;
    const newValue = option === 'never_expires' ? 
        !passwordOptions.never_expires : 
        !passwordOptions.must_change;
    
    fetch('api/user-password-options.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            username: username,
            action: option,
            value: newValue
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (option === 'never_expires') {
                passwordOptions.never_expires = newValue;
            } else {
                passwordOptions.must_change = newValue;
            }
            updatePasswordOptionButtons();
            showToast('success', '<?php echo __('password_option_updated_successfully'); ?>');
        } else {
            throw new Error(result.error || '<?php echo __('failed_to_update_password_option'); ?>');
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        showToast('error', '<?php echo __('failed_to_update_password_option'); ?>: ' + error.message);
    });
}

function saveUserEdit() {
    const form = document.getElementById('editUserForm');
    const data = {
        username: form.querySelector('#edit_username').value,
        displayName: form.querySelector('#edit_displayname').value.trim(),
        email: form.querySelector('#edit_email').value.trim(),
        title: form.querySelector('#edit_title').value.trim(),
        phone: form.querySelector('#edit_phone').value.trim(),
        mobile: form.querySelector('#edit_mobile').value.trim(),
        department: form.querySelector('#edit_department').value.trim(),
        description: form.querySelector('#edit_description').value.trim()
    };

    // Debug logging əmrini silirik
    // console.log('Sending update data:', data);
    
    // Yükləmə göstəricisini göstəririk
    const saveButton = document.querySelector('button[onclick="saveUserEdit()"]');
    const originalButtonContent = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        <?php echo __('saving'); ?>...
    `;

    fetch('api/user-edit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        return response.text().then(text => {
            try {
                // Əvvəlcə JSON kimi parse etməyə çalışırıq
                const jsonData = JSON.parse(text);
                return jsonData;
            } catch (e) {
                // Əgər JSON deyilsə, xəta mesajını göstəririk
                // console.error('Invalid JSON response:', text); - Bu sətri də silirik
                
                // Əgər cavabda JSON hissəsi varsa, onu çıxarmağa çalışırıq
                const jsonMatch = text.match(/(\{.*\})/);
                if (jsonMatch && jsonMatch[0]) {
                    try {
                        const extractedJson = JSON.parse(jsonMatch[0]);
                        // console.log('Extracted JSON from response:', extractedJson); - Bu sətri də silirik
                        return extractedJson;
                    } catch (innerError) {
                        // console.error('Failed to extract JSON from response'); - Bu sətri də silirik
                    }
                }
                
                throw new Error('<?php echo __('invalid_response_from_server'); ?>');
            }
        });
    })
    .then(result => {
        // console.log('Update response:', result); - Bu sətri də silirik
        
        // Düyməni normal vəziyyətə qaytarırıq
        saveButton.disabled = false;
        saveButton.innerHTML = originalButtonContent;
        
        if (result.success) {
            showToast('success', '<?php echo __('user_updated_successfully'); ?>');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            if (modal) {
                modal.hide();
            }
            loadUsers();
        } else {
            throw new Error(result.error || '<?php echo __('failed_to_update_user'); ?>');
        }
    })
    .catch(error => {
        // console.error('Update error:', error); - Bu sətri də silirik
        
        // Düyməni normal vəziyyətə qaytarırıq
        saveButton.disabled = false;
        saveButton.innerHTML = originalButtonContent;
        
        showToast('error', '<?php echo __('failed_to_update_user'); ?>: ' + error.message);
    });
}

function updateUser(username, attributes) {
    fetch('api/get-user-edit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            username: username,
            attributes: attributes
        })
    })
    .then(response => {
        // Debug: Log raw response
        console.log('Raw response:', response);
        return response.json();
    })
    .then(data => {
        // Debug: Log parsed data
        console.log('Parsed response:', data);
        
        if (data.success) {
            showToast('success', '<?php echo __('user_updated_successfully'); ?>');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            loadUsers();
        } else {
            throw new Error(data.error || '<?php echo __('failed_to_update_user'); ?>');
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        showToast('error', '<?php echo __('failed_to_update_user'); ?>: ' + error.message);
    });
}

// Standart bildiriş funksiyaları
function showConfirmDialog(title, message, icon, confirmText, confirmIcon, confirmColor = '#198754') {
    return Swal.fire({
        title: title,
        html: `<div class="text-center">
                <p class="mb-2">${message}</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo __('this_operation_will'); ?>:
                </div>
                <ul class="text-start text-muted small">
                    ${Array.isArray(icon.steps) ? icon.steps.map(step => 
                        `<li><i class="fas ${step.icon} text-success me-2"></i>${step.text}</li>`
                    ).join('') : ''}
                </ul>
               </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: `<i class="fas ${confirmIcon} me-2"></i>${confirmText}`,
        cancelButtonText: '<i class="fas fa-times me-2"></i><?php echo __('cancel'); ?>',
        reverseButtons: true,
        allowOutsideClick: false,
        showClass: {
            popup: 'animate__animated animate__fadeInDown animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp animate__faster'
        }
    });
}

function showActionResult(title, message, icon, username, action) {
    return Swal.fire({
        title: title,
        html: `<div class="text-center">
                <i class="fas ${icon} ${icon.includes('times') ? 'text-danger' : 'text-success'} fa-3x mb-3"></i>
                <p class="mb-2">User <strong>${username}</strong> has been ${message}.</p>
                <div class="alert alert-${icon.includes('times') ? 'danger' : 'success'}">
                    <i class="fas fa-${icon.includes('times') ? 'times' : 'check'}-circle me-2"></i>
                    ${action}
                </div>
               </div>`,
        icon: icon.includes('times') ? 'error' : 'success',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true,
        allowOutsideClick: false,
        showClass: {
            popup: 'animate__animated animate__fadeIn animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOut animate__faster'
        }
    });
}

// Aktivləşdirmə funksiyası
function activateUser(username) {
    showConfirmDialog(
        '<?php echo __('activate_user'); ?>',
        `<?php echo __('are_you_sure_you_want_to_activate_user'); ?> <strong>${username}</strong>?`,
        {
            steps: [
                { icon: 'fa-check-circle', text: '<?php echo __('enable_user_login'); ?>' },
                { icon: 'fa-users', text: '<?php echo __('restore_access_to_resources'); ?>' },
                { icon: 'fa-shield-alt', text: '<?php echo __('enable_account_features'); ?>' }
            ]
        },
        '<?php echo __('yes_activate'); ?>',
        'fa-user-check',
        '#198754'
    ).then((result) => {
        if (result.isConfirmed) {
            handleUserAction('activate', username);
        }
    });
}

// Kiliddən çıxartma funksiyası
function unlockUser(username) {
    showConfirmDialog(
        '<?php echo __('unlock_user_account'); ?>',
        `<?php echo __('are_you_sure_you_want_to_unlock_user'); ?> <strong>${username}</strong>?`,
        {
            steps: [
                { icon: 'fa-unlock', text: '<?php echo __('remove_account_lockout'); ?>' },
                { icon: 'fa-sign-in-alt', text: '<?php echo __('allow_user_to_log_in_again'); ?>' },
                { icon: 'fa-redo', text: '<?php echo __('reset_failed_login_attempts'); ?>' }
            ]
        },
        '<?php echo __('yes_unlock'); ?>',
        'fa-unlock',
        '#ffc107'
    ).then((result) => {
        if (result.isConfirmed) {
            handleUserAction('unlock', username);
        }
    });
}

// API sorğularını idarə edən funksiya
function handleUserAction(action, username) {
    fetch('api/user-action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, username: username })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const actions = {
                activate: {
                    title: '<?php echo __('user_activated'); ?>',
                    message: '<?php echo __('successfully_activated'); ?>',
                    icon: 'fa-user-check',
                    action: '<?php echo __('user_can_now_access_the_system'); ?>'
                },
                unlock: {
                    title: '<?php echo __('account_unlocked'); ?>',
                    message: '<?php echo __('successfully_unlocked'); ?>',
                    icon: 'fa-unlock-alt',
                    action: '<?php echo __('user_can_now_log_in_to_their_account'); ?>'
                }
                // Digər əməliyyatlar üçün də əlavə edilə bilər
            };

            const currentAction = actions[action];
            showActionResult(
                currentAction.title,
                currentAction.message,
                currentAction.icon,
                username,
                currentAction.action
            ).then(() => {
                loadUsers();
                loadStats();
                const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
                if (modal) modal.hide();
            });
        } else {
            throw new Error(data.error || `Failed to ${action} user`);
        }
    })
    .catch(error => {
        showActionResult(
            '<?php echo __('error'); ?>',
            `<?php echo __('not'); ?> ${action}d`,
            'fa-times-circle',
            username,
            error.message
        );
    });
}

function deactivateUser(username) {
    Swal.fire({
        title: '<?php echo __('user_deactivation'); ?>',
        html: `<div class="text-center">
                <p class="mb-2"><?php echo __('are_you_sure_you_want_to_deactivate_user'); ?> <strong>${username}</strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo __('this_operation_will'); ?>:
                </div>
                <ul class="text-start text-muted small">
                    <li><i class="fas fa-user-slash text-warning me-2"></i><?php echo __('prevent_user_from_logging_in'); ?></li>
                    <li><i class="fas fa-users text-warning me-2"></i><?php echo __('suspend_group_memberships'); ?></li>
                    <li><i class="fas fa-shield-alt text-warning me-2"></i><?php echo __('disable_network_resource_access'); ?></li>
                </ul>
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-user-slash me-2"></i><?php echo __('yes_deactivate'); ?>',
        cancelButtonText: '<i class="fas fa-times me-2"></i><?php echo __('cancel'); ?>',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Close user details modal if open
            const userModal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
            if (userModal) {
                userModal.hide();
            }

            Swal.fire({
                title: '<?php echo __('deactivating_user'); ?>',
                html: `<div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-shield text-warning fa-2x mb-3"></i>
                            <p class="mb-0"><?php echo __('please_wait'); ?></p>
                            <p class="text-muted small"><?php echo __('deactivating_user'); ?>...</p>
                        </div>
                      </div>`,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('api/user-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'deactivate',
                    username: username
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            throw new Error(data.error || '<?php echo __('network_error_occurred'); ?>');
                        } catch (e) {
                            throw new Error(text || '<?php echo __('network_error_occurred'); ?>');
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '<?php echo __('user_deactivated'); ?>',
                        html: `<div class="text-center">
                                <div class="mb-3">
                                    <i class="fas fa-user-slash text-warning fa-3x mb-3"></i>
                                    <p class="mb-0"><?php echo __('user'); ?> <strong>${username}</strong> <?php echo __('has_been_successfully_deactivated'); ?></p>
                                    <p class="text-muted small"><?php echo __('all_access_permissions_have_been_suspended'); ?></p>
                                </div>
                              </div>`,
                        showConfirmButton: false,
                        timer: 2000
                    });
                    loadUsers();
                } else {
                    throw new Error(data.error || '<?php echo __('deactivation_error_occurred'); ?>');
                }
            })
            .catch(error => {
                console.error('Deactivation error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo __('error'); ?>',
                    html: `<div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo __('deactivation_error'); ?>: ${error.message}
                          </div>`,
                    confirmButtonText: '<?php echo __('close'); ?>',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function deleteUser(username) {
    Swal.fire({
        title: '<?php echo __('are_you_sure_you_want_to_delete_user'); ?>',
        html: `<div class="text-center">
                <p class="mb-2"><?php echo __('user'); ?> <strong>${username}</strong> <?php echo __('will_be_deleted'); ?>.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo __('this_action_cannot_be_undone'); ?>!
                </div>
                <ul class="text-start text-muted small">
                    <li><?php echo __('user_account_will_be_permanently_deleted'); ?></li>
                    <li><?php echo __('all_group_memberships_will_be_removed'); ?></li>
                    <li><?php echo __('all_associated_data_will_be_deleted'); ?></li>
                </ul>
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Close user details modal if open
            const userModal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
            if (userModal) {
                userModal.hide();
            }

            // Show loading state
            Swal.fire({
                title: '<?php echo __('deleting_user'); ?>',
                html: '<?php echo __('please_wait_while_we_process_your_request'); ?>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send delete request
            fetch('api/user-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    username: username
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            throw new Error(data.error || '<?php echo __('network_response_was_not_ok'); ?>');
                        } catch (e) {
                            throw new Error(text || '<?php echo __('network_response_was_not_ok'); ?>');
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '<?php echo __('success'); ?>',
                        text: `<?php echo __('user'); ?> ${username} <?php echo __('has_been_successfully_deleted'); ?>`,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    loadUsers();
                } else {
                    throw new Error(data.error || '<?php echo __('failed_to_delete_user'); ?>');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);  
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo __('error'); ?>',
                    text: `<?php echo __('failed_to_delete_user'); ?>: ${error.message}`,
                    confirmButtonText: '<?php echo __('close'); ?>',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function showToast(type, message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    const icons = {
        'success': 'success',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    };

    Toast.fire({
        icon: icons[type] || 'info',
        title: message,
        background: type === 'error' ? '#fff5f5' : '#f8f9fa',
        color: type === 'error' ? '#dc3545' : '#212529'
    });
}

/**
 * Shows a loading modal with a spinner and message
 * @param {string} title - The title of the loading modal
 * @param {string} message - The message to display in the loading modal
 * @returns {Object} - An object with a close method to close the modal
 */
function showLoadingModal(title, message) {
    // Create modal element
    const modalElement = document.createElement('div');
    modalElement.className = 'modal fade';
    modalElement.id = 'loadingModal';
    modalElement.setAttribute('data-bs-backdrop', 'static');
    modalElement.setAttribute('data-bs-keyboard', 'false');
    modalElement.setAttribute('tabindex', '-1');
    modalElement.setAttribute('aria-labelledby', 'loadingModalLabel');
    modalElement.setAttribute('aria-hidden', 'true');
    
    // Set modal content
    modalElement.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loadingModalLabel">${escapeHtml(title)}</h5>
                </div>
                <div class="modal-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden"><?php echo __('loading'); ?>...</span>
                        </div>
                        <p class="mb-0"><?php echo __('loading'); ?>...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add to document
    document.body.appendChild(modalElement);
    
    // Initialize and show modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Return object with close method
    return {
        close: function() {
            modal.hide();
            // Remove modal element after hidden
            modalElement.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modalElement);
            });
        }
    };
}

function showNewUserModal() {
    const modal = new bootstrap.Modal(document.getElementById('newUserModal'));
    
    // Clear form
    const form = document.getElementById('newUserForm');
    if (form) {
        form.reset();
    }
    
    // Set default values
    const displayNameField = document.getElementById('new_displayname');
    if (displayNameField) {
        displayNameField.value = '';
    }
    
    // Add event listeners for auto-updating display name
    const firstNameInput = document.getElementById('new_firstname');
    const lastNameInput = document.getElementById('new_lastname');
    
    if (firstNameInput && lastNameInput) {
        // Remove existing event listeners to prevent duplicates
        firstNameInput.removeEventListener('input', updateDisplayName);
        lastNameInput.removeEventListener('input', updateDisplayName);
        
        // Add fresh event listeners
        firstNameInput.addEventListener('input', updateDisplayName);
        lastNameInput.addEventListener('input', updateDisplayName);
    }
    
    // Load OUs and show modal
    loadOUsAndGroups();
    modal.show();
}

function updatePasswordOptions(username, options) {
    const data = {
        username: username,
        never_expires: options.neverExpires,
        must_change: options.mustChange
    };

    fetch('api/user-edit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', '<?php echo __('user_updated_successfully'); ?>');
            if (data.details) {
                console.log('<?php echo __('update_details'); ?>:', data.details);
            }
        } else {
            showToast('error', data.error || '<?php echo __('failed_to_update_user'); ?>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', '<?php echo __('failed_to_update_user'); ?>');
    });
}

// Usage in your existing code:
const neverExpiresCheck = document.getElementById('neverExpiresCheck');
if (neverExpiresCheck) {
    neverExpiresCheck.addEventListener('change', function(e) {
        const mustChangeCheck = document.getElementById('mustChangeCheck');
    updatePasswordOptions(currentUsername, {
        neverExpires: e.target.checked,
            mustChange: mustChangeCheck ? mustChangeCheck.checked : false
    });
});
}

const mustChangeCheck = document.getElementById('mustChangeCheck');
if (mustChangeCheck) {
    mustChangeCheck.addEventListener('change', function(e) {
        const neverExpiresCheck = document.getElementById('neverExpiresCheck');
    updatePasswordOptions(currentUsername, {
            neverExpires: neverExpiresCheck ? neverExpiresCheck.checked : false,
        mustChange: e.target.checked
    });
});
}

async function resetPassword(username) {
    try {
        // Get default password from config first
        const configResponse = await fetch('api/get-config.php');
        const config = await configResponse.json();
        const defaultPassword = config.password_settings.default_temp_password;

        // Əvvəlcə user details modalını bağlayırıq
        const userModal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
        if (userModal) {
            userModal.hide();
        }

        await new Promise(resolve => setTimeout(resolve, 300));

        // Şifrə seçimi üçün dialog
        const passwordChoice = await Swal.fire({
            title: '<?php echo __('reset_password'); ?>',
            html: `
                <div class="mb-3">
                    <label class="form-label"><?php echo __('password_reset_method'); ?>:</label>
                    <div class="d-flex gap-2 flex-column">
                        <button type="button" class="btn btn-outline-secondary" onclick="Swal.clickConfirm()">
                            <i class="fas fa-keyboard me-2"></i><?php echo __('manual_entry_password'); ?>
                        </button>
                        <button type="button" class="btn btn-outline-warning" onclick="Swal.clickDeny()">
                            <i class="fas fa-key me-2"></i><?php echo __('must_change_at_next_logon'); ?>: ${defaultPassword}
                        </button>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showDenyButton: false,
            showCloseButton: true,
            allowOutsideClick: false
        });

        if (passwordChoice.dismiss === Swal.DismissReason.close) {
            return;
        }

        // Must Change at Next Logon seçilibsə
        if (passwordChoice.isDenied) {
            const response = await fetch('api/user-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'reset_password',
                    username: username,
                    use_default_password: true,
                    must_change: true
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || '<?php echo __('failed_to_reset_password'); ?>');
            }

            Swal.fire({
                title: '<?php echo __('success'); ?>',
                text: '<?php echo __('password_has_been_reset_and_user_must_change_it_at_next_logon'); ?>',
                icon: 'success'
            });
            return;
        }

        // Manual Entry
        if (passwordChoice.isConfirmed) {
            const manualEntry = await Swal.fire({
                title: '<?php echo __('enter_new_password'); ?>',
                html: `
                    <div class="mb-3">
                        <input type="password" id="newPassword" class="form-control mb-2" placeholder="Enter new password">
                        <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm password">
                        <div class="form-text">
                            <?php echo __('password_must_be_at_least_8_characters_long_and_contain'); ?>:
                            <ul class="mb-0 ps-3">
                                <li><?php echo __('uppercase_letters'); ?></li>
                                <li><?php echo __('lowercase_letters'); ?></li>
                                <li><?php echo __('numbers'); ?></li>
                                <li><?php echo __('special_characters'); ?></li>
                            </ul>
                        </div>
                    </div>
                `,
                confirmButtonText: '<?php echo __('set_password'); ?>',
                showCancelButton: true,
                preConfirm: () => {
                    const pass1 = document.getElementById('newPassword').value;
                    const pass2 = document.getElementById('confirmPassword').value;
                    
                    if (!pass1 || !pass2) {
                        Swal.showValidationMessage('<?php echo __('please_fill_in_both_password_fields'); ?>');
                        return false;
                    }
                    
                    if (pass1 !== pass2) {
                        Swal.showValidationMessage('<?php echo __('passwords_do_not_match'); ?>');
                        return false;
                    }
                    
                    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                    if (!passwordRegex.test(pass1)) {
                        Swal.showValidationMessage('<?php echo __('password_does_not_meet_requirements'); ?>');
                        return false;
                    }
                    
                    return pass1;
                }
            });

            if (manualEntry.dismiss) return;

            const response = await fetch('api/user-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'reset_password',
                    username: username,
                    password: manualEntry.value,
                    must_change: false
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || '<?php echo __('failed_to_reset_password'); ?>');
            }

            Swal.fire({
                title: '<?php echo __('success'); ?>',
                text: '<?php echo __('password_has_been_set_successfully'); ?>',
                icon: 'success'
            });
        }

    } catch (error) {
        console.error('<?php echo __('reset_password_error'); ?>:', error);
        Swal.fire({
            title: '<?php echo __('error'); ?>',
            text: error.message || '<?php echo __('failed_to_reset_password'); ?>',
            icon: 'error'
        });
    }
}

// Keşi təmizləmək üçün funksiya əlavə edirik
async function clearCache() {
    cachedUsers = null;
    lastFetchTime = 0;
    allUsers = [];
    
    // IndexedDB-dən keşi təmizləyirik
    try {
        const request = indexedDB.open('UsersAppCache', 1);
        request.onsuccess = function(event) {
            const db = event.target.result;
            const transaction = db.transaction(['cache'], 'readwrite');
            const store = transaction.objectStore('cache');
            store.delete('users_data');
        };
    } catch (error) {
        console.error('Error clearing IndexedDB cache:', error);
    }
    
    // Server tərəfdəki keşi də təmizləyirik
    return fetch('api/clear-session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            cache_keys: ['all_users_cache', 'formatted_users_cache']
        })
    }).catch(error => {
        console.error('<?php echo __('failed_to_clear_server_cache'); ?>:', error);
    });
}

// OU dəyişdirmə funksiyası
function changeOU(username) {
    // Əvvəlcə user details modalını bağlayırıq
    const userModal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
    if (userModal) {
        userModal.hide();
    }
    
    // OU siyahısını yükləyirik
    fetch('api/ous.php')
        .then(response => response.json())
        .then(data => {
            if (!data.ous || !Array.isArray(data.ous)) {
                throw new Error('Invalid OU data received');
            }
            
            // İstifadəçinin cari OU məlumatını alırıq
            return fetch('api/user-details.php?username=' + encodeURIComponent(username))
                .then(response => response.json())
                .then(userData => {
                    return { ous: data.ous, user: userData };
                });
        })
        .then(({ ous, user }) => {
            // OU seçimi üçün HTML hazırlayırıq
            let ouOptions = '<option value="CN=Users,DC=not,DC=local"><?php echo __('default_users_container'); ?></option>';
            
            ous.forEach(ou => {
                const selected = user.ou === ou.path ? 'selected' : '';
                ouOptions += `<option value="${escapeHtml(ou.dn)}" ${selected}>${escapeHtml(ou.path)}</option>`;
            });
            
            // OU seçimi üçün dialog göstəririk
            Swal.fire({
                title: '<?php echo __('change_organizational_unit'); ?>',
                html: `
                    <div class="mb-3">
                        <label for="ouSelect" class="form-label"><?php echo __('select_new_ou_for_user'); ?> <strong>${escapeHtml(username)}</strong>:</label>
                        <select id="ouSelect" class="form-select">
                            ${ouOptions}
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo __('moving_a_user_to_a_different_ou_may_affect_their_group_policy_settings'); ?>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<?php echo __('move_user'); ?>',
                confirmButtonColor: '#0d6efd',
                cancelButtonText: '<?php echo __('cancel'); ?>',
                preConfirm: () => {
                    return document.getElementById('ouSelect').value;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    // Yükləmə göstəricisini göstəririk
                    Swal.fire({
                        title: '<?php echo __('moving_user'); ?>...',
                        html: `<?php echo __('moving'); ?> <strong>${escapeHtml(username)}</strong> <?php echo __('to_new_ou'); ?>...`,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // OU dəyişdirmə sorğusu göndəririk
                    fetch('api/user-action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'move_ou',
                            username: username,
                            new_ou: result.value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '<?php echo __('success'); ?>',
                                text: `<?php echo __('user'); ?> ${username} <?php echo __('has_been_moved_to_the_new_ou'); ?>`,
                                showConfirmButton: false,
                                timer: 1500
                            });
                            loadUsers();
                        } else {
                            throw new Error(data.error || '<?php echo __('failed_to_move_user'); ?>');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: '<?php echo __('error'); ?>',
                            text: `<?php echo __('failed_to_move_user'); ?>: ${error.message}`,
                            confirmButtonText: '<?php echo __('close'); ?>',
                            confirmButtonColor: '#dc3545'
                        });
                    });
                }
            });
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: '<?php echo __('error'); ?>',
                text: `<?php echo __('failed_to_load_ou_data'); ?>: ${error.message}`,
                confirmButtonText: '<?php echo __('close'); ?>',
                confirmButtonColor: '#dc3545'
            });
        });
}

// Qrup idarəetmə funksiyası
function manageGroups(username) {
    // Əvvəlcə user details modalını bağlayırıq
    const userModal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
    if (userModal) {
        userModal.hide();
    }
    
    // İstifadəçi və qrup məlumatlarını yükləyirik
    Promise.all([
        fetch('api/user-details.php?username=' + encodeURIComponent(username)).then(r => r.json()),
        fetch('api/groups.php').then(r => r.json())
    ])
    .then(([userData, groupsData]) => {
        if (!userData || !groupsData || !groupsData.groups) {
            throw new Error('<?php echo __('failed_to_load_user_or_group_data'); ?>');
        }
        
        // İstifadəçinin mövcud qruplarını array-ə çeviririk
        const userGroups = userData.groups ? userData.groups.split(',').map(g => g.trim()) : [];
        
        // Qrup seçimi üçün HTML hazırlayırıq
        let groupsHTML = '';
        
        groupsData.groups.forEach(group => {
            const isChecked = userGroups.includes(group.name) ? 'checked' : '';
            groupsHTML += `
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="group_${escapeHtml(group.name)}" 
                           name="groups[]" value="${escapeHtml(group.name)}" ${isChecked}>
                    <label class="form-check-label d-flex justify-content-between" for="group_${escapeHtml(group.name)}">
                        <span>${escapeHtml(group.name)}</span>
                        <span class="badge ${group.type === 'Security' ? 'bg-primary' : 'bg-secondary'} ms-2">
                            ${escapeHtml(group.type)}
                        </span>
                    </label>
                </div>
            `;
        });
        
        // Qrup idarəetmə dialoqu göstəririk
        Swal.fire({
            title: '<?php echo __('manage_group_memberships'); ?>',
            html: `
                <form id="groupsForm">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('user'); ?>: <strong>${escapeHtml(username)}</strong></label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="<?php echo __('search_groups'); ?>..." id="groupSearchInput">
                            <button class="btn btn-outline-secondary" type="button" id="clearGroupSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <div id="groupsList">
                                ${groupsHTML}
                            </div>
                        </div>
                    </div>
                </form>
            `,
            didOpen: () => {
                // Qrup axtarışı üçün event listener əlavə edirik
                const searchInput = document.getElementById('groupSearchInput');
                const clearButton = document.getElementById('clearGroupSearch');
                
                searchInput.addEventListener('input', function() {
                    const searchText = this.value.toLowerCase();
                    const groupItems = document.querySelectorAll('#groupsList .form-check');
                    
                    groupItems.forEach(item => {
                        const groupName = item.querySelector('.form-check-label span').textContent.toLowerCase();
                        if (groupName.includes(searchText)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
                
                clearButton.addEventListener('click', function() {
                    searchInput.value = '';
                    const groupItems = document.querySelectorAll('#groupsList .form-check');
                    groupItems.forEach(item => {
                        item.style.display = '';
                    });
                });
            },
            showCancelButton: true,
            confirmButtonText: '<?php echo __('save_changes'); ?>',
            confirmButtonColor: '#0d6efd',
            cancelButtonText: '<?php echo __('cancel'); ?>',
            width: '600px',
            preConfirm: () => {
                // Seçilmiş qrupları toplayırıq
                const selectedGroups = [];
                document.querySelectorAll('#groupsForm input[name="groups[]"]:checked').forEach(checkbox => {
                    selectedGroups.push(checkbox.value);
                });
                return selectedGroups;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Yükləmə göstəricisini göstəririk
                Swal.fire({
                    title: '<?php echo __('updating_group_memberships'); ?>...',
                    html: '<?php echo __('please_wait_while_we_update_group_memberships'); ?>',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Qrup üzvlüyünü yeniləyirik
                fetch('api/user-action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_groups',
                        username: username,
                        groups: result.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '<?php echo __('success'); ?>',
                            text: `<?php echo __('group_memberships_updated_for'); ?> ${username}`,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        loadUsers();
                    } else {
                        throw new Error(data.error || '<?php echo __('failed_to_update_group_memberships'); ?>');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: '<?php echo __('error'); ?>',
                        text: `<?php echo __('failed_to_update_group_memberships'); ?>: ${error.message}`,
                        confirmButtonText: '<?php echo __('close'); ?>',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: '<?php echo __('error'); ?>',
            text: `<?php echo __('failed_to_load_data'); ?>: ${error.message}`,
            confirmButtonText: '<?php echo __('close'); ?>',
            confirmButtonColor: '#dc3545'
        });
    });
}

function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
    let password = "";
    
    // Ensure at least one of each required character type
    password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)]; // Uppercase
    password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)]; // Lowercase
    password += "0123456789"[Math.floor(Math.random() * 10)]; // Number
    password += "!@#$%^&*()_+"[Math.floor(Math.random() * 12)]; // Special
    
    // Fill the rest randomly
    for (let i = password.length; i < length; i++) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }
    
    // Shuffle the password
    password = password.split('').sort(() => Math.random() - 0.5).join('');
    
    // Set password fields
    document.getElementById('new_password').value = password;
    document.getElementById('new_confirm_password').value = password;
    
    // Show password temporarily
    const pwdField = document.getElementById('new_password');
    pwdField.type = 'text';
    setTimeout(() => {
        pwdField.type = 'password';
    }, 3000); // Hide after 3 seconds

    // Copy to clipboard and show notification
    navigator.clipboard.writeText(password).then(() => {
        const infoElement = document.getElementById('generated_password_info');
        infoElement.style.display = 'block';
        setTimeout(() => {
            infoElement.style.display = 'none';
        }, 3000);
    });
}

function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.parentElement.querySelector('button:last-child');
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function updateDisplayName() {
    const firstName = document.getElementById('new_firstname');
    const lastName = document.getElementById('new_lastname');
    const displayName = document.getElementById('new_displayname');
    
    if (!firstName || !lastName || !displayName) {
        console.warn('<?php echo __('one_or_more_elements_for_updating_display_name_not_found'); ?>');
        return;
    }
    
    const firstNameValue = firstName.value ? firstName.value.trim() : '';
    const lastNameValue = lastName.value ? lastName.value.trim() : '';
    
    if (firstNameValue && lastNameValue) {
        displayName.value = `${firstNameValue} ${lastNameValue}`;
    } else if (firstNameValue) {
        displayName.value = firstNameValue;
    } else if (lastNameValue) {
        displayName.value = lastNameValue;
    }
}

function loadOUsAndGroups() {
    const ouSelect = document.getElementById('new_ou');
    ouSelect.innerHTML = '<option value=""><?php echo __('loading'); ?>...</option>';
    ouSelect.disabled = true;

    // Load OUs
    fetch('api/get-ous.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                ouSelect.innerHTML = '<option value=""><?php echo __('select_organizational_unit'); ?></option>';
                
                // Add Root option if base_dn is available
                if (data.base_dn) {
                    ouSelect.innerHTML += `
                        <option value="${escapeHtml(data.base_dn)}" class="fw-bold">
                            / (Root)
                        </option>
                    `;
                }
                
                // Sort OUs by path to maintain hierarchy
                const sortedOUs = data.ous.sort((a, b) => a.path.localeCompare(b.path));
                
                sortedOUs.forEach(ou => {
                    // Calculate the depth of the OU in the hierarchy
                    const depth = ou.path.split('/').length - 1;
                    // Add appropriate indentation
                    const indent = '─ '.repeat(depth);
                    // Get the OU name (last part of the path)
                    const name = ou.path.split('/').pop();
                    
                    ouSelect.innerHTML += `
                        <option value="${escapeHtml(ou.dn)}" title="${escapeHtml(ou.path)}">
                            ${indent}${escapeHtml(name)}
                        </option>
                    `;
                });
                
                // Add styling to the select element
                ouSelect.style.fontFamily = 'monospace';
                ouSelect.classList.add('form-select-lg');
            } else {
                throw new Error(data.message || '<?php echo __('failed_to_load_ous'); ?>');
            }
        })
        .catch(error => {
            console.error('<?php echo __('error_loading_ous'); ?>:', error);
            ouSelect.innerHTML = '<option value=""><?php echo __('failed_to_load_ous'); ?></option>';
            showToast('error', '<?php echo __('failed_to_load_organizational_units'); ?>');
        })
        .finally(() => {
            ouSelect.disabled = false;
        });
}

function validateNewUserForm() {
    const form = document.getElementById('newUserForm');
    
    // Check required fields
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    // Check if OU is selected
    const ouSelect = document.getElementById('new_ou');
    if (!ouSelect.value) {
        showToast('error', '<?php echo __('please_select_an_organizational_unit'); ?>');
        ouSelect.focus();
        return false;
    }

    // Check if passwords match
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('new_confirm_password').value;
    if (password !== confirmPassword) {
        showToast('error', '<?php echo __('passwords_do_not_match'); ?>');
        return false;
    }
    
    // Validate password complexity
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*()_+]/.test(password);
    const isLongEnough = password.length >= 8;
    
    if (!(hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar && isLongEnough)) {
        showToast('error', '<?php echo __('password_does_not_meet_complexity_requirements'); ?>');
        return false;
    }
    
    return true;
}

// Event listeners for new user form
document.addEventListener('DOMContentLoaded', function() {
    const newUserModal = document.getElementById('newUserModal');
    if (newUserModal) {
        newUserModal.addEventListener('show.bs.modal', function() {
            document.getElementById('newUserForm').reset();
            loadOUsAndGroups();
        });
        
        // Auto-update display name
        document.getElementById('new_firstname').addEventListener('input', updateDisplayName);
        document.getElementById('new_lastname').addEventListener('input', updateDisplayName);
    }
});




</script>
