<?php
/*
 * Open Source Version - Security checks removed
 * Original: Copyright (C) [2025] [Ali Zeynalli]
 */

// Prevent direct access - BUT DO NOTHING DESTRUCTIVE
if (!defined('SYSTEM_LOAD')) {
    // Just redirect to index, don't delete anything
    header('Location: /index.php');
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $session_options = [
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'gc_maxlifetime' => 3600
    ];
    
    session_start($session_options);
    
    if (!isset($_SESSION['security_canary'])) {
        $_SESSION['security_canary'] = bin2hex(random_bytes(16));
        $_SESSION['security_timestamp'] = time();
    }
}

// Load essential files
require_once __DIR__ . '/functions.php';