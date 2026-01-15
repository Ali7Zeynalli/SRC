<?php
// Schema Update Utility
$config = require_once __DIR__ . '/../config/config.php';

// Simple Database PDO wrapper since we might not have the full App environment loaded if running standalone
class SchemaUpdater {
    private $pdo;
    
    public function __construct($config) {
        $host = getenv('MYSQL_HOST') ?: ($config['db_settings']['host'] ?? 'mysql');
        $dbname = getenv('MYSQL_DATABASE') ?: ($config['db_settings']['database'] ?? 'ldap_auth');
        $user = getenv('MYSQL_USER') ?: ($config['db_settings']['username'] ?? 'srcs_admin');
        $pass = getenv('MYSQL_PASSWORD') ?: ($config['db_settings']['password'] ?? 'SrcS@2026!Secure');
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }
    
    public function runMigration($sqlFile) {
        if (!file_exists($sqlFile)) {
            die("Error: SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        try {
            echo "Starting migration...\n";
            $this->pdo->exec($sql);
            echo "Migration completed successfully!\n";
            echo "Tables created: task_categories, tasks, task_history\n";
        } catch (PDOException $e) {
            die("Migration Failed: " . $e->getMessage());
        }
    }
}

$start = microtime(true);
$updater = new SchemaUpdater($config);
$updater->runMigration(__DIR__ . '/task_management_schema.sql');
$end = microtime(true);

echo "Execution time: " . round($end - $start, 2) . " seconds.\n";
?>
