<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [ali] <[ali.z.zeynalli@gmail.com]> [2025]
  */
  
  session_start();
  require_once(__DIR__ . '/includes/functions.php');
  


// Load config
$config_file = __DIR__ . '/config/config.php';
$config = require($config_file);

// Check if system is installed through config
if (empty($config['installation']['installed'])) {
    header('Location: install.php');
    exit;
}

// Archive folder path
$archiveDir = __DIR__ . '/_archive';

// List of files to ARCHIVE (move to _archive folder)
$itemsToArchive = [
    __DIR__ . '/installer.php',
    __DIR__ . '/install.php',
    __DIR__ . '/preinstall.php',
    __DIR__ . '/install.sql',
    __DIR__ . '/install/schema.sql',
    __FILE__  // uninstall.php itself
];

// Create archive directory if not exists
if (!is_dir($archiveDir)) {
    mkdir($archiveDir, 0755, true);
}

// Archive function - moves files to _archive folder
function archiveFile($source, $archiveDir) {
    if (!file_exists($source)) {
        return true; // Already archived or doesn't exist
    }
    
    $filename = basename($source);
    $destination = $archiveDir . '/' . $filename;
    
    return @rename($source, $destination);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_uninstall'])) {
    header('Content-Type: application/json');
    try {
        // Security check
        if (!isset($_POST['confirm_uninstall']) || $_POST['confirm_uninstall'] !== 'UNINSTALL') {
            throw new Exception('Confirmation code is incorrect');
        }

        // Remove the config reset operation
        // REMOVE OR COMMENT OUT these lines:
        /*
        $default_config = [
            'installation' => [
                'installed' => false,
                'date' => null,
                'version' => null,
                'last_update' => null
            ]
        ];
        
        $config_content = "<?php\nreturn " . var_export($default_config, true) . ";\n";
        if (!file_put_contents($config_file, $config_content)) {
            throw new Exception('Failed to reset configuration');
        }
        */

        // Archive files instead of deleting
        $selfPath = __FILE__;
        $otherFiles = array_filter($itemsToArchive, function($path) use ($selfPath) {
            return $path !== $selfPath;
        });

        // Archive other files first
        foreach ($otherFiles as $path) {
            if (file_exists($path)) {
                if (!archiveFile($path, $archiveDir)) {
                    throw new Exception("Could not archive file: $path");
                }
            }
        }

        $result = [
            'success' => true,
            'message' => 'Installation files successfully archived to _archive folder',
            'archived' => true
        ];

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode($result);
        } else {
            $_SESSION['uninstall_message'] = $result;
            header('Location: index.php');
        }
        
        // Archive uninstall.php itself (move, not delete)
        archiveFile($selfPath, $archiveDir);
        
        exit;

    } catch (Exception $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } else {
            $_SESSION['uninstall_error'] = $e->getMessage();
            header('Location: ' . $_SERVER['PHP_SELF']);
        }
        exit;
    }
} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Delete Installation Files</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
        <style>
            :root {
                --gradient-start: #ff416c;
                --gradient-end: #ff4b2b;
            }
            
            body {
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                min-height: 100vh;
            }

            .container {
                max-width: 800px;
            }

            .card {
                border: none;
                border-radius: 15px;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                overflow: hidden;
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.95);
            }

            .card-header {
                background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
                padding: 1.5rem;
                border-bottom: none;
            }

            .file-list {
                max-height: 300px;
                overflow-y: auto;
                background: #f8f9fa;
                border-radius: 10px;
                padding: 0.5rem;
                scrollbar-width: thin;
            }

            .file-list::-webkit-scrollbar {
                width: 6px;
            }

            .file-list::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 3px;
            }

            .file-list::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 3px;
            }

            .file-item {
                padding: 0.75rem 1rem;
                border-bottom: 1px solid rgba(0,0,0,0.05);
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
            }

            .file-item:hover {
                background: rgba(0,0,0,0.02);
                transform: translateX(5px);
            }

            .file-item i {
                font-size: 1.2rem;
                margin-right: 1rem;
            }

            .file-item i.fa-folder {
                color: #ffd43b;
            }

            .file-item i.fa-file {
                color: #74c0fc;
            }

            .btn {
                padding: 0.75rem 1.5rem;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                transition: all 0.3s ease;
            }

            .btn-danger {
                background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
                border: none;
                box-shadow: 0 4px 15px rgba(255, 65, 108, 0.2);
            }

            .btn-danger:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(255, 65, 108, 0.3);
            }

            .btn-secondary {
                background: #6c757d;
                border: none;
                box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
            }

            .btn-secondary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
            }

            .alert {
                border: none;
                border-radius: 10px;
                padding: 1rem 1.5rem;
            }

            .alert-warning {
                background: linear-gradient(45deg, #ffd43b, #fab005);
                color: #fff;
                box-shadow: 0 4px 15px rgba(255, 212, 59, 0.2);
            }

            .form-control {
                border-radius: 8px;
                padding: 0.75rem 1rem;
                border: 2px solid #e9ecef;
                transition: all 0.3s ease;
            }

            .form-control:focus {
                border-color: var(--gradient-start);
                box-shadow: 0 0 0 0.2rem rgba(255, 65, 108, 0.1);
            }

            .toast {
                background: white;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }

            .toast-header {
                background: transparent;
                border-bottom: 1px solid rgba(0,0,0,0.05);
            }

            .progress {
                height: 6px;
                border-radius: 3px;
                overflow: hidden;
            }

            .confirmation-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.8);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 1050;
            }

            .confirmation-dialog {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                text-align: center;
                transform: scale(0.9);
                transition: transform 0.3s ease;
            }

            .confirmation-dialog.show {
                transform: scale(1);
            }
        </style>
    </head>
    <body>
        <div class="container py-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 text-white d-flex align-items-center">
                        <i class="fas fa-archive me-3"></i>
                        Archive Installation Files
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['uninstall_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['uninstall_message']['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['uninstall_message']); ?>
                    <?php endif; ?>

                    <div class="alert alert-warning mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="alert-heading mb-1">Info</h5>
                                <p class="mb-0">This operation will ARCHIVE the following files to _archive folder:</p>
                            </div>
                        </div>
                    </div>

                    <div class="file-list mb-4">
                        <?php foreach ($itemsToArchive as $path): ?>
                            <div class="file-item">
                                <i class="fas <?php echo is_dir($path) ? 'fa-folder' : 'fa-file'; ?>"></i>
                                <span><?php echo basename($path); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form id="uninstallForm" method="post">
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-keyboard me-2"></i>
                                Type "UNINSTALL" to confirm:
                            </label>
                            <input type="text" name="confirm_uninstall" class="form-control" 
                                   required pattern="UNINSTALL" 
                                   title="Must type 'UNINSTALL' exactly">
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-archive me-2"></i>
                                Archive Files
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Go Back
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirmation Dialog -->
        <div class="confirmation-overlay" id="confirmationOverlay">
            <div class="confirmation-dialog p-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5>Confirmation</h5>
                <p>Are you sure you want to delete all installation files?</p>
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-danger" id="confirmYes">
                        <i class="fas fa-check me-2"></i>Yes
                    </button>
                    <button class="btn btn-secondary" id="confirmNo">
                        <i class="fas fa-times me-2"></i>No
                    </button>
                </div>
            </div>
        </div>

        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.getElementById('uninstallForm').onsubmit = async function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete all installation files?')) {
                return;
            }

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                
                // Create new element for toast notification
                const toastContainer = document.createElement('div');
                toastContainer.className = 'position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '1050';
                
                toastContainer.innerHTML = `
                    <div class="toast align-items-center ${result.success ? 'bg-success' : 'bg-danger'} text-white" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                ${result.success ? result.message : result.error}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(toastContainer);
                
                const toastElement = toastContainer.querySelector('.toast');
                const toast = new bootstrap.Toast(toastElement, {
                    animation: true,
                    autohide: true,
                    delay: 3000
                });
                
                toast.show();
                
                if (result.success) {
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                }
            } catch (error) {
                alert('Error occurred: ' + error.message);
            }
        };
        </script>
    </body>
    </html>
    <?php
}
?>
