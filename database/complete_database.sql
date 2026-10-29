-- =======================================================
-- StaffPulse EMS / ERP - Complete All-In-One Database
-- Compatible with MySQL 5.7+, 8.0+ & MariaDB 10.3+
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =======================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------
-- 1. Table: roles
-- -------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 for system roles that cannot be deleted (e.g. Admin)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Table: permissions
-- -------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(150) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. Table: role_permissions (Pivot Table for RBAC)
-- -------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 4. Table: employees
-- -------------------------------------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` VARCHAR(30) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `department` VARCHAR(60) NOT NULL,
  `designation` VARCHAR(80) NOT NULL,
  `joining_date` DATE NOT NULL,
  `salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `role_id` INT UNSIGNED NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_employees_status` (`status`),
  INDEX `idx_employees_department` (`department`),
  INDEX `idx_employees_role_id` (`role_id`),
  CONSTRAINT `fk_employees_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 5. Table: departments
-- -------------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
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

-- -------------------------------------------------------
-- 6. Table: attendance
-- -------------------------------------------------------
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
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

-- -------------------------------------------------------
-- 7. Table: leave_types
-- -------------------------------------------------------
DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE `leave_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `days_allowed` INT UNSIGNED NOT NULL DEFAULT 12,
  `color` VARCHAR(25) NOT NULL DEFAULT 'primary',
  `description` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 8. Table: leaves
-- -------------------------------------------------------
DROP TABLE IF EXISTS `leaves`;
CREATE TABLE `leaves` (
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

-- -------------------------------------------------------
-- 9. Table: payroll
-- -------------------------------------------------------
DROP TABLE IF EXISTS `payroll`;
CREATE TABLE `payroll` (
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

-- -------------------------------------------------------
-- 10. Table: notices
-- -------------------------------------------------------
DROP TABLE IF EXISTS `notices`;
CREATE TABLE `notices` (
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


-- =======================================================
-- SEED DATA
-- =======================================================

-- 1. Seed Permissions
INSERT INTO `permissions` (`id`, `name`, `description`, `module`) VALUES
(1, 'view_dashboard', 'View overall executive dashboard and metrics', 'Dashboard'),
(2, 'view_employees', 'View employee directory and employee profiles', 'Employees'),
(3, 'create_employees', 'Add new employee records to the system', 'Employees'),
(4, 'edit_employees', 'Modify existing employee details and status', 'Employees'),
(5, 'delete_employees', 'Remove employee records from the system', 'Employees'),
(6, 'export_employees', 'Export employee records to CSV spreadsheets', 'Employees'),
(7, 'view_roles', 'View all roles and their assigned permissions', 'Roles'),
(8, 'create_roles', 'Create custom roles and assign permissions', 'Roles'),
(9, 'edit_roles', 'Update role details and toggle permissions', 'Roles'),
(10, 'delete_roles', 'Delete custom non-system roles', 'Roles'),
(11, 'view_attendance', 'View daily attendance records and punch logs', 'Attendance'),
(12, 'manage_attendance', 'Clock in/out staff and update attendance records', 'Attendance'),
(13, 'view_leaves', 'View leave requests and personal leave balances', 'Leaves'),
(14, 'manage_leaves', 'Approve or reject employee leave requests', 'Leaves'),
(15, 'view_payroll', 'View payroll history and access salary slips', 'Payroll'),
(16, 'manage_payroll', 'Generate payroll and issue salary disbursements', 'Payroll'),
(17, 'manage_departments', 'Manage company departments and department heads', 'Departments'),
(18, 'manage_notices', 'Publish and manage company-wide notices and bulletins', 'Notices')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 2. Seed Default Roles
INSERT INTO `roles` (`id`, `name`, `description`, `is_system`) VALUES
(1, 'Admin', 'Super Administrator with unrestricted access to all modules and system actions', 1),
(2, 'HR Manager', 'Human Resources Manager with full control over employee lifecycle and viewing roles', 0),
(3, 'Manager', 'Departmental Manager with view and export permissions for operational oversight', 0),
(4, 'Employee', 'Standard staff member with basic dashboard access', 0)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 3. Seed Role-Permissions Mapping
-- Admin (All permissions 1-18)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18);

-- HR Manager
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 1), (2, 2), (2, 3), (2, 4), (2, 6), (2, 7),
(2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18);

-- Manager
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 1), (3, 2), (3, 6), (3, 11), (3, 13), (3, 14), (3, 15);

-- Employee
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, 1), (4, 11), (4, 13), (4, 15);

-- 4. Seed Default Admin & Employees
INSERT INTO `employees` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `password`, `phone`, `department`, `designation`, `joining_date`, `salary`, `status`, `role_id`) VALUES
(1, 'EMP-001', 'Alex', 'Vance', 'admin@company.com', '$2y$12$VMROmt.4O8vMONQQBTHABe7lQHHleuV6FQTQj1TxJ0QXlmeDdqQBS', '+1 (555) 019-2831', 'Executive Management', 'Chief Technology Officer', '2022-01-15', 135000.00, 'Active', 1),
(2, 'EMP-002', 'Sarah', 'Jenkins', 'hr@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 234-5678', 'Human Resources', 'HR Director', '2022-04-10', 92000.00, 'Active', 2),
(3, 'EMP-003', 'Marcus', 'Chen', 'marcus.chen@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 345-6789', 'Engineering', 'Lead Software Architect', '2022-08-01', 118000.00, 'Active', 3),
(4, 'EMP-004', 'Elena', 'Rostova', 'elena.rostova@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 456-7890', 'Marketing', 'Senior Brand Strategist', '2023-02-14', 85000.00, 'Active', 4),
(5, 'EMP-005', 'David', 'Kim', 'david.kim@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 567-8901', 'Finance', 'Financial Analyst', '2023-06-20', 78000.00, 'Active', 4),
(6, 'EMP-006', 'Jessica', 'Taylor', 'jessica.taylor@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 678-9012', 'Operations', 'Supply Chain Coordinator', '2023-09-05', 68000.00, 'Inactive', 4),
(7, 'EMP-007', 'Liam', 'O’Connor', 'liam.oconnor@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 789-0123', 'Engineering', 'DevOps Specialist', '2024-01-10', 96000.00, 'Active', 4)
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`);

-- 5. Seed Departments
INSERT IGNORE INTO `departments` (`id`, `name`, `code`, `description`, `head_id`, `budget`, `status`) VALUES
(1, 'Executive Management', 'EXEC', 'C-Suite and executive steering committee oversight', 1, 500000.00, 'Active'),
(2, 'Human Resources', 'HR', 'Workforce talent acquisition, culture, payroll, and benefits', 2, 250000.00, 'Active'),
(3, 'Engineering', 'ENG', 'Product design, full-stack software development, and cloud DevOps', 3, 1200000.00, 'Active'),
(4, 'Marketing & Growth', 'MKT', 'Brand strategy, social media campaigns, and customer acquisition', 4, 380000.00, 'Active'),
(5, 'Finance & Accounting', 'FIN', 'Corporate treasury, audit, regulatory compliance, and fiscal forecasting', 5, 420000.00, 'Active'),
(6, 'Operations & Logistics', 'OPS', 'Supply chain efficiency, facilities management, and vendor contracts', 6, 310000.00, 'Active');

-- 6. Seed Leave Types
INSERT IGNORE INTO `leave_types` (`id`, `name`, `days_allowed`, `color`, `description`) VALUES
(1, 'Paid Annual Leave', 18, 'primary', 'Standard paid vacation and annual leave entitlement'),
(2, 'Medical & Sick Leave', 12, 'danger', 'Leave for illness, doctor appointments, or medical recovery'),
(3, 'Casual Personal Leave', 8, 'warning', 'Short-term personal matters and unplanned domestic exigencies'),
(4, 'Maternity / Paternity Leave', 60, 'info', 'Parental leave for childbirth and neonatal care'),
(5, 'Unpaid Leave of Absence', 30, 'secondary', 'Authorized leave without compensation');

-- 7. Seed Attendance Records
INSERT IGNORE INTO `attendance` (`employee_id`, `date`, `clock_in`, `clock_out`, `total_hours`, `status`, `notes`) VALUES
(1, CURRENT_DATE(), '08:52:00', '17:35:00', 8.72, 'Present', 'Punctual executive check-in'),
(2, CURRENT_DATE(), '09:05:00', NULL, 0.00, 'Present', 'HR on-site - active session'),
(3, CURRENT_DATE(), '08:48:00', NULL, 0.00, 'Present', 'Engineering standup attended'),
(4, CURRENT_DATE(), '09:32:00', NULL, 0.00, 'Late', 'Late arrival due to morning transit delay'),
(5, CURRENT_DATE(), '08:55:00', NULL, 0.00, 'Present', 'Finance quarter-end reconcile'),
(7, CURRENT_DATE(), '09:00:00', NULL, 0.00, 'Present', 'DevOps cloud deployment review');

-- 8. Seed Leave Applications
INSERT IGNORE INTO `leaves` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `admin_notes`, `approved_by`) VALUES
(1, 4, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE(), INTERVAL 9 DAY), 5, 'Annual family holiday trip planned in advance.', 'Approved', 'Approved by HR Director. Have a great vacation!', 2),
(2, 3, 2, DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY), DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY), 2, 'Seasonal viral flu and doctor-advised rest.', 'Approved', 'Medical note submitted and verified.', 2),
(3, 5, 3, DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), 1, 'Attending family real estate registration and personal paperwork.', 'Pending', NULL, NULL),
(4, 7, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 14 DAY), DATE_ADD(CURRENT_DATE(), INTERVAL 18 DAY), 5, 'Traveling out of state for sister\'s wedding ceremony.', 'Pending', NULL, NULL),
(5, 4, 3, DATE_SUB(CURRENT_DATE(), INTERVAL 20 DAY), DATE_SUB(CURRENT_DATE(), INTERVAL 20 DAY), 1, 'Emergency plumbing repair at home residence.', 'Approved', 'Approved by Sarah Jenkins.', 2);

-- 9. Seed Payroll Records
INSERT IGNORE INTO `payroll` (`id`, `employee_id`, `month`, `basic_salary`, `allowances`, `deductions`, `net_salary`, `payment_method`, `payment_date`, `status`, `remarks`) VALUES
(1, 1, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 11250.00, 1500.00, 1250.00, 11500.00, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'Monthly executive payroll - Direct deposit to Chase Bank'),
(2, 2, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 7666.67, 850.00, 780.00, 7736.67, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'HR Director standard monthly remuneration'),
(3, 3, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 9833.33, 1100.00, 950.00, 9983.33, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'Lead Architect engineering remuneration'),
(4, 4, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 7083.33, 600.00, 620.00, 7063.33, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'Marketing Strategist monthly salary'),
(5, 5, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 6500.00, 550.00, 580.00, 6470.00, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'Financial Analyst regular payroll disbursement'),
(6, 7, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 8000.00, 800.00, 750.00, 8050.00, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'DevOps Specialist monthly payroll');

-- 10. Seed Company Notices
INSERT IGNORE INTO `notices` (`id`, `title`, `content`, `priority`, `target_audience`, `created_by`, `is_pinned`, `created_at`) VALUES
(1, 'Q4 Enterprise All-Hands Town Hall & Strategy Summit', 'We are pleased to announce our global Q4 All-Hands gathering scheduled for next Friday at 2:00 PM EST. The executive team will review company OKR benchmarks, celebrate team accomplishments, and outline strategic milestones for the upcoming fiscal quarter. Refreshments will be catered at the central auditorium, and remote streaming links will be available via portal.', 'Urgent', 'All Departments', 1, 1, DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 2 DAY)),
(2, 'Annual Health Insurance & Benefits Open Enrollment Period', 'The annual open enrollment window for healthcare coverage, dental/vision plans, and 401(k) retirement matching begins on the 1st of next month. All staff members are invited to review updated benefit tiers and submit any beneficiary modifications through Human Resources by the 25th.', 'High', 'All Departments', 2, 1, DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 5 DAY)),
(3, 'Corporate Cybersecurity Awareness & Mandatory 2FA Setup', 'In line with ISO 27001 data security compliance protocols, IT Operations is rolling out mandatory hardware/software multi-factor authentication across all internal accounts. Please ensure your device authenticator is configured before the end of the week.', 'Normal', 'Engineering & Operations', 3, 0, DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 8 DAY));

SET FOREIGN_KEY_CHECKS = 1;
