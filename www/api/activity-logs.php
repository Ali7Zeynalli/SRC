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
    echo json_encode(['error' => __('error_unauthorized')]);
    exit;
}



try {
    $db = Database::getInstance();
    
    // Get parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    
    $filters = [
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'action' => $_GET['action'] ?? null,
        'user_id' => $_GET['search'] ?? null
    ];
    
    // Get logs with pagination
    $result = $db->getActivityLogs($page, $limit, $filters);
    
    // Translate operation types and details
    foreach ($result['logs'] as &$log) {
        // Translate operation type
        $operation_key = 'operation_' . strtolower($log['action']);
        $log['action'] = isset($lang[$operation_key]) ? $lang[$operation_key] : $log['action'];
        
        // Translate details if they exist
        if (!empty($log['details'])) {
            $details = json_decode($log['details'], true);
            if ($details) {
                foreach ($details as $key => $value) {
                    $detail_key = 'operation_' . strtolower($key);
                    if (isset($lang[$detail_key])) {
                        $details[$key] = $lang[$detail_key];
                    }
                }
                $log['details'] = json_encode($details);
            }
        }
    }
    
    // Get stats
    $stats = $db->getActivityStats();
    
    echo json_encode([
        'success' => true,
        'logs' => $result['logs'],
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $result['pages'],
            'total_records' => $result['total'],
            'per_page' => $limit
        ],
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'logs' => [],
        'pagination' => [
            'current_page' => 1,
            'total_pages' => 0,
            'total_records' => 0,
            'per_page' => 20
        ],
        'stats' => [
            'total' => 0,
            'today' => 0,
            'this_week' => 0,
            'this_month' => 0
        ]
    ]);
} 