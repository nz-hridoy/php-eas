-- Employee Attendance System (EAS) Database Schema
-- Database: nz_eas

-- Drop existing tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS holidays;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS shifts;
DROP TABLE IF EXISTS departments;

-- ============================================
-- TABLE: departments
-- ============================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: shifts
-- ============================================
CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    grace_minutes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'manager', 'employee') NOT NULL,
    department_id INT NULL,
    shift_id INT NULL,
    employee_code VARCHAR(50) UNIQUE NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    join_date DATE NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_employee_code (employee_code),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_department_id (department_id),
    INDEX idx_shift_id (shift_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: holidays
-- ============================================
CREATE TABLE holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_holiday_date (holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: attendance_records
-- ============================================
CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    att_date DATE NOT NULL,
    shift_id INT NOT NULL,
    check_in_time DATETIME NULL,
    check_out_time DATETIME NULL,
    late_minutes INT DEFAULT 0,
    early_minutes INT DEFAULT 0,
    work_minutes INT DEFAULT 0,
    status ENUM('present', 'late', 'early', 'absent', 'holiday', 'weekend') DEFAULT 'absent',
    check_in_ip VARCHAR(45) NULL,
    check_out_ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_user_date (user_id, att_date),
    INDEX idx_att_date (att_date),
    INDEX idx_user_date (user_id, att_date),
    INDEX idx_status_date (status, att_date),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: audit_logs
-- ============================================
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity VARCHAR(100) NOT NULL,
    entity_id INT NULL,
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DUMMY DATA
-- ============================================

-- Insert Departments
INSERT INTO departments (name) VALUES
('Human Resources'),
('Information Technology'),
('Finance'),
('Marketing'),
('Operations'),
('Sales');

-- Insert Shifts
INSERT INTO shifts (name, start_time, end_time, grace_minutes) VALUES
('Morning Shift', '09:00:00', '17:00:00', 15),
('Afternoon Shift', '13:00:00', '21:00:00', 15),
('Night Shift', '21:00:00', '05:00:00', 15),
('Flexible Shift', '10:00:00', '18:00:00', 30);

-- Insert Users
-- Password for all users: 12345678
-- Using bcrypt hash generated with PHP password_hash()
INSERT INTO users (role, department_id, shift_id, employee_code, name, email, password, phone, join_date, status) VALUES
('admin', NULL, NULL, NULL, 'Admin User', 'admin@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', NULL, NULL, 'active'),
('employee', 1, 1, 'EMP001', 'John Doe', 'user@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567890', '2023-01-15', 'active'),
('manager', 2, 1, 'EMP002', 'Jane Smith', 'jane.smith@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567891', '2023-02-20', 'active'),
('employee', 2, 2, 'EMP003', 'Mike Johnson', 'mike.johnson@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567892', '2023-03-10', 'active'),
('employee', 3, 1, 'EMP004', 'Sarah Williams', 'sarah.williams@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567893', '2023-04-05', 'active'),
('employee', 4, 1, 'EMP005', 'David Brown', 'david.brown@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567894', '2023-05-12', 'active'),
('employee', 5, 3, 'EMP006', 'Emily Davis', 'emily.davis@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567895', '2023-06-18', 'active'),
('employee', 6, 1, 'EMP007', 'Robert Wilson', 'robert.wilson@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567896', '2023-07-22', 'active'),
('employee', 2, 1, 'EMP008', 'Lisa Anderson', 'lisa.anderson@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567897', '2023-08-30', 'active'),
('employee', 1, 2, 'EMP009', 'Michael Taylor', 'michael.taylor@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567898', '2023-09-14', 'active'),
('employee', 3, 1, 'EMP010', 'Jennifer Martinez', 'jennifer.martinez@example.com', '$2y$10$8r6/h6JWbk651jfOxcDVheGN8i.JIbCsUdw/rP.e3zNPowKmm5Edq', '1234567899', '2023-10-25', 'active');

-- Insert Holidays (2024)
INSERT INTO holidays (holiday_date, title) VALUES
('2024-01-01', 'New Year\'s Day'),
('2024-01-26', 'Republic Day'),
('2024-03-29', 'Good Friday'),
('2024-04-14', 'Ambedkar Jayanti'),
('2024-05-01', 'Labour Day'),
('2024-08-15', 'Independence Day'),
('2024-10-02', 'Gandhi Jayanti'),
('2024-12-25', 'Christmas');

-- Insert Attendance Records (Sample data for last 30 days)
-- Note: This generates attendance for employee users for the past 30 days
INSERT INTO attendance_records (user_id, att_date, shift_id, check_in_time, check_out_time, late_minutes, early_minutes, work_minutes, status, check_in_ip, check_out_ip)
SELECT 
    u.id as user_id,
    DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY) as att_date,
    u.shift_id,
    CASE 
        WHEN DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) NOT IN (1, 7) 
        AND NOT EXISTS (SELECT 1 FROM holidays h WHERE h.holiday_date = DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY))
        THEN CONCAT(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY), ' ', ADDTIME(s.start_time, SEC_TO_TIME(RAND() * 1800)))
        ELSE NULL
    END as check_in_time,
    CASE 
        WHEN DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) NOT IN (1, 7)
        AND NOT EXISTS (SELECT 1 FROM holidays h WHERE h.holiday_date = DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY))
        THEN CONCAT(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY), ' ', ADDTIME(s.end_time, SEC_TO_TIME(RAND() * -1800)))
        ELSE NULL
    END as check_out_time,
    CASE 
        WHEN DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) NOT IN (1, 7)
        AND NOT EXISTS (SELECT 1 FROM holidays h WHERE h.holiday_date = DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY))
        THEN GREATEST(0, FLOOR(RAND() * 30))
        ELSE 0
    END as late_minutes,
    CASE 
        WHEN DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) NOT IN (1, 7)
        AND NOT EXISTS (SELECT 1 FROM holidays h WHERE h.holiday_date = DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY))
        THEN GREATEST(0, FLOOR(RAND() * 20))
        ELSE 0
    END as early_minutes,
    CASE 
        WHEN DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) NOT IN (1, 7)
        AND NOT EXISTS (SELECT 1 FROM holidays h WHERE h.holiday_date = DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY))
        THEN 480 - GREATEST(0, FLOOR(RAND() * 60))
        ELSE 0
    END as work_minutes,
    CASE 
        WHEN DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) IN (1, 7) THEN 'weekend'
        WHEN EXISTS (SELECT 1 FROM holidays h WHERE h.holiday_date = DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) THEN 'holiday'
        WHEN RAND() > 0.85 THEN 'absent'
        WHEN RAND() > 0.7 THEN 'late'
        WHEN RAND() > 0.9 THEN 'early'
        ELSE 'present'
    END as status,
    CASE 
        WHEN DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) NOT IN (1, 7)
        AND NOT EXISTS (SELECT 1 FROM holidays h WHERE h.holiday_date = DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY))
        THEN CONCAT('192.168.1.', FLOOR(RAND() * 255))
        ELSE NULL
    END as check_in_ip,
    CASE 
        WHEN DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY)) NOT IN (1, 7)
        AND NOT EXISTS (SELECT 1 FROM holidays h WHERE h.holiday_date = DATE_SUB(CURDATE(), INTERVAL n.day_offset DAY))
        THEN CONCAT('192.168.1.', FLOOR(RAND() * 255))
        ELSE NULL
    END as check_out_ip
FROM users u
CROSS JOIN shifts s ON u.shift_id = s.id
CROSS JOIN (
    SELECT 0 as day_offset UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION
    SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
) n
WHERE u.status = 'active' AND u.role IN ('employee', 'manager') AND u.shift_id IS NOT NULL
ORDER BY u.id, n.day_offset;

-- Insert Sample Audit Logs
INSERT INTO audit_logs (user_id, action, entity, entity_id, ip) VALUES
(1, 'login', 'user', 1, '192.168.1.100'),
(2, 'check_in', 'attendance', 1, '192.168.1.101'),
(1, 'create', 'employee', 1, '192.168.1.100'),
(1, 'update', 'employee', 2, '192.168.1.100'),
(2, 'check_out', 'attendance', 1, '192.168.1.101'),
(3, 'login', 'user', 3, '192.168.1.102'),
(1, 'delete', 'holiday', 1, '192.168.1.100'),
(2, 'view', 'attendance', NULL, '192.168.1.101');

-- ============================================
-- END OF DATABASE SETUP
-- ============================================

-- Login Credentials:
-- Admin: admin@example.com / 12345678
-- User: user@example.com / 12345678

