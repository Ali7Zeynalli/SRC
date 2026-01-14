<?php
/**
 * LDAP Authentication System
 * Theme Toggle AJAX Endpoint
 */

// Include configuration
define('INCLUDED', true);
require_once '../../config/init.php';

// Allow both GET and POST requests for better compatibility
$theme = null;

// Check POST request
if (isset($_POST['theme'])) {
    $theme = $_POST['theme'];
}

// Check GET request
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'];
}

// Set theme if valid
if ($theme === 'dark' || $theme === 'light') {
    $isDarkMode = ($theme === 'dark');
    setDarkMode($isDarkMode);
    
    // Return JSON for AJAX requests
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'theme' => $theme]);
    }
    // Return simple message for non-AJAX requests
    else {
        echo 'Theme updated successfully.';
    }
    exit;
}

// Fall-through error response
if (isAjaxRequest()) {
    header("HTTP/1.0 400 Bad Request");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid theme value']);
} else {
    echo 'Error: Invalid theme value.';
}
exit; 