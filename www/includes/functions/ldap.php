<?php

/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/functions/language.php';




// Cari dili yükləyirik
$currentLang = getCurrentLanguage();
$lang = loadLanguageFile($currentLang);

/* SECTION 2: Authentication and Connection (Lines 40-110)
- AD connection functions
- Session handling
- Basic LDAP connection setup
*/

function connectToAD($username, $password) {
    try {
        $config = require(getConfigPath());
        $dc = $config['ad_settings']['domain_controllers'][0];
        
        // LDAPS URL-ni düzgün format
        $ldap_url = 'ldaps://' . $dc . ':636';
        
        $ldap_conn = ldap_connect($ldap_url);
        if (!$ldap_conn) {
            error_log("ERROR: LDAP connection failed to $ldap_url");
            throw new Exception(__('ldap_connection_failed'));
        }
        
        // LDAP əsas parametrləri
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap_conn, LDAP_OPT_NETWORK_TIMEOUT, 30);
        ldap_set_option($ldap_conn, LDAP_OPT_TIMELIMIT, 60);
        ldap_set_option($ldap_conn, LDAP_OPT_SIZELIMIT, 5000);
        
        // SSL/TLS parametrləri
        if (isset($config['ad_settings']['ssl_options'])) {
            
            // verify_cert = false: Development/Docker üçün SSL yoxlamasını tamamilə söndür
            if (isset($config['ad_settings']['ssl_options']['verify_cert']) && 
                $config['ad_settings']['ssl_options']['verify_cert'] === false) {
                putenv('LDAPTLS_REQCERT=never');
                ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                error_log("WARNING: SSL certificate verification disabled for development");
            }
            // CA sertifikatı varsa, tam yoxlama et
            else if (!empty($config['ad_settings']['ssl_options']['ca_cert']) && 
                file_exists($config['ad_settings']['ssl_options']['ca_cert'])) {
                putenv('LDAPTLS_CACERT=' . $config['ad_settings']['ssl_options']['ca_cert']);
                ldap_set_option(NULL, LDAP_OPT_X_TLS_CACERTFILE, $config['ad_settings']['ssl_options']['ca_cert']);
                ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_CACERTFILE, $config['ad_settings']['ssl_options']['ca_cert']);
                putenv('LDAPTLS_REQCERT=demand');
                ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);
                ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);
            }
            // allow_self_signed aktiv olsa, TRY rejimi
            else if (isset($config['ad_settings']['ssl_options']['allow_self_signed']) && 
                $config['ad_settings']['ssl_options']['allow_self_signed'] === true) {
                putenv('LDAPTLS_REQCERT=allow');
                ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_ALLOW);
                ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_ALLOW);
                error_log("INFO: Self-signed certificates allowed");
            }
            // Default: verify_cert yoxdursa da sertifikat yoxlamasını söndür
            else {
                putenv('LDAPTLS_REQCERT=never');
                ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            }
        } else {
            // ssl_options yoxdursa, sertifikat yoxlamasını söndür
            putenv('LDAPTLS_REQCERT=never');
            ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
        }
        
        // Peer name yoxlamasını təyin et (əgər varsa)
        if (isset($config['ad_settings']['ssl_options']['peer_name']) && 
            !empty($config['ad_settings']['ssl_options']['peer_name'])) {
            if (defined('LDAP_OPT_X_TLS_PEERNAME')) {
                ldap_set_option($ldap_conn, LDAP_OPT_X_TLS_PEERNAME, $config['ad_settings']['ssl_options']['peer_name']);
            } else {
                putenv('LDAPTLS_PEERNAME=' . $config['ad_settings']['ssl_options']['peer_name']);
            }
        }
        
        // Retry mexanizmi
        $retry_count = 3;
        $retry_delay = 2;
        
        for ($i = 0; $i < $retry_count; $i++) {
            if (strpos($username, '@') === false) {
                $domain_suffix = $config['ad_settings']['account_suffix'];
                $username = $username . $domain_suffix;
            }
            
            // İstifadəçi adını təhlükəsizləşdiririk - LDAP injektion qarşısını almaq üçün
            $bind_username = $username;
            // Xüsusi simvolları yoxlayırıq və təmizləyirik
            if (preg_match('/[\\\\*\(\)\[\]":;,\/<>=+@]/', $bind_username)) {
                error_log("WARNING: Potentially dangerous characters detected in username: $bind_username");
                
                // Əsas xüsusi simvolları təmizləyirik
                $bind_username = preg_replace('/[\\\\*\(\)\[\]":;,\/<>=+]/', '', $bind_username);
            }
            
            $bind = @ldap_bind($ldap_conn, $bind_username, $password);
            if ($bind) {
                error_log("SUCCESS: Authentication succeeded for username: $username");
                return $ldap_conn;
            }
            
            if ($i < $retry_count - 1) {
                error_log(__('ldap_retry_auth'));
                sleep($retry_delay);
            }
        }
        
        throw new Exception(str_replace('{}', $retry_count, __('ldap_auth_final_failed')));
        
    } catch (Exception $e) {
        error_log("ERROR: LDAP connection error - " . $e->getMessage());
        throw $e;
    }
}

function getLDAPConnection() {
    // SecureStore sinfini daxil edirik
    require_once(__DIR__ . '/SecureStore.php');
    
    // Yeni sessiya təhlükəsizlik yoxlaması
    if (!isset($_SESSION['ad_username']) || !isset($_SESSION['auth_token']) || !isset($_SESSION['auth_token_expiry'])) {
        throw new Exception(__('ldap_no_session'));
    }
    
    // Token vaxtının keçib-keçmədiyini yoxlayırıq
    if ($_SESSION['auth_token_expiry'] < time()) {
        // Sessiya müddəti bitmiş, logout etmək lazımdır
        session_destroy();
        throw new Exception(__('ldap_session_expired'));
    }
    
    $username = $_SESSION['ad_username'];
    $auth_token = $_SESSION['auth_token'];
    
    // Təhlükəsiz saxlanmış şifrəni əldə edirik
    $secureStore = new SecureStore();
    $password = $secureStore->retrieve('ad_credential_' . $auth_token);
    
    // Şifrə əldə edilə bilmədisə
    if ($password === false) {
        // Token mövcud deyil və ya vaxtı keçmiş, logout etmək lazımdır
        session_destroy();
        throw new Exception(__('ldap_auth_error'));
    }
    
    // Token vaxtını yeniləyirik
    // Əgər sessiya müddətinin yarısından çoxu keçmişsə, tokeni yeniləyirik
    $token_lifetime = $_SESSION['auth_token_expiry'] - $_SESSION['last_activity'];
    $half_lifetime = $token_lifetime / 2;
    
    if (time() - $_SESSION['last_activity'] > $half_lifetime) {
        // Yeni token yaradırıq
        $new_token = bin2hex(random_bytes(32));
        $new_expiry = time() + 3600; // 1 saat
        
        // Köhnə şifrəni əldə edirik və yeni tokenə bağlayırıq
        $secureStore->store('ad_credential_' . $new_token, $password, 3600);
        
        // Köhnə tokeni silirik
        $secureStore->delete('ad_credential_' . $auth_token);
        
        // Session məlumatlarını yeniləyirik
        $_SESSION['auth_token'] = $new_token;
        $_SESSION['auth_token_expiry'] = $new_expiry;
    }
    
    // Son aktivlik vaxtını yeniləyirik
    $_SESSION['last_activity'] = time();
    
    // LDAP bağlantısını yaradırıq
    return connectToAD($username, $password);
}
