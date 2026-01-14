<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
// Clear any previous output
if (ob_get_level()) ob_end_clean();

session_start();
require_once(__DIR__ . '/../includes/functions.php');




header('Content-Type: application/json');

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}


try {
    $config = require(__DIR__ . '/../config/config.php');
    
    // Only return necessary config settings
    echo json_encode([
        'password_settings' => [
            'default_temp_password' => $config['password_settings']['default_temp_password']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 