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
    echo json_encode(['success' => false, 'error' => __('unauthorized')]);
    exit;
}

try {
    // Check for required parameters
    if (!isset($_GET['query']) || empty($_GET['query'])) {
        throw new Exception(__('search_query_required'));
    }
    
    $searchQuery = $_GET['query'];
    $groupDN = isset($_GET['group_dn']) ? $_GET['group_dn'] : '';
    
    // Minimum search query length
    if (strlen($searchQuery) < 2) {
        throw new Exception(__('search_query_min_length'));
    }
    
    $ldap_conn = getLDAPConnection();
    
    // If group DN provided, get current members to exclude them from search results
    $currentMembers = [];
    if (!empty($groupDN)) {
        $filter = "(&(objectClass=group)(distinguishedName=" . ldap_escape($groupDN, "", LDAP_ESCAPE_FILTER) . "))";
        $attributes = ['member'];
        $result = ldap_read($ldap_conn, $groupDN, $filter, $attributes);
        
        if ($result) {
            $entries = ldap_get_entries($ldap_conn, $result);
            if ($entries['count'] > 0 && isset($entries[0]['member'])) {
                for ($i = 0; $i < $entries[0]['member']['count']; $i++) {
                    $currentMembers[] = $entries[0]['member'][$i];
                }
            }
        }
    }
    
    // Search for users
    $config = require(getConfigPath());
    $base_dn = $config['ad_settings']['base_dn'];
    
    // Build search filter - search by display name, SAM account name, or email
    $filter = "(&(objectClass=user)(objectCategory=person)(!(objectClass=computer))(|" .
              "(displayName=*" . ldap_escape($searchQuery, "", LDAP_ESCAPE_FILTER) . "*)" .
              "(sAMAccountName=*" . ldap_escape($searchQuery, "", LDAP_ESCAPE_FILTER) . "*)" .
              "(mail=*" . ldap_escape($searchQuery, "", LDAP_ESCAPE_FILTER) . "*)" .
              "))";
    
    // Attributes to retrieve
    $attributes = ['distinguishedName', 'displayName', 'sAMAccountName', 'mail', 'userAccountControl'];
    
    // Execute search
    $result = ldap_search($ldap_conn, $base_dn, $filter, $attributes);
    
    if (!$result) {
        throw new Exception(__('user_search_failed'));
    }
    
    $entries = ldap_get_entries($ldap_conn, $result);
    $users = [];
    
    // Process and filter results
    for ($i = 0; $i < $entries['count']; $i++) {
        $entry = $entries[$i];
        $dn = $entry['distinguishedname'][0];
        
        // Skip disabled accounts
        $userAccountControl = intval($entry['useraccountcontrol'][0]);
        if ($userAccountControl & 2) { // Account is disabled
            continue;
        }
        
        // Skip current members
        if (in_array($dn, $currentMembers)) {
            continue;
        }
        
        $users[] = [
            'dn' => $dn,
            'username' => $entry['samaccountname'][0],
            'displayName' => isset($entry['displayname'][0]) ? $entry['displayname'][0] : $entry['samaccountname'][0],
            'email' => isset($entry['mail'][0]) ? $entry['mail'][0] : ''
        ];
        
        // Limit results to avoid excessive data
        if (count($users) >= 50) {
            break;
        }
    }
    
    // Sort users by display name
    usort($users, function($a, $b) {
        return strcasecmp($a['displayName'], $b['displayName']);
    });
    
    // Return results
    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => count($users),
        'query' => $searchQuery
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'users' => []
    ]);
} 