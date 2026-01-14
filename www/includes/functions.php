<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
// Base functions file - loads all function modules
require_once __DIR__ . '/Database.php';

// Load all function modules
require_once __DIR__ . '/functions/ldap.php';
require_once __DIR__ . '/functions/user.php';
require_once __DIR__ . '/functions/ou.php';
require_once __DIR__ . '/functions/group.php';
require_once __DIR__ . '/functions/computer.php';
require_once __DIR__ . '/functions/gpo.php';
require_once __DIR__ . '/functions/Session.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions/language.php';

// Core utility functions that are used across modules
function getConfigPath() {
    return dirname(__DIR__) . '/config/config.php';
}

/**
 * LDAP sorğuları üçün təhlükəsiz filtr yaradır
 * 
 * @param string $attribute Axtarış atributu (məsələn, 'sAMAccountName', 'cn' və s.)
 * @param string $value Axtarış dəyəri
 * @param string $condition Şərt operatoru (=, >=, <= və s.). Defolt olaraq '='
 * @param bool $exact Tam uyğunluq axtarılmalıdır? Defolt olaraq true
 * @return string Təhlükəsiz LDAP filtr
 */
function buildSecureLdapFilter($attribute, $value, $condition = '=', $exact = true) {
    // Atributu təmizləyirik (ümumiyyətlə sabit dəyər olmalıdır, amma ehtiyat üçün)
    $attribute = preg_replace('/[^a-zA-Z0-9_-]/', '', $attribute);
    
    // Dəyəri LDAP injektion qarşısı üçün təmizləyirik
    $value = ldap_escape($value, "", LDAP_ESCAPE_FILTER);
    
    // Tam uyğunluq deyilsə, wildcard istifadə edirik
    if (!$exact) {
        return "($attribute$condition*$value*)";
    }
    
    return "($attribute$condition$value)";
}

function isAdminUser($ldap_conn, $username) {
    try {
        $config = require(__DIR__ . '/../config/config.php');
        $admin_group = $config['ad_settings']['admin_group'];
        
        // İstifadəçinin DN-ni əldə edirik
        $filter = buildSecureLdapFilter('sAMAccountName', $username);
        $result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $filter);
        $entries = ldap_get_entries($ldap_conn, $result);
        
        if ($entries['count'] === 0) {
            return false;
        }

        // İstifadəçinin admin qrupuna üzv olub-olmadığını yoxlayırıq
        $user_dn = $entries[0]['distinguishedname'][0];
        
        // Admin qrupunu tapırıq
        $group_filter = "(&(objectClass=group)" . buildSecureLdapFilter('cn', $admin_group) . ")";
        $group_result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $group_filter);
        $group_entries = ldap_get_entries($ldap_conn, $group_result);
        
        if ($group_entries['count'] === 0) {
            return false;
        }

        // İstifadəçinin qrupa üzvlüyünü yoxlayırıq
        $group_dn = $group_entries[0]['distinguishedname'][0];
        $member_filter = "(&(objectClass=user)" . 
            buildSecureLdapFilter('distinguishedName', $user_dn) . 
            buildSecureLdapFilter('memberOf', $group_dn) . ")";
            
        $member_result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $member_filter);
        
        return ldap_count_entries($ldap_conn, $member_result) > 0;
    } catch (Exception $e) {
        return false;
    }
}

function checkUserAccess($ldap_conn, $username) {
    try {
        $config = require(__DIR__ . '/../config/config.php');
        $allowed_groups = $config['ad_settings']['allowed_groups'];
        
        // İstifadəçinin DN-ni və qrup üzvlüklərini əldə edirik
        $filter = buildSecureLdapFilter('sAMAccountName', $username);
        $result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $filter, ['memberOf', 'distinguishedName']);
        $entries = ldap_get_entries($ldap_conn, $result);
        
        if ($entries['count'] === 0) {
            return ['allowed' => false, 'message' => __('user_not_found')];
        }

        // Qrup üzvlüyünü daha dəqiq yoxlayırıq
        $user_dn = $entries[0]['distinguishedname'][0];
        $user_groups = [];

        // Bütün icazəli qrupları yoxlayırıq
        foreach ($allowed_groups as $group_name) {
            $group_filter = "(&(objectClass=group)" . buildSecureLdapFilter('cn', $group_name) . ")";
            $group_result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $group_filter);
            $group_entries = ldap_get_entries($ldap_conn, $group_result);
            
            if ($group_entries['count'] === 0) {
                continue;
            }

            // İstifadəçinin qrupa üzvlüyünü yoxlayırıq (həm birbaşa, həm də nested)
            $group_dn = $group_entries[0]['distinguishedname'][0];
            $member_filter = "(&(objectClass=user)" . 
                buildSecureLdapFilter('distinguishedName', $user_dn) . 
                "(memberOf:1.2.840.113556.1.4.1941:=" . ldap_escape($group_dn, "", LDAP_ESCAPE_FILTER) . "))";
            
            $member_result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $member_filter);
            
            if (ldap_count_entries($ldap_conn, $member_result) > 0) {
                $user_groups[] = $group_name;
            }
        }

        // İstifadəçinin ən azı bir icazəli qrupda olub-olmadığını yoxlayırıq
        if (!empty($user_groups)) {
            return [
                'allowed' => true,
                'groups' => $user_groups,
                'is_admin' => in_array($config['ad_settings']['admin_group'], $user_groups)
            ];
        }

        // Heç bir icazəli qrupda deyilsə
        return [
            'allowed' => false, 
            'message' => __('user_not_authorized'),
            'required_groups' => $allowed_groups
        ];
        
    } catch (Exception $e) {
        error_log("Error checking user access: " . $e->getMessage());
        return ['allowed' => false, 'message' => __('user_access_check_error') . ': ' . $e->getMessage()];
    }
}

function isInstalled() {
    // Open Source version - always installed
    return true;
}

// Get OU Count
function getOUCount($ldap_conn) {
    try {
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        $filter = "(objectClass=organizationalUnit)";
        $result = ldap_search($ldap_conn, $base_dn, $filter);
        return ldap_count_entries($ldap_conn, $result);
    } catch (Exception $e) {
        error_log(__('ou_count_error') . ": " . $e->getMessage());
        return 0;
    }
}

// Get Group Count
function getGroupCount($ldap_conn) {
    try {
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        $filter = "(&(objectClass=group)(groupType:1.2.840.113556.1.4.803:=2147483648))";
        $result = ldap_search($ldap_conn, $base_dn, $filter);
        return ldap_count_entries($ldap_conn, $result);
    } catch (Exception $e) {
        error_log(__('group_count_error') . ": " . $e->getMessage());
        return 0;
    }
}

// Get Computer Count
function getComputerCount($ldap_conn) {
    try {
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        $filter = "(objectClass=computer)";
        $result = ldap_search($ldap_conn, $base_dn, $filter);
        return ldap_count_entries($ldap_conn, $result);
    } catch (Exception $e) {
        error_log(__('computer_count_error') . ": " . $e->getMessage());
        return 0;
    }
}

// Get GPO Count
function getGPOCount($ldap_conn) {
    try {
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        $filter = "(objectClass=groupPolicyContainer)";
        $result = ldap_search($ldap_conn, $base_dn, $filter);
        return ldap_count_entries($ldap_conn, $result);
    } catch (Exception $e) {
        error_log(__('gpo_count_error') . ": " . $e->getMessage());
        return 0;
    }
}

function logActivity($action, $targetUserID = null, $details = null) {
    try {
        $db = Database::getInstance();
        
        // Check if database is connected
        if (!$db->isConnected()) {
            error_log(__('db_connection_error'));
            return false;
        }
        
        // Get username from session or use 'system' as fallback
        $userID = isset($_SESSION['ad_username']) ? $_SESSION['ad_username'] : 
                 (isset($GLOBALS['temp_username']) ? $GLOBALS['temp_username'] : 'system');
        
        $sql = "INSERT INTO activity_logs 
                (user_id, action, target_user_id, details, ip_address, user_agent) 
                VALUES (:user_id, :action, :target_user_id, :details, :ip_address, :user_agent)";
                
        $params = [
            ':user_id' => $userID,
            ':action' => $action,
            ':target_user_id' => $targetUserID,
            ':details' => $details,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        $db->query($sql, $params);
        return true;
    } catch (Exception $e) {
        error_log( __('activity_log_error') . ": " . $e->getMessage());
        return false;
    }
}

// Helper function to get activity logs
function getActivityLogs($filters = []) {
    try {
        $db = Database::getInstance();
        $where = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "action = :action";
            $params[':action'] = $filters['action'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "timestamp >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "timestamp <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT * FROM activity_logs 
                $whereClause 
                ORDER BY timestamp DESC 
                LIMIT 1000";
                
        return $db->query($sql, $params)->fetchAll();
    } catch (Exception $e) {
        error_log( __('activity_log_fetch_error') . ": " . $e->getMessage());
        return [];
    }
}

// Activity Log Action Constants
define('LOG_ACTION_LOGIN', 'LOGIN');
define('LOG_ACTION_LOGIN_FAILED', 'LOGIN_FAILED');
define('LOG_ACTION_LOGOUT', 'LOGOUT');

define('LOG_ACTION_USER_UPDATE', 'USER_UPDATE');
define('LOG_ACTION_USER_DELETE', 'DELETE_USER');
define('LOG_ACTION_USER_DATA_CHANGE', 'USER_DATA_CHANGE');

define('LOG_ACTION_ENABLE_USER', 'ENABLE_USER');
define('LOG_ACTION_DISABLE_USER', 'DISABLE_USER');
define('LOG_ACTION_UNLOCK_USER', 'UNLOCK_USER');

/**
 * API təhlükəsizlik başlıqlarını təyin edir - şəbəkə sniffing və digər hücumlara qarşı
 */
function setSecurityHeaders() {
    // Cross-Site Scripting (XSS) əleyhinə
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:;');
    
    // Content sniffing qarşısını almaq üçün
    header('X-Content-Type-Options: nosniff');
    
    // Clickjacking hücumlarına qarşı
    header('X-Frame-Options: DENY');
    
    // XSS filterlərini aktivləşdirmək üçün
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer məlumatlarını məhdudlaşdırmaq
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // İstifadəçi davranışlarını izləməyi qadağan etmək
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

/**
 * API sorğularını məhdudlaşdırmaq üçün Rate Limiting funksiyası
 * Qısa müddət ərzində çoxlu sorğuların qarşısını alır
 * 
 * @param string $key İstifadəçi/IP üçün unikal açar
 * @param int $limit İcazə verilən maksimum sorğu sayı
 * @param int $period Vaxt dövrü (saniyələrlə)
 * @return bool İcazə verilir mi (true) və ya bloklanıb (false)
 */
function rateLimiter($key, $limit = 60, $period = 60) {
    // Rate limiter üçün məlumat faylı yolu
    $storage_dir = __DIR__ . '/../temp/rate_limits/';
    
    // Qovluq mövcud deyilsə, yarat
    if (!file_exists($storage_dir)) {
        mkdir($storage_dir, 0755, true);
    }
    
    // Açardan təhlükəsiz fayl adı yarat
    $filename = $storage_dir . md5($key) . '.json';
    
    // Cari zaman
    $now = time();
    
    // Mövcud məlumatları oxu və ya yeni məlumat yarat
    if (file_exists($filename)) {
        $data = json_decode(file_get_contents($filename), true);
        
        // Vaxtı keçmiş məlumatları təmizlə
        foreach ($data['requests'] as $i => $timestamp) {
            if ($timestamp < $now - $period) {
                unset($data['requests'][$i]);
            }
        }
        
        // Massivi indeksləri ilə yenidən düzəlt
        $data['requests'] = array_values($data['requests']);
    } else {
        $data = [
            'requests' => []
        ];
    }
    
    // Sorğu sayını yoxla
    if (count($data['requests']) >= $limit) {
        // Limit aşılıb
        // Son bloklanma zamanını yenilə
        $data['last_blocked'] = $now;
        file_put_contents($filename, json_encode($data));
        return false;
    }
    
    // Yeni sorğu əlavə et
    $data['requests'][] = $now;
    file_put_contents($filename, json_encode($data));
    return true;
}



/**
 * Təhlükəsiz şəkildə JSON çıxışı hazırlayır
 * 
 * @param mixed $data JSON-a çevriləcək məlumat 
 * @return string Təhlükəsiz JSON mətn
 */
function safeJsonEncode($data) {
    // Məlumatları XSS üçün təmizləyirik
    $clean_data = xssClean($data);
    
    // JSON-a çeviririk
    $json = json_encode($clean_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    
    if ($json === false) {
        // JSON xətası olduqda təhlükəsiz alternativ qaytar
        error_log('JSON encoding error: ' . json_last_error_msg());
        return json_encode(['error' => 'Data encoding error']);
    }
    
    return $json;
}

/**
 * Daxil edilmiş məlumatı santizasiya edir
 * 
 * @param mixed $input Təmizlənəcək məlumat 
 * @param string $type Məlumat növü (string, email, int, url, və s.)
 * @return mixed Təmizlənmiş məlumat
 */
function sanitizeInput($input, $type = 'string') {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value, $type);
        }
        return $input;
    }
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
            
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
            
        case 'string':
        default:
            // Xüsusi simvolları təmizlə
            return filter_var($input, FILTER_SANITIZE_STRING);
    }
}

/**
 * Subresource Integrity (SRI) hash yaradır
 * 
 * @param string $file_path Fayl yolu
 * @param string $algo Hash alqoritmi (sha256, sha384, sha512)
 * @return string SRI integrit yəattributu
 */
function generateSRIHash($file_path, $algo = 'sha384') {
    // Faylın tam yolunu alırıq
    $full_path = __DIR__ . '/../' . ltrim($file_path, '/');
    
    // Faylın mövcudluğunu yoxlayırıq
    if (!file_exists($full_path)) {
        error_log("SRI: File not found: $full_path");
        return '';
    }
    
    // Faylın məzmununu oxuyuruq
    $content = file_get_contents($full_path);
    
    // İstifadə ediləcək hash alqoritminin mövcudluğunu yoxlayırıq
    if (!in_array($algo, ['sha256', 'sha384', 'sha512'])) {
        $algo = 'sha384'; // Default alqoritm
    }
    
    // Hash yaradırıq və base64 kodlayırıq
    $hash = base64_encode(hash($algo, $content, true));
    
    // SRI atributunu formatda qaytarırıq: "algoritm-kodu"
    return "$algo-$hash";
}

/**
 * Bütövlük atributu ilə script tegi yaradır
 * 
 * @param string $file_path Skript faylının yolu
 * @return string HTML script tag with integrity attribute
 */
function secureScriptTag($file_path) {
    $integrity = generateSRIHash($file_path);
    
    // Əgər hash yarana bilməyibsə, standart teq qaytarırıq
    if (empty($integrity)) {
        return '<script src="' . htmlspecialchars($file_path) . '"></script>';
    }
    
    // Təhlükəsiz skript teqi yaradırıq
    return '<script src="' . htmlspecialchars($file_path) . '" 
            integrity="' . $integrity . '" 
            crossorigin="anonymous"></script>';
}

/**
 * Bütövlük atributu ilə stylesheet tegi yaradır
 * 
 * @param string $file_path CSS faylının yolu
 * @return string HTML link tag with integrity attribute
 */
function secureStylesheetTag($file_path) {
    $integrity = generateSRIHash($file_path);
    
    // Əgər hash yarana bilməyibsə, standart teq qaytarırıq
    if (empty($integrity)) {
        return '<link rel="stylesheet" href="' . htmlspecialchars($file_path) . '">';
    }
    
    // Təhlükəsiz CSS teqi yaradırıq
    return '<link rel="stylesheet" href="' . htmlspecialchars($file_path) . '" 
            integrity="' . $integrity . '" 
            crossorigin="anonymous">';
}


