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

// Təhlükəsizlik yoxlaması
if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['error' => __('delete_logs_unauthorized')]);
    exit;
}

try {
    $db = Database::getInstance();
    $result = $db->backupAndDeleteLogs();
    
    // Translate success/error messages
    if (isset($result['message'])) {
        $result['message'] = __('delete_logs_completed');
    }
    if (isset($result['error'])) {
        $result['error'] = __('delete_logs_failed');
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => __('delete_logs_server_error')
    ]);
} 