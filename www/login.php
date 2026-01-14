<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
  
session_start();
require_once(__DIR__ . '/includes/functions.php');
require_once(__DIR__ . '/includes/functions/SecureStore.php');



// Əgər artıq login olubsa, dashboarda yönləndir
if (isset($_SESSION['ad_username'])) {
    header('Location: dashboard.php');
    exit;
}

// Yalnız AJAX/POST sorğularını qəbul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    header('Location: index.php');
    exit;
}

try {
    // Təmizlənmiş username
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    // Təhlükəsizlik təkmilləşdirilməsi: LDAP injektion təhdidləri üçün əlavə təmizləmə
    $username = preg_replace('/[\\\\*\(\)\[\]":;,\/<>=+]/', '', $username);
    $username = trim(explode('@', $username)[0]); // Domain hissəsini silirik
    
    // Boş istifadəçi adı yoxlaması
    if (empty($username)) {
        throw new Exception(__('error_username_empty'));
    }
    
    // Minimum uzunluq yoxlaması
    if (strlen($username) < 3) {
        throw new Exception(__('error_username_short'));
    }
    
    // Şifrəni əldə edirik (sanitize etmirik, çünki şifrədə xüsusi simvollar ola bilər)
    $password = $_POST['password'];
    // Boş şifrə yoxlaması
    if (empty($password)) {
        throw new Exception(__('error_password_empty'));
    }
    
    // LDAP qoşulması
    $ldap_conn = connectToAD($username, $password);
    
    // İstifadəçi səlahiyyətlərini yoxlayırıq
    $access = checkUserAccess($ldap_conn, $username);
    
    if (!$access['allowed']) {
        throw new Exception(__('error_access_denied'));        
    }
    
    // Təhlükəsiz token yaradırıq
    $secure_token = bin2hex(random_bytes(32));
    $token_expiry = 3600; // 1 saat
    
    // Şifrəni təhlükəsiz saxlayırıq
    $secureStore = new SecureStore();
    $store_key = 'ad_credential_' . $secure_token;
    
    // Metadata hazırlayırıq - istifadəçi adı və session_id
    $metadata = [
        'username' => $username,
        'session_id' => session_id(),
        'type' => 'auth_credential'
    ];
    
    // Şifrəni şifrələnmiş formada saxlayırıq (token ilə əlaqəli və metadata ilə)
    if (!$secureStore->store($store_key, $password, $token_expiry, $metadata)) {
        throw new Exception(__('error_secure_store'));
    }
    
    // Session məlumatlarını saxlayırıq (şifrə olmadan)
    $_SESSION['ad_username'] = $username;
    // $_SESSION['ad_password'] = base64_encode($password); // Köhnə təhlükəsiz olmayan metod
    
    // Tokenə əsaslanan təhlükəsiz metod
    $_SESSION['auth_token'] = $secure_token;
    $_SESSION['auth_token_expiry'] = time() + $token_expiry;
    $_SESSION['user_access'] = $access;
    $_SESSION['last_activity'] = time();
    $_SESSION['logged_in'] = true;
    
    // Session-u yeniləyirik (Session fixation qarşısını almaq üçün)
    session_regenerate_id(true);
    
    // Successful login
    logActivity(LOG_ACTION_LOGIN, null, __('success_login'));
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'redirect' => 'dashboard.php',
        'message' => __('success_login')
    ]);
    
} catch (Exception $e) {
    // Failed login
    logActivity(LOG_ACTION_LOGIN_FAILED, null, __('error_login_failed') . ': ' . $username);
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
