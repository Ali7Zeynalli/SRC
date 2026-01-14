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
ob_clean();

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    die(json_encode(['error' => __('gpo_unauthorized')]));
}

try {
    $ldap_conn = getLDAPConnection();
    $config = require(__DIR__ . '/../config/config.php');
    $gpo_container = "CN=Policies,CN=System," . $config['ad_settings']['base_dn'];

    // Search for GPOs with expanded attributes
    $result = ldap_search($ldap_conn, $gpo_container, "(objectClass=groupPolicyContainer)", [
        "displayName", "distinguishedName", "flags", "gPCFileSysPath",
        "versionNumber", "whenCreated", "whenChanged", "description",
        "gPCMachineExtensionNames", "gPCUserExtensionNames", "objectClass"
    ]);

    if (!$result) {
        throw new Exception(__('gpo_search_failed') . ": " . ldap_error($ldap_conn));
    }

    $entries = ldap_get_entries($ldap_conn, $result);
    $gpos = [];
    $linkedOUsCount = 0;
    $computerPoliciesCount = 0;
    $userPoliciesCount = 0;

    for ($i = 0; $i < $entries['count']; $i++) {
        $entry = $entries[$i];
        $dn = $entry['distinguishedname'][0];
        $flags = intval($entry['flags'][0] ?? 0);
        
        // Get linked OUs
        $link_result = @ldap_search(
            $ldap_conn, 
            $config['ad_settings']['base_dn'], 
            "(&(objectClass=organizationalUnit)(gPLink=*$dn*))",
            ["distinguishedName", "gPOptions"]
        );

        $linked_ous = [];
        if ($link_result) {
            $ou_entries = ldap_get_entries($ldap_conn, $link_result);
            for ($j = 0; $j < $ou_entries['count']; $j++) {
                $linked_ous[] = formatOUPath($ou_entries[$j]['distinguishedname'][0]);
            }
            $linkedOUsCount += count($linked_ous);
        }
        
        // Determine type
        $type = determineGPOType($flags);
        if ($type === 'Computer') {
            $computerPoliciesCount++;
        } elseif ($type === 'User') {
            $userPoliciesCount++;
        }
        
        // Get status information
        $status = [
            'enabled' => !($flags & 1),
            'enforced' => ($flags & 2) === 2,
            'block_inheritance' => ($flags & 4) === 4
        ];
        
        // Format version info
        $versionNumber = intval($entry['versionnumber'][0] ?? 0);
        $userVersion = $versionNumber >> 16;
        $computerVersion = $versionNumber & 0xFFFF;
        
        // Build the object
        $gpos[] = [
            'name' => $entry['displayname'][0] ?? __('gpo_unknown'),
            'type' => $type,
            'path' => $entry['gpcfilesyspath'][0] ?? '',
            'version' => [
                'user' => $userVersion,
                'computer' => $computerVersion,
                'combined' => $versionNumber
            ],
            'created' => isset($entry['whencreated']) ? formatLDAPDate($entry['whencreated'][0]) : 'N/A',
            'modified' => isset($entry['whenchanged']) ? formatLDAPDate($entry['whenchanged'][0]) : 'N/A',
            'description' => $entry['description'][0] ?? '',
            'dn' => $dn,
            'linkedOUs' => $linked_ous,
            'status' => $status
        ];
    }

    // Sort GPOs alphabetically by name
    usort($gpos, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    // Return the complete information
    die(json_encode([
        'gpos' => $gpos,
        'stats' => [
            'total' => count($gpos),
            'linked_ous' => $linkedOUsCount
        ]
    ]));

} catch (Exception $e) {
    error_log("GPO API Error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => $e->getMessage()]));
}

function determineGPOType($flags) {
    $flags = intval($flags);
    if ($flags & 1) return 'User';
    if ($flags & 2) return 'Computer';
    return 'Both';
}
