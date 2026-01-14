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
    die(json_encode([
        'error' => __('delete_report_unauthorized'),
        'details' => __('delete_report_unauthorized_details')
    ]));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['filename'])) {
        throw new Exception(__('delete_report_filename_required'));
    }
    
    $filename = basename($data['filename']); // Security: prevent directory traversal
    $filepath = __DIR__ . '/../reports/' . $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception(__('delete_report_file_not_found'));
    }
    
    if (unlink($filepath)) {
        echo json_encode([
            'success' => true,
            'message' => __('delete_report_success')
        ]);
    } else {
        throw new Exception(__('delete_report_error'));
    }
    
} catch (Exception $e) {
    error_log("Report deletion error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => error_get_last()
    ]);
}
