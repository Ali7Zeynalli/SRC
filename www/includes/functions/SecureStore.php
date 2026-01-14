<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */

class SecureStore {
    private $encrypt_method = "AES-256-CBC";
    private $secret_key;
    private $secret_iv;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Config faylından secret key əldə etmək
        $config = require(__DIR__ . '/../../config/config.php');
        
        // Mövcud konfiqurasiyada secret key olub-olmadığını yoxlayırıq
        if (isset($config['security_settings']['secret_key']) && !empty($config['security_settings']['secret_key'])) {
            $this->secret_key = $config['security_settings']['secret_key'];
        } else {
            // Key mövcud deyilsə, yeni yaradırıq və konfiqurasiya faylına yazırıq
            $this->generateNewSecretKey();
        }
        
        // IV üçün server-spesifik məlumatlardan hash yaradırıq
        $server_specific = $_SERVER['SERVER_NAME'] . $_SERVER['DOCUMENT_ROOT'];
        $this->secret_iv = hash('sha256', $server_specific);
    }
    
    /**
     * Yeni təsadüfi gizli açar yaradır
     */
    private function generateNewSecretKey() {
        // 32 bayt təsadüfi key yaradırıq
        $this->secret_key = bin2hex(openssl_random_pseudo_bytes(32));
        
        // Config faylını oxumaq və yeniləmək
        $config_file = __DIR__ . '/../../config/config.php';
        $config = require($config_file);
        
        // Security settings bölməsi yoxdursa, yaradırıq
        if (!isset($config['security_settings'])) {
            $config['security_settings'] = [];
        }
        
        // Secret key əlavə edirik
        $config['security_settings']['secret_key'] = $this->secret_key;
        
        // Config faylını yeniləyirik
        file_put_contents($config_file, '<?php return ' . var_export($config, true) . ';');
    }
    
    /**
     * Təhlükəsiz məlumat saxlama
     * 
     * @param string $key Unikal açar
     * @param string $value Saxlanacaq məlumat
     * @param int $ttl Saxlama müddəti (saniyə ilə)
     * @param array $metadata Əlavə məlumatlar (username, session_id və s.)
     * @return bool Əməliyyat nəticəsi
     */
    public function store($key, $value, $ttl = 3600, $metadata = []) {
        try {
            // Key üçün MD5 hash yaradırıq
            $file_key = md5($key);
            $file_path = __DIR__ . '/../../temp/secure_store/' . $file_key . '.dat';
            
            // Məlumatı şifrələyirik
            $encrypted_data = $this->encrypt($value);
            
            // Tamamlanmış məlumat obyekti
            $data = [
                'created' => time(),
                'expires' => time() + $ttl,
                'data' => $encrypted_data,
                'metadata' => $metadata
            ];
            
            // Məlumatı fayla yazırıq
            if (!file_put_contents($file_path, json_encode($data))) {
                error_log("SecureStore error: Could not write to file $file_path");
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("SecureStore error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Saxlanmış təhlükəsiz məlumatı əldə edir
     * 
     * @param string $key Unikal açar
     * @return mixed Açılmış məlumat və ya null (əgər məlumat tapılmayıbsa və ya vaxtı keçmişsə)
     */
    public function retrieve($key) {
        try {
            // Key üçün MD5 hash yaradırıq
            $file_key = md5($key);
            $file_path = __DIR__ . '/../../temp/secure_store/' . $file_key . '.dat';
            
            // Fayl mövcud deyilsə, null qaytarırıq
            if (!file_exists($file_path)) {
                return null;
            }
            
            // Məlumatı oxuyuruq
            $stored_data = json_decode(file_get_contents($file_path), true);
            if (!$stored_data || !isset($stored_data['data'])) {
                error_log("SecureStore error: Invalid data format in file $file_path");
                return null;
            }
            
            // Vaxtı bitmiş məlumatı yoxlayırıq
            if (isset($stored_data['expires']) && $stored_data['expires'] > 0 && time() > $stored_data['expires']) {
                // Vaxtı keçmiş faylı silirik
                $this->delete($key);
                return null;
            }
            
            // Məlumatı deşifrələyirik
            return $this->decrypt($stored_data['data']);
            
        } catch (Exception $e) {
            error_log("SecureStore error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Saxlanmış məlumatı silir
     * 
     * @param string $key Unikal açar
     * @return bool Əməliyyat nəticəsi
     */
    public function delete($key) {
        try {
            $key = md5($key);
            $file_path = __DIR__ . '/../../temp/secure_store/' . $key . '.dat';
            
            if (file_exists($file_path)) {
                return unlink($file_path);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("SecureStore error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * İstifadəçi sessiyası ilə əlaqəli bütün təhlükəsiz məlumatları təmizləyir
     * 
     * @param string $username İstifadəçi adı
     * @param string $session_id Sessiya ID (optional) 
     * @param string $type Məlumat tipi (optional, məs. 'auth_credential')
     * @return bool Əməliyyat nəticəsi
     */
    public function cleanupUserData($username, $session_id = null, $type = null) {
        try {
            $store_path = __DIR__ . '/../../temp/secure_store/';
            $files = glob($store_path . '*.dat');
            $count = 0;
            
            // İlk mərhələdə həm bu sessiya üçün, həm də bu istifadəçi üçün qalıq 
            // təhlükəsiz məlumatları təmizləyirik
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (!file_exists($file)) {
                        continue;
                    }
                    
                    $data = json_decode(file_get_contents($file), true);
                    
                    // Vaxtı keçmiş faylları təmizləyirik
                    if (isset($data['expires']) && time() > $data['expires']) {
                        unlink($file);
                        $count++;
                        continue;
                    }
                    
                    // İstifadəçi adı və ya sessiya məlumatları ilə uyğunlaşan faylları təmizləyirik
                    if (isset($data['metadata'])) {
                        // Əgər tip təyin olunubsa, yalnız həmin tipdə olan faylları təmizləyirik
                        if ($type !== null && (!isset($data['metadata']['type']) || $data['metadata']['type'] !== $type)) {
                            continue;
                        }
                        
                        if (
                            (isset($data['metadata']['username']) && $data['metadata']['username'] === $username) ||
                            ($session_id && isset($data['metadata']['session_id']) && $data['metadata']['session_id'] === $session_id)
                        ) {
                            unlink($file);
                            $count++;
                        }
                    }
                }
            }
            
            error_log("SecureStore: Cleaned up $count file(s) for user $username" . ($type ? " (type: $type)" : ""));
            return true;
        } catch (Exception $e) {
            error_log("SecureStore cleanup error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bütün vaxtı keçmiş təhlükəsiz məlumat fayllarını təmizləyir
     * 
     * @return int Silinən faylların sayı
     */
    public function cleanupExpiredData() {
        try {
            $store_path = __DIR__ . '/../../temp/secure_store/';
            $files = glob($store_path . '*.dat');
            $count = 0;
            
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (!file_exists($file)) {
                        continue;
                    }
                    
                    $data = json_decode(file_get_contents($file), true);
                    
                    // Vaxtı keçmiş faylları təmizləyirik
                    if (isset($data['expires']) && time() > $data['expires']) {
                        unlink($file);
                        $count++;
                    }
                }
            }
            
            return $count;
        } catch (Exception $e) {
            error_log("SecureStore cleanup error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Məlumatı şifrələyir
     * 
     * @param string $value Şifrələnəcək məlumat
     * @return string Şifrələnmiş məlumat
     */
    private function encrypt($value) {
        $key = hash('sha256', $this->secret_key);
        $iv = substr(hash('sha256', $this->secret_iv), 0, 16);
        
        $encrypted = openssl_encrypt($value, $this->encrypt_method, $key, 0, $iv);
        return $encrypted;
    }
    
    /**
     * Şifrələnmiş məlumatı deşifrə edir
     * 
     * @param string $encrypted Şifrələnmiş məlumat
     * @return string Deşifrə edilmiş məlumat
     */
    private function decrypt($encrypted) {
        $key = hash('sha256', $this->secret_key);
        $iv = substr(hash('sha256', $this->secret_iv), 0, 16);
        
        $decrypted = openssl_decrypt($encrypted, $this->encrypt_method, $key, 0, $iv);
        return $decrypted;
    }
} 