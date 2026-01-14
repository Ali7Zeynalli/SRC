<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [ali] <[ali.z.zeynalli@gmail.com]> [2025]
  */
  
// Clean any previous output and start fresh buffer
while (ob_get_level()) ob_end_clean();
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Add this function at the top of the file after the initial comments
function getMacAddress() {
    try {
        // For Windows systems
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "ipconfig /all";
            exec($cmd, $output);
            foreach($output as $line) {
                if (preg_match('/Physical Address[^:]+: ([A-F0-9-]+)/', $line, $matches)) {
                    return str_replace('-', ':', $matches[1]);
                }
            }
        } 
        // For Linux systems
        else {
            $cmd = "ifconfig -a || ip link";
            exec($cmd, $output);
            foreach($output as $line) {
                if (preg_match('/ether ([0-9a-f:]+)/i', $line, $matches)) {
                    return $matches[1];
                }
            }
        }
    } catch (Exception $e) {
        error_log("MAC address detection failed: " . $e->getMessage());
    }
    
    return 'Unknown';
}

// Add this function at the top of the file after the initial comments
function getSystemIdentifiers() {
    $identifiers = [
        'machine_id' => '',
        'disk_serial' => '',
        'motherboard_id' => '',
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
        'mac_address' => getMacAddress()
    ];

    try {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows identifiers
            $identifiers['machine_id'] = trim(shell_exec('reg query HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Cryptography /v MachineGuid'));
            $identifiers['disk_serial'] = trim(shell_exec('wmic diskdrive get serialnumber'));
            $identifiers['motherboard_id'] = trim(shell_exec('wmic baseboard get serialnumber'));
        } else {
            // Linux identifiers
            $identifiers['machine_id'] = trim(file_get_contents('/etc/machine-id'));
            $identifiers['disk_serial'] = trim(shell_exec('lsblk -o UUID | head -n2 | tail -n1'));
            $identifiers['motherboard_id'] = trim(shell_exec('sudo dmidecode -s baseboard-serial-number'));
        }
    } catch (Exception $e) {
        error_log("Error getting system identifiers: " . $e->getMessage());
    }

    return $identifiers;
}

function generateLicenseKey($identifiers) {
    $unique_string = implode('|', [
        $identifiers['machine_id'],
        $identifiers['disk_serial'],
        $identifiers['motherboard_id'],
        $identifiers['server_ip'],
        $identifiers['mac_address']
    ]);

    return hash('sha256', $unique_string . 'YOUR_SECRET_SALT_HERE');
}

// Define module requirements
$modules = [
    'ldap' => [
        'name' => 'LDAP Extension',
        'required' => true,
        'manual_steps' => [
            'Windows' => [
                '1. Open php.ini in your XAMPP installation',
                '2. Find and uncomment ;extension=ldap',
                '3. Restart Apache server'
            ],
            'Linux' => [
                '1. Run: sudo apt-get install php-ldap',
                '2. Restart Apache: sudo service apache2 restart'
            ]
        ]
    ],
    'pdo' => [
        'name' => 'PDO Extension',
        'required' => true,
        'manual_steps' => [
            'Windows' => [
                '1. Open php.ini in your XAMPP installation',
                '2. Find and uncomment ;extension=pdo',
                '3. Restart Apache server'
            ],
            'Linux' => [
                '1. Run: sudo apt-get install php-mysql',
                '2. Restart Apache: sudo service apache2 restart'
            ]
        ]
    ],
    'pdo_mysql' => [
        'name' => 'MySQL Extension',
        'required' => true,
        'manual_steps' => [
            'Windows' => [
                '1. Open php.ini in your XAMPP installation',
                '2. Find and uncomment ;extension=pdo_mysql',
                '3. Restart Apache server'
            ],
            'Linux' => [
                '1. Run: sudo apt-get install php-mysql',
                '2. Restart Apache: sudo service apache2 restart'
            ]
        ]
    ],
    'openssl' => [
        'name' => 'OpenSSL Extension',
        'required' => true,
        'manual_steps' => [
            'Windows' => [
                '1. Open php.ini in your XAMPP installation',
                '2. Find and uncomment ;extension=openssl',
                '3. Restart Apache server'
            ],
            'Linux' => [
                '1. Run: sudo apt-get install php-openssl',
                '2. Restart Apache: sudo service apache2 restart'
            ]
        ]
    ],
    'mail' => [
        'name' => 'Mail Function',
        'required' => true,
        'manual_steps' => [
            'XAMPP' => [
                "1. Open php.ini at: {php_ini_path}",
                "2. Find [mail function] section",
                "3. Set SMTP=localhost",
                "4. Set smtp_port=25",
                "5. Configure Mercury in XAMPP Control Panel",
                "6. Start Mercury mail server",
                "7. Restart Apache"
            ],
            'WAMP' => [
                "1. Open WAMP tray icon",
                "2. PHP -> PHP Settings -> mail.smtp",
                "3. Set to your SMTP server",
                "4. PHP -> PHP Settings -> mail.smtp_port",
                "5. Set port (usually 25 or 587)",
                "6. Configure sendmail path in php.ini",
                "7. Restart WAMP"
            ],
            'Standard Apache' => [
                "1. Install postfix: sudo apt-get install postfix",
                "2. Configure postfix: sudo dpkg-reconfigure postfix",
                "3. Install sendmail: sudo apt-get install sendmail",
                "4. Edit php.ini and set sendmail_path = /usr/sbin/sendmail -t -i",
                "5. Restart Apache: sudo service apache2 restart"
            ],
            'IIS' => [
                "1. Install IIS SMTP Server role",
                "2. Configure SMTP Server in IIS Manager",
                "3. Open php.ini",
                "4. Set SMTP=localhost",
                "5. Set smtp_port=25",
                "6. Restart IIS"
            ]
        ],
        'config_test' => [
            'command' => '<?php mail("test@example.com", "Test", "Test"); ?>',
            'expected_output' => 'Email sent successfully'
        ]
    ]
];

// Handle requests
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Request to get system information
        if (isset($_GET['action']) && $_GET['action'] === 'get_system_info') {
            // Get system identifiers
            $system_identifiers = getSystemIdentifiers();
            
            // Add hostname and other server information
            $system_info = [
                'hostname' => gethostname(),
                'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
                'mac_address' => $system_identifiers['mac_address'],
                'os' => [
                    'name' => PHP_OS_FAMILY,
                    'version' => php_uname('v'),
                    'full_details' => php_uname()
                ],
                'server' => [
                    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                ],
                'php' => [
                    'version' => phpversion()
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'system_info' => $system_info
            ]);
            exit;
        }

        // Detect web server and environment
        function detectWebServer() {
            $server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
            $server_info = [
                'type' => 'Unknown',
                'version' => '',
                'details' => $server_software
            ];

            // Detect XAMPP
            if (stripos($server_software, 'XAMPP') !== false) {
                $server_info['type'] = 'XAMPP';
                if (preg_match('/XAMPP\/([0-9\.]+)/', $server_software, $matches)) {
                    $server_info['version'] = $matches[1];
                }
            }
            // Detect WAMP
            elseif (stripos($server_software, 'WAMP') !== false || file_exists('c:/wamp') || file_exists('c:/wamp64')) {
                $server_info['type'] = 'WAMP';
                // Try to get WAMP version from phpinfo
                ob_start();
                phpinfo(INFO_GENERAL);
                $phpinfo = ob_get_clean();
                if (preg_match('/WAMP([0-9\.]+)/', $phpinfo, $matches)) {
                    $server_info['version'] = $matches[1];
                }
            }
            // Detect MAMP
            elseif (stripos($server_software, 'MAMP') !== false || file_exists('/Applications/MAMP/')) {
                $server_info['type'] = 'MAMP';
            }
            // Detect standard Apache
            elseif (stripos($server_software, 'Apache') !== false) {
                $server_info['type'] = 'Apache';
                if (preg_match('/Apache\/([0-9\.]+)/', $server_software, $matches)) {
                    $server_info['version'] = $matches[1];
                }
            }
            // Detect IIS
            elseif (stripos($server_software, 'IIS') !== false) {
                $server_info['type'] = 'IIS';
                if (preg_match('/IIS\/([0-9\.]+)/', $server_software, $matches)) {
                    $server_info['version'] = $matches[1];
                }
            }

            // Get installation path
            $server_info['install_path'] = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
            $server_info['php_ini'] = str_replace('\\', '/', php_ini_loaded_file());
            
            // Add mail configuration detection
            $mail_config = [
                'smtp_host' => ini_get('SMTP'),
                'smtp_port' => ini_get('smtp_port'),
                'sendmail_path' => ini_get('sendmail_path'),
                'mail_enabled' => function_exists('mail'),
                'has_openssl' => extension_loaded('openssl')
            ];

            $server_info['mail_config'] = $mail_config;
            
            // Get specific mail instructions based on server type
            switch($server_info['type']) {
                case 'XAMPP':
                    $server_info['mail_instructions'] = $modules['mail']['manual_steps']['XAMPP'];
                    break;
                case 'WAMP':
                    $server_info['mail_instructions'] = $modules['mail']['manual_steps']['WAMP'];
                    break;
                case 'IIS':
                    $server_info['mail_instructions'] = $modules['mail']['manual_steps']['IIS'];
                    break;
                default:
                    $server_info['mail_instructions'] = $modules['mail']['manual_steps']['Standard Apache'];
            }

            return $server_info;
        }

        // Get system information
        $server_details = detectWebServer();
        $system_info = [
            'os' => [
                'name' => PHP_OS_FAMILY,
                'version' => php_uname('v'),
                'is_windows' => stripos(PHP_OS, 'WIN') === 0,
                'full_details' => php_uname()
            ],
            'server' => array_merge([
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'is_xampp' => stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'XAMPP') !== false,
                'is_wamp' => stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'WAMP') !== false,
            ], $server_details),
            'php' => [
                'version' => phpversion(),
                'modules' => get_loaded_extensions(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'php_ini_path' => php_ini_loaded_file()
            ]
        ];

        // Check system requirements based on detected environment
        $requirements = [];
        foreach ($modules as $module => $info) {
            $installed = extension_loaded($module);
            
            // Customize installation steps based on server type
            $manual_steps = [
                'Windows' => [],
                'Linux' => []
            ];

            if ($system_info['os']['is_windows']) {
                $php_ini_path = $system_info['server']['php_ini'];
                $server_type = $system_info['server']['type'];
                
                switch($server_type) {
                    case 'XAMPP':
                        $manual_steps['Windows'] = [
                            "1. Open php.ini at: {$php_ini_path}",
                            "2. Find and uncomment ;extension={$module}",
                            "3. Save php.ini",
                            "4. Open XAMPP Control Panel",
                            "5. Click 'Apache' -> 'Stop'",
                            "6. Click 'Apache' -> 'Start'"
                        ];
                        break;
                    case 'WAMP':
                        $manual_steps['Windows'] = [
                            "1. Open WAMP tray icon",
                            "2. Go to PHP -> PHP Extensions",
                            "3. Check {$module}",
                            "4. Wait for WAMP to restart"
                        ];
                        break;
                    default:
                        $manual_steps['Windows'] = [
                            "1. Locate php.ini at: {$php_ini_path}",
                            "2. Add or uncomment: extension={$module}",
                            "3. Save php.ini",
                            "4. Restart your web server"
                        ];
                }
            } else {
                // Linux steps
                $package_name = match($module) {
                    'ldap' => 'php-ldap',
                    'pdo_mysql' => 'php-mysql',
                    'openssl' => 'php-openssl',
                    default => "php-{$module}"
                };

                $manual_steps['Linux'] = [
                    "1. Run: sudo apt-get install {$package_name}",
                    "2. Restart Apache: sudo service apache2 restart"
                ];
            }

            if ($module === 'mail') {
                $mail_enabled = function_exists('mail');
                $smtp_configured = ini_get('SMTP') && ini_get('smtp_port');
                
                $current_config = [
                    'SMTP Server' => ini_get('SMTP') ?: 'Not configured',
                    'SMTP Port' => ini_get('smtp_port') ?: 'Not configured',
                    'Sendmail Path' => ini_get('sendmail_path') ?: 'Not configured'
                ];
            
                $requirements[$info['name']] = [
                    'required' => 'Enabled & Configured',
                    'current' => $mail_enabled ? ($smtp_configured ? 'Enabled & Configured' : 'Enabled but not configured') : 'Disabled',
                    'status' => $mail_enabled && $smtp_configured,
                    'manual_steps' => $info['manual_steps'][$system_info['server']['type']] ?? $info['manual_steps']['Standard Apache'],
                    'system_detected' => $system_info['os']['is_windows'] ? 'Windows' : 'Linux',
                    'server_detected' => $system_info['server']['type'],
                    'current_config' => $current_config
                ];
                continue;
            }

            $requirements[$info['name']] = [
                'required' => 'Enabled',
                'current' => $installed ? 'Enabled' : 'Disabled',
                'status' => $installed,
                'manual_steps' => $manual_steps,
                'system_detected' => $system_info['os']['is_windows'] ? 'Windows' : 'Linux',
                'server_detected' => $system_info['server']['type']
            ];
        }

        // Add PHP version check
        $requirements['PHP Version'] = [
            'required' => '7.4.0',
            'current' => phpversion(),
            'status' => version_compare(phpversion(), '7.4.0', '>=')
        ];

        // Add directory check
        $requirements['Config Directory'] = [
            'required' => 'Writable',
            'current' => is_writable(__DIR__ . '/config') ? 'Writable' : 'Not Writable',
            'status' => is_writable(__DIR__ . '/config')
        ];

        // Add memory check
        $requirements['Memory Limit'] = [
            'required' => '128M',
            'current' => ini_get('memory_limit'),
            'status' => intval(ini_get('memory_limit')) >= 128
        ];

        // Calculate overall status
        $allPassed = array_reduce($requirements, function($carry, $item) {
            return $carry && $item['status'];
        }, true);

        echo json_encode([
            'success' => true,
            'system_info' => $system_info,
            'requirements' => $requirements,
            'allPassed' => $allPassed,
            'message' => $allPassed ? 'All requirements met' : 'Some requirements not met'
        ]);
        exit;
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid installation data');
            }

            // Get system identifiers
            $system_identifiers = getSystemIdentifiers();
            $license_key = generateLicenseKey($system_identifiers);

            // Test AD connection
            try {
                // Format LDAPS URL correctly
                $ldap_url = 'ldaps://' . $input['domain_controllers'][0] . ':636';
                
                $ldap = ldap_connect($ldap_url);
                if (!$ldap) {
                    throw new Exception("Could not connect to AD server");
                }

                // LDAP basic parameters
                ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 10);
                ldap_set_option($ldap, LDAP_OPT_TIMELIMIT, 15);
                
                // SSL/TLS parameters
                putenv('LDAPTLS_REQCERT=never');
                ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                ldap_set_option($ldap, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);

                // Test bind with admin credentials
                $admin_dn = $input['admin_username'] . '@' . $input['domain_name'];
                if (!@ldap_bind($ldap, $admin_dn, $input['admin_password'])) {
                    throw new Exception("Could not bind to AD server with provided credentials");
                }
            } catch (Exception $e) {
                throw new Exception('AD connection test failed: ' . $e->getMessage());
            }

            // Test database connection with error mode set
            try {
                $dsn = "mysql:host={$input['db_host']};charset=utf8mb4";
                $pdo = new PDO($dsn, $input['db_user'], $input['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                
                // Create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$input['db_name']}`");
                
                // Select the database
                $pdo->exec("USE `{$input['db_name']}`");
                
                // Import SQL schema
                $schemaFile = __DIR__ . '/install/schema.sql';
                importSQL($pdo, $schemaFile);

            } catch (PDOException $e) {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }

            // Load or create config
            $config_file = __DIR__ . '/config/config.php';
            $config = file_exists($config_file) ? require($config_file) : [];
            
            // Installation data with enhanced identifier
            $config['installation'] = [
                'installed' => true,
                'first_login' => true,
                'date' => date('Y-m-d H:i:s'),
                'version' => '1.2.0',
                'last_update' => date('Y-m-d H:i:s'),
                'installer' => $input['admin_username'],
                'install_hash' => hash('sha256', implode('|', [
                    gethostname(),                    // Server name
                    php_uname(),                      // System information
                    $input['domain_name'],            // Domain name
                    date('Y-m-d H:i:s'),              // Installation date
                    $input['admin_username'],         // Installing user
                    uniqid(mt_rand(), true)           // Random string
                ])),
                'install_details' => [
                    'hostname' => gethostname(),
                    'os' => PHP_OS,
                    'php_version' => phpversion(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'install_date' => date('Y-m-d H:i:s'),
                    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
                    'mac_address' => getMacAddress(), // Replace client_ip with mac_address
                ],
                'environment' => 'production'
            ];
            
            // Password settings
            $config['password_settings'] = [
                'default_temp_password' => 'Welcome2024!1111',  // Default temporary password
                'min_length' => 8,                              // Minimum password length
                'complexity' => true                            // Password complexity requirement
            ];
            
            // AD settings
            $config['ad_settings'] = [
                'domain_controllers' => $input['domain_controllers'],
                'domain_name' => $input['domain_name'],
                'base_dn' => 'DC=' . implode(',DC=', explode('.', $input['domain_name'])),
                'account_suffix' => '@' . $input['domain_name'],
                'use_ssl' => true,  // Always true for LDAPS
                'port' => 636,      // LDAPS port
                'admin_group' => $input['admin_group'] ?? 'Administrators',
                'allowed_groups' => [
                    $input['admin_group'] ?? 'Administrators'
                ],
                'timeout' => 10,
                'ssl_options' => [
                    'verify_cert' => true,
                    'allow_self_signed' => true,
                    'ca_cert' => null,  // SSL certificate path (if available)
                    'peer_name' => null // Server hostname (if needed)
                ]
            ];
            
            // Database settings
            $config['db_settings'] = [
                'host' => $input['db_host'],
                'database' => $input['db_name'],
                'username' => $input['db_user'],
                'password' => $input['db_pass'],
                'charset' => 'utf8mb4'
            ];

            // Server settings
            $config['server_settings'] = [
                'environment' => 'production',
                'debug' => false,
                'timezone' => 'UTC'
            ];

            // Language settings - only store default language
            $config['language_settings'] = [
                'default_language' => $input['default_language'] ?? 'en'
            ];

            // Pagination settings
            $config['pagination_settings'] = [
                'default_page_size' => 15,
                'page_size_options' => [5, 10, 15, 25, 50, 100, -1]
            ];

    

            // Update configuration with system identifiers and license
            $config['system_identifiers'] = $system_identifiers;
            $config['license'] = [
                'key' => $license_key,
                'generated_at' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ];
            
            // Save configuration
            $config_content = "<?php\nreturn " . var_export($config, true) . ";\n";
            if (!file_put_contents($config_file, $config_content)) {
                throw new Exception('Failed to save configuration');
            }

            // Create .installed file
            file_put_contents(__DIR__ . '/config/.installed', json_encode([
                'date' => date('Y-m-d H:i:s'),
                'version' => '1.5.0',
                'license_key' => $license_key,
                'server_info' => [
                    'hostname' => gethostname(),
                    'ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
                    'os' => PHP_OS,
                    'php_version' => phpversion()
                ]
            ]));

            // Prepare response data
            $installation_details = [
                'success' => true,
                'message' => 'Installation completed successfully',
                'version' => '1.5.0',
                'license_key' => $license_key,
                'date' => date('Y-m-d H:i:s'),
                'admin_username' => $input['admin_username'],
                'domain' => $input['domain_name'],
                'server_info' => [
                    'hostname' => gethostname(),
                    'ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
                    'os' => PHP_OS,
                    'php' => phpversion(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
                ],
                'system_identifiers' => [
                    'mac_address' => $system_identifiers['mac_address'],
                    'server_ip' => $system_identifiers['server_ip']
                ]
            ];

            echo json_encode($installation_details);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Ensure clean output
    if (ob_get_length()) ob_end_flush();
    exit;
}

// Update the importSQL function
function importSQL($pdo, $sqlFile) {
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL schema file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        throw new Exception("Could not read SQL file");
    }

    // Execute each query separately without transaction
    $queries = array_filter(
        array_map(
            'trim',
            explode(';', $sql)
        )
    );

    try {
        foreach ($queries as $query) {
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
        return true;
    } catch (PDOException $e) {
        throw new Exception("SQL Import failed: " . $e->getMessage());
    }
}
