<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/../includes/functions.php');



require_once(__DIR__ . '/../includes/ReportGenerator.php');

header('Content-Type: application/json');


ob_start(); // Output buffering start

try {
    if (!isset($_SESSION['ad_username'])) {
        throw new Exception('Unauthorized');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || empty($data['sections'])) {
        throw new Exception('Please select at least one section');
    }

    $ldap = getLDAPConnection();
    $generator = new ReportGenerator($ldap);
    
    $reportData = $generator->generateReport($data['sections']);
    
    if (empty($reportData)) {
        throw new Exception('No data available');
    }

    // Generate filename based on selected sections
    $sectionNames = [
        'users' => 'Users',
        'groups' => 'Groups',
        'computers' => 'Computers',
        'ous' => 'OUs',
        'gpos' => 'GPOs'
    ];

    $selectedSections = array_map(function($section) use ($sectionNames) {
        return $sectionNames[$section] ?? $section;
    }, $data['sections']);

    // Create a more descriptive filename
    $reportType = count($selectedSections) > 1 ? 'Combined' : $selectedSections[0];
    $baseFilename = 'ADReport_' . $reportType . '_' . date('Y-m-d_H-i-s', time());
    
    // Save in selected format
    $format = $data['format'] ?? 'csv';
    if ($format === 'excel') {
        $reportFile = $generator->saveToExcel($reportData, $baseFilename . '.xls');
    } else {
        $reportFile = $generator->saveToCSV($reportData, $baseFilename . '.csv');
    }

    $responseData = [
        'success' => true,
        'reportData' => [],
        'exportLinks' => [
            $format => $reportFile
        ]
    ];

    // Structure the report data
    foreach ($reportData as $section => $items) {
        if (!empty($items) && isset($items[0]) && is_array($items[0])) {
            $responseData['reportData'][] = [
                'section' => $section,
                'headers' => array_keys($items[0]),
                'rows' => array_slice($items, 0, 10),
                'totalRows' => count($items)
            ];
        } else {
            // Handle empty sections or sections with no data
            $responseData['reportData'][] = [
                'section' => $section,
                'headers' => ['No data available'],
                'rows' => [],
                'totalRows' => 0
            ];
        }
    }

    // Clear any output and send JSON response
    if (ob_get_length()) ob_clean();
    echo json_encode($responseData);
    exit;

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
