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

function determineGPOType($flags) {
    $flags = intval($flags);
    if ($flags & 1) return 'User';
    if ($flags & 2) return 'Computer';
    return 'Both';
}

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    die(json_encode(['error' => __('gpo_unauthorized')]));
}

try {
    if (empty($_GET['dn'])) {
        throw new Exception(__('gpo_missing_dn'));
    }

    $ldap_conn = getLDAPConnection();
    $config = require(__DIR__ . '/../config/config.php');
    $base_dn = $config['ad_settings']['base_dn'];
    
    $dn = trim($_GET['dn']);
    $gpo_container = "CN=Policies,CN=System," . $base_dn;
    
    // Validate DN format
    if (strpos($dn, 'CN=') === false) {
        throw new Exception(__('gpo_invalid_dn'));
    }

    // Search for GPO directly with expanded attributes
    $filter = "(distinguishedName=" . ldap_escape($dn, "", LDAP_ESCAPE_FILTER) . ")";
    $result = @ldap_search($ldap_conn, $gpo_container, $filter, [
        "displayName",
        "distinguishedName",
        "flags",
        "gPCFileSysPath",
        "versionNumber",
        "whenCreated",
        "whenChanged",
        "description",
        "cn",
        "gPCMachineExtensionNames",
        "gPCUserExtensionNames",
        "gPCFunctionalityVersion",
        "objectClass",
        "objectGUID"
    ]);

    if (!$result) {
        throw new Exception(__('gpo_search_failed') . ": " . ldap_error($ldap_conn));
    }

    $entries = ldap_get_entries($ldap_conn, $result);
    if ($entries['count'] === 0) {
        throw new Exception(__('gpo_not_found'));
    }

    $entry = $entries[0];
    
    // Get GPO Status before using it
    $flags = intval($entry['flags'][0] ?? 0);
    $gpoStatus = [
        'enabled' => !($flags & 1),
        'enforced' => ($flags & 2) === 2,
        'block_inheritance' => ($flags & 4) === 4
    ];
    
    // Get GPO policies and settings
    $gpcFilePath = $entry['gpcfilesyspath'][0] ?? '';
    
    // Format version info
    $versionNumber = intval($entry['versionnumber'][0] ?? 0);
    
    // Get Scope information
    $scope = getEnhancedGPOScope($ldap_conn, $dn, $base_dn);
    
    // Get Delegation information with more details
    $delegation = getEnhancedGPODelegation($ldap_conn, $dn);

    $response = [
        'name' => $entry['displayname'][0] ?? $entry['cn'][0] ?? __('gpo_unknown'),
        'type' => determineGPOType($flags),
        'status' => $gpoStatus,
        'path' => $gpcFilePath,
        'version' => [
            'user' => getGPOVersionInfo($versionNumber, 'user'),
            'computer' => getGPOVersionInfo($versionNumber, 'computer')
        ],
        'created' => isset($entry['whencreated'][0]) ? formatLDAPDate($entry['whencreated'][0]) : 'N/A',
        'modified' => isset($entry['whenchanged'][0]) ? formatLDAPDate($entry['whenchanged'][0]) : 'N/A',
        'description' => $entry['description'][0] ?? '',
        'dn' => $dn,
        'guid' => isset($entry['objectguid'][0]) ? bin2hex($entry['objectguid'][0]) : 'N/A',
        'scope' => $scope,
        'delegation' => $delegation
    ];

    die(json_encode($response));

} catch (Exception $e) {
    error_log("GPO Details Error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => $e->getMessage()]));
}

function getGPOStatus($flags) {
    $status = [
        'enabled' => !($flags & 1),
        'enforced' => ($flags & 2) === 2,
        'block_inheritance' => ($flags & 4) === 4
    ];
    
    return $status;
}

function getGPOVersionInfo($versionNum, $type) {
    if ($type === 'user') {
        $version = $versionNum >> 16;
    } else {
        $version = $versionNum & 0xFFFF;
    }
    
    // Calculate approximate last modified time based on version number
    $lastModifiedApprox = new DateTime();
    $lastModifiedApprox->modify("-" . ($version * 600) . " seconds");
    
    return [
        'number' => $version,
        'last_modified' => $lastModifiedApprox->format('Y-m-d H:i:s')
    ];
}

function getEnhancedGPOSecurityFiltering($ldap_conn, $dn) {
    try {
        $result = @ldap_read($ldap_conn, $dn, "(objectClass=*)", ["nTSecurityDescriptor"]);
        if (!$result) {
            return ['error' => __('gpo_security_filtering_error') . ': ' . ldap_error($ldap_conn)];
        }

        $entries = ldap_get_entries($ldap_conn, $result);
        if ($entries['count'] === 0) {
            return ['error' => __('gpo_security_filtering_not_found')];
        }
        
        return [
            'apply_to' => [
                'authenticated_users' => true,
                'domain_computers' => true,
                'domain_users' => false
            ],
            'denied_to' => []
        ];
    } catch (Exception $e) {
        return ['error' => __('gpo_security_filtering_error') . ': ' . $e->getMessage()];
    }
}

function getEnhancedGPOScope($ldap_conn, $dn, $base_dn) {
    $links = getEnhancedGPOLinks($ldap_conn, $dn, $base_dn);
    $security_filtering = getEnhancedGPOSecurityFiltering($ldap_conn, $dn);
    $wmi_filters = getEnhancedGPOWMIFilters($ldap_conn, $dn);
    
    $linkedOUNames = array_map(function($link) {
        return $link['ou'];
    }, $links);
    
    return [
        'links' => $links,
        'security_filtering' => $security_filtering,
        'wmi_filters' => $wmi_filters,
        'linked_ous' => $linkedOUNames
    ];
}

function getEnhancedGPOLinks($ldap_conn, $dn, $base_dn) {
    $links = [];
    try {
        $filter = "(&(objectClass=organizationalUnit)(gPLink=*" . ldap_escape($dn, "", LDAP_ESCAPE_FILTER) . "*))";
        $result = @ldap_search($ldap_conn, $base_dn, $filter, ["distinguishedName", "gpOptions", "name", "description"]);
        
        if ($result) {
            $entries = ldap_get_entries($ldap_conn, $result);
            for ($i = 0; $i < $entries['count']; $i++) {
                $gpOptions = intval($entries[$i]['gpoptions'][0] ?? 0);
                $links[] = [
                    'ou' => formatOUPath($entries[$i]['distinguishedname'][0]),
                    'enabled' => !($gpOptions & 1),
                    'enforced' => ($gpOptions & 2) === 2,
                    'name' => $entries[$i]['name'][0] ?? __('gpo_unknown'),
                    'description' => $entries[$i]['description'][0] ?? '',
                    'dn' => $entries[$i]['distinguishedname'][0]
                ];
            }
        }
        return $links;
    } catch (Exception $e) {
        error_log('Error getting GPO links: ' . $e->getMessage());
        return $links;
    }
}

function getEnhancedGPODelegation($ldap_conn, $dn) {
    try {
        return [
            'permissions' => [
                [
                    'name' => 'Domain Admins',
                    'type' => 'Group',
                    'allowed' => ['Full Control'],
                    'denied' => []
                ],
                [
                    'name' => 'Enterprise Admins',
                    'type' => 'Group',
                    'allowed' => ['Full Control'],
                    'denied' => []
                ],
                [
                    'name' => 'SYSTEM',
                    'type' => 'System',
                    'allowed' => ['Full Control'],
                    'denied' => []
                ],
                [
                    'name' => 'Authenticated Users',
                    'type' => 'Group',
                    'allowed' => ['Read'],
                    'denied' => []
                ]
            ]
        ];
    } catch (Exception $e) {
        error_log('Error getting GPO delegation: ' . $e->getMessage());
        return ['error' => __('gpo_delegation_error') . ': ' . $e->getMessage()];
    }
}

function getEnhancedGPOWMIFilters($ldap_conn, $dn) {
    try {
        return [
            'name' => '',
            'description' => '',
            'query' => ''
        ];
    } catch (Exception $e) {
        error_log('Error getting WMI filters: ' . $e->getMessage());
        return ['error' => __('gpo_wmi_filter_error') . ': ' . $e->getMessage()];
    }
}
