<?php
/*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */

// GitHub Raw URL for notifications
$GITHUB_URL = 'https://raw.githubusercontent.com/Ali7Zeynalli/SRC/main/notifications.json';
$CACHE_FILE = __DIR__ . '/../config/notifications_cache.json';
$CACHE_TTL = 3600; // 1 hour cache

// Get notifications from GitHub or cache
function getNotifications() {
    global $GITHUB_URL, $CACHE_FILE, $CACHE_TTL;
    
    // Check if cache exists and is valid
    if (file_exists($CACHE_FILE)) {
        $cacheData = json_decode(file_get_contents($CACHE_FILE), true);
        if ($cacheData && isset($cacheData['timestamp'])) {
            $cacheAge = time() - $cacheData['timestamp'];
            if ($cacheAge < $CACHE_TTL) {
                // Return cached data
                return $cacheData['data'];
            }
        }
    }
    
    // Fetch from GitHub
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $GITHUB_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'S-RCS/1.3.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("CURL Error: " . curl_error($ch));
        curl_close($ch);
        
        // Try to return stale cache if available
        if (file_exists($CACHE_FILE)) {
            $cacheData = json_decode(file_get_contents($CACHE_FILE), true);
            if ($cacheData && isset($cacheData['data'])) {
                return $cacheData['data'];
            }
        }
        return null;
    }
    
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        // Save to cache
        $cacheData = [
            'timestamp' => time(),
            'data' => $data
        ];
        file_put_contents($CACHE_FILE, json_encode($cacheData, JSON_PRETTY_PRINT));
        
        return $data;
    }
    
    // Return stale cache on error
    if (file_exists($CACHE_FILE)) {
        $cacheData = json_decode(file_get_contents($CACHE_FILE), true);
        if ($cacheData && isset($cacheData['data'])) {
            return $cacheData['data'];
        }
    }
    
    return null;
}

// Format notification card
function formatNotification($notification) {
    global $categories, $priorities;
    
    $category = $notification['category'] ?? 'news';
    $categoryInfo = $categories[$category] ?? ['label' => $category, 'icon' => 'bell', 'color' => 'primary'];
    
    // Custom icon and color check
    $icon = $notification['icon'] ?? $categoryInfo['icon'];
    $color = $notification['color'] ?? $categoryInfo['color'];
    $priority = (int)($notification['priority'] ?? 0);
    $priorityInfo = $priorities[$priority] ?? $priorities[0];
    
    return sprintf('
        <div class="notification-card %s">
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
        $priorityInfo['class'],
        $color,
        $icon,
        $color,
        htmlspecialchars($notification['title'] ?? ''),
        isset($notification['created_at']) ? date('d.m.Y H:i', strtotime($notification['created_at'])) : date('d.m.Y'),
        // Custom color for category badge
        isset($notification['color']) ? sprintf('background-color: var(--bs-%s); color: white;', $color) : '',
        // Category name
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
        nl2br(htmlspecialchars($notification['message'] ?? '')),
        // Link if exists
        isset($notification['link']) && $notification['link'] ? sprintf('
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

// Priorities
$priorities = [
    0 => ['label' => __('notification_priority_normal'), 'class' => 'secondary', 'icon' => 'circle'],
    1 => ['label' => __('notification_priority_high'), 'class' => 'info', 'icon' => 'arrow-up'],
    2 => ['label' => __('notification_priority_urgent'), 'class' => 'warning', 'icon' => 'exclamation'],
    3 => ['label' => __('notification_priority_critical'), 'class' => 'danger', 'icon' => 'radiation'],
    4 => ['label' => __('notification_priority_emergency'), 'class' => 'danger', 'icon' => 'bolt']
];
