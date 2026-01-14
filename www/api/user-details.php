<?php
 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once(__DIR__ . '/../includes/functions.php');


header('Content-Type: application/json');

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['error' => __('user_unauthorized')]);
    exit;
}


try {
    if (!isset($_GET['username'])) {
        throw new Exception(__('user_username_required'));
    }

    $ldap_conn = getLDAPConnection();
    $config = require(__DIR__ . '/../config/config.php');
    
    $username = $_GET['username'];
    $escaped_username = ldap_escape($username, "", LDAP_ESCAPE_FILTER);
    
    // Search for specific user
    $filter = "(&(objectClass=user)(objectCategory=person)(sAMAccountName=$escaped_username))";
    $base_dn = $config['ad_settings']['base_dn'];
    
    $result = ldap_search($ldap_conn, $base_dn, $filter, [
        "samaccountname", "displayname", "mail", "department",
        "lastlogon", "pwdlastset", "useraccountcontrol", 
        "whencreated",
        "title", "telephonenumber", "mobile", "memberof",
        "distinguishedname", "description"
    ]);

    if ($result === false) {
        throw new Exception(__('user_ldap_search_failed') . ': ' . ldap_error($ldap_conn));
    }

    $entries = ldap_get_entries($ldap_conn, $result);
    
    if ($entries['count'] === 0) {
        throw new Exception(__('user_not_found'));
    }

    $userDetails = formatUserDetails($entries[0], $ldap_conn);
    
    // UAC statuslarını düzgün hesablayaq
    $uac = intval($entries[0]['useraccountcontrol'][0]);
    $userDetails['locked'] = ($uac & 16) === 16 || in_array($username, array_column(getLockedUsers($ldap_conn), 'username'));
    $userDetails['enabled'] = ($uac & 2) !== 2; // UF_ACCOUNTDISABLE bayrağını yoxlayırıq
    
    echo json_encode($userDetails);

} catch (Exception $e) {
    error_log("User details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'type' => 'error'
    ]);
}
