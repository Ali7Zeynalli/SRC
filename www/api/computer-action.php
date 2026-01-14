<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */

session_start();
require_once('../includes/functions.php');




header('Content-Type: application/json');

// İstifadəçi giriş edibmi yoxla
if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('unauthorized')]);
    exit;
}



// POST parametrini al
$action = $_POST['action'] ?? '';

// LDAP bağlantısı
try {
    $ldap_conn = getLDAPConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('ldap_connection_failed', ['error' => $e->getMessage()])]);
    exit;
}

switch ($action) {
    case 'delete_computer':
        // Kompüter silmə əməliyyatı
        $dn = $_POST['dn'] ?? '';
        
        if (empty($dn)) {
            echo json_encode(['success' => false, 'error' => __('computer_dn_required')]);
            exit;
        }
        
        $result = deleteComputer($ldap_conn, $dn);
        echo json_encode($result);
        break;
    
    case 'move_computer':
        // Kompüteri OU-lar arası köçürmə əməliyyatı
        $dn = $_POST['dn'] ?? '';
        $new_ou_dn = $_POST['new_ou_dn'] ?? '';
        
        if (empty($dn) || empty($new_ou_dn)) {
            echo json_encode(['success' => false, 'error' => __('computer_move_required_fields')]);
            exit;
        }
        
        $result = moveComputerToOU($ldap_conn, $dn, $new_ou_dn);
        echo json_encode($result);
        break;
    
    case 'get_computer_details':
        // Kompüter haqqında ətraflı məlumat əldə etmə
        $dn = $_POST['dn'] ?? '';
        
        if (empty($dn)) {
            echo json_encode(['success' => false, 'error' => __('computer_dn_required')]);
            exit;
        }
        
        $result = getComputerDetails($ldap_conn, $dn);
        echo json_encode($result);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => __('invalid_action')]);
        break;
} 