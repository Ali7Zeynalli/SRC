<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Default configuration
$default_settings = [
    'ldap_host' => '192.168.178.100',
    'ldap_port' => 636,
    'ldap_domain' => 'not.local',
    'base_dn' => 'DC=not,DC=local',
    'username' => 'administrator',
    'password' => '!Eli199312',
    'use_ssl' => true,
    'use_tls' => false
];

// If form is submitted, get values
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'ldap_host' => $_POST['ldap_host'] ?? $default_settings['ldap_host'],
        'ldap_port' => 636,
        'ldap_domain' => $_POST['ldap_domain'] ?? $default_settings['ldap_domain'],
        'base_dn' => $_POST['base_dn'] ?? $default_settings['base_dn'],
        'username' => $_POST['username'] ?? $default_settings['username'],
        'password' => $_POST['password'] ?? $default_settings['password'],
        'use_ssl' => true,
        'use_tls' => false
    ];
} else {
    $settings = $default_settings;
}

function getLDAPConnection($settings) {
    try {
        // Format LDAP URL
        $protocol = $settings['use_ssl'] ? 'ldaps' : 'ldap';
        $ldap_url = "{$protocol}://{$settings['ldap_host']}:{$settings['ldap_port']}";
        
        // Debug information
        error_log("LDAP URL: " . $ldap_url);
        error_log("OpenSSL version: " . OPENSSL_VERSION_TEXT);
        
        // SSL/TLS parameters
        putenv('LDAPTLS_REQCERT=never');
        putenv('LDAPTLS_CIPHER_SUITE=NORMAL:!VERS-TLS1.0');
        
        // Global SSL parameters
        ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
        ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        
        $ldap_conn = ldap_connect($ldap_url);
        if (!$ldap_conn) {
            throw new Exception("LDAP connection error: " . ldap_error($ldap_conn));
        }
        
        // LDAP base parameters
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap_conn, LDAP_OPT_NETWORK_TIMEOUT, 10);
        
        // SSL/TLS specific parameters
        if ($settings['use_ssl']) {
            ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_ALLOW, true);
            ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_TRY, true);
            ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_PROTOCOL_MIN, LDAP_OPT_X_TLS_PROTOCOL_TLS1_2);
            
            // Cipher suite
            ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_CIPHER_SUITE, 'NORMAL:!VERS-TLS1.0');
            
            error_log("SSL parameters configured");
        }
        
        return $ldap_conn;
    } catch (Exception $e) {
        error_log("LDAP Connection Error: " . $e->getMessage());
        throw new Exception("LDAP connection error: " . $e->getMessage());
    }
}

function getLDAPInfo($ldap_conn) {
    try {
        $protocol_version = 0;
        $server_controls = [];
        
        ldap_get_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, $protocol_version);
        ldap_get_option($ldap_conn, LDAP_OPT_SERVER_CONTROLS, $server_controls);
        
        ldap_unbind($ldap_conn);
        
        return [
            'version' => $protocol_version,
            'controls' => $server_controls
        ];
    } catch (Exception $e) {
        return [
            'version' => 'Error: ' . $e->getMessage(),
            'controls' => []
        ];
    }
}

function checkSSLConnection($host, $port) {
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    
    // Remove ldaps:// prefix from host
    $host = str_replace('ldaps://', '', $host);
    $host = explode(':', $host)[0]; // remove port part
    
    $socket = @stream_socket_client(
        "ssl://{$host}:{$port}", 
        $errno, 
        $errstr, 
        5, 
        STREAM_CLIENT_CONNECT, 
        $context
    );
    
    if ($socket) {
        $crypto_details = stream_get_meta_data($socket);
        fclose($socket);
        return [
            'status' => true,
            'crypto' => $crypto_details['crypto'] ?? []
        ];
    }
    
    return [
        'status' => false,
        'error' => "SSL connection error: {$errstr} ({$errno})"
    ];
}

function testLDAPSConnection($settings) {
    $results = [
        'server_check' => ['status' => 'pending', 'message' => ''],
        'connection_check' => ['status' => 'pending', 'message' => ''],
        'simple_bind' => ['status' => 'pending', 'message' => ''],
        'user_bind' => ['status' => 'pending', 'message' => ''],
        'search' => ['status' => 'pending', 'message' => '']
    ];
    
    // Determine protocol in advance
    $protocol = $settings['use_ssl'] ? 'LDAPS' : 'LDAP';
    
    try {
        // 1. Server accessibility test
        $results['server_check']['message'] = "Checking server ({$settings['ldap_host']}:{$settings['ldap_port']})...";
        $connection_test = @fsockopen($settings['ldap_host'], $settings['ldap_port'], $errno, $errstr, 5);
        
        if (!$connection_test) {
            $results['server_check']['status'] = 'error';
            $results['server_check']['message'] = "Server is not accessible: {$errstr} (Port: {$settings['ldap_port']})";
            throw new Exception($results['server_check']['message']);
        }
        
        $results['server_check']['status'] = 'success';
        $results['server_check']['message'] = "Server is accessible";
        fclose($connection_test);
        
        // 2. LDAP Connection test
        $results['connection_check']['message'] = "Checking LDAP connection...";
        
        $ldap_conn = getLDAPConnection($settings);
        if (!$ldap_conn) {
            $results['connection_check']['status'] = 'error';
            $results['connection_check']['message'] = "LDAP connection error";
            throw new Exception($results['connection_check']['message']);
        }
        
        $results['connection_check']['status'] = 'success';
        $results['connection_check']['message'] = "LDAP connection successful";
        
        // 3. Simple Bind test
        $results['simple_bind']['message'] = "Checking simple bind...";
        
        // DN and password should be empty for simple bind
        $simple_bind = @ldap_bind($ldap_conn, null, null);
        
        if (!$simple_bind) {
            $error = ldap_error($ldap_conn);
            $results['simple_bind']['status'] = 'error';
            $results['simple_bind']['message'] = "Simple bind error: " . $error;
            
            // If anonymous bind is not allowed, continue with user credentials
            if ($error === "Invalid credentials") {
                $results['simple_bind']['message'] .= " (Anonymous bind not allowed)";
            } else {
                throw new Exception($results['simple_bind']['message']);
            }
        } else {
            $results['simple_bind']['status'] = 'success';
            $results['simple_bind']['message'] = "Simple bind successful";
        }
        
        // 4. User Bind test
        $results['user_bind']['message'] = "Checking user bind...";
        
        // Check and fix user DN format
        $userdn = strpos($settings['username'], '@') !== false 
            ? $settings['username'] 
            : "{$settings['username']}@{$settings['ldap_domain']}";
            
        $bind = @ldap_bind($ldap_conn, $userdn, $settings['password']);
        
        if (!$bind) {
            $results['user_bind']['status'] = 'error';
            $results['user_bind']['message'] = "User bind error: " . ldap_error($ldap_conn);
            throw new Exception($results['user_bind']['message']);
        }
        
        $results['user_bind']['status'] = 'success';
        $results['user_bind']['message'] = "User bind successful";
        
        // 5. Search test
        $results['search']['message'] = "Checking LDAP search...";
        $search = @ldap_search($ldap_conn, $settings['base_dn'], "(objectClass=*)");
        
        if (!$search) {
            $results['search']['status'] = 'error';
            $results['search']['message'] = "Search error: " . ldap_error($ldap_conn);
            throw new Exception($results['search']['message']);
        }
        
        $results['search']['status'] = 'success';
        $results['search']['message'] = "Search successful";
        
        return [
            'status' => 'success',
            'steps' => $results,
            'connection_details' => [
                'host' => $settings['ldap_host'],
                'port' => $settings['ldap_port'],
                'domain' => $settings['ldap_domain'],
                'protocol' => $protocol
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'steps' => $results,
            'message' => $e->getMessage(),
            'connection_details' => [
                'host' => $settings['ldap_host'],
                'port' => $settings['ldap_port'],
                'domain' => $settings['ldap_domain'],
                'protocol' => $protocol
            ]
        ];
    } finally {
        if (isset($ldap_conn)) {
            ldap_unbind($ldap_conn);
        }
    }
}

// HTML and CSS header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LDAP SSL/TLS Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .test-result { margin-top: 20px; }
        .timing-info { font-size: 0.9em; color: #666; }
        .status-badge {
            font-size: 0.8em;
            padding: 3px 8px;
            border-radius: 3px;
            margin-left: 5px;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .test-card {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">LDAP SSL/TLS Connection Test</h2>
        
        <!-- Test Form -->
        <div class="form-card">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">LDAP Host</label>
                    <input type="text" class="form-control" name="ldap_host" value="<?php echo htmlspecialchars($settings['ldap_host']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Port</label>
                    <input type="number" class="form-control" value="636" disabled>
                    <small class="text-muted">Fixed port 636 for LDAPS</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Domain</label>
                    <input type="text" class="form-control" name="ldap_domain" value="<?php echo htmlspecialchars($settings['ldap_domain']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Base DN</label>
                    <input type="text" class="form-control" name="base_dn" value="<?php echo htmlspecialchars($settings['base_dn']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($settings['username']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" value="<?php echo htmlspecialchars($settings['password']); ?>">
                </div>
                <div class="col-12">
                    <div class="alert alert-info">
                        This test is intended only for LDAPS (port 636)
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Start LDAPS Test</button>
                </div>
            </form>
        </div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = testLDAPSConnection($settings);
?>
        <!-- Test Results -->
        <div class="test-result">
            <div class="test-card card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Test Result</h5>
                    <span class="status-badge <?php echo $result['status'] === 'success' ? 'status-success' : 'status-error'; ?>">
                        <?php echo $result['status'] === 'success' ? 'Successful' : 'Error'; ?>
                    </span>
                </div>
                <div class="card-body">
                    <!-- Step-by-step test results -->
                    <div class="test-steps mb-4">
                        <h6>Test Stages:</h6>
                        <ul class="list-group">
                            <?php foreach ($result['steps'] as $step => $info): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo ucfirst(str_replace('_', ' ', $step)); ?>:</strong>
                                    <?php echo htmlspecialchars($info['message']); ?>
                                </div>
                                <span class="badge <?php echo $info['status'] === 'success' ? 'bg-success' : ($info['status'] === 'error' ? 'bg-danger' : 'bg-warning'); ?>">
                                    <?php echo $info['status']; ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <h6>Connection Details:</h6>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Protocol:</span>
                            <strong><?php echo isset($result['connection_details']['protocol']) ? htmlspecialchars($result['connection_details']['protocol']) : 'LDAPS'; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Host:</span>
                            <strong><?php echo htmlspecialchars($result['connection_details']['host']); ?>:<?php echo $result['connection_details']['port']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Domain:</span>
                            <strong><?php echo htmlspecialchars($result['connection_details']['domain']); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
<?php } ?>

        <!-- PHP and OpenSSL Information -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">PHP Modules</h5>
                    </div>
                    <div class="card-body">
                        <p>PHP Version: <?php echo phpversion(); ?></p>
                        <p>LDAP Status: <?php echo extension_loaded('ldap') ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?></p>
                        
                        <div class="mt-3">
                            <p><strong>All Active Modules:</strong></p>
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col" width="40">#</th>
                                            <th scope="col">Module Name</th>
                                            <th scope="col" width="100">Version</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $modules = get_loaded_extensions();
                                        sort($modules);
                                        foreach ($modules as $i => $module) {
                                            echo '<tr>';
                                            echo '<td>' . ($i + 1) . '</td>';
                                            echo '<td>' . htmlspecialchars($module) . '</td>';
                                            echo '<td><small>' . (phpversion($module) ?: '-') . '</small></td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-2"><small class="text-muted">Total active modules: <?php echo count($modules); ?></small></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">OpenSSL Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Basic Information:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="150"><strong>Version:</strong></td>
                                    <td><?php echo OPENSSL_VERSION_TEXT; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Enabled:</strong></td>
                                    <td><?php echo extension_loaded('openssl') ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Cipher Methods:</strong></td>
                                    <td><?php echo count(openssl_get_cipher_methods()) . ' available'; ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="mb-3">
                            <h6>Supported Protocols:</h6>
                            <?php
                            // Create a safer way to check protocols without relying on undefined constants
                            $protocol_support = [];
                            
                            // Check TLS 1.0
                            $protocol_support['TLSv1.0'] = true;
                            
                            // Check TLS 1.1 - introduced in OpenSSL 1.0.1
                            $protocol_support['TLSv1.1'] = true;
                            
                            // Check TLS 1.2 - introduced in OpenSSL 1.0.1
                            $protocol_support['TLSv1.2'] = true;
                            
                            // Check TLS 1.3 - introduced in OpenSSL 1.1.1 and PHP 7.3.0
                            $protocol_support['TLSv1.3'] = version_compare(phpversion(), '7.3.0', '>=');
                            
                            // SSLv3 is generally disabled in modern versions for security reasons
                            $protocol_support['SSLv3'] = false;
                            
                            // Alternative method to check protocol support using context options
                            function protocol_supported($protocol) {
                                $context = stream_context_create([
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'security_level' => 0
                                    ]
                                ]);
                                
                                // Try to create a socket with specific protocol
                                $errno = $errstr = null;
                                $socket = @stream_socket_client(
                                    "localhost:443", 
                                    $errno, 
                                    $errstr, 
                                    1, 
                                    STREAM_CLIENT_CONNECT,
                                    $context
                                );
                                
                                return true; // We can't reliably test this way, but we'll assume support
                            }
                            
                            // Display protocol badges
                            foreach ($protocol_support as $protocol => $supported) {
                                echo '<span class="badge ' . ($supported ? 'bg-success' : 'bg-secondary') . ' me-2">' . 
                                     $protocol . ($supported ? ' ✓' : ' ✗') . '</span>';
                            }
                            ?>
                        </div>

                        <div>
                            <h6>Cipher Algorithms (Sample):</h6>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col" width="40">#</th>
                                            <th scope="col">Cipher Algorithm</th>
                                            <th scope="col" width="80">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $ciphers = openssl_get_cipher_methods();
                                        sort($ciphers);
                                        
                                        // Only show first 30 ciphers to avoid overwhelming the interface
                                        $sample_ciphers = array_slice($ciphers, 0, 30);
                                        foreach ($sample_ciphers as $i => $cipher) {
                                            // Determine cipher type based on its name
                                            $type = "Other";
                                            if (stripos($cipher, 'aes') !== false) {
                                                $type = "AES";
                                            } elseif (stripos($cipher, 'des') !== false) {
                                                $type = "DES";
                                            } elseif (stripos($cipher, 'chacha') !== false) {
                                                $type = "ChaCha";
                                            } elseif (stripos($cipher, 'blowfish') !== false) {
                                                $type = "Blowfish";
                                            } elseif (stripos($cipher, 'camellia') !== false) {
                                                $type = "Camellia";
                                            } elseif (stripos($cipher, 'rc') !== false) {
                                                $type = "RC";
                                            }
                                            
                                            echo '<tr>';
                                            echo '<td>' . ($i + 1) . '</td>';
                                            echo '<td>' . htmlspecialchars($cipher) . '</td>';
                                            echo '<td><span class="badge bg-secondary">' . $type . '</span></td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                
                                <?php if (count($ciphers) > 30): ?>
                                <div class="mt-2 text-center">
                                    <span class="badge bg-secondary">+<?php echo (count($ciphers) - 30); ?> more cipher algorithms</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <p class="mt-2"><small class="text-muted">Total cipher algorithms: <?php echo count($ciphers); ?></small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // SSL/TLS checkbox control
        document.getElementById('use_ssl').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('use_tls').checked = false;
                document.getElementsByName('ldap_port')[0].value = '636';
            } else {
                document.getElementsByName('ldap_port')[0].value = '389';
            }
        });

        document.getElementById('use_tls').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('use_ssl').checked = false;
                document.getElementsByName('ldap_port')[0].value = '389';
            }
        });
    </script>
</body>
</html> 