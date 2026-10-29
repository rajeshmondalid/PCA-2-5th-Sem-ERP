-- =======================================================
-- Enterprise ERP Extension Tables
-- StaffPulse ERP System
-- =======================================================

-- 1. Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `head_id` INT UNSIGNED NULL,
  `budget` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_departments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Attendance Table
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `clock_in` TIME NULL,
  `clock_out` TIME NULL,
  `total_hours` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Present', 'Late', 'Half Day', 'Absent') NOT NULL DEFAULT 'Present',
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_attendance_date` (`employee_id`, `date`),
  INDEX `idx_attendance_date` (`date`),
  INDEX `idx_attendance_status` (`status`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Leave Types Table
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `days_allowed` INT UNSIGNED NOT NULL DEFAULT 12,
  `color` VARCHAR(25) NOT NULL DEFAULT 'primary',
  `description` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Leaves Table
CREATE TABLE IF NOT EXISTS `leaves` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT UNSIGNED NOT NULL,
  `leave_type_id` INT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT UNSIGNED NOT NULL DEFAULT 1,
  `reason` TEXT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `admin_notes` TEXT NULL,
  `approved_by` INT UNSIGNED NULL,
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_leaves_status` (`status`),
  INDEX `idx_leaves_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_leaves_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leaves_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Payroll Table
CREATE TABLE IF NOT EXISTS `payroll` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT UNSIGNED NOT NULL,
  `month` VARCHAR(7) NOT NULL COMMENT 'Format YYYY-MM',
  `basic_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `allowances` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `deductions` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `net_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('Bank Transfer', 'Cheque', 'Direct Deposit', 'Cash') NOT NULL DEFAULT 'Bank Transfer',
  `payment_date` DATE NULL,
  `status` ENUM('Pending', 'Processed', 'Paid') NOT NULL DEFAULT 'Paid',
  `remarks` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_payroll_month` (`employee_id`, `month`),
  INDEX `idx_payroll_month` (`month`),
  INDEX `idx_payroll_status` (`status`),
  CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Notices & Announcements Table
CREATE TABLE IF NOT EXISTS `notices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `priority` ENUM('Normal', 'High', 'Urgent') NOT NULL DEFAULT 'Normal',
  `target_audience` VARCHAR(100) NOT NULL DEFAULT 'All Departments',
  `created_by` INT UNSIGNED NOT NULL,
  `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_notices_priority` (`priority`),
  INDEX `idx_notices_pinned` (`is_pinned`),
  CONSTRAINT `fk_notices_author` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
