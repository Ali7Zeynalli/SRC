<?php

 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
class Database {
    private static $instance = null;
    private $pdo;
    private $connected = false;

    private function __construct() {
        try {
            $config = require(__DIR__ . '/../config/config.php');
            
            // Check if database settings exist
            if (!isset($config['db_settings'])) {
                throw new Exception("Database configuration is missing");
            }

            $dsn = "mysql:host={$config['db_settings']['host']};charset={$config['db_settings']['charset']}";
            
            // First connect without database
            $this->pdo = new PDO(
                $dsn,
                $config['db_settings']['username'],
                $config['db_settings']['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Create database if not exists
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_settings']['database']}`");
            
            // Select database
            $this->pdo->exec("USE `{$config['db_settings']['database']}`");
            
            // Create activity_logs table if not exists
            $this->createActivityLogsTable();
            
            $this->connected = true;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    private function createActivityLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            target_user_id VARCHAR(255) NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            INDEX idx_timestamp (timestamp),
            INDEX idx_user_id (user_id),
            INDEX idx_action (action)
        )";
        
        $this->pdo->exec($sql);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }

    public function getVersion() {
        try {
            $stmt = $this->pdo->query('SELECT VERSION()');
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 'Unknown';
        }
    }

    public function isConnected() {
        return $this->connected;
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    public function getActionLogs($filters = []) {
        $query = "SELECT * FROM action_logs WHERE 1=1";
        return $this->applyFiltersAndExecute($query, $filters);
    }

    public function getErrorLogs($filters = []) {
        $query = "SELECT * FROM error_logs WHERE 1=1";
        return $this->applyFiltersAndExecute($query, $filters);
    }

    public function getLoginLogs($filters = []) {
        $query = "SELECT * FROM login_logs WHERE 1=1";
        return $this->applyFiltersAndExecute($query, $filters);
    }

    public function getPasswordResetLogs($filters = []) {
        $query = "SELECT * FROM password_reset_logs WHERE 1=1";
        return $this->applyFiltersAndExecute($query, $filters);
    }

    private function applyFiltersAndExecute($query, $filters) {
        $params = [];
        
        if (!empty($filters['username'])) {
            $query .= " AND username LIKE ?";
            $params[] = "%" . $filters['username'] . "%";
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $query .= " ORDER BY created_at DESC LIMIT 1000";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActivityLogs($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['start_date'])) {
            $where[] = 'timestamp >= ?';
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = 'timestamp <= ?';
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        
        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id LIKE ?';
            $params[] = '%' . $filters['user_id'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM activity_logs WHERE $whereClause";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get logs
        $sql = "SELECT * FROM activity_logs 
                WHERE $whereClause 
                ORDER BY timestamp DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        return [
            'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    public function getActivityStats($filters = []) {
        try {
            $stats = [
                'total' => 0,
                'today' => 0,
                'weekly' => 0,
                'monthly' => 0
            ];
            
            // Total count
            $sql = "SELECT COUNT(*) as count FROM activity_logs";
            $stmt = $this->pdo->query($sql);
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Today's count
            $sql = "SELECT COUNT(*) as count FROM activity_logs 
                    WHERE DATE(timestamp) = CURDATE()";
            $stmt = $this->pdo->query($sql);
            $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // This week's count
            $sql = "SELECT COUNT(*) as count FROM activity_logs 
                    WHERE YEARWEEK(timestamp, 1) = YEARWEEK(CURDATE(), 1)";
            $stmt = $this->pdo->query($sql);
            $stats['weekly'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // This month's count
            $sql = "SELECT COUNT(*) as count FROM activity_logs 
                    WHERE YEAR(timestamp) = YEAR(CURDATE()) 
                    AND MONTH(timestamp) = MONTH(CURDATE())";
            $stmt = $this->pdo->query($sql);
            $stats['monthly'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error getting activity stats: " . $e->getMessage());
            return [
                'total' => 0,
                'today' => 0,
                'weekly' => 0,
                'monthly' => 0
            ];
        }
    }

    public function backupAndDeleteLogs() {
        try {
            // Backup folder yarat
            $backupDir = __DIR__ . '/../reports';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Backup faylı adı
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $backupDir . "/activity_logs_backup_{$timestamp}.csv";

            // CSV header
            $headers = [
                'id',
                'user_id',
                'action',
                'target_user_id',
                'timestamp',
                'details',
                'ip_address',
                'user_agent'
            ];

            // Backup CSV faylı yarat
            $file = fopen($backupFile, 'w');
            fputcsv($file, $headers);

            // Bütün logları seç
            $sql = "SELECT * FROM activity_logs ORDER BY timestamp DESC";
            $stmt = $this->pdo->query($sql);

            // Logları CSV-ə yaz
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($file, $row);
            }
            fclose($file);

            // Logları sil
            $sql = "TRUNCATE TABLE activity_logs";
            $this->pdo->exec($sql);

            return [
                'success' => true,
                'backup_file' => basename($backupFile),
                'message' => 'Logs have been backed up and deleted successfully'
            ];

        } catch (PDOException $e) {
            error_log("Error backing up and deleting logs: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to backup and delete logs'
            ];
        }
    }

    public function exportLogs() {
        try {
            // Export folder yarat
            $exportDir = __DIR__ . '/../reports';
            if (!file_exists($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            // Export faylı adı
            $timestamp = date('Y-m-d_H-i-s');
            $exportFile = $exportDir . "/activity_export_{$timestamp}.csv";

            // CSV header
            $headers = [
                'id',
                'user_id',
                'action',
                'target_user_id',
                'timestamp',
                'details',
                'ip_address',
                'user_agent'
            ];

            // Export CSV faylı yarat
            $file = fopen($exportFile, 'w');
            fputcsv($file, $headers);

            // Bütün logları seç
            $sql = "SELECT * FROM activity_logs ORDER BY timestamp DESC";
            $stmt = $this->pdo->query($sql);

            // Logları CSV-ə yaz
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($file, $row);
            }
            fclose($file);

            return [
                'success' => true,
                'export_file' => basename($exportFile),
                'message' => 'Logs have been exported successfully'
            ];

        } catch (PDOException $e) {
            error_log("Error exporting logs: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to export logs'
            ];
        }
    }

    public function getRecentActivities($limit = 5) {
        try {
            $sql = "SELECT * FROM activity_logs 
                    ORDER BY timestamp DESC 
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            
            return [
                'success' => true,
                'activities' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            error_log("Error getting recent activities: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to get recent activities',
                'activities' => []
            ];
        }
    }
}

