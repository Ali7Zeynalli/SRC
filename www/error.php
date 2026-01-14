<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
// Simple error handling without dependencies
error_reporting(0);
ini_set('display_errors', 0);

// Get error code with fallback
$error_code = isset($_GET['code']) ? (int)$_GET['code'] : 500;
if (!$error_code) $error_code = 500;

// Get custom message if provided
$custom_message = isset($_GET['message']) ? $_GET['message'] : '';

$error_messages = [
    400 => [
        'title' => 'Bad Request',
        'icon' => 'exclamation-circle',
        'description' => 'The request could not be processed due to invalid syntax.'
    ],
    401 => [
        'title' => 'Unauthorized Access',
        'icon' => 'lock',
        'description' => 'Authentication is required to access this resource.'
    ],
    402 => 'Payment Required',
    403 => [
        'title' => 'Access Denied',
        'icon' => 'shield-alt',
        'description' => $custom_message ?: 'You do not have permission to access this resource.'
    ],
    404 => [
        'title' => 'Page Not Found',
        'icon' => 'search',
        'description' => 'The requested page could not be found on the server.'
    ],
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    500 => [
        'title' => 'Internal Server Error',
        'icon' => 'server',
        'description' => 'An unexpected error occurred while processing your request.'
    ],
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Temporarily Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported'
];

// Set default if error code not found
if (!isset($error_messages[$error_code])) {
    $error_code = 500;
}

$error = $error_messages[$error_code];

// Set error code header if not already sent
if (!headers_sent()) {
    header("HTTP/1.1 $error_code {$error['title']}");
}

// Special handling for system copy detection (403)
$show_system_copy_warning = false;
if ($error_code === 403 && strpos($custom_message, 'copy detection') !== false) {
    $show_system_copy_warning = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo $error_code; ?> - linkedin.com/in/ali7zeynalli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="temp/css/error.css">
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i class="fas fa-<?php echo $error['icon']; ?>"></i>
            </div>
            <h1 class="error-code"><?php echo $error_code; ?></h1>
            <h2 class="error-message"><?php echo $error['title']; ?></h2>
            
            <div class="alert alert-<?php echo $error_code === 403 ? 'danger' : 'info'; ?> mt-4">
                <?php if ($show_system_copy_warning): ?>
                    <h4 class="alert-heading">
                        <i class="fas fa-shield-alt me-2"></i>Security Alert
                    </h4>
                    <p><?php echo htmlspecialchars($custom_message); ?></p>
                    <hr>
                    <p class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        All sensitive directories and configurations have been removed for security purposes.
                        Please contact support to obtain a valid license.
                    </p>
                <?php else: ?>
                    <p><?php echo htmlspecialchars($error['description']); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($error_code === 403 && $show_system_copy_warning): ?>
                <div class="mt-4">
                    <a href="https://feedback.linkedin.com/in/ali7zeynalli/contact.php" 
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-headset me-2"></i>
                        Contact Support
                    </a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="/" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        Return to Homepage
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <a href="https://linkedin.com/in/ali7zeynalli" class="footer-link" target="_blank">
                <i class="fas fa-globe"></i>
                <span>linkedin.com/in/ali7zeynalli</span>
            </a>
            <span class="version-badge">2025</span>
        </div>
    </footer>
</body>
</html>
