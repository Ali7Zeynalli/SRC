<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */

/**
 * Cari dili qaytarır
 * @return string Dil kodu (az, en, ru, tr, de)
 */
function getCurrentLanguage() {
    // Config faylından default dili yüklə
    $config = require(__DIR__ . '/../../config/config.php');
    $defaultLang = $config['language_settings']['default_language'] ?? 'en';
    
    // Cookie-dən dili yoxla
    if (isset($_COOKIE['site_language'])) {
        $langCode = sanitizeLanguageCode($_COOKIE['site_language']);
        if (validateLanguageCode($langCode)) {
            return $langCode;
        }
    }
    
    // Default dili qaytar
    return $defaultLang;
}

/**
 * Mövcud dilləri qaytarır
 * @return array Dillər siyahısı
 */
function getAvailableLanguages() {
    $config = require(__DIR__ . '/../../config/config.php');
    $availableLanguages = $config['language_settings']['available_languages'] ?? ['en', 'az', 'ru', 'tr', 'de'];
    $languages = [];
    $lang_dir = __DIR__ . '/../languages/';
    
    // Dil fayllarını oxuyur
    if (is_dir($lang_dir)) {
        $files = glob($lang_dir . '*.php');
        foreach ($files as $file) {
            // Fayl adından dil kodunu alırıq (məs: az.php -> az)
            $lang_code = basename($file, '.php');
            
            // Yalnız icazə verilən dilləri əlavə et
            if (in_array($lang_code, $availableLanguages)) {
                // Dil faylını yükləyirik
                $lang_data = require($file);
                
                // Dil məlumatlarını alırıq
                $lang_info = [
                    'name' => $lang_data['language_name'] ?? ucfirst($lang_code),
                    'code' => $lang_data['language_code'] ?? $lang_code,
                    'locale' => $lang_data['language_locale'] ?? $lang_code . '_' . strtoupper($lang_code),
                    'author' => $lang_data['language_author'] ?? 'System',
                    'direction' => $lang_data['language_direction'] ?? 'ltr',
                    'description' => $lang_data['language_description'] ?? '',
                    'file' => $file
                ];
                
                // Dili siyahıya əlavə edirik
                $languages[$lang_code] = $lang_info;
            }
        }
    }
    
    return $languages;
}

/**
 * Tərcümə funksiyası
 * @param string $key Tərcümə açarı
 * @return string Tərcümə edilmiş mətn
 */
function __($key) {
    global $lang;
    return isset($lang[$key]) ? $lang[$key] : $key;
}

/**
 * Dil kodunu validasiya edir
 * @param string $code Dil kodu
 * @return bool
 */
function validateLanguageCode($code) {
    $config = require(__DIR__ . '/../../config/config.php');
    $availableLanguages = $config['language_settings']['available_languages'] ?? ['en', 'az', 'ru', 'tr', 'de'];
    return in_array($code, $availableLanguages);
}

/**
 * Dil kodunu təmizləyir
 * @param string $code Dil kodu
 * @return string
 */
function sanitizeLanguageCode($code) {
    return preg_replace('/[^a-z]/', '', strtolower($code));
}

/**
 * Dil faylını yükləyir
 * @param string $langCode Dil kodu
 * @return array
 */
function loadLanguageFile($langCode) {
    $config = require(__DIR__ . '/../../config/config.php');
    $fallbackLang = $config['language_settings']['fallback_language'] ?? 'en';
    $langFile = __DIR__ . '/../languages/' . $langCode . '.php';
    
    if (file_exists($langFile) && validateLanguageCode($langCode)) {
        return include $langFile;
    }
    
    // Fallback dil faylını yüklə
    return include __DIR__ . '/../languages/' . $fallbackLang . '.php';
} 