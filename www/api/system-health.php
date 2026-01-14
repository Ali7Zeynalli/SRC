<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */

// Başlanğıcda bütün bufferləri təmizləyirik
if (ob_get_level()) ob_end_clean();

session_start();
require_once(__DIR__ . '/../includes/functions.php');



// Debug output capture start
ob_start();

// Basic security headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

// Clear any previous output and check session
if (ob_get_length()) ob_clean();

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['error' => __('error_unauthorized')]);
    exit;
}

try {
    $config = require(getConfigPath());
    $ldap_conn = getLDAPConnection();
    
    // LDAP connection test
    $start = microtime(true);
    $ldapResponseTime = round((microtime(true) - $start) * 1000);
    
    // Get MAC addresses function
    function getMACAddress($ip) {
        // Linux üçün
        if (PHP_OS === 'Linux') {
            // ARP cədvəlindən MAC ünvanını əldə et
            $cmd = "ip neigh show | grep '$ip' | awk '{print $5}'";
            $mac = trim(shell_exec($cmd));
            
            if (empty($mac)) {
                // Alternativ olaraq arp -n istifadə et
                $cmd = "arp -n | grep '$ip' | awk '{print $3}'";
                $mac = trim(shell_exec($cmd));
            }
            
            return $mac ?: __('network_mac_not_available');
        }
        // Windows üçün
        elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "arp -a $ip";
            $output = shell_exec($cmd);
            
            if (preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $output, $matches)) {
                return strtoupper($matches[0]);
            }
            return __('network_mac_not_available');
        }
        return __('network_mac_not_available');
    }
    
    // Get Network Interfaces function
    function getNetworkInterfaces() {
        $interfaces = [];
        
        if (PHP_OS === 'Linux') {
            $cmd = "ip -o link show | awk -F': ' '{print $2}'";
            $output = shell_exec($cmd);
            $ifaces = explode("\n", trim($output));
            
            foreach ($ifaces as $iface) {
                if (empty($iface) || $iface === 'lo') continue;
                
                $mac_cmd = "ip link show $iface | awk '/link\/ether/ {print $2}'";
                $mac = trim(shell_exec($mac_cmd));
                
                $ip_cmd = "ip -o -4 addr show $iface | awk '{print $4}' | cut -d'/' -f1";
                $ip = trim(shell_exec($ip_cmd));
                
                $interfaces[] = [
                    'name' => $iface,
                    'mac' => $mac ?: __('network_mac_not_available'),
                    'ip' => $ip ?: __('network_ip_not_available')
                ];
            }
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "ipconfig /all";
            $output = shell_exec($cmd);
            
            if (preg_match_all('/Ethernet adapter (.*?):\s*\n(.*?)(?=\n\s*\n|\z)/s', $output, $matches)) {
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $name = trim($matches[1][$i]);
                    $details = $matches[2][$i];
                    
                    preg_match('/Physical Address.*?: (.*?)\n/', $details, $mac);
                    preg_match('/IPv4 Address.*?: (.*?)\n/', $details, $ip);
                    
                    $interfaces[] = [
                        'name' => $name,
                        'mac' => isset($mac[1]) ? trim($mac[1]) : __('network_mac_not_available'),
                        'ip' => isset($ip[1]) ? trim(str_replace('(Preferred)', '', $ip[1])) : __('network_ip_not_available')
                    ];
                }
            }
        }
        
        return $interfaces;
    }
    
    // AD Server network məlumatlarını əldə etmək funksiyasını yeniləyirik
    function getADServerNetworkInfo($ldap_conn) {
        try {
            if (!$ldap_conn) {
                throw new Exception(__('error_no_ldap_connection'));
            }

            // Root DSE məlumatlarını əldə edirik
            $result = @ldap_read($ldap_conn, "", "(objectClass=*)", [
                "dnsHostName",
                "defaultNamingContext",
                "domainFunctionality",
                "forestFunctionality",
                "domainControllerFunctionality",
                "serverName"
            ]);

            if (!$result) {
                throw new Exception(__('error_could_not_read_ad_info'));
            }

            $entries = ldap_get_entries($ldap_conn, $result);
            $rootDSE = $entries[0];
            
            // Hostname əldə edirik
            $hostname = $rootDSE['dnshostname'][0] ?? __('unknown');
            
            // IP ünvanını əldə edirik
            $ip_address = gethostbyname($hostname);
            
            // MAC ünvanını əldə edirik
            $mac_address = getMACAddress($ip_address);
            
            // Default Gateway və digər şəbəkə məlumatlarını əldə edirik
            $network_info = [];
            if (PHP_OS === 'WINNT') {
                // Windows üçün
                $cmd = "ipconfig /all";
                $output = shell_exec($cmd);
                
                // Default Gateway
                preg_match('/Default Gateway.*?: (.*?)\n/', $output, $gateway);
                $default_gateway = $gateway[1] ?? __('unknown');
                
                // Subnet Mask
                preg_match('/Subnet Mask.*?: (.*?)\n/', $output, $subnet);
                $subnet_mask = $subnet[1] ?? __('unknown');
                
                // DNS Servers
                preg_match_all('/DNS Servers.*?: (.*?)\n/', $output, $dns);
                $dns_servers = $dns[1] ?? [];
                
                $network_info = [
                    'default_gateway' => trim($default_gateway),
                    'subnet_mask' => trim($subnet_mask),
                    'dns_servers' => array_map('trim', $dns_servers)
                ];
            } else {
                // Linux üçün
                $gateway_cmd = "ip route | grep default | awk '{print $3}'";
                $default_gateway = trim(shell_exec($gateway_cmd));
                
                $subnet_cmd = "ip addr show | grep inet | grep -v inet6 | awk '{print $2}'";
                $subnet_mask = trim(shell_exec($subnet_cmd));
                
                $dns_cmd = "cat /etc/resolv.conf | grep nameserver | awk '{print $2}'";
                $dns_servers = array_filter(explode("\n", trim(shell_exec($dns_cmd))));
                
                $network_info = [
                    'default_gateway' => $default_gateway ?: __('unknown'),
                    'subnet_mask' => $subnet_mask ?: __('unknown'),
                    'dns_servers' => $dns_servers ?: []
                ];
            }

            // Domain adını əldə edirik
            $domain = '';
            $defaultNamingContext = $rootDSE['defaultnamingcontext'][0] ?? '';
            if ($defaultNamingContext) {
                $parts = explode(',', $defaultNamingContext);
                $domainParts = [];
                foreach ($parts as $part) {
                    if (strpos($part, 'DC=') === 0) {
                        $domainParts[] = substr($part, 3);
                    }
                }
                $domain = implode('.', $domainParts);
            }

            // Server məlumatlarını qaytarırıq
            return [
                'hostname' => $hostname,
                'server_name' => $rootDSE['servername'][0] ?? __('unknown'),
                'domain' => $domain,
                'domain_functionality' => $rootDSE['domainfunctionality'][0] ?? __('unknown'),
                'forest_functionality' => $rootDSE['forestfunctionality'][0] ?? __('unknown'),
                'dc_functionality' => $rootDSE['domaincontrollerfunctionality'][0] ?? __('unknown'),
                'network' => [
                    'ip_address' => $ip_address,
                    'mac_address' => $mac_address,
                    'default_gateway' => $network_info['default_gateway'],
                    'subnet_mask' => $network_info['subnet_mask'],
                    'dns_servers' => $network_info['dns_servers']
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting AD network info: " . $e->getMessage());
            return [
                'hostname' => __('unknown'),
                'server_name' => __('unknown'),
                'domain' => __('unknown'),
                'domain_functionality' => __('unknown'),
                'forest_functionality' => __('unknown'),
                'dc_functionality' => __('unknown'),
                'network' => [
                    'ip_address' => __('unknown'),
                    'mac_address' => __('unknown'),
                    'default_gateway' => __('unknown'),
                    'subnet_mask' => __('unknown'),
                    'dns_servers' => []
                ]
            ];
        }
    }
    
    // AD Server məlumatları
    $dc = $config['ad_settings']['domain_controllers'][0] ?? __('unknown');
    $dc_ip = gethostbyname($dc);
    
    // Get domain from LDAP
    $domain = '';
    if ($ldap_conn) {
        $rootDse = @ldap_read($ldap_conn, '', '(objectClass=*)', ['defaultNamingContext']);
        if ($rootDse) {
            $entry = ldap_get_entries($ldap_conn, $rootDse);
            if (isset($entry[0]['defaultnamingcontext'][0])) {
                // Convert DN to domain name
                $dnParts = explode(',', $entry[0]['defaultnamingcontext'][0]);
                $domainParts = [];
                foreach ($dnParts as $part) {
                    if (strpos($part, 'DC=') === 0) {
                        $domainParts[] = substr($part, 3);
                    }
                }
                $domain = implode('.', $domainParts);
            }
        }
    }
    
    $adServerInfo = [
        'status' => $ldap_conn ? true : false,
        'response_time' => $ldapResponseTime,
        'network_info' => getADServerNetworkInfo($ldap_conn)
    ];
    
    // Hosting Server məlumatları
    $db = Database::getInstance();
    $hostingInfo = [
        'server' => [
            'hostname' => gethostname(),
            'ip' => $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()),
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? __('unknown')
        ],
        'network' => [
            'interfaces' => getNetworkInterfaces()
        ],
        'database' => [
            'status' => $db->isConnected() ? __('database_connected') : __('database_disconnected'),
            'version' => $db->getVersion()
        ]
    ];

    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'ad_server' => $adServerInfo,
        'hosting_server' => $hostingInfo
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("System Health Error: " . $e->getMessage());
    if (ob_get_length()) ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => __('error_system_health')
    ]);
    exit;
}
