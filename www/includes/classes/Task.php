<?php
// includes/classes/Task.php

class Task {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Create new task
    public function create($subject, $description, $priority, $categoryId, $requesterDn, $requesterName, $createdBy, $affectedUserDn = null, $affectedUserName = null) {
        $sql = "INSERT INTO tasks 
                (subject, description, priority, category_id, requester_dn, requester_name, created_by, affected_user_dn, affected_user_name, status) 
                VALUES (:subject, :description, :priority, :category_id, :requester_dn, :requester_name, :created_by, :affected_user_dn, :affected_user_name, 'New')";
                
        $params = [
            ':subject' => $subject,
            ':description' => $description,
            ':priority' => $priority,
            ':category_id' => $categoryId,
            ':requester_dn' => $requesterDn,
            ':requester_name' => $requesterName,
            ':created_by' => $createdBy,
            ':affected_user_dn' => $affectedUserDn,
            ':affected_user_name' => $affectedUserName
        ];
        
        $this->db->query($sql, $params);
        $taskId = $this->db->lastInsertId();
        
        // Log creation
        $this->addHistory($taskId, $createdBy, 'System', "Task created by $createdBy");
        
        return $taskId;
    }
    
    // Assign task
    public function assign($taskId, $adminUsername, $actor) {
        $sql = "UPDATE tasks SET assigned_to = :admin, status = 'Assigned' WHERE id = :id";
        $this->db->query($sql, [':admin' => $adminUsername, ':id' => $taskId]);
        
        $this->addHistory($taskId, $actor, 'Assignment', "Assigned to $adminUsername");
        return true;
    }
    
    // Update status
    public function updateStatus($taskId, $newStatus, $actor) {
        $sql = "UPDATE tasks SET status = :status WHERE id = :id";
        $this->db->query($sql, [':status' => $newStatus, ':id' => $taskId]);
        
        $this->addHistory($taskId, $actor, 'StatusChange', "Status changed to $newStatus");
        return true;
    }
    
    // Add comment
    public function addComment($taskId, $username, $message, $isInternal = 0) {
        $this->addHistory($taskId, $username, 'Comment', $message, $isInternal);
        return true;
    }
    
    // Get Task Details
    public function get($taskId) {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM tasks t 
                LEFT JOIN task_categories c ON t.category_id = c.id 
                WHERE t.id = :id";
        return $this->db->query($sql, [':id' => $taskId])->fetch();
    }
    
    // Get History/Comments
    public function getHistory($taskId, $includeInternal = false) {
        $sql = "SELECT * FROM task_history WHERE task_id = :id ";
        if (!$includeInternal) {
            $sql .= " AND is_internal = 0 ";
        }
        $sql .= " ORDER BY created_at ASC";
        
        return $this->db->query($sql, [':id' => $taskId])->fetchAll();
    }
    
    // Internal helper for history
    private function addHistory($taskId, $actor, $type, $message, $isInternal = 0) {
        $sql = "INSERT INTO task_history (task_id, actor_username, action_type, message, is_internal) 
                VALUES (:task_id, :actor, :type, :message, :is_internal)";
        $this->db->query($sql, [
            ':task_id' => $taskId,
            ':actor' => $actor,
            ':type' => $type,
            ':message' => $message,
            ':is_internal' => $isInternal
        ]);
    }
    
    // Get Statistics for Dashboard
    public function getStats() {
        return [
            'total' => $this->db->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
            'open' => $this->db->query("SELECT COUNT(*) FROM tasks WHERE status != 'Closed'")->fetchColumn(),
            'unassigned' => $this->db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to IS NULL AND status != 'Closed'")->fetchColumn()
        ];
    }
    
    // Get All Tasks (with filters)
    public function getAll($filters = []) {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM tasks t 
                LEFT JOIN task_categories c ON t.category_id = c.id 
                WHERE 1=1 ";
                
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    // Get All Categories
    public function getCategories() {
        return $this->db->query("SELECT * FROM task_categories ORDER BY name ASC")->fetchAll();
    }

    // Add Category
    public function addCategory($name, $color) {
        $sql = "INSERT INTO task_categories (name, color) VALUES (:name, :color)";
        return $this->db->query($sql, [':name' => $name, ':color' => $color]);
    }

    // Update Category
    public function updateCategory($id, $name, $color) {
        $sql = "UPDATE task_categories SET name = :name, color = :color WHERE id = :id";
        return $this->db->query($sql, [':name' => $name, ':color' => $color, ':id' => $id]);
    }

    // Delete Category
    public function deleteCategory($id) {
        // Check if used
        $count = $this->db->query("SELECT COUNT(*) FROM tasks WHERE category_id = :id", [':id' => $id])->fetchColumn();
        if ($count > 0) {
            throw new Exception("Cannot delete category in use by tickets.");
        }
        $sql = "DELETE FROM task_categories WHERE id = :id";
        return $this->db->query($sql, [':id' => $id]);
    }
}
?>
