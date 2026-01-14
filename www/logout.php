<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
  
session_start();
require_once('includes/functions.php');
require_once('includes/functions/SecureStore.php');

if (isset($_SESSION['ad_username'])) {
    $username = $_SESSION['ad_username'];
    $session_id = session_id();
    
    // Log logout action
    logActivity('LOGOUT', null, __('user_logged_out'));
    
    // Təhlükəsiz saxlanmış şifrəni silirik
    $secureStore = new SecureStore();
    
    // Əgər oauth_token varsa, onu silirik
    if (isset($_SESSION['auth_token'])) {
        $secureStore->delete('ad_credential_' . $_SESSION['auth_token']);
    }
    
    // Bu istifadəçi ilə əlaqəli bütün təhlükəsiz məlumatları təmizləyirik
    // İlk öncə xüsusi olaraq auth_credential tipli məlumatları təmizləyirik
    $secureStore->cleanupUserData($username, $session_id, 'auth_credential');
    
    // Sonra digər bütün məlumatları təmizləyirik (tip göstərmədən)
    $secureStore->cleanupUserData($username, $session_id);
    
    // Vaxtı keçmiş bütün təhlükəsiz məlumat fayllarını təmizləyirik
    $cleaned_count = $secureStore->cleanupExpiredData();
    if ($cleaned_count > 0) {
        error_log(__('logout_cleaned_files', ['count' => $cleaned_count]));
    }
    
    // Clear session
    $_SESSION = array();
    
    // Delete session cookie if it exists
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

header('Location: index.php');
exit;