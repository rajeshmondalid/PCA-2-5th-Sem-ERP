-- =======================================================
-- Enterprise ERP Extension - Seed Data
-- StaffPulse ERP System
-- =======================================================

-- 1. Seed New Permissions
INSERT IGNORE INTO `permissions` (`id`, `name`, `description`, `module`) VALUES
(11, 'view_attendance', 'View daily attendance records and punch logs', 'Attendance'),
(12, 'manage_attendance', 'Clock in/out staff and update attendance records', 'Attendance'),
(13, 'view_leaves', 'View leave requests and personal leave balances', 'Leaves'),
(14, 'manage_leaves', 'Approve or reject employee leave requests', 'Leaves'),
(15, 'view_payroll', 'View payroll history and access salary slips', 'Payroll'),
(16, 'manage_payroll', 'Generate payroll and issue salary disbursements', 'Payroll'),
(17, 'manage_departments', 'Manage company departments and department heads', 'Departments'),
(18, 'manage_notices', 'Publish and manage company-wide notices and bulletins', 'Notices');

-- Map to Admin (Admin gets all permissions)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18);

-- Map to HR Manager (Attendance, Leaves, Payroll, Departments, Notices)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18);

-- Map to Manager (View attendance, view leaves, view payroll, view notices)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 11), (3, 13), (3, 14), (3, 15);

-- Map to Employee (View self attendance, apply/view leaves, view self payroll)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, 11), (4, 13), (4, 15);

-- 2. Seed Departments
INSERT IGNORE INTO `departments` (`id`, `name`, `code`, `description`, `head_id`, `budget`, `status`) VALUES
(1, 'Executive Management', 'EXEC', 'C-Suite and executive steering committee oversight', 1, 500000.00, 'Active'),
(2, 'Human Resources', 'HR', 'Workforce talent acquisition, culture, payroll, and benefits', 2, 250000.00, 'Active'),
(3, 'Engineering', 'ENG', 'Product design, full-stack software development, and cloud DevOps', 3, 1200000.00, 'Active'),
(4, 'Marketing & Growth', 'MKT', 'Brand strategy, social media campaigns, and customer acquisition', 4, 380000.00, 'Active'),
(5, 'Finance & Accounting', 'FIN', 'Corporate treasury, audit, regulatory compliance, and fiscal forecasting', 5, 420000.00, 'Active'),
(6, 'Operations & Logistics', 'OPS', 'Supply chain efficiency, facilities management, and vendor contracts', 6, 310000.00, 'Active');

-- 3. Seed Leave Types
INSERT IGNORE INTO `leave_types` (`id`, `name`, `days_allowed`, `color`, `description`) VALUES
(1, 'Paid Annual Leave', 18, 'primary', 'Standard paid vacation and annual leave entitlement'),
(2, 'Medical & Sick Leave', 12, 'danger', 'Leave for illness, doctor appointments, or medical recovery'),
(3, 'Casual Personal Leave', 8, 'warning', 'Short-term personal matters and unplanned domestic exigencies'),
(4, 'Maternity / Paternity Leave', 60, 'info', 'Parental leave for childbirth and neonatal care'),
(5, 'Unpaid Leave of Absence', 30, 'secondary', 'Authorized leave without compensation');

-- 4. Seed Attendance Records for Today & Recent Days
-- Ensure clean seed
INSERT IGNORE INTO `attendance` (`employee_id`, `date`, `clock_in`, `clock_out`, `total_hours`, `status`, `notes`) VALUES
(1, CURRENT_DATE(), '08:52:00', '17:35:00', 8.72, 'Present', 'Punctual executive check-in'),
(2, CURRENT_DATE(), '09:05:00', NULL, 0.00, 'Present', 'HR on-site - active session'),
(3, CURRENT_DATE(), '08:48:00', NULL, 0.00, 'Present', 'Engineering standup attended'),
(4, CURRENT_DATE(), '09:32:00', NULL, 0.00, 'Late', 'Late arrival due to morning transit delay'),
(5, CURRENT_DATE(), '08:55:00', NULL, 0.00, 'Present', 'Finance quarter-end reconcile'),
(7, CURRENT_DATE(), '09:00:00', NULL, 0.00, 'Present', 'DevOps cloud deployment review');

-- 5. Seed Leave Applications
INSERT IGNORE INTO `leaves` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `admin_notes`, `approved_by`) VALUES
(1, 4, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE(), INTERVAL 9 DAY), 5, 'Annual family holiday trip planned in advance.', 'Approved', 'Approved by HR Director. Have a great vacation!', 2),
(2, 3, 2, DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY), DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY), 2, 'Seasonal viral flu and doctor-advised rest.', 'Approved', 'Medical note submitted and verified.', 2),
(3, 5, 3, DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), 1, 'Attending family real estate registration and personal paperwork.', 'Pending', NULL, NULL),
(4, 7, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 14 DAY), DATE_ADD(CURRENT_DATE(), INTERVAL 18 DAY), 5, 'Traveling out of state for sister\'s wedding ceremony.', 'Pending', NULL, NULL),
(5, 4, 3, DATE_SUB(CURRENT_DATE(), INTERVAL 20 DAY), DATE_SUB(CURRENT_DATE(), INTERVAL 20 DAY), 1, 'Emergency plumbing repair at home residence.', 'Approved', 'Approved by Sarah Jenkins.', 2);

-- 6. Seed Payroll Records for Current & Previous Month
INSERT IGNORE INTO `payroll` (`id`, `employee_id`, `month`, `basic_salary`, `allowances`, `deductions`, `net_salary`, `payment_method`, `payment_date`, `status`, `remarks`) VALUES
(1, 1, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 11250.00, 1500.00, 1250.00, 11500.00, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'Monthly executive payroll - Direct deposit to Chase Bank'),
(2, 2, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 7666.67, 850.00, 780.00, 7736.67, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'HR Director standard monthly remuneration'),
(3, 3, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 9833.33, 1100.00, 950.00, 9983.33, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'Lead Architect engineering remuneration'),
(4, 4, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 7083.33, 600.00, 620.00, 7063.33, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'Marketing Strategist monthly salary'),
(5, 5, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 6500.00, 550.00, 580.00, 6470.00, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'Financial Analyst regular payroll disbursement'),
(6, 7, DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m'), 8000.00, 800.00, 750.00, 8050.00, 'Bank Transfer', DATE_SUB(CURRENT_DATE(), INTERVAL 25 DAY), 'Paid', 'DevOps Specialist monthly payroll');

-- 7. Seed Company Notices
INSERT IGNORE INTO `notices` (`id`, `title`, `content`, `priority`, `target_audience`, `created_by`, `is_pinned`, `created_at`) VALUES
(1, 'Q4 Enterprise All-Hands Town Hall & Strategy Summit', 'We are pleased to announce our global Q4 All-Hands gathering scheduled for next Friday at 2:00 PM EST. The executive team will review company OKR benchmarks, celebrate team accomplishments, and outline strategic milestones for the upcoming fiscal quarter. Refreshments will be catered at the central auditorium, and remote streaming links will be available via portal.', 'Urgent', 'All Departments', 1, 1, DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 2 DAY)),
(2, 'Annual Health Insurance & Benefits Open Enrollment Period', 'The annual open enrollment window for healthcare coverage, dental/vision plans, and 401(k) retirement matching begins on the 1st of next month. All staff members are invited to review updated benefit tiers and submit any beneficiary modifications through Human Resources by the 25th.', 'High', 'All Departments', 2, 1, DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 5 DAY)),
(3, 'Corporate Cybersecurity Awareness & Mandatory 2FA Setup', 'In line with ISO 27001 data security compliance protocols, IT Operations is rolling out mandatory hardware/software multi-factor authentication across all internal accounts. Please ensure your device authenticator is configured before the end of the week.', 'Normal', 'Engineering & Operations', 3, 0, DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 8 DAY));
