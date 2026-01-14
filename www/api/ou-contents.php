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
    echo json_encode(['error' => __('ou_unauthorized')]);
    exit;
}

try {
    if (!isset($_GET['dn'])) {
        throw new Exception(__('ou_dn_required'));
    }

    $ldap_conn = getLDAPConnection();
    $dn = $_GET['dn'];
    
    // First, get the OU details
    $filter = "(objectClass=*)";
    $result = ldap_read($ldap_conn, $dn, $filter, [
        "ou", "cn", "objectClass", "distinguishedName", "whenCreated",
        "description", "showInAdvancedViewOnly"
    ]);
    
    if ($result === false) {
        throw new Exception(__('ou_read_failed'));
    }
    
    $entries = ldap_get_entries($ldap_conn, $result);
    
    if ($entries['count'] === 0) {
        throw new Exception(__('ou_not_found'));
    }
    
    $entry = $entries[0];
    $isOU = in_array('organizationalUnit', $entry['objectclass']);
    
    // Get the name from ou or cn attribute
    $name = isset($entry['ou'][0]) ? $entry['ou'][0] : ($entry['cn'][0] ?? '');
    
    // Format OU path
    $path = formatOUPath($dn);
    
    // Get parent OU
    $parent_ou = '';
    $dn_parts = explode(',', $dn);
    array_shift($dn_parts);
    foreach ($dn_parts as $part) {
        if (strpos($part, 'OU=') === 0) {
            $parent_ou = substr($part, 3); // "OU=" prefiksini silir
            break;
        } else if (strpos($part, 'CN=') === 0 && strpos($part, 'CN=Users') === 0) {
            $parent_ou = 'Users'; // Xüsusi hal: Users konteynerini göstəririk
            break;
        }
    }
    
    // Get member count
    $memberFilter = "(|(objectClass=user)(objectClass=group)(objectClass=computer))";
    $memberSearch = ldap_search($ldap_conn, $dn, $memberFilter);
    $memberCount = ldap_count_entries($ldap_conn, $memberSearch);
    
    $ou = [
        'name' => $name,
        'dn' => $dn,
        'path' => $path,
        'description' => $entry['description'][0] ?? '',
        'memberCount' => $memberCount,
        'parentOU' => $parent_ou,
        'created' => formatLDAPDate($entry['whencreated'][0] ?? ''),
        'type' => $isOU ? 'Organizational Unit' : 'Container',
        'isContainer' => !$isOU
    ];
    
    // Now, search for all objects in this OU
    $filter = "(|(objectClass=user)(objectClass=group)(objectClass=computer))";
    $result = ldap_list($ldap_conn, $dn, $filter, [
        "name", "cn", "sAMAccountName", "objectClass", "distinguishedName", "whenCreated",
        "member", "memberOf", "description"
    ]);
    
    $entries = ldap_get_entries($ldap_conn, $result);
    $contents = [];
    
    for ($i = 0; $i < $entries['count']; $i++) {
        $entry = $entries[$i];
        $objectClasses = $entry['objectclass'];
        
        // Determine type
        $type = getObjectType($objectClasses);
        
        if ($type) {
            // Get name from appropriate attribute
            $itemName = '';
            if (isset($entry['cn'][0])) {
                $itemName = $entry['cn'][0];
            } elseif (isset($entry['name'][0])) {
                $itemName = $entry['name'][0];
            } elseif (isset($entry['samaccountname'][0])) {
                $itemName = $entry['samaccountname'][0];
            }
            
            $contents[] = [
                'name' => $itemName,
                'type' => strtolower($type),
                'dn' => $entry['distinguishedname'][0] ?? '',
                'created' => formatLDAPDate($entry['whencreated'][0] ?? ''),
                'memberCount' => isset($entry['member']) ? $entry['member']['count'] : null,
                'description' => $entry['description'][0] ?? ''
            ];
        }
    }
    
    // Sort contents by type and name
    usort($contents, function($a, $b) {
        // First sort by type
        if ($a['type'] !== $b['type']) {
            $typeOrder = ['user' => 1, 'group' => 2, 'computer' => 3];
            return $typeOrder[$a['type']] <=> $typeOrder[$b['type']];
        }
        // Then by name
        return $a['name'] <=> $b['name'];
    });
    
    echo json_encode([
        'ou' => $ou,
        'contents' => $contents
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($ldap_conn) && $ldap_conn) {
        ldap_unbind($ldap_conn);
    }
}

function getObjectType($objectClasses) {
    if (in_array('user', $objectClasses)) return 'user';
    if (in_array('group', $objectClasses)) return 'group';
    if (in_array('computer', $objectClasses)) return 'computer';
    return null;
}
