<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */


session_start();
require_once('../includes/functions.php');




header('Content-Type: application/json');

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}



try {
    $ldap_conn = getLDAPConnection();
    $result = getAllComputers($ldap_conn);
    
    // Əmin ol ki, data düzgün formatdadır
    $response = [
        'success' => true,
        'computers' => $result['computers'] ?? [],
        'stats' => $result['stats'] ?? [
            'total' => 0,
            'servers' => 0,
            'workstations' => 0,
            'windows' => 0,
            'linux' => 0,
            'unknown' => 0
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'computers' => [],
        'stats' => [
            'total' => 0,
            'servers' => 0,
            'workstations' => 0,
            'windows' => 0,
            'linux' => 0,
            'unknown' => 0
        ]
    ]);
}
