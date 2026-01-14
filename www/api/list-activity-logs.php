<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
// Enable error reporting at the top of the file
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    
    $query = "SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT ?";
    $stmt = $db->query($query, [$limit]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Translate operation types and details
    foreach ($logs as &$log) {
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

    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);

} catch (Exception $e) {
    error_log("Activity Logs Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => __('error_load_logs')
    ]);
} 