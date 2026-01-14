<?php
require_once dirname(__DIR__) . '/functions.php';
 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */

function setupDatabase($config) {
    try {
        $db = new PDO(
            "mysql:host={$config['db_settings']['host']};charset={$config['db_settings']['charset']}",
            $config['db_settings']['username'],
            $config['db_settings']['password']
        );
        
        // Create database if not exists
        $db->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_settings']['database']}`");
        $db->exec("USE `{$config['db_settings']['database']}`");
        
        // Import schema
        $schema = file_get_contents(__DIR__ . '/../install/schema.sql');
        $db->exec($schema);
        
        return true;
    } catch (PDOException $e) {
        error_log("Database setup error: " . $e->getMessage());
        return false;
    }
}
