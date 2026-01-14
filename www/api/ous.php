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

try {
    $ldap_conn = getLDAPConnection();
    $ous = getAllOUs($ldap_conn);
    $hierarchy = getOUHierarchy($ous);
    
    echo json_encode([
        'ous' => $ous,
        'hierarchy' => $hierarchy,
        'stats' => [
            'total' => count($ous),
            'types' => array_count_values(array_column($ous, 'type'))
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
