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
    die(json_encode(['error' => 'Unauthorized']));
}

try {
    $ldap = getLDAPConnection();
    
    // Calculate user stats if not in session
    if (!isset($_SESSION['user_stats'])) {
        $users = getAllUsers($ldap);
        $lockedUsers = getLockedUsers($ldap);
        
        $stats = [
            'total' => count($users) - 1,
            'active' => 0,
            'inactive' => 0,
            'expired_password' => 0,
            'locked' => count($lockedUsers),
            'never_expires' => 0,
            'must_change' => 0
        ];
        
        // Loop through users to calculate stats
        for ($i = 0; $i < $stats['total']; $i++) {
            $user = $users[$i];
            $uac = isset($user['useraccountcontrol'][0]) ? $user['useraccountcontrol'][0] : 0;
            $pwdLastSet = isset($user['pwdlastset'][0]) ? $user['pwdlastset'][0] : 0;
            
            // Check if account is enabled
            if (($uac & 2) !== 2) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }
            
            // Check password expiry and never expires status
            $pwdStatus = getPasswordExpiryStatus($pwdLastSet, $ldap, $uac);
            if ($pwdStatus['status'] === 'Expired') {
                $stats['expired_password']++;
            } else if ($pwdStatus['status'] === 'Never Expires') {
                $stats['never_expires']++;
            } else if ($pwdStatus['status'] === 'Must Change') {
                $stats['must_change']++;
            }
        }

        // Save to session
        $_SESSION['user_stats'] = [
            'total' => $stats['total'],
            'active' => $stats['active'],
            'inactive' => $stats['inactive'],
            'locked' => $stats['locked'],
            'password_status' => [
                'expired' => $stats['expired_password'],
                'never_expires' => $stats['never_expires'],
                'must_change' => $stats['must_change']
            ]
        ];
    }

    $userStats = $_SESSION['user_stats'];

    // Get groups stats
    $groups = getAllGroups($ldap);
    $security_groups = 0;
    $distribution_groups = 0;

    foreach ($groups as $group) {
        if ($group['type'] === 'Security') {
            $security_groups++;
        } else {
            $distribution_groups++;
        }
    }

    $groupStats = [
        'total' => count($groups),
        'security' => $security_groups,
        'distribution' => $distribution_groups
    ];

    // Get computers stats
    $computers = getAllComputers($ldap);
    $server_computers = 0;
    $workstation_computers = 0;

    foreach ($computers as $computer) {
        // Check if computer is a server by checking operatingSystem attribute
        if (isset($computer['operatingsystem'][0])) {
            $os = strtolower($computer['operatingsystem'][0]);
            if (strpos($os, 'server') !== false) {
                $server_computers++;
            } else {
                $workstation_computers++;
            }
        } else {
            // If no OS info, assume it's a workstation
            $workstation_computers++;
        }
    }

    $computerStats = [
        'total' => count($computers),
        'servers' => $server_computers,
        'workstations' => $workstation_computers
    ];

    echo json_encode([
        'success' => true,
        'stats' => [
            'users' => $userStats,
            'groups' => $groupStats,
            'computers' => $computerStats
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
