<?php
require_once dirname(__DIR__) . '/functions.php';

 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */



function getAllComputers($ldap_conn) {
    $config = require(getConfigPath());
    $base_dn = $config['ad_settings']['base_dn'];
    
    $filter = "(&(objectClass=computer))";
    $attributes = [
        "cn", "distinguishedName", "operatingSystem", 
        "operatingSystemVersion", "dNSHostName",
        "lastLogon", "whenCreated", "objectClass",
        "memberOf", "description", "userAccountControl"
    ];
    
    $result = ldap_search($ldap_conn, $base_dn, $filter, $attributes);
    $entries = ldap_get_entries($ldap_conn, $result);
    
    $computers = [];
    $stats = [
        'total' => 0,
        'servers' => 0,
        'workstations' => 0,
        'windows' => 0,
        'linux' => 0,
        'unknown' => 0
    ];
    
    for ($i = 0; $i < $entries['count']; $i++) {
        $entry = $entries[$i];
        $dn = $entry['distinguishedname'][0];
        
        // Get OS info
        $os = $entry['operatingsystem'][0] ?? '';
        $osLower = strtolower($os);
        
        // Determine computer type and OS
        $type = determineComputerType($os);
        $osType = determineOSType($os);
        
        // Update statistics
        $stats['total']++;
        
        if ($type === 'Server') {
            $stats['servers']++;
        } else {
            $stats['workstations']++;
        }
        
        switch ($osType) {
            case 'Windows':
                $stats['windows']++;
                break;
            case 'Linux':
                $stats['linux']++;
                break;
            default:
                $stats['unknown']++;
        }
        
        // Check if computer is enabled
        $uac = $entry['useraccountcontrol'][0] ?? 0;
        $enabled = ($uac & 2) !== 2;
        
        // Format last logon time
        $lastLogon = isset($entry['lastlogon'][0]) ? 
            formatLDAPTime($entry['lastlogon'][0]) : 'Never';
        
        $computers[] = [
            'name' => $entry['cn'][0],
            'dn' => $dn,
            'type' => $type,
            'os' => $os,
            'osType' => $osType,
            'osVersion' => $entry['operatingsystemversion'][0] ?? '',
            'deviceName' => $entry['dnshostname'][0] ?? $entry['cn'][0],
            'ou' => formatOUPath($dn),
            'enabled' => $enabled,
            'lastLogon' => $lastLogon,
            'created' => formatLDAPDate($entry['whencreated'][0] ?? ''),
            'description' => $entry['description'][0] ?? ''
        ];
    }
    
    usort($computers, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    return ['computers' => $computers, 'stats' => $stats];
}

function isComputerReachable($hostname) {
    // Try to ping the computer (with short timeout)
    $pingResult = @fsockopen($hostname, 445, $errno, $errstr, 1);
    if ($pingResult) {
        fclose($pingResult);
        return true;
    }
    return false;
}

function determineComputerType($os) {
    $os = strtolower($os);
    if (strpos($os, 'server') !== false) {
        return 'Server';
    } else {
        return 'Workstation'; // Bütün digər sistemlər Workstation sayılır
    }
}

function determineOSType($os) {
    $os = strtolower($os);
    if (strpos($os, 'windows') !== false) {
        return 'Windows';
    } elseif (strpos($os, 'linux') !== false || 
              strpos($os, 'ubuntu') !== false || 
              strpos($os, 'centos') !== false || 
              strpos($os, 'debian') !== false || 
              strpos($os, 'red hat') !== false) {
        return 'Linux';
    }
    return 'Unknown';
}

/**
 * Kompüter obyektini Active Directory-dən silir
 * 
 * @param resource $ldap_conn LDAP bağlantısı
 * @param string $dn Silinəcək kompüterin DN-i (Distinguished Name)
 * @return array Əməliyyatın nəticəsi
 */
function deleteComputer($ldap_conn, $dn) {
    try {
        // Kompüterin mövcudluğunu yoxla
        $sr = ldap_read($ldap_conn, $dn, "(objectClass=computer)");
        if (!$sr || ldap_count_entries($ldap_conn, $sr) !== 1) {
            return [
                'success' => false,
                'error' => __('computer_not_found')
            ];
        }
        
        // Get computer name for logging
        $entry = ldap_get_entries($ldap_conn, $sr);
        $computerName = $entry[0]['cn'][0] ?? __('computer_unknown');
        
        // Kompüteri sil
        if (ldap_delete($ldap_conn, $dn)) {
            // Log with standardized action type for activity logs
            logActivity('delete_computer', null, json_encode([
                'name' => $computerName,
                'dn' => $dn
            ]));
            
            return [
                'success' => true,
                'message' => __('computer_deleted_success')
            ];
        } else {
            $error = ldap_error($ldap_conn);
            return [
                'success' => false,
                'error' => str_replace('{error}', $error, __('computer_delete_failed'))
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => str_replace('{error}', $e->getMessage(), __('computer_delete_exception'))
        ];
    }
}

/**
 * Kompüteri bir OU-dan digərinə köçürür
 * 
 * @param resource $ldap_conn LDAP bağlantısı
 * @param string $dn Köçürüləcək kompüterin DN-i
 * @param string $new_ou_dn Yeni OU-nun DN-i
 * @return array Əməliyyatın nəticəsi
 */
function moveComputerToOU($ldap_conn, $dn, $new_ou_dn) {
    try {
        // Kompüterin mövcudluğunu yoxla
        $sr = ldap_read($ldap_conn, $dn, "(objectClass=computer)");
        if (!$sr || ldap_count_entries($ldap_conn, $sr) !== 1) {
            return [
                'success' => false,
                'error' => __('computer_not_found')
            ];
        }
        
        // OU-nun mövcudluğunu yoxla
        $sr_ou = ldap_read($ldap_conn, $new_ou_dn, "(objectClass=organizationalUnit)");
        if (!$sr_ou || ldap_count_entries($ldap_conn, $sr_ou) !== 1) {
            return [
                'success' => false,
                'error' => __('computer_destination_ou_not_found')
            ];
        }
        
        // Kompüter adını al
        $entry = ldap_get_entries($ldap_conn, $sr);
        $cn = $entry[0]['cn'][0];
        
        // Get current OU path for logging
        $dnParts = explode(',', $dn, 2);
        $currentOUPath = $dnParts[1] ?? '';
        
        // Yeni rdn hazırla
        $new_rdn = "CN=$cn";
        
        // Kompüteri yeni OU-ya köçür
        if (ldap_rename($ldap_conn, $dn, $new_rdn, $new_ou_dn, true)) {
            $new_dn = "$new_rdn,$new_ou_dn";
            
            // Log with standardized action type for activity logs
            logActivity('move_computer', null, json_encode([
                'name' => $cn,
                'from' => $currentOUPath,
                'to' => $new_ou_dn
            ]));
            
            return [
                'success' => true,
                'message' => __('computer_moved_success'),
                'new_dn' => $new_dn
            ];
        } else {
            $error = ldap_error($ldap_conn);
            return [
                'success' => false,
                'error' => str_replace('{error}', $error, __('computer_move_failed'))
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => str_replace('{error}', $e->getMessage(), __('computer_move_exception'))
        ];
    }
}

/**
 * Kompüter haqqında ətraflı məlumat əldə edir
 * 
 * @param resource $ldap_conn LDAP bağlantısı
 * @param string $dn Kompüterin DN-i
 * @return array Kompüter məlumatları
 */
function getComputerDetails($ldap_conn, $dn) {
    try {
        // Kompüterin bütün atributlarını al
        $sr = ldap_read($ldap_conn, $dn, "(objectClass=computer)", ["*"]);
        if (!$sr) {
            return [
                'success' => false,
                'error' => __('computer_not_found')
            ];
        }
        
        $entry = ldap_get_entries($ldap_conn, $sr);
        if ($entry['count'] === 0) {
            return [
                'success' => false,
                'error' => __('computer_no_data_found')
            ];
        }
        
        $computer = $entry[0];
        
        // OS xüsusiyyətlərini təyin et
        $os = $computer['operatingsystem'][0] ?? '';
        $osType = determineOSType($os);
        $type = determineComputerType($os);
        
        // UAC-dən status ala bilmək üçün
        $uac = $computer['useraccountcontrol'][0] ?? 0;
        $enabled = ($uac & 2) !== 2;
        
        $computerDetails = [
            'success' => true,
            'name' => $computer['cn'][0] ?? '',
            'dn' => $dn,
            'type' => $type,
            'osType' => $osType,
            'os' => $os,
            'osVersion' => $computer['operatingsystemversion'][0] ?? '',
            'deviceName' => $computer['dnshostname'][0] ?? ($computer['cn'][0] ?? ''),
            'description' => $computer['description'][0] ?? '',
            'lastLogon' => isset($computer['lastlogon'][0]) ? formatLDAPTime($computer['lastlogon'][0]) : __('computer_never_logged_in'),
            'created' => formatLDAPDate($computer['whencreated'][0] ?? ''),
            'ou' => formatOUPath($dn),
            'enabled' => $enabled,
            'logonCount' => $computer['logoncount'][0] ?? 0,
            'distinguishedName' => $dn,
        ];
        
        return $computerDetails;
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => str_replace('{error}', $e->getMessage(), __('computer_details_exception'))
        ];
    }
}