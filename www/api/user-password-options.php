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
    echo json_encode(['error' => __('user_unauthorized')]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['username'])) {
        throw new Exception(__('user_username_required'));
    }

    if (!isset($data['action']) || !isset($data['value'])) {
        throw new Exception(__('user_action_required'));
    }

    $ldap_conn = getLDAPConnection();
    $username = $data['username'];
    
    // Get config for base_dn
    $config = require(__DIR__ . '/../config/config.php');
    $base_dn = $config['ad_settings']['base_dn'];
    
    // Get user DN
    $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
    $result = ldap_search($ldap_conn, $base_dn, $filter);
    
    if (!$result || ldap_count_entries($ldap_conn, $result) !== 1) {
        throw new Exception(__('user_not_found'));
    }

    $entries = ldap_get_entries($ldap_conn, $result);
    $userDN = $entries[0]['dn'];
    
    switch ($data['action']) {
        case 'never_expires':
            $uac = isset($entries[0]["useraccountcontrol"][0]) ? 
                $entries[0]["useraccountcontrol"][0] : 0;
            
            if ($data['value']) {
                $uac |= 65536; // UF_DONT_EXPIRE_PASSWORD
            } else {
                $uac &= ~65536; // Remove UF_DONT_EXPIRE_PASSWORD
            }
            
            $mod = ["userAccountControl" => $uac];
            if (!ldap_mod_replace($ldap_conn, $userDN, $mod)) {
                throw new Exception(__('failed_to_update_password_option'));
            }
            break;
            
        case 'must_change':
            if ($data['value']) {
                $mod = ["pwdLastSet" => 0];
            } else {
                $mod = ["pwdLastSet" => -1];
            }
            
            if (!ldap_mod_replace($ldap_conn, $userDN, $mod)) {
                throw new Exception(__('failed_to_update_password_option'));
            }
            break;
            
        default:
            throw new Exception(__('invalid_action'));
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($ldap_conn)) {
        ldap_unbind($ldap_conn);
    }
} 