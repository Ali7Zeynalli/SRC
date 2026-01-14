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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['default_temp_password'])) {
        throw new Exception('Default temporary password is required');
    }

    $config = require(__DIR__ . '/../config/config.php');
    
    // Update password settings
    $config['password_settings'] = [
        'default_temp_password' => $input['default_temp_password'],
        'min_length' => $input['min_length'] ?? 8,
        'complexity' => $input['complexity'] ?? true
    ];
    
    // Save config
    $config_content = "<?php\nreturn " . var_export($config, true) . ";\n";
    file_put_contents(__DIR__ . '/../config/config.php', $config_content);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 