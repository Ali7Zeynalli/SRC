<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
session_start();
require_once(__DIR__ . '/../includes/functions.php');




if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    die('Unauthorized');
}

try {
    $filename = basename($_GET['file'] ?? '');
    if (empty($filename)) {
        throw new Exception('No file specified');
    }

    $filepath = __DIR__ . '/../reports/' . $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception('File not found');
    }

    // Set correct content type based on file extension
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'xls':
            header('Content-Type: application/vnd.ms-excel');
            break;
        case 'csv':
            header('Content-Type: text/csv');
            break;
        default:
            header('Content-Type: application/octet-stream');
    }

    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // Output file
    readfile($filepath);
    exit;

}


 catch (Exception $e) {
    http_response_code(404);
    die($e->getMessage());
}
