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
    die(json_encode(['error' => __('error_unauthorized')]));
}

try {
    $reportsDir = __DIR__ . '/../reports/';
    $reports = [];

    // Create reports directory if it doesn't exist
    if (!file_exists($reportsDir)) {
        mkdir($reportsDir, 0777, true);
    }
    
    // Get both CSV and XLS files
    $files = array_merge(
        glob($reportsDir . '*.csv'),
        glob($reportsDir . '*.xls')
    );

    foreach ($files as $file) {
        $filename = basename($file);
        $created = filectime($file);
        
        // Parse report type from filename
        $nameParts = explode('_', $filename);
        $reportType = $nameParts[1] ?? __('unknown_report_type');
        
        $reports[] = [
            'filename' => $filename,
            'date' => date('Y-m-d H:i:s', $created),
            'formatted_date' => date('M d, Y H:i', $created),
            'type' => $reportType,
            'path' => '/reports/' . $filename,
            'size' => filesize($file)
        ];
    }

    // Sort by date descending
    usort($reports, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'directory' => $reportsDir
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => __('error_load_reports'),
        'details' => error_get_last()
    ]);
}
