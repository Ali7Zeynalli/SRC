<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */

session_start();
require_once(__DIR__ . '/../includes/functions.php');

header('Content-Type: application/json');

// Clear any previous output
ob_clean();

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => __('config_unauthorized')]));
}

try {
    // Get JSON input and decode
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(__('config_invalid_json') . ': ' . json_last_error_msg());
    }

    // Load current config
    $config_file = __DIR__ . '/../config/config.php';
    $current_config = require($config_file);

    // Update config sections
    foreach ($data as $section => $settings) {
        if (isset($current_config[$section])) {
            // Skip empty password in db_settings
            if ($section === 'db_settings' && isset($settings['password']) && empty($settings['password'])) {
                unset($settings['password']);
            }
            $current_config[$section] = array_merge($current_config[$section], $settings);
        }
    }

    // Generate new config content
    $config_content = "<?php\nreturn " . var_export($current_config, true) . ";\n";

    // Save new config
    if (file_put_contents($config_file, $config_content) === false) {
        throw new Exception(__('config_save_failed'));
    }

    // Clear PHP opcode cache
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($config_file, true);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => __('config_updated_success')
    ]);

} catch (Exception $e) {
    // Log error
    error_log('Config update error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
