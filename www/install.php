<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [ali] <[ali.z.zeynalli@gmail.com]> [2025]
  */
  
session_start();

// License verification
$config = require(__DIR__ . '/config/config.php');

// Get available languages from languages folder
$languages_dir = __DIR__ . '/includes/languages';
$available_languages = [];
if (is_dir($languages_dir)) {
    $language_files = glob($languages_dir . '/*.php');
    foreach ($language_files as $file) {
        $lang_code = basename($file, '.php');
        $lang_name = ucfirst($lang_code);
        $available_languages[$lang_code] = $lang_name;
    }
}

// Check if system is already installed
if (file_exists(__DIR__ . '/config/.installed')) {
    header('Location: uninstall.php');
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Installation helper functions
function updateConfig($data) {
    $config_file = __DIR__ . '/config/config.php';
    $current_config = require($config_file);
    
    // Adding installation information
    $current_config['installation'] = [
        'date' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'installed' => true,
        'installer' => $data['admin_username'] ?? 'unknown',
        'install_type' => 'fresh_install',
        'last_update' => date('Y-m-d H:i:s')
    ];

    // Adding password settings
    $current_config['password_settings'] = [
        'default_temp_password' => 'Welcome2024!1111',
        'min_length' => 8,
        'complexity' => true
    ];

    // Adding pagination settings
    $current_config['pagination_settings'] = [
        'default_page_size' => 15,
        'page_size_options' => [5, 10, 15, 25, 50, 100, -1]
    ];

    // Get available languages from languages folder
    $languages_dir = __DIR__ . '/includes/languages';
    $available_languages = [];
    if (is_dir($languages_dir)) {
        $language_files = glob($languages_dir . '/*.php');
        foreach ($language_files as $file) {
            $lang_code = basename($file, '.php');
            $available_languages[] = $lang_code;
        }
    }

    // Adding language settings
    $current_config['language_settings'] = [
        'default_language' => $data['default_language'] ?? 'en'
    ];

    // Merging other configurations
    if (isset($data['ad'])) {
        $current_config['ad_settings'] = array_merge(
            $current_config['ad_settings'] ?? [],
            $data['ad']
        );
    }
    
    if (isset($data['db'])) {
        $current_config['db_settings'] = array_merge(
            $current_config['db_settings'] ?? [],
            $data['db']
        );
    }

    // Creating config file
    $config_content = "<?php\nreturn " . var_export($current_config, true) . ";\n";
    
    return file_put_contents($config_file, $config_content) !== false;
}

// Installation check
if (!empty($config['installation']['installed'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        if (empty($_POST['dc_host']) || empty($_POST['base_dn'])) {
            throw new Exception("Domain Controller and Base DN are required");
        }

        // Test AD connection
        $ldap = ldap_connect($_POST['dc_host'], $_POST['port'] ?? 389);
        if (!$ldap) {
            throw new Exception("Could not connect to AD server");
        }

        // Prepare configuration data
        $install_data = [
            'ad' => [
                'domain_controllers' => [$_POST['dc_host']],
                'base_dn' => $_POST['base_dn'],
                'account_suffix' => $_POST['account_suffix'],
                'use_ssl' => isset($_POST['use_ssl']),
                'port' => $_POST['port'] ?? 389,
                'timeout' => $_POST['timeout'] ?? 5,
                'admin_group' => $_POST['admin_group'] ?? 'Administrators',
                'allowed_groups' => [$_POST['admin_group'] ?? 'Administrators']
            ],
            'db' => [
                'host' => $_POST['db_host'] ?? 'localhost',
                'database' => $_POST['db_name'] ?? 'ad_management',
                'username' => $_POST['db_user'] ?? 'root',
                'password' => $_POST['db_pass'] ?? '',
                'charset' => 'utf8mb4'
            ],
            'default_language' => $_POST['default_language'] ?? 'en'
        ];

        // Update configuration
        if (!updateConfig($install_data)) {
            throw new Exception("Failed to save configuration");
        }

        // Redirect to success page
        header('Location: index.php?installed=1');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AD Management - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --progress-color: #36b9cc;
        }

        body {
            background: linear-gradient(135deg, #f8f9fc 0%, #dee2e6 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
        }

        .install-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .install-steps::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e3e6f0;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            background: #fff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid #e3e6f0;
            transition: all 0.3s ease;
        }

        .step.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }

        .step.completed {
            border-color: var(--success-color);
            background: var(--success-color);
            color: white;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }

        .progress {
            height: 0.5rem;
            background-color: #eaecf4;
        }

        .progress-bar {
            background-color: var(--progress-color);
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .step-title {
            text-align: center;
            margin: 1rem 0;
            color: #5a5c69;
        }

        .validation-error {
            color: #e74a3b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section.active {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <!-- Progress steps -->
        <div class="install-steps mb-4">
            <div class="step active" id="step1">1</div>
            <div class="step" id="step2">2</div>
            <div class="step" id="step3">3</div>
            <div class="step" id="step4">4</div>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    S-RCS - Installation
                </h4>
            </div>
            <div class="card-body p-4">
                <!-- Step titles -->
                <h5 class="step-title" id="stepTitle">S-RCS - Installation</h5>

                <!-- Installation sections -->
                <div class="form-sections">
                    <!-- Section 1: System Requirements -->
                    <div class="form-section active" id="section1">
                        <div id="requirementsCheck">
                            <div class="card">
                                <div class="card-header bg-primary text-white">System Requirements</div>
                                <div class="card-body">
                                    <div class="list-group" id="requirementsList">
                                        <!-- Requirements will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Domain Settings -->
                    <div class="form-section" id="section2">
                        <form id="domainSettingsForm" class="needs-validation" novalidate>
                            <!-- Domain Settings -->
                            <h5 class="border-bottom pb-2">Domain Settings</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="domain_controllers" class="form-label">Domain Controller IP</label>
                                    <input type="text" class="form-control" id="domain_controllers" required
                                           placeholder="192.168.1.1">
                                    <div class="invalid-feedback">Domain Controller IP is required</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="domain_name" class="form-label">Domain Name</label>
                                    <input type="text" class="form-control" id="domain_name" required
                                           placeholder="domain.local">
                                    <div class="invalid-feedback">Domain name is required</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="port" class="form-label">LDAPS Port</label>
                                    <input type="number" class="form-control" id="port" value="636" readonly>
                                    <small class="form-text text-muted">Default port for LDAPS is 636 (Ensure LDAPS is listening on TCP 636 and firewall rules allow traffic.)</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <label class="form-check-label" for="use_ssl">Use SSL (Required for LDAPS)</label>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle"></i>
                                            LDAPS requires SSL connection for secure communication
                                        </div>
                                    </div>
                                </div>
                               
                            </div>

                            <!-- Admin Settings -->
                            <h5 class="border-bottom pb-2">Admin Settings</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="admin_username" class="form-label">Admin Username</label>
                                    <input type="text" class="form-control" id="admin_username" required>
                                    <div class="form-text">Enter without domain (e.g., 'administrator')</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="admin_password" class="form-label">Admin Password</label>
                                    <input type="password" class="form-control" id="admin_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="admin_group" class="form-label">Admin Group</label>
                                    <input type="text" class="form-control" id="admin_group" value="Administrators">
                                </div>
                                <div class="col-md-6">
                                    <label for="default_language" class="form-label">Default Language</label>
                                    <select class="form-select" id="default_language" name="default_language" required>
                                        <?php foreach ($available_languages as $code => $name): ?>
                                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $code === 'en' ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i>
                                        Select the default language for the system
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Section 3: Database Settings -->
                    <div class="form-section" id="section3">
                        <form id="databaseSettingsForm" class="needs-validation" novalidate>
                            <!-- Database Settings -->
                            <h5 class="border-bottom pb-2">Database Settings</h5>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Docker:</strong> Host = <code>mysql</code> | <strong>XAMPP:</strong> Host = <code>localhost</code>
                            </div>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-cog me-2"></i>
                                <strong>Özelleştirme:</strong> Bu değerleri özelleştirmek istiyorsanız, <code>.env</code> dosyasını düzenleyin (docker-compose.yml ile aynı klasörde).
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="db_host" value="mysql" required>
                                    <div class="form-text">Docker: mysql | XAMPP: localhost</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="db_name" class="form-label">Database Name</label>
                                    <input type="text" class="form-control" id="db_name" value="ldap_auth" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="db_user" class="form-label">Database Username</label>
                                    <input type="text" class="form-control" id="db_user" value="srcs_admin" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="db_pass" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="db_pass" value="SrcS@2026!Secure" required>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Section 4: Confirmation -->
                    <div class="form-section" id="section4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Installation Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="mb-0">Domain Parameters</h6>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th>Domain Controller:</th>
                                                        <td id="summary_domain_controller"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Domain Name:</th>
                                                        <td id="summary_domain_name"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Port:</th>
                                                        <td id="summary_port">636 (LDAPS)</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Admin User:</th>
                                                        <td id="summary_admin_username"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Admin Group:</th>
                                                        <td id="summary_admin_group"></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="mb-0">Database Parameters</h6>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th>Host:</th>
                                                        <td id="summary_db_host"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Database:</th>
                                                        <td id="summary_db_name"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>User:</th>
                                                        <td id="summary_db_user"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Password:</th>
                                                        <td>******** (Hidden)</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">System Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th>Operating System:</th>
                                                        <td id="summary_os"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Web Server:</th>
                                                        <td id="summary_web_server"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>PHP Version:</th>
                                                        <td id="summary_php_version"></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th>Hostname:</th>
                                                        <td id="summary_hostname"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Server IP:</th>
                                                        <td id="summary_server_ip"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>MAC Address:</th>
                                                        <td id="summary_mac_address"></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Security Recommendations</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning">
                                            <p><i class="fas fa-shield-alt me-2"></i>Recommendations:</p>
                                            <ul class="mb-0">
                                                <li>Perform regular connection tests for AD verification</li>
                                                <li>Make regular database backups</li>
                                                <li>Store configuration information in a secure location</li>
                                                <li>Use a strong password for the admin account</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Attention:</strong> After clicking the "Start Installation" button, all the information you entered will be recorded in the system.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn">
                        Next<i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>

                <!-- Installation progress -->
                <div class="progress mt-4" style="display: none;" id="installProgress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 4;

        // Step titles
        const stepTitles = {
            1: 'System Requirements',
            2: 'Domain Settings',
            3: 'Database Settings',
            4: 'Confirmation'
        };

        function updateSteps(step) {
            document.querySelectorAll('.step').forEach((el, index) => {
                if (index + 1 < step) {
                    el.classList.add('completed');
                    el.classList.remove('active');
                } else if (index + 1 === step) {
                    el.classList.add('active');
                    el.classList.remove('completed');
                } else {
                    el.classList.remove('active', 'completed');
                }
            });

            // Update step title
            document.getElementById('stepTitle').textContent = stepTitles[step];

            // Show/hide sections
            document.querySelectorAll('.form-section').forEach((section, index) => {
                if (index + 1 === step) {
                    section.classList.add('active');
                } else {
                    section.classList.remove('active');
                }
            });

            // Update buttons
            document.getElementById('prevBtn').style.display = step > 1 ? 'block' : 'none';
            const nextBtn = document.getElementById('nextBtn');
            nextBtn.textContent = step === totalSteps ? 'Start Installation' : 'Next';
        }

        document.getElementById('nextBtn').addEventListener('click', async function() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateSteps(currentStep);
                
                // If it's the last step, update summary data
                if (currentStep === totalSteps) {
                    updateSummaryData();
                }
            } else {
                // Start installation
                this.disabled = true;
                document.getElementById('prevBtn').disabled = true;
                document.getElementById('installProgress').style.display = 'block';
                
                // Notify user
                console.log('Installation process started...');
                
                // Clean up existing dialogs
                cleanupExistingDialogs();
                
                try {
                    // Submit installation data
                    const response = await submitInstallation();
                    
                    if (response.success) {
                        console.log('Installation successful, data displayed...');
                        // Show installation data
                        showInstallationCompleteDialog(response);
                    } else {
                        throw new Error(response.error);
                    }
                } catch (error) {
                    alert('Installation error: ' + error.message);
                    this.disabled = false;
                    document.getElementById('prevBtn').disabled = false;
                    document.getElementById('installProgress').style.display = 'none';
                }
            }
        });

        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateSteps(currentStep);
            }
        });

        // Initialize first step
        updateSteps(1);

        // Check system requirements on load
        checkRequirements();

        async function submitInstallation() {
            // Get all form data
            const dcValue = document.getElementById('domain_controllers').value.trim();
            const formData = {
                domain_controllers: dcValue ? dcValue.split(',').map(ip => ip.trim()) : [], // Split by comma for multiple IPs
                domain_name: document.getElementById('domain_name').value.trim(),
                port: parseInt(document.getElementById('port').value),
                admin_username: document.getElementById('admin_username').value.trim(),
                admin_password: document.getElementById('admin_password').value,
                admin_group: document.getElementById('admin_group').value.trim(),
                default_language: document.getElementById('default_language').value,
                db_host: document.getElementById('db_host').value.trim(),
                db_name: document.getElementById('db_name').value.trim(),
                db_user: document.getElementById('db_user').value.trim(),
                db_pass: document.getElementById('db_pass').value
            };

            // Update progress bar
            const progressBar = document.querySelector('.progress-bar');
            progressBar.style.width = '0%';
            document.getElementById('installProgress').style.display = 'block';

            try {
                // Validate form data
                if (!formData.domain_controllers.length) {
                    throw new Error('Domain Controller IP is required');
                }

                // Update progress
                progressBar.style.width = '25%';

                // Send installation request
                const response = await fetch('installer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(formData)
                });

                progressBar.style.width = '50%';

                // Parse response
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error || 'Unknown error');
                }

                progressBar.style.width = '100%';
                
                return result;

            } catch (error) {
                showAlert('danger', 'Error: ' + error.message);
                throw error;
            }
        }

        // Quraşdırma tamamlandıqda informasiya dialoqunu göstərmək üçün funksiya
        function showInstallationCompleteDialog(data) {
            // Əvvəlcə bütün mövcud card-body elementlərini təmizləyək
            const card = document.querySelector('.card');
            const cardBodies = card.querySelectorAll('.card-body');
            
            // Bütün card-body elementlərini silib təmizləyirik
            cardBodies.forEach(element => {
                element.remove();
            });
            
            // Əgər hələ də mövcuddursa progress bar-ı gizlədək
            const progressBar = document.getElementById('installProgress');
            if (progressBar) {
                progressBar.style.display = 'none';
            }
            
            // Düymələri gizlədək
            const buttonsContainer = document.querySelector('.d-flex.justify-content-between.mt-4');
            if (buttonsContainer) {
                buttonsContainer.style.display = 'none';
            }
            
            // İndi yeni təmiz kart body əlavə edək
            const successDialog = document.createElement('div');
            successDialog.className = 'card-body text-center';
            successDialog.innerHTML = `
                <div class="my-5">
                    <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                    <h3 class="text-success mb-4">Installation Successful!</h3>
                    
                    <div class="card mb-4 mx-auto" style="max-width: 650px;">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i>License Information</h5>
                        </div>
                        <div class="card-body">
                            <p class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Please keep your license key in a secure location. It will be needed to reinstall the system or get technical support!
                            </p>
                            <div class="mb-3">
                                <label class="form-label fw-bold">License Key:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" value="${data.license_key}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="copyLicenseBtn">
                                        <i class="fas fa-copy me-1"></i> Copy
                                    </button>
                                </div>
                                <small class="text-muted">License Expiration Date: ${data.date || new Date().toLocaleString()}</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">System Identifier:</label>
                                <p class="mb-1 font-monospace small">Hostname: ${data.server_info?.hostname || 'Unknown'}</p>
                                <p class="mb-1 font-monospace small">MAC: ${data.system_identifiers?.mac_address || 'Unknown'}</p>
                                <p class="font-monospace small">IP: ${data.system_identifiers?.server_ip || 'Unknown'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success mx-auto" style="max-width: 650px;">
                        <h5><i class="fas fa-info-circle me-2"></i>Installation Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Version:</strong> ${data.version || '1.5.0'}</p>
                                <p><strong>Installation Date:</strong> ${data.date || new Date().toLocaleString()}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Admin Username:</strong> ${data.admin_username || document.getElementById('admin_username').value}</p>
                                <p><strong>Domain:</strong> ${data.domain || document.getElementById('domain_name').value}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-home me-2"></i>Go to Homepage
                        </a>
                        <a href="#" class="btn btn-outline-secondary btn-lg" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </a>
                    </div>
                </div>
            `;
            
            // Add to card element
            card.appendChild(successDialog);
            
            console.log('Installation data displayed');
            
            // Add copy button click event
            setTimeout(() => {
                const copyBtn = document.getElementById('copyLicenseBtn');
                if (copyBtn) {
                    copyBtn.addEventListener('click', () => {
                        navigator.clipboard.writeText(data.license_key)
                            .then(() => {
                                copyBtn.innerHTML = '<i class="fas fa-check me-1"></i> Copied';
                                copyBtn.classList.remove('btn-outline-secondary');
                                copyBtn.classList.add('btn-success');
                                setTimeout(() => {
                                    copyBtn.innerHTML = '<i class="fas fa-copy me-1"></i> Copy';
                                    copyBtn.classList.remove('btn-success');
                                    copyBtn.classList.add('btn-outline-secondary');
                                }, 2000);
                            })
                            .catch(err => {
                                console.error('Copy error:', err);
                                alert('Copy failed. Please copy the license key manually.');
                            });
                    });
                }
            }, 500);
        }

        function validateFormData(data) {
            // Domain settings validation
            if (!data.domain_settings.domain_controllers[0]) return false;
            if (!data.domain_settings.domain_name) return false;
            
            // Admin settings validation
            if (!data.admin_settings.admin_username) return false;
            if (!data.admin_settings.admin_password) return false;
            
            // Database settings validation
            if (!data.db_settings.db_host) return false;
            if (!data.db_settings.db_name) return false;
            if (!data.db_settings.db_user) return false;
            
            return true;
        }

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const cardBody = document.querySelector('.card-body');
            cardBody.insertBefore(alertDiv, cardBody.firstChild);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        async function checkRequirements() {
            try {
                const response = await fetch('installer.php', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.toLowerCase().includes("application/json")) {
                    const text = await response.text();
                    console.error('Server response:', text);
                    throw new Error(`Invalid content type: ${contentType}`);
                }

                const data = await response.json();
                if (!data || typeof data.success === 'undefined') {
                    throw new Error('Invalid response format');
                }

                if (!data.success) {
                    throw new Error(data.error || 'Unknown error');
                }

                const requirementsDiv = document.getElementById('requirementsList');
                let html = '';

                // Show detected system info
                if (data.system_info) {
                    html += `
                        <div class="alert alert-info">
                            <h6>Detected System Information:</h6>
                            <small>
                                Operating System: ${data.system_info.os.name} ${data.system_info.os.version}<br>
                                Server: ${data.system_info.server.software}<br>
                                PHP Version: ${data.system_info.php.version}
                            </small>
                        </div>
                    `;
                }
                
                Object.entries(data.requirements).forEach(([key, value]) => {
                    const statusClass = value.status ? 'success' : 'danger';
                    const icon = value.status ? 'check' : 'times';
                    
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">${key}</h6>
                                    <small class="text-muted">
                                        Required: ${value.required}<br>
                                        Current: ${value.current}
                                    </small>
                                    ${key === 'Mail Function' && value.current_config ? `
                                        <div class="mt-2 alert alert-info">
                                            <strong>Current Mail Configuration:</strong><br>
                                            ${Object.entries(value.current_config).map(([k, v]) => 
                                                `<small>${k}: ${v}</small><br>`
                                            ).join('')}
                                        </div>
                                    ` : ''}
                                    ${!value.status ? `
                                        <div class="mt-2 alert alert-warning">
                                            <strong>Installation Instructions for ${value.server_detected}:</strong>
                                            <ol>
                                                ${value.manual_steps[value.system_detected]?.map(step => `<li>${step}</li>`).join('') || 
                                                  value.manual_steps[value.server_detected]?.map(step => `<li>${step}</li>`).join('') || 
                                                  'No specific instructions available'}
                                            </ol>
                                        </div>
                                    ` : ''}
                                </div>
                                <span class="badge bg-${statusClass} rounded-pill">
                                    <i class="fas fa-${icon}"></i>
                                </span>
                            </div>
                        </div>
                    `;
                });
                
                requirementsDiv.innerHTML = html;
                document.getElementById('nextBtn').disabled = !data.allPassed;
                
            } catch (error) {
                console.error('Requirements check failed:', error);
                document.getElementById('requirementsList').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to check requirements: ${error.message}
                        <br><small class="text-muted">Check browser console for details</small>
                    </div>
                `;
            }
        }

        // Initialize
        updateSteps(1);
        checkRequirements();

        // Domain settings validation function
        function validateDomainSettings() {
            const domainControllers = document.getElementById('domain_controllers').value;
            const domainName = document.getElementById('domain_name').value;
            
            // Basic validation
            if (!domainControllers || !domainName) {
                showAlert('danger', 'Domain Controller IP and Domain Name are required');
                return false;
            }
            
            // IP address format validation
            const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
            if (!ipRegex.test(domainControllers)) {
                showAlert('danger', 'Please enter a valid IP address for Domain Controller');
                return false;
            }
            
            // Domain name format validation
            const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-]*\.[a-zA-Z]{2,}$/;
            if (!domainRegex.test(domainName)) {
                showAlert('danger', 'Please enter a valid domain name (e.g., domain.local)');
                return false;
            }
            
            return true;
        }

        // LDAPS connection test function
        async function testLDAPSConnection() {
            const testData = {
                domain_controllers: [document.getElementById('domain_controllers').value],
                domain_name: document.getElementById('domain_name').value,
                admin_username: document.getElementById('admin_username').value,
                admin_password: document.getElementById('admin_password').value,
                use_ssl: true,
                port: 636
            };

            try {
                const response = await fetch('installer.php?action=test_ldaps', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testData)
                });

                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error);
                }

                showAlert('success', 'LDAPS connection test successful');
                return true;
            } catch (error) {
                showAlert('danger', 'LDAPS connection test failed: ' + error.message);
                return false;
            }
        }

        // Update summary data function
        function updateSummaryData() {
            // Get entered data
            // Domain data
            document.getElementById('summary_domain_controller').textContent = document.getElementById('domain_controllers').value || 'Not set';
            document.getElementById('summary_domain_name').textContent = document.getElementById('domain_name').value || 'Not set';
            document.getElementById('summary_admin_username').textContent = document.getElementById('admin_username').value || 'Not set';
            document.getElementById('summary_admin_group').textContent = document.getElementById('admin_group').value || 'Administrators';
            
            // Database data
            document.getElementById('summary_db_host').textContent = document.getElementById('db_host').value || 'localhost';
            document.getElementById('summary_db_name').textContent = document.getElementById('db_name').value || 'ad_management';
            document.getElementById('summary_db_user').textContent = document.getElementById('db_user').value || 'root';
            
            // Fetch system info
            fetchSystemInfo();
        }
        
        // Fetch system info function
        async function fetchSystemInfo() {
            try {
                const response = await fetch('installer.php?action=get_system_info', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Set system data
                    document.getElementById('summary_os').textContent = data.system_info.os.name + ' ' + data.system_info.os.version;
                    document.getElementById('summary_web_server').textContent = data.system_info.server.software;
                    document.getElementById('summary_php_version').textContent = data.system_info.php.version;
                    document.getElementById('summary_hostname').textContent = data.system_info.hostname || 'Unknown';
                    document.getElementById('summary_server_ip').textContent = data.system_info.server_ip || 'Unknown';
                    document.getElementById('summary_mac_address').textContent = data.system_info.mac_address || 'Unknown';
                    document.getElementById('summary_install_date').textContent = new Date().toLocaleString();
                }
            } catch (error) {
                console.error('Error fetching system info:', error);
            }
        }

        // Clean up existing dialogs and other automatically created elements
        function cleanupExistingDialogs() {
            // Clean up existing dialogs
            console.log('Cleaning up existing dialogs and elements...');
            
            // Sazlama məlumatı
            console.log('Mövcud pəncərələr və məlumatlar təmizlənir...');
            
            // Mövcud ola biləcək success dialog divlərini təmizləyirik
            const successDialogs = document.querySelectorAll('div.card-body.text-center');
            successDialogs.forEach(dialog => {
                dialog.remove();
            });
            
            // Bütün imkanları araşdıraq - bəlkə birdən çox kart var?
            const allCards = document.querySelectorAll('.card');
            allCards.forEach(card => {
                // Əsas kartı saxlamaq üçün - yalnız əlavə edilmiş dialoqları təmizləyirik
                if (card.querySelector('.card-header')) {
                    // Bu əsas kartdır, yalnız əlavə edilmiş məlumatları siləcəyik
                    const dialogs = card.querySelectorAll('.card-body.text-center');
                    dialogs.forEach(dialog => dialog.remove());
                } else {
                    // Bu əlavə edilmiş ikinci kartdır, tamamilə silinə bilər
                    card.remove();
                }
            });
            
            // Əmin olmaq üçün bütün success alertləri də silərik
            document.querySelectorAll('.alert-success').forEach(alert => {
                // Əsas məlumat bölməsində olmayan alertləri silək
                if (!alert.closest('.form-section')) {
                    alert.remove();
                }
            });
            
            console.log('Mövcud pəncərələr və məlumatlar təmizləndi');
        }
    </script>
</body>
</html>
