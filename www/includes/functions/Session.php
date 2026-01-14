<?php
require_once dirname(__DIR__) . '/functions.php';

 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */

function checkSession() {
    // System integrity check removed for Open Source version

    // Session məlumatlarının tam olduğunu yoxlayaq
    if (!isset($_SESSION['ad_username']) || 
        !isset($_SESSION['ad_password']) || 
        !isset($_SESSION['logged_in']) || 
        !isset($_SESSION['user_access'])) {
        return false;
    }

    // Session timeout yoxlaması
    $config = require(getConfigPath());
    $timeout = $config['app_settings']['session_timeout'] ?? 3600; // default 1 saat
    
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session-u təmizləmək
        session_unset();
        session_destroy();
        return false;
    }
    
    // Son aktivlik vaxtını yeniləmək
    $_SESSION['last_activity'] = time();
    
    // LDAP bağlantısını yoxlamaq
    try {
        $ldap = getLDAPConnection();
        ldap_read($ldap, $_SESSION['user_dn'], "(objectClass=*)");
    } catch (Exception $e) {
        // LDAP bağlantısı kəsilibsə session-u təmizləmək
        session_unset();
        session_destroy();
        return false;
    }
    
    return true;
}

function isAdmin() {
    try {
        if (!isset($_SESSION['ad_username'])) {
            return false;
        }
        $ldap = getLDAPConnection();
        $config = require(dirname(dirname(__DIR__)) . '/config/config.php');
        
        // Get user's groups
        $userDN = $_SESSION['user_dn'];
        $filter = "(member:1.2.840.113556.1.4.1941:=$userDN)";
        $result = ldap_search($ldap, $config['ad_settings']['base_dn'], $filter);
        
        if (!$result) {
            return false;
        }

        $entries = ldap_get_entries($ldap, $result);
        
        // Check if user is member of admin group
        for ($i = 0; $i < $entries['count']; $i++) {
            if (isset($entries[$i]['cn'][0]) && 
                $entries[$i]['cn'][0] === $config['ad_settings']['admin_group']) {
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("isAdmin check failed: " . $e->getMessage());
        return false;
    }
}

