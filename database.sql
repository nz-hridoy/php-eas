-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert admin user
-- Password: 12345678 (bcrypt hash)
INSERT INTO users (email, password, name, role) 
VALUES ('admin@example.com', '$2y$10$6ltcVL0aEIva7uvSiIT0M.FNijA7rs4KGrJF8XPWrqfz7PY/1lC0u', 'Mr Admin', 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- Insert regular user
-- Password: 12345678 (bcrypt hash)
INSERT INTO users (email, password, name, role) 
VALUES ('user@example.com', '$2y$10$6ltcVL0aEIva7uvSiIT0M.FNijA7rs4KGrJF8XPWrqfz7PY/1lC0u', 'Mr User', 'user')
ON DUPLICATE KEY UPDATE email=email;
