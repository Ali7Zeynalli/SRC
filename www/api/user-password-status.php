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

// Constants for UAC flags
define('UF_NORMAL_ACCOUNT', 0x0200);      // 512
define('UF_DONT_EXPIRE_PASSWORD', 0x10000); // 65536

try {
    if (empty($_GET['username'])) {
        throw new Exception('Username is required');
    }

    $ldap_conn = getLDAPConnection();
    $username = $_GET['username'];
    
    // Get config for base_dn
    $config = require(__DIR__ . '/../config/config.php');
    $base_dn = $config['ad_settings']['base_dn'];
    
    // Get user DN and account details
    $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
    $result = ldap_search($ldap_conn, $base_dn, $filter);
    
    if (!$result || ldap_count_entries($ldap_conn, $result) !== 1) {
        throw new Exception('User not found');
    }

    $entries = ldap_get_entries($ldap_conn, $result);
    $entry = $entries[0];

    // Get account control flags
    $uac = isset($entry['useraccountcontrol'][0]) ? (int)$entry['useraccountcontrol'][0] : UF_NORMAL_ACCOUNT;
    
    // Get password last set value
    $pwdLastSet = isset($entry['pwdlastset'][0]) ? $entry['pwdlastset'][0] : -1;

    echo json_encode([
        'success' => true,
        'password_status' => [
            'never_expires' => ($uac & UF_DONT_EXPIRE_PASSWORD) ? true : false,
            'must_change' => ($pwdLastSet === "0") ? true : false
        ]
    ]);

} catch (Exception $e) {
    error_log("Password status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($ldap_conn)) {
        ldap_unbind($ldap_conn);
    }
} 