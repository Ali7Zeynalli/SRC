<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
session_start();
require_once(__DIR__ . '/../includes/functions.php');


header('Content-Type: application/json');

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $ldap_conn = getLDAPConnection();
    
    // Cache key
    $cache_key = 'formatted_users_cache';
    $cache_lifetime = 300; // 5 dəqiqə
    
    // Cache-dən yoxlayırıq
    if (isset($_SESSION[$cache_key]) && 
        (time() - $_SESSION[$cache_key . '_time'] < $cache_lifetime)) {
        echo json_encode($_SESSION[$cache_key]);
        exit;
    }
    
    $users = getAllUsers($ldap_conn);
    $lockedUsers = getLockedUsers($ldap_conn);
    
    $formatted_users = array_map(function($user) use ($ldap_conn, $lockedUsers) {
        $uac = $user['useraccountcontrol'][0] ?? 0;
        $pwdLastSet = $user['pwdlastset'][0] ?? 0;
        $pwdStatus = getPasswordExpiryStatus($pwdLastSet, $ldap_conn, $uac);
        
        // Extract OU from distinguishedName with improved parsing
        $dn = $user['distinguishedname'][0] ?? '';
        $dn_parts = explode(',', $dn);
        $ou_parts = array_filter($dn_parts, function($part) {
            return strpos(trim($part), 'OU=') === 0;
        });
        
        // Reverse array to show parent OUs first
        $ou_parts = array_reverse($ou_parts);
        $ou = implode(' > ', array_map(function($ou) {
            return trim(substr($ou, 3)); // Remove "OU=" prefix and trim spaces
        }, $ou_parts));

        // Get user groups with better formatting
        $groups = [];
        if (isset($user['memberof'])) {
            foreach ($user['memberof'] as $group_dn) {
                if (is_string($group_dn)) {  // Skip count entry
                    $group_name = explode(',', $group_dn)[0];  // Get first part
                    if (strpos($group_name, 'CN=') === 0) {
                        $groups[] = substr($group_name, 3);  // Remove "CN=" prefix
                    }
                }
            }
            sort($groups);  // Sort groups alphabetically
        }
        
        // Check if user is locked
        $isLocked = false;
        foreach ($lockedUsers as $lockedUser) {
            if ($lockedUser['username'] === $user['samaccountname'][0]) {
                $isLocked = true;
                break;
            }
        }
        
        return [
            'username' => $user['samaccountname'][0] ?? '',
            'displayName' => $user['displayname'][0] ?? '',
            'email' => $user['mail'][0] ?? '',
            'department' => $user['department'][0] ?? '',
            'enabled' => ($uac & 2) !== 2,
            'locked' => $isLocked,
            'lastLogon' => formatLDAPTime($user['lastlogon'][0] ?? 0),
            'passwordStatus' => $pwdStatus['status'],
            'passwordStatusClass' => $pwdStatus['class'],
            'title' => $user['title'][0] ?? '',
            'phone' => $user['telephonenumber'][0] ?? '',
            'mobile' => $user['mobile'][0] ?? '',
            'ou' => $ou ?: 'Root',  // Use 'Root' instead of 'Default' if no OU
            'groups' => implode(', ', $groups),
            'lockoutTime' => $isLocked ? $lockedUser['lockoutTime'] : null
        ];
    }, array_slice($users, 1)); // Skip first entry which is count
    
    $result = [
        'users' => $formatted_users,
        'stats' => calculateStats($formatted_users)
    ];
    
    // Cache-ə yazırıq
    $_SESSION[$cache_key] = $result;
    $_SESSION[$cache_key . '_time'] = time();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function calculateStats($users) {
    $stats = [
        'total' => count($users),
        'active' => 0,
        'inactive' => 0,
        'expired_password' => 0,
        'locked' => 0,
        'never_expires' => 0
    ];
    
    foreach ($users as $user) {
        if ($user['enabled']) $stats['active']++;
        else $stats['inactive']++;
        
        if ($user['passwordStatus'] === 'Expired') $stats['expired_password']++;
        if ($user['locked']) $stats['locked']++;
        if ($user['passwordStatus'] === 'Never Expires') $stats['never_expires']++;
    }
    
    return $stats;
}
