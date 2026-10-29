-- =======================================================
-- Employee Management System - Seed Data
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
(10, 'delete_roles', 'Delete custom non-system roles', 'Roles');

-- 2. Seed Default Roles
INSERT INTO `roles` (`id`, `name`, `description`, `is_system`) VALUES
(1, 'Admin', 'Super Administrator with unrestricted access to all modules and system actions', 1),
(2, 'HR Manager', 'Human Resources Manager with full control over employee lifecycle and viewing roles', 0),
(3, 'Manager', 'Departmental Manager with view and export permissions for operational oversight', 0),
(4, 'Employee', 'Standard staff member with basic dashboard access', 0);

-- 3. Seed Role-Permissions Mapping
-- Admin has all permissions (1 through 10)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10);

-- HR Manager: view_dashboard, view_employees, create_employees, edit_employees, export_employees, view_roles
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 1), (2, 2), (2, 3), (2, 4), (2, 6), (2, 7);

-- Manager: view_dashboard, view_employees, export_employees
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 1), (3, 2), (3, 6);

-- Employee: view_dashboard
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, 1);

-- 4. Seed Default Admin & Sample Employees
-- Passwords:
-- Admin: AdminPassword123!
-- Others: Employee123!
-- Note: Hashes generated with password_hash(..., PASSWORD_BCRYPT)
INSERT INTO `employees` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `password`, `phone`, `department`, `designation`, `joining_date`, `salary`, `status`, `role_id`) VALUES
(1, 'EMP-001', 'Alex', 'Vance', 'admin@company.com', '$2y$12$VMROmt.4O8vMONQQBTHABe7lQHHleuV6FQTQj1TxJ0QXlmeDdqQBS', '+1 (555) 019-2831', 'Executive Management', 'Chief Technology Officer', '2022-01-15', 135000.00, 'Active', 1),
(2, 'EMP-002', 'Sarah', 'Jenkins', 'hr@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 234-5678', 'Human Resources', 'HR Director', '2022-04-10', 92000.00, 'Active', 2),
(3, 'EMP-003', 'Marcus', 'Chen', 'marcus.chen@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 345-6789', 'Engineering', 'Lead Software Architect', '2022-08-01', 118000.00, 'Active', 3),
(4, 'EMP-004', 'Elena', 'Rostova', 'elena.rostova@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 456-7890', 'Marketing', 'Senior Brand Strategist', '2023-02-14', 85000.00, 'Active', 4),
(5, 'EMP-005', 'David', 'Kim', 'david.kim@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 567-8901', 'Finance', 'Financial Analyst', '2023-06-20', 78000.00, 'Active', 4),
(6, 'EMP-006', 'Jessica', 'Taylor', 'jessica.taylor@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 678-9012', 'Operations', 'Supply Chain Coordinator', '2023-09-05', 68000.00, 'Inactive', 4),
(7, 'EMP-007', 'Liam', 'O’Connor', 'liam.oconnor@company.com', '$2y$12$DOZ2m0oWrlvpYRUSPazNc.vjXtTtC2BUKRH05ur1wKixRnEhKUgkO', '+1 (555) 789-0123', 'Engineering', 'DevOps Specialist', '2024-01-10', 96000.00, 'Active', 4);
