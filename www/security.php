<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */

session_start();
require_once(__DIR__ . '/includes/functions.php');
require_once(__DIR__ . '/includes/Database.php');

if (!isset($_SESSION['ad_username'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = __('page_title_system_config');
$activePage = __('page_active_security');



// Dil funksiyalarını əlavə et
require_once(__DIR__ . '/includes/functions/language.php');

// Dil dəyişmə funksiyası
if (isset($_GET['action']) && $_GET['action'] === 'switch_language' && isset($_GET['lang'])) {
    $lang = sanitizeInput($_GET['lang']);
    if (validateLanguageCode($lang)) {
        setcookie('site_language', $lang, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // AJAX sorğusu üçün JSON cavab
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => __('success_language_switch')
            ]);
            exit;
        }
    }
}

// Load current language
$current_language = getCurrentLanguage();
$default_language = $config['language_settings']['default_language'] ?? 'en';

// Load language file
loadLanguageFile($current_language);

try {
    $db = Database::getInstance();
    if (!$db->isConnected()) {
        throw new Exception("Database connection failed");
    }

    // Load current configuration
    $config = require(__DIR__ . '/config/config.php');

    // Define complete configuration schema
    $config_sections = [
        'language_settings' => [
            'title' => __('security_language_settings'),
            'icon' => 'fas fa-globe',
            'fields' => [
                'default_language' => [
                    'type' => 'select',
                    'label' => __('security_default_language'),
                    'current' => $config['language_settings']['default_language'] ?? 'en',
                    'options' => [],
                    'description' => __('security_default_language_desc')
                ]
            ]
        ],
        'ad_settings' => [
            'title' => 'Active Directory Settings',
            'icon' => 'fas fa-network-wired',
            'fields' => [
                'domain_controllers' => [
                    'type' => 'array',
                    'label' => __('security_domain_controllers'),
                    'current' => $config['ad_settings']['domain_controllers'] ?? [],
                    'description' => __('security_domain_controllers_desc')
                ],
                'admin_group' => [
                    'type' => 'text',
                    'label' => __('security_admin_group'),
                    'current' => $config['ad_settings']['admin_group'] ?? '',
                    'description' => __('security_admin_group_desc')
                ],
                'allowed_groups' => [
                    'type' => 'array',
                    'label' => __('security_allowed_groups'),
                    'current' => $config['ad_settings']['allowed_groups'] ?? [],
                    'description' => __('security_allowed_groups_desc')
                ],
                'base_dn' => [
                    'type' => 'text',
                    'label' => __('security_base_dn'),
                    'current' => $config['ad_settings']['base_dn'] ?? '',
                    'description' => __('security_base_dn_desc')
                ],
                'account_suffix' => [
                    'type' => 'text',
                    'label' => __('security_account_suffix'),
                    'current' => $config['ad_settings']['account_suffix'] ?? '',
                    'description' => __('security_account_suffix_desc')
                ],
                'port' => [
                    'type' => 'number',
                    'label' => __('security_ldap_port'),
                    'current' => $config['ad_settings']['port'] ?? 389,
                    'min' => 1,
                    'max' => 65535
                ],
                'timeout' => [
                    'type' => 'number',
                    'label' => __('security_connection_timeout'),
                    'current' => $config['ad_settings']['timeout'] ?? 5,
                    'min' => 1,
                    'max' => 60
                ],
                'use_ssl' => [
                    'type' => 'boolean',
                    'label' => __('security_use_ssl'),
                    'current' => $config['ad_settings']['use_ssl'] ?? false,
                    'description' => __('security_use_ssl_desc')
                ]
            ]
        ],
        'db_settings' => [
            'title' => __('security_db_settings'),
            'icon' => 'fas fa-database',
            'fields' => [
                'host' => [
                    'type' => 'text',
                    'label' => __('security_db_host'),
                    'current' => $config['db_settings']['host'] ?? 'localhost'
                ],
                'database' => [
                    'type' => 'text',
                    'label' => __('security_db_name'),
                    'current' => $config['db_settings']['database'] ?? ''
                ],
                'username' => [
                    'type' => 'text',
                    'label' => __('security_db_username'),
                    'current' => $config['db_settings']['username'] ?? ''
                ],
                'password' => [
                    'type' => 'password',
                    'label' => __('security_db_password'),
                    'current' => $config['db_settings']['password'] ?? '',
                    'description' => __('security_db_password_desc')
                ],
                'charset' => [
                    'type' => 'text',
                    'label' => __('security_db_charset'),
                    'current' => $config['db_settings']['charset'] ?? 'utf8mb4'
                ]
            ]
        ],
        'app_settings' => [
            'title' => __('security_app_settings'),
            'icon' => 'fas fa-cogs',
            'fields' => [
                'session_timeout' => [
                    'type' => 'number',
                    'label' => __('security_session_timeout'),
                    'current' => $config['app_settings']['session_timeout'] ?? 3600,
                    'min' => 300,
                    'max' => 86400
                ],
                'max_login_attempts' => [
                    'type' => 'number',
                    'label' => __('security_max_login_attempts'),
                    'current' => $config['app_settings']['max_login_attempts'] ?? 5,
                    'min' => 3,
                    'max' => 10
                ],
                'login_block_duration' => [
                    'type' => 'number',
                    'label' => __('security_login_block_duration'),
                    'current' => $config['app_settings']['login_block_duration'] ?? 30,
                    'min' => 5,
                    'max' => 120
                ],
                'debug_mode' => [
                    'type' => 'boolean',
                    'label' => __('security_debug_mode'),
                    'current' => $config['app_settings']['debug_mode'] ?? false
                ],
                'page_size' => [
                    'type' => 'number',
                    'label' => __('security_page_size'),
                    'current' => $config['app_settings']['page_size'] ?? 50,
                    'min' => 10,
                    'max' => 100
                ]
            ]
        ],
        'password_settings' => [
            'title' => __('security_password_settings'),
            'icon' => 'fas fa-key',
            'fields' => [
                'default_temp_password' => [
                    'type' => 'text',
                    'label' => __('security_default_temp_password'),
                    'current' => $config['password_settings']['default_temp_password'] ?? '',
                    'description' => __('security_default_temp_password_desc')
                ]
            ]
        ]
    ];

    // Get available languages from languages folder
    $languages_dir = __DIR__ . '/includes/languages';
    if (is_dir($languages_dir)) {
        $language_files = glob($languages_dir . '/*.php');
        foreach ($language_files as $file) {
            $lang_code = basename($file, '.php');
            $lang_name = ucfirst($lang_code);
            $config_sections['language_settings']['fields']['default_language']['options'][$lang_code] = $lang_name;
        }
    }

} catch (Exception $e) {
    error_log("Security page error: " . $e->getMessage());
    $_SESSION['error_message'] = __('error_configuration');
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}

require_once('includes/header.php');
?>

<!-- SweetAlert2 və Animate.css üçün CDN kitabxanaları əlavə edək -->
<link rel="stylesheet" href="temp/assets/lib/animate/animate.min.css">
<script src="temp/assets/lib/SweetAlert2/sweetalert2.min.js"></script>
<link rel="stylesheet" href="temp/css/security.css">
<script>
    // Pass PHP config sections to JavaScript
    const configSections = <?php echo json_encode($config_sections); ?>;
    
    // Define translation function
    function __(key) {
        const translations = <?php echo json_encode($lang); ?>;
        return translations[key] || key;
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
    
    // Add form submission handlers
    document.querySelectorAll('.config-form').forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });
    
    // Tab button click handling
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
        button.addEventListener('click', function() {
            // Toggle active class
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // If this button is expanding a section, add active class
            if(!this.classList.contains('collapsed')) {
                this.classList.add('active');
            }
        });
    });
});

function handleFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const sectionId = form.id.replace('_form', '');
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Disable form while saving
    submitButton.disabled = true;
    const originalButtonText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + __('loading_saving');
    
    form.querySelectorAll('input, select, button').forEach(el => {
        if (el !== submitButton) el.classList.add('saving-disabled');
    });
    
    // Prepare data for saving
    const formData = {};
    formData[sectionId] = {};
    
    // Get fields for this section
    const sectionFields = configSections[sectionId].fields;
    
    // Process each field
    Object.entries(sectionFields).forEach(([fieldId, field]) => {
        const element = document.getElementById(fieldId);
        if (element) {
            if (field.type === 'boolean') {
                formData[sectionId][fieldId] = element.checked;
            } else if (field.type === 'array') {
                formData[sectionId][fieldId] = Array.from(element.options).map(opt => opt.value);
            } else if (field.type === 'number') {
                formData[sectionId][fieldId] = parseInt(element.value);
            } else if (field.type === 'password' && !element.value) {
                // Skip empty password
                return;
            } else {
                formData[sectionId][fieldId] = element.value;
            }
        }
    });

    // Send data to server
    fetch('api/update-config.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('success', __('js_config_updated'));
            
            // Reload page after successful save
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            throw new Error(result.error);
        }
    })
    .catch(error => {
        // Show error notification
        showAlert('danger', __('js_error_occurred'));
        
        // Re-enable form
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
        
        form.querySelectorAll('.saving-disabled').forEach(el => {
            el.classList.remove('saving-disabled');
        });
        
        // Show error notice
        const errorNotice = document.createElement('div');
        errorNotice.className = 'alert alert-danger mt-3';
        errorNotice.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + __('js_error_occurred');
        form.prepend(errorNotice);
        
        setTimeout(() => {
            errorNotice.style.opacity = '0';
            setTimeout(() => errorNotice.remove(), 300);
        }, 5000);
    });
}

function resetForm(sectionId) {
    Swal.fire({
        title: __('js_reset_confirm_title'),
        text: __('js_reset_confirm_text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: __('js_reset_confirm_yes'),
        cancelButtonText: __('js_reset_confirm_no'),
        buttonsStyling: true,
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            location.reload();
        }
    });
}

function addNewItem(fieldId) {
    Swal.fire({
        title: __('js_add_new_item_title'),
        input: 'text',
        inputPlaceholder: __('js_add_new_item_placeholder'),
        inputAttributes: {
            autocapitalize: 'off',
            autocorrect: 'off'
        },
        showCancelButton: true,
        confirmButtonText: __('js_add_new_item_add'),
        cancelButtonText: __('js_add_new_item_cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        inputValidator: (value) => {
            if (!value) {
                return __('js_add_new_item_required');
            }
            
            const select = document.getElementById(fieldId);
            const existingValues = Array.from(select.options).map(opt => opt.value);
            if (existingValues.includes(value)) {
                return __('js_add_new_item_exists');
            }
        },
        preConfirm: (value) => {
            try {
                const select = document.getElementById(fieldId);
                const option = new Option(value.trim(), value.trim(), true, true);
                select.appendChild(option);
                return value;
            } catch (error) {
                Swal.showValidationMessage(__('js_add_new_item_error'));
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            showAlert('success', __('js_add_new_item_success'));
        }
    });
}

function deleteSelectedItems(fieldId) {
    const select = document.getElementById(fieldId);
    const selectedOptions = Array.from(select.selectedOptions);
    
    if (selectedOptions.length === 0) {
        showAlert('warning', __('js_delete_select_items'));
        return;
    }

    if (fieldId === 'allowed_groups' && select.options.length <= 1) {
        showAlert('danger', __('js_delete_min_groups'));
        return;
    }

    Swal.fire({
        title: __('js_delete_confirm_title'),
        html: `<p>${selectedOptions.length} ${__('js_delete_confirm_message')}</p>
              <div class="alert alert-light">
                <ul class="list-group">
                  ${selectedOptions.map(opt => `<li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${opt.text}</span>
                    <span class="badge bg-danger rounded-pill"><i class="fas fa-trash"></i></span>
                  </li>`).join('')}
                </ul>
              </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: __('js_delete_confirm_yes'),
        cancelButtonText: __('js_delete_confirm_no')
    }).then((result) => {
        if (result.isConfirmed) {
            selectedOptions.forEach(option => option.remove());
            showAlert('success', __('js_delete_success'));
        }
    });
}

function showAlert(type, message) {
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle',
        'danger': 'fas fa-exclamation-circle'
    };
    
    const colors = {
        'success': '#198754',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#0dcaf0',
        'danger': '#dc3545'
    };
    
    const icon = icons[type === 'danger' ? 'error' : type];
    const color = colors[type === 'danger' ? 'error' : type];
    
    Swal.fire({
        html: `<div class="d-flex align-items-center">
                <div class="me-3" style="font-size: 1.5rem; color: ${color};">
                    <i class="${icon}"></i>
                </div>
                <div class="text-start">
                    <div style="font-weight: 500;">${message}</div>
                </div>
              </div>`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        background: '#fff',
        padding: '1rem',
        customClass: {
            popup: 'swal-notification',
        },
        showClass: {
            popup: 'animate__animated animate__fadeInRight animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutRight animate__faster'
        }
    });
    
    console.log(`Bildiriş: ${type} - ${message}`);
}

function showDocumentation(sectionId) {
    const documentationContent = getDocumentationForSection(sectionId);
    
    Swal.fire({
        title: `<div class="d-flex align-items-center">
                   <div class="documentation-icon me-3">
                       <i class="fas fa-book"></i>
                   </div>
                   <span>${getSectionTitle(sectionId)} - ${__('js_documentation_title')}</span>
                </div>`,
        html: documentationContent,
        showCloseButton: true,
        showConfirmButton: false,
        width: 600,
        customClass: {
            container: 'documentation-modal',
            popup: 'documentation-popup',
            closeButton: 'documentation-close-btn',
            htmlContainer: 'documentation-content'
        }
    });
}

function getSectionTitle(sectionId) {
    return configSections[sectionId]?.title || sectionId;
}

function getDocumentationForSection(sectionId) {
    const docs = {
        'ad_settings': `
            <div class="doc-section">
                <h5>${__('security_ad_settings')}</h5>
                <p>${__('security_ad_settings_desc')}</p>
                
                <h6>${__('security_domain_controllers')}</h6>
                <p>${__('security_domain_controllers_desc')}</p>
                
                <h6>${__('security_admin_group')}</h6>
                <p>${__('security_admin_group_desc')}</p>
                
                <div class="doc-tip">
                    <i class="fas fa-lightbulb me-2"></i>
                    <span>${__('security_ssl_tip')}</span>
                </div>
            </div>
        `,
        'db_settings': `
            <div class="doc-section">
                <h5>${__('security_db_settings')}</h5>
                <p>${__('security_db_settings_desc')}</p>
                
                <h6>${__('security_db_connection_params')}</h6>
                <ul>
                    <li><strong>${__('security_db_host')}:</strong> ${__('security_db_host_desc')}</li>
                    <li><strong>${__('security_db_name')}:</strong> ${__('security_db_name_desc')}</li>
                    <li><strong>${__('security_db_username')}:</strong> ${__('security_db_username_desc')}</li>
                    <li><strong>${__('security_db_password')}:</strong> ${__('security_db_password_desc')}</li>
                </ul>
                
                <div class="doc-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span>${__('security_db_password_warning')}</span>
                </div>
            </div>
        `,
        'app_settings': `
            <div class="doc-section">
                <h5>${__('security_app_settings')}</h5>
                <p>${__('security_app_settings_desc')}</p>
                
                <h6>${__('security_session_security')}</h6>
                <p>${__('security_session_security_desc')}</p>
                
                <div class="doc-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>${__('security_debug_mode_warning')}</span>
                </div>
            </div>
        `,
        'password_settings': `
            <div class="doc-section">
                <h5>${__('security_password_settings')}</h5>
                <p>${__('security_password_settings_desc')}</p>
                
                <h6>${__('security_temp_password')}</h6>
                <p>${__('security_temp_password_desc')}</p>
                
                <div class="doc-tip">
                    <i class="fas fa-lightbulb me-2"></i>
                    <span>${__('security_temp_password_tip')}</span>
                </div>
            </div>
        `
    };
    
    return docs[sectionId] || `<p>${__('security_documentation_coming_soon')}</p>`;
}

</script>
<div class="container-fluid">
    <div class="row">
        <?php require_once('includes/sidebar.php'); ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 bg-light">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-shield-alt me-2" title="<?php echo __('icon_shield'); ?>"></i><?php echo __('security_title'); ?>
                    </h1>
                    <p class="text-muted"><?php echo __('security_subtitle'); ?></p>
                </div>
            </div>

            <!-- Əsas məzmun sahəsi -->
            <div class="settings-container">
                <!-- Naviqasiya tabları - daha göz oxşayan dizayn -->
                <div class="menu-container mb-4">
                    <ul class="nav nav-pills nav-settings" id="settingsTabs" role="tablist">
                <?php foreach ($config_sections as $section_id => $section): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link collapsed menu-item" 
                                    id="<?php echo $section_id; ?>-tab" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#<?php echo $section_id; ?>-content" 
                                    type="button" 
                                    aria-expanded="false"
                                    aria-controls="<?php echo $section_id; ?>-content">
                                    <div class="d-flex align-items-center">
                                        <div class="menu-icon me-3">
                                            <i class="<?php echo $section['icon']; ?>"></i>
                                        </div>
                                        <div class="menu-text">
                                            <span><?php echo $section['title']; ?></span>
                                        </div>
                            </div>
                                </button>
                            </li>
                <?php endforeach; ?>
                    </ul>
            </div>

                <!-- Tab məzmunları -->
                <div class="tab-content" id="settingsContent">
            <?php foreach ($config_sections as $section_id => $section): ?>
                        <div class="collapse" 
                             id="<?php echo $section_id; ?>-content" 
                             data-bs-parent="#settingsContent">
                            
                            <div class="card settings-card mb-4">
                                <div class="card-header d-flex align-items-center">
                                    <div class="section-icon me-3">
                                        <i class="<?php echo $section['icon']; ?>"></i>
                        </div>
                                    <h5 class="mb-0"><?php echo $section['title']; ?></h5>
                    </div>
                                
                    <div class="card-body">
                                    <div class="settings-description mb-4 text-muted">
                                        <i class="fas fa-info-circle me-2" title="<?php echo __('icon_info'); ?>"></i>
                                        <?php echo $section['title']; ?> <?php echo __('security_section_has'); ?> <strong><?php echo count($section['fields']); ?></strong> <?php echo __('security_parameters_available'); ?>.
                                    </div>
                                    
                        <form id="<?php echo $section_id; ?>_form" class="config-form">
                                        <div class="row">
                                <?php foreach ($section['fields'] as $field_id => $field): ?>
                                                <div class="col-md-6 col-lg-6 mb-3">
                                        <div class="form-group">
                                                        <label class="form-label fw-medium">
                                                <?php echo $field['label']; ?>
                                                <?php if (isset($field['description'])): ?>
                                                                <i class="fas fa-info-circle ms-2 text-info tooltip-icon" 
                                                       data-bs-toggle="tooltip" 
                                                       title="<?php echo htmlspecialchars($field['description']); ?>">
                                                    </i>
                                                <?php endif; ?>
                                            </label>
                                                        
                                            <?php if ($field['type'] === 'boolean'): ?>
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" class="form-check-input" id="<?php echo $field_id; ?>"
                                                           <?php echo $field['current'] ? 'checked' : ''; ?>>
                                                </div>
                                            <?php elseif ($field['type'] === 'array'): ?>
                                                <div class="input-group array-input-group">
                                                    <select class="form-select" id="<?php echo $field_id; ?>" multiple>
                                                        <?php foreach ($field['current'] as $value): ?>
                                                            <option value="<?php echo htmlspecialchars($value); ?>" selected>
                                                                <?php echo htmlspecialchars($value); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="d-flex gap-2 mt-2">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addNewItem('<?php echo $field_id; ?>')">
                                                            <i class="fas fa-plus"></i> <?php echo __('security_add'); ?>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSelectedItems('<?php echo $field_id; ?>')">
                                                            <i class="fas fa-trash"></i> <?php echo __('security_delete'); ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php elseif ($field['type'] === 'select'): ?>
                                                <select class="form-select" id="<?php echo $field_id; ?>">
                                                    <?php foreach ($field['options'] as $value => $label): ?>
                                                        <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $field['current'] === $value ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <input type="<?php echo $field['type']; ?>" 
                                                       class="form-control" 
                                                       id="<?php echo $field_id; ?>"
                                                       value="<?php echo htmlspecialchars($field['current']); ?>"
                                                       <?php if (isset($field['min'])): ?>min="<?php echo $field['min']; ?>"<?php endif; ?>
                                                       <?php if (isset($field['max'])): ?>max="<?php echo $field['max']; ?>"<?php endif; ?>
                                                       required>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                                        
                            <div class="d-flex justify-content-end mt-4">
                                            <button type="button" class="btn btn-outline-secondary me-2" onclick="resetForm('<?php echo $section_id; ?>')">
                                                <i class="fas fa-undo me-2"></i><?php echo __('security_reset'); ?>
                                </button>
                                <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i><?php echo __('security_save_changes'); ?>
                                </button>
                            </div>
                        </form>
                                    
                                    <div class="mt-4 pt-4 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="fas fa-book me-2" title="<?php echo __('icon_book'); ?>"></i><?php echo __('security_documentation'); ?></h6>
                                            <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="showDocumentation('<?php echo $section_id; ?>')">
                                                <?php echo __('security_more_info'); ?> <i class="fas fa-chevron-right ms-1" title="<?php echo __('icon_arrow_right'); ?>"></i>
                                            </button>
                                        </div>
                                        <p class="text-muted small">
                                            <?php echo __('security_documentation_desc'); ?>
                                        </p>
                                    </div>
                                </div>
                    </div>
                </div>
            <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- İstifadəçi kitabxanaları footer-də artıq əlavə olunub -->
<?php require_once('includes/footer.php'); ?>
