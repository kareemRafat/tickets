-- -------------------------------------------------------------
-- CRV Tickets System Schema and Initial Seed Data
-- Database Name: tickets
-- Database Engine: InnoDB with utf8mb4 collation
-- -------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `tickets` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tickets`;

-- -------------------------------------------------------------
-- 1. Table: branches
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `branches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) UNIQUE,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. Table: employees (stores both admins and employees)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NULL,
    `name` VARCHAR(150) NOT NULL,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `email` VARCHAR(190) UNIQUE NULL,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `role` ENUM('admin','employee') DEFAULT 'employee',
    `status` ENUM('active','inactive') DEFAULT 'active',
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_employees_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. Table: categories
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `type` ENUM('student','support') NOT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. Table: support_tickets (internal support/employee tickets)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `employee_id` INT NOT NULL,
    `ticket_number` VARCHAR(50) UNIQUE NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('low','medium','high') DEFAULT 'medium',
    `status` ENUM('open','in_progress','closed') DEFAULT 'open',
    `last_reply_by` INT NULL,
    `last_reply_at` DATETIME NULL,
    `closed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_support_tickets_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_support_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
    CONSTRAINT `fk_support_tickets_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_support_tickets_reply_by` FOREIGN KEY (`last_reply_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. Table: support_ticket_replies
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `support_ticket_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `employee_id` INT NOT NULL,
    `reply` TEXT NOT NULL,
    `old_status` ENUM('open','in_progress','closed') NULL,
    `new_status` ENUM('open','in_progress','closed') NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_support_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_support_replies_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. Table: student_tickets
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `ticket_number` VARCHAR(50) UNIQUE NOT NULL,
    `student_name` VARCHAR(255) NOT NULL,
    `national_id` VARCHAR(20) NOT NULL,
    `student_code` VARCHAR(100) NULL,
    `contact_phone` VARCHAR(30) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('low','medium','high') DEFAULT 'medium',
    `status` ENUM('open','in_progress','closed') DEFAULT 'open',
    `last_reply_by` INT NULL,
    `last_reply_at` DATETIME NULL,
    `closed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_student_tickets_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_student_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
    CONSTRAINT `fk_student_tickets_reply_by` FOREIGN KEY (`last_reply_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. Table: student_ticket_replies
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_ticket_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `employee_id` INT NOT NULL,
    `reply` TEXT NOT NULL,
    `old_status` ENUM('open','in_progress','closed') NULL,
    `new_status` ENUM('open','in_progress','closed') NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_student_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `student_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_student_replies_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. Table: audit_logs
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `table_name` VARCHAR(150) NULL,
    `record_id` INT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(100) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_logs_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. Table: login_attempts
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(150) NULL,
    `ip_address` VARCHAR(100) NULL,
    `is_success` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. Table: system_settings
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(150) UNIQUE NOT NULL,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- INDEXES
-- =============================================================

-- Indexes for support_tickets
CREATE INDEX idx_support_tickets_branch ON support_tickets(branch_id);
CREATE INDEX idx_support_tickets_status ON support_tickets(status);
CREATE INDEX idx_support_tickets_priority ON support_tickets(priority);
CREATE INDEX idx_support_tickets_ticket_number ON support_tickets(ticket_number);
CREATE INDEX idx_support_tickets_created_at ON support_tickets(created_at);

-- Indexes for student_tickets
CREATE INDEX idx_student_tickets_branch ON student_tickets(branch_id);
CREATE INDEX idx_student_tickets_status ON student_tickets(status);
CREATE INDEX idx_student_tickets_priority ON student_tickets(priority);
CREATE INDEX idx_student_tickets_ticket_number ON student_tickets(ticket_number);
CREATE INDEX idx_student_tickets_national_id ON student_tickets(national_id);
CREATE INDEX idx_student_tickets_created_at ON student_tickets(created_at);


-- =============================================================
-- SEED DATA
-- =============================================================

-- 1. Seed branches
INSERT INTO `branches` (`id`, `name`, `code`, `status`) VALUES 
(1, 'Cairo', 'CAI', 'active'),
(2, 'Giza', 'GIZ', 'active'),
(3, 'Alexandria', 'ALX', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `code` = VALUES(`code`), `status` = VALUES(`status`);

-- 2. Seed default admin (Admin@123456)
INSERT INTO `employees` (`id`, `branch_id`, `name`, `username`, `email`, `password`, `role`, `status`) VALUES
(1, NULL, 'Super Admin', 'admin', 'admin@crv.com', '$2y$10$wj7mvMYkiXSgAWBN2TxP3eP63EMh0Xyt3M4AUgtRzV/DVxzdwq97a', 'admin', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `username` = VALUES(`username`), `email` = VALUES(`email`), `password` = VALUES(`password`), `role` = VALUES(`role`), `status` = VALUES(`status`);

-- 3. Seed categories
INSERT INTO `categories` (`id`, `name`, `type`, `status`) VALUES
-- Support categories
(1, 'IT Support', 'support', 'active'),
(2, 'HR Inquiries', 'support', 'active'),
(3, 'Finance Support', 'support', 'active'),
(4, 'Operations', 'support', 'active'),
-- Student categories
(5, 'Financial Complaint', 'student', 'active'),
(6, 'Academic Issue', 'student', 'active'),
(7, 'Technical Issue', 'student', 'active'),
(8, 'General Complaint', 'student', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `type` = VALUES(`type`), `status` = VALUES(`status`);
