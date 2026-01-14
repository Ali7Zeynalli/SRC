<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */



// Sessiyanı başlat
session_start([
    'cookie_httponly' => true,     // JavaScript-dən qorunma
    'cookie_secure' => true,       // Yalnız HTTPS üçün
    'cookie_samesite' => 'Lax',    // CSRF hücumlarından qorunma
    'use_strict_mode' => true,     // Session Fixation qarşısını almaq
    'use_only_cookies' => true,    // URL-based session ID qarşısını alır
    'gc_maxlifetime' => 3600       // 1 saat sonra sessiya bitmə
]);

if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    // HTTPS-ə məcburi yönləndirmə
    $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect_url);
    exit;
}

// HTTP Strict Transport Security (HSTS) - sniffing qarşısını almaq üçün
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// System checker removed for Open Source version

// Check if install.php or installer.php exists - redirect to uninstall to archive them
if (file_exists(__DIR__ . '/install.php') || file_exists(__DIR__ . '/installer.php')) {
    if (basename($_SERVER['PHP_SELF']) !== 'uninstall.php') {
        header('Location: uninstall.php');
        exit();
    }
}

// Load config
$config = require(__DIR__ . '/config/config.php');

/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [ali] <[ali.z.zeynalli@gmail.com]> [2025]
  */
  

require_once('includes/functions.php');
require_once('includes/functions/SecureStore.php');

// Əgər artıq login olubsa, dashboarda yönləndir
if (isset($_SESSION['ad_username'])) {
    header('Location: dashboard.php');
    exit;
}

if (isset($_POST['login'])) {
    try {
        // Təmizlənmiş username
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $username = trim(explode('@', $username)[0]); // Domain hissəsini silirik
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            throw new Exception("Username and password are required");
        }
        
        // LDAP qoşulması
        $ldap_conn = connectToAD($username, $password);
        
        // İstifadəçi səlahiyyətlərini yoxlayırıq
        $access = checkUserAccess($ldap_conn, $username);
        
        if (!$access['allowed']) {
            throw new Exception("Access denied. You are not a member of allowed groups: " . 
                implode(', ', $access['required_groups'] ?? []));
        }
        
        // Təhlükəsiz token yaradırıq
        $secure_token = bin2hex(random_bytes(32));
        $token_expiry = 3600; // 1 saat
        
        // Şifrəni təhlükəsiz saxlayırıq
        $secureStore = new SecureStore();
        $store_key = 'ad_credential_' . $secure_token;
        
        // Metadata hazırlayırıq
        $metadata = [
            'username' => $username,
            'session_id' => session_id(),
            'type' => 'auth_credential'
        ];
        
        // Şifrəni şifrələnmiş formada saxlayırıq
        if (!$secureStore->store($store_key, $password, $token_expiry, $metadata)) {
            throw new Exception("Could not securely store credentials");
        }
        
        // Session məlumatlarını saxlayırıq
        $_SESSION['ad_username'] = $username;
        $_SESSION['auth_token'] = $secure_token;
        $_SESSION['auth_token_expiry'] = time() + $token_expiry;
        $_SESSION['user_access'] = $access;
        $_SESSION['last_activity'] = time();
        $_SESSION['logged_in'] = true;
        
        // Session-u yeniləyirik (Session fixation qarşısını almaq üçün)
        session_regenerate_id(true);
        
        // JavaScript ilə bildiriş göstərmək üçün flag təyin edirik
        $login_success = true;
        
        // PHP redirection-u silib, JavaScript ilə redirection edəcəyik
        // header('Location: dashboard.php');
        // exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    <link href="temp/assets/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="temp/assets/lib/font-awesome/all.min.css" rel="stylesheet">
    <link href="temp/assets/lib/SweetAlert2/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="temp/css/index.css">
    
    <!-- JavaScript faylları -->
    <script src="temp/assets/lib/jquery/jquery.min.js"></script>
    <script src="temp/assets/lib/popper/popper.min.js"></script>
    <script src="temp/assets/lib/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="temp/js/fun/language.js"></script>
</head>
<body class="login-body">
    <div class="fullscreen-bg">
        <!-- Arxa plan şəkli CSS ilə təyin ediləcək -->
    </div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                <img src="temp/assets/images/logo.png" alt="<?php echo __('app_name'); ?> Logo" class="me-2" style="width: 190px; height: 100px;">
             
                </div>
                <h6 class="mb-1"><?php echo __('app_name'); ?></h6>
            </div>
            
            <div class="login-form">
         
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger error-alert mb-4">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="form-floating mb-3">
                        <input type="text" name="username" class="form-control" 
                               id="username" placeholder="<?php echo __('placeholder_username'); ?>" required
                               autocomplete="off">
                        <label for="username">
                            <i class="fas fa-user me-2"></i><?php echo __('label_username'); ?>
                        </label>
                        <div class="invalid-feedback">
                            <i class="fas fa-info-circle me-1"></i><?php echo __('error_username_required'); ?>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-4">
                        <input type="password" name="password" class="form-control" 
                               id="password" placeholder="<?php echo __('placeholder_password'); ?>" required>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i><?php echo __('label_password'); ?>
                        </label>
                        <div class="invalid-feedback">
                            <i class="fas fa-info-circle me-1"></i><?php echo __('error_password_required'); ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt me-2"></i><?php echo __('button_login'); ?>
                    </button>
                </form>

                <?php if (isset($access) && !$access['allowed']): ?>
                <div class="required-groups">
                    <h6>
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo __('label_required_groups'); ?>:
                    </h6>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($access['required_groups'] ?? [] as $group): ?>
                            <li>
                                <i class="fas fa-users me-2 text-muted"></i>
                                <?php echo htmlspecialchars($group); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
       
    </div>

    <script src="temp/assets/lib/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="temp/assets/lib/SweetAlert2/sweetalert2.min.js"></script>
    
    <script>
    /**
 * Server Reporting and Controlling System
 * Login səhifəsi üçün JavaScript funksiyaları
 */

document.addEventListener('DOMContentLoaded', function() {
    // Form validasiyası
    const loginForm = document.querySelector('form.needs-validation');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    }

    // Arxa plan animasiyası və effektlərini işə salır
    initBackgroundEffects();
    
    // Qaranlıq/işıqlı mövzu dəyişdirmə
    initThemeToggle();
    
    // Login animasiyaları
    animateLoginElements();
});

/**
 * Arxa plan effektlərini işə salır
 */
function initBackgroundEffects() {
    const background = document.querySelector('.fullscreen-bg');
    if (!background) return;
    
    // Data center texnoloji effektləri əlavə edirik
    createTechEffects();
    
    // Server işıqlarını əlavə edirik
    createServerLights();
    
    // Texnoloji qrid əlavə edirik
    createTechGrid();
    
    // Sayrışan rəqəmli elementlər əlavə edirik
    createDigitalElements();
}

/**
 * Data center üçün texnoloji effektlər yaradır
 */
function createTechEffects() {
    const background = document.querySelector('.fullscreen-bg');
    if (!background) return;
    
    const techEffectsContainer = document.createElement('div');
    techEffectsContainer.className = 'tech-effects-container';
    
    for (let i = 0; i < 30; i++) {
        const techEffect = document.createElement('div');
        techEffect.className = 'tech-effect';
        techEffectsContainer.appendChild(techEffect);
    }
    
    background.appendChild(techEffectsContainer);
}

/**
 * Server kabinelərində sayrışan işıqlar yaradır
 */
function createServerLights() {
    const background = document.querySelector('.fullscreen-bg');
    if (!background) return;
    
    const serverLightsContainer = document.createElement('div');
    serverLightsContainer.className = 'server-lights-container';
    
    // Server kabinelərini qeyd etdiyi yerlərdə işıqlar
    const serverPositions = [
        // Sol tərəfdəki serverlər
        {top: 30, left: 20}, {top: 32, left: 20}, {top: 34, left: 20}, {top: 36, left: 20},
        {top: 38, left: 20}, {top: 40, left: 20}, {top: 42, left: 20}, {top: 44, left: 20},
        {top: 46, left: 20}, {top: 48, left: 20}, {top: 50, left: 20}, {top: 52, left: 20},
        
        // Sağ tərəfdəki serverlər
        {top: 30, left: 80}, {top: 32, left: 80}, {top: 34, left: 80}, {top: 36, left: 80},
        {top: 38, left: 80}, {top: 40, left: 80}, {top: 42, left: 80}, {top: 44, left: 80},
        {top: 46, left: 80}, {top: 48, left: 80}, {top: 50, left: 80}, {top: 52, left: 80},
        
        // Orta serverlər
        {top: 35, left: 50}, {top: 37, left: 50}, {top: 39, left: 50}, {top: 41, left: 50},
        {top: 43, left: 50}, {top: 45, left: 50}, {top: 47, left: 50}, {top: 49, left: 50}
    ];
    
    serverPositions.forEach(pos => {
        const light = document.createElement('div');
        light.className = 'server-light';
        light.style.top = `${pos.top}%`;
        light.style.left = `${pos.left}%`;
        serverLightsContainer.appendChild(light);
    });
    
    background.appendChild(serverLightsContainer);
}

/**
 * Texnoloji qrid/tor effekti yaradır
 */
function createTechGrid() {
    const background = document.querySelector('.fullscreen-bg');
    if (!background) return;
    
    const gridContainer = document.createElement('div');
    gridContainer.className = 'grid-container';
    
    // Üfüqi xətlər
    for (let i = 0; i < 8; i++) {
        const line = document.createElement('div');
        line.className = 'grid-line grid-line-h';
        line.style.top = `${20 + (i * 10)}%`;
        gridContainer.appendChild(line);
    }
    
    // Şaquli xətlər
    for (let i = 0; i < 10; i++) {
        const line = document.createElement('div');
        line.className = 'grid-line grid-line-v';
        line.style.left = `${10 + (i * 10)}%`;
        gridContainer.appendChild(line);
    }
    
    background.appendChild(gridContainer);
}

/**
 * Sayrışan rəqəmli elementlər yaratmaq
 */
function createDigitalElements() {
    const background = document.querySelector('.fullscreen-bg');
    if (!background) return;
    
    const digitalContainer = document.createElement('div');
    digitalContainer.className = 'digital-container';
    
    for (let i = 0; i < 20; i++) {
        const digitalElement = document.createElement('div');
        digitalElement.className = 'digital-element';
        const binaryString = Array.from({length: 10}, () => Math.random() > 0.5 ? '1' : '0').join('');
        digitalElement.textContent = binaryString;
        digitalElement.style.top = `${Math.random() * 100}%`;
        digitalElement.style.left = `${Math.random() * 100}%`;
        digitalContainer.appendChild(digitalElement);
    }
    
    background.appendChild(digitalContainer);
}

/**
 * Qaranlıq/işıqlı mövzu dəyişdirmə
 */
function initThemeToggle() {
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
    
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        });
    }
}

/**
 * Login elementlərini animasiya edir
 */
function animateLoginElements() {
    const loginCard = document.querySelector('.login-card');
    if (loginCard) {
        loginCard.classList.add('visible');
    }
}

// Form validation and submission
document.querySelector('form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        animateErrorFields();
        return;
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
    
    try {
        const response = await fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(new FormData(this))
        });

        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '<?php echo __('success_title'); ?>',
                text: '<?php echo __('success_login_message'); ?>',
                timer: 1500,
                showConfirmButton: false,
                willClose: () => {
                    window.location.href = result.redirect;
                }
            });
        } else {
            throw new Error(result.error || '<?php echo __('error_login_check_credentials'); ?>');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: '<?php echo __('error_login_title'); ?>',
            text: error.message,
            confirmButtonColor: '#0d6efd'
        });
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i><?php echo __('button_login'); ?>';
    }
});

// Helper function to animate error fields
function animateErrorFields() {
    const invalidFields = document.querySelectorAll('.form-control:invalid');
    invalidFields.forEach(field => {
        field.classList.add('shake-error');
        setTimeout(() => field.classList.remove('shake-error'), 500);
    });
}
</script>
    <?php include('includes/footer.php'); ?>

</body>
</html>
