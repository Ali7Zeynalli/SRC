CREATE TABLE activity_logs (
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
);

-- Task Categories
CREATE TABLE IF NOT EXISTS task_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT 'primary',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Initial Categories
INSERT IGNORE INTO task_categories (name, color) VALUES 
('Hardware', 'danger'),
('Software', 'info'),
('Network', 'warning'),
('User Access', 'success'),
('Security', 'secondary'),
('Other', 'primary');

-- Tasks Table
CREATE TABLE IF NOT EXISTS tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status ENUM('New', 'Assigned', 'In_Progress', 'Pending_User', 'Resolved', 'Closed') DEFAULT 'New',
    category_id INT,
    
    -- AD Integration
    requester_dn VARCHAR(512),
    requester_name VARCHAR(255),
    affected_user_dn VARCHAR(512) NULL,
    affected_user_name VARCHAR(255) NULL,
    assigned_to VARCHAR(255) NULL, -- sAMAccountName of Admin
    related_asset VARCHAR(512) NULL, -- DN of related computer
    
    created_by VARCHAR(255), -- sAMAccountName
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES task_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Task History / Comments
CREATE TABLE IF NOT EXISTS task_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    actor_username VARCHAR(255),
    action_type ENUM('Comment', 'StatusChange', 'Assignment', 'System') DEFAULT 'Comment',
    message TEXT,
    is_internal BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
