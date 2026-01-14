<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */

session_start();


header('Content-Type: application/json');



try {
    // Request body-ni alırıq
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['cache_keys']) || !is_array($data['cache_keys'])) {
        throw new Exception('Invalid request format');
    }
    
    // Göstərilən keş açarlarını təmizləyirik
    foreach ($data['cache_keys'] as $key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            unset($_SESSION[$key . '_time']);
        }
    }
    
    // Local Storage-dan statistikanı təmizləyirik
    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 