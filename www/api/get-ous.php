<?php
/*
    * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
    * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [ali] <[ali.z.zeynalli@gmail.com]> [2025]
 */

session_start();
require_once(__DIR__ . '/../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}


try {
    $ldap_conn = getLDAPConnection();
    
    // Load config
    $config = require(__DIR__ . '/../config/config.php');
    $baseDN = $config['ad_settings']['base_dn'];
    
    // Search for all OUs
    $filter = "(objectClass=organizationalUnit)";
    $attributes = ["distinguishedName", "name", "description"];
    
    $search = ldap_search($ldap_conn, $baseDN, $filter, $attributes);
    if (!$search) {
        throw new Exception("Failed to search for OUs");
    }
    
    $entries = ldap_get_entries($ldap_conn, $search);
    $ous = [];
    
    // Skip the first entry (count)
    for ($i = 0; $i < $entries['count']; $i++) {
        $entry = $entries[$i];
        
        // Get the DN and convert it to a path
        $dn = $entry['distinguishedname'][0];
        $path = convertDNtoPath($dn);
        
        $ous[] = [
            'dn' => $dn,
            'name' => isset($entry['name'][0]) ? $entry['name'][0] : '',
            'path' => $path,
            'description' => isset($entry['description'][0]) ? $entry['description'][0] : ''
        ];
    }
    
    // Sort OUs by path
    usort($ous, function($a, $b) {
        return strcmp($a['path'], $b['path']);
    });
    
    echo json_encode([
        'success' => true,
        'base_dn' => $baseDN,
        'ous' => $ous
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function to convert DN to readable path
function convertDNtoPath($dn) {
    $parts = ldap_explode_dn($dn, 1);
    $path = [];
    
    // Remove count and domain components
    for ($i = 0; $i < $parts['count']; $i++) {
        if (!preg_match('/^(DC|domain)=/i', $parts[$i])) {
            array_unshift($path, $parts[$i]);
        }
    }
    
    return implode('/', $path);
} 