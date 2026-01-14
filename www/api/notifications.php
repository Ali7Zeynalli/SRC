<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
// API konfiqurasiyası
$API_URL = 'https://web-api.linkedin.com/in/ali7zeynalli/api/notifications/index.php';
$API_KEY = 'adasdfsrtdf4545fgfd352fgfd342445trgdf4w';

// Bildirişləri əldə et
function getNotifications() {
    global $API_URL, $API_KEY;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . $API_KEY,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("CURL Error: " . curl_error($ch));
        return null;
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

// Bildiriş kartını formatla
function formatNotification($notification) {
    global $categories, $priorities;
    
    $category = $notification['category'];
    $categoryInfo = $categories[$category] ?? ['label' => $category, 'icon' => 'bell', 'color' => 'primary'];
    
    // Custom icon və rəng yoxlanışı
    $icon = $notification['icon'] ?? $categoryInfo['icon'];
    $color = $notification['color'] ?? $categoryInfo['color'];
    $priority = (int)($notification['priority'] ?? 0);
    $priorityInfo = $priorities[$priority] ?? $priorities[0];
    
    return sprintf('
        <div class="notification-card %s" onclick="showNotificationDetails(\'%s\')">
            <div class="notification-priority-indicator priority-%s"></div>
            <div class="card-body">
                <div class="notification-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="notification-icon bg-%s bg-opacity-10">
                            <i class="fas fa-%s text-%s"></i>
                        </div>
                        <div>
                            <h5 class="notification-title">%s</h5>
                            <div class="notification-meta">
                                <span class="text-muted">
                                    <i class="far fa-clock me-1"></i>%s
                                </span>
                                <span class="category-badge" style="%s">
                                    %s
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="notification-badges">
                        %s
                    </div>
                </div>
                
                <div class="notification-content">
                    <p class="notification-message">%s</p>
                    %s
                </div>
            </div>
        </div>
    ',
        $priority >= 3 ? 'priority-high' : '',
        $notification['id'] ?? uniqid(),
        $priorityInfo['class'],
        $color,
        $icon,
        $color,
        htmlspecialchars($notification['title']),
        date('d.m.Y H:i', strtotime($notification['created_at'])),
        // Custom rəng varsa category badge-i üçün style
        $notification['color'] ? sprintf('background-color: var(--bs-%s); color: white;', $color) : '',
        // Kateqoriya adı (icon olmadan)
        $categoryInfo['label'],
        // Priority badge
        $priority > 0 ? sprintf('
            <span class="badge bg-%s %s" data-bs-toggle="tooltip" title="%s Priority">
                <i class="fas fa-%s me-1"></i>%s
            </span>',
            $priorityInfo['class'],
            $priority >= 3 ? 'flash-badge' : '',
            $priorityInfo['label'],
            $priorityInfo['icon'],
            $priorityInfo['label']
        ) : '',
        nl2br(htmlspecialchars($notification['message'])),
        // Link if exists
        $notification['link'] ? sprintf('
            <div class="notification-actions">
                <a href="%s" class="btn btn-sm btn-%s" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>View Details
                    <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>',
            htmlspecialchars($notification['link']),
            $color
        ) : ''
    );
}

// Categories
$categories = [
    'system_updates' => ['label' => __('notification_category_system_updates'), 'icon' => 'sync', 'color' => 'primary'],
    'security_alerts' => ['label' => __('notification_category_security_alerts'), 'icon' => 'shield-alt', 'color' => 'danger'],
    'feature_updates' => ['label' => __('notification_category_feature_updates'), 'icon' => 'star', 'color' => 'success'],
    'maintenance' => ['label' => __('notification_category_maintenance'), 'icon' => 'tools', 'color' => 'warning'],
    'news' => ['label' => __('notification_category_news'), 'icon' => 'newspaper', 'color' => 'info']
];

// Prioritetlər
$priorities = [
    0 => ['label' => __('notification_priority_normal'), 'class' => 'secondary', 'icon' => 'circle'],
    1 => ['label' => __('notification_priority_high'), 'class' => 'info', 'icon' => 'arrow-up'],
    2 => ['label' => __('notification_priority_urgent'), 'class' => 'warning', 'icon' => 'exclamation'],
    3 => ['label' => __('notification_priority_critical'), 'class' => 'danger', 'icon' => 'radiation'],
    4 => ['label' => __('notification_priority_emergency'), 'class' => 'danger', 'icon' => 'bolt']
];
