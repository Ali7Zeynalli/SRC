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
    echo json_encode(['error' => __('group_unauthorized')]);
    exit;
}

try {
    if (!isset($_GET['dn'])) {
        throw new Exception(__('group_dn_required'));
    }

    $ldap_conn = getLDAPConnection();
    $dn = $_GET['dn'];

    // Search for specific group
    $filter = "(&(objectClass=group)(distinguishedName=" . ldap_escape($dn, "", LDAP_ESCAPE_FILTER) . "))";
    $attributes = ["cn", "description", "grouptype", "member", "whencreated", "mail", "info"];
    
    $result = ldap_read($ldap_conn, $dn, $filter, $attributes);
    if (!$result) {
        throw new Exception(__('group_not_found'));
    }

    $entries = ldap_get_entries($ldap_conn, $result);
    if ($entries['count'] == 0) {
        throw new Exception(__('group_not_found'));
    }

    $entry = $entries[0];
    $groupType = $entry['grouptype'][0];

    // Get members with display names
    $members = [];
    if (isset($entry['member'])) {
        for ($i = 0; $i < $entry['member']['count']; $i++) {
            $memberDn = $entry['member'][$i];
            $memberResult = ldap_read($ldap_conn, $memberDn, "(objectClass=*)", ["displayName", "cn"]);
            if ($memberResult) {
                $memberEntry = ldap_get_entries($ldap_conn, $memberResult);
                $members[] = $memberEntry[0]['displayname'][0] ?? $memberEntry[0]['cn'][0] ?? $memberDn;
            }
        }
    }
    sort($members); // Sort members alphabetically

    $groupDetails = [
        'name' => $entry['cn'][0],
        'dn' => $dn,
        'type' => getGroupSecurityType($groupType),
        'scope' => getGroupScope($groupType),
        'description' => $entry['description'][0] ?? '',
        'email' => $entry['mail'][0] ?? '',
        'notes' => $entry['info'][0] ?? '',
        'created' => formatLDAPDate($entry['whencreated'][0] ?? ''),
        'memberCount' => isset($entry['member']) ? $entry['member']['count'] : 0,
        'members' => $members,
        'ou' => formatOUPath($dn)
    ];

    echo json_encode($groupDetails);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
