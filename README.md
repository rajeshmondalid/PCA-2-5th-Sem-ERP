# StaffPulse - Enterprise Human Resource Planning (HR-ERP) System

An enterprise-ready, professional Human Resource & Enterprise Resource Planning system built with **PHP (PDO)**, **MySQL**, **HTML5**, **CSS3**, **Bootstrap 5.3**, and **JavaScript**.

---

## Enterprise ERP Modules & Features

### 1. Executive ERP Command Center
- **Key Operations Telemetry:** Live counters for Total Workforce, On-Time Attendance, Pending Leave Authorizations, Monthly Payroll Spend, and Active Bulletins.
- **Biometric & Web Punch Widget:** Live digital clock with one-click shift punch in/out and real-time shift duration tracking.
- **Pending Approvals Queue:** Quick review and one-click approve/reject actions for pending leaves.
- **Recent Shift Stream:** Real-time attendance check-in feed.
- **Department Telemetry:** Headcount progress bars and cost center budget distribution.

### 2. Time & Attendance Management
- **Clock In / Out:** Web clock-in recording timestamps with automatic punctuality detection (On Time vs Late arrivals after 09:15 AM).
- **Daily Attendance Logs:** Tabular roster filtered by Date, Department, and Status (Present, Late, Half Day, Absent).
- **Manual Time Entry:** Administrative override modal for HR to adjust or log punch times.
- **Work Hours Calculation:** Automatic duration computation upon shift completion.

### 3. Leave & Absence Portal
- **Leave Application Workflow:** Interactive form with live duration calculation and leave category selector.
- **Entitlement Tiers:** Pre-configured for Paid Annual Leave, Medical & Sick Leave, Casual Leave, and Parental Leave.
- **Multi-Status Approval Queue:** Filter by All, Pending, Approved, and Rejected requests.
- **HR Decision Modal:** Quick approval or rejection with reviewer comments.

### 4. Corporate Payroll & Payslip Issuance
- **Monthly Payroll Processing:** Salary calculations incorporating Base Pay, Allowances (HRA, Transport, Special), and Deductions (TDS Tax, PF, Health Insurance).
- **One-Click Automated Bulk Run:** Automated payroll calculation across all active staff for any selected month.
- **Printable & PDF-Ready Payslips:** High-end salary vouchers featuring corporate registration headers, earnings vs deductions breakdowns, net salary callouts, and dual signature blocks.

### 5. Department Master Directory
- **Organizational Units:** Department codes, operational budgets, designated department heads, and active staff headcounts.
- **Department Administration:** Creation modal with budget allocations and leadership mapping.

### 6. Company Bulletin & Notice Board
- **Official Announcements:** Priority-tagged bulletins (Urgent in Red, High in Amber, Normal in Blue).
- **Pinned Bulletins:** Prominently featured town hall notices pinned directly to the executive dashboard.
- **Target Audience:** Departmental broadcasts and company-wide notifications.

### 7. Workforce Management (CRUD) & RBAC
- **Employee Directory:** Search by Name, Employee ID, Email, or Job Title with multi-factor filters and CSV export.
- **Granular RBAC:** 18 permissions across 6 modules with system role protections.

### 4. Authentication & Security
- **Authentication:** Secure login and logout with password hashing via `password_hash(..., PASSWORD_BCRYPT)` and verification via `password_verify()`.
- **Dual Login Identifiers:** Login with either work Email or Employee ID.
- **Inactive Account Lockout:** Inactive employees cannot sign in.
- **Session Security:** Session fixation mitigation with `session_regenerate_id(true)`, `HttpOnly` cookie flags, and session timeout.
- **CSRF Protection:** Cryptographic CSRF tokens on all POST requests (`generateCsrfToken()`, `verifyCsrfToken()`).
- **Prepared SQL Statements:** All queries use PHP Data Objects (PDO) with parameterized prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`).
- **XSS Escaping:** Output escaping with `e()` helper function utilizing `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.

---

## Project Structure

```text
employee-management-system/
├── config/
│   ├── database.php          # PDO connection, credentials, charset utf8mb4
│   ├── config.php            # Session config, base URL, sanitization & flash helpers
│   └── auth.php              # RBAC helpers: isLoggedIn(), hasPermission(), requirePermission()
├── database/
│   ├── schema.sql            # MySQL schema (roles, permissions, role_permissions, employees)
│   └── seed.sql              # Seed data (permissions, default roles, admin user, sample staff)
├── includes/
│   ├── header.php            # HTML5 head, Bootstrap 5 CDN, Bootstrap Icons CDN
│   ├── navbar.php            # Top navbar, profile badge, role indicator, quick logout
│   ├── sidebar.php           # Responsive sidebar with permission-filtered links
│   ├── footer.php            # Global footer, delete modal hook, Bootstrap bundle script
│   └── flash.php             # Toast / alert message renderer
├── assets/
│   ├── css/
│   │   └── style.css         # Modern styling: slate dark sidebar, cards, badges, mobile overlay
│   └── js/
│       └── app.js            # UI interactions: delete confirmation modal, auto-dismiss alerts
├── auth/
│   ├── login.php             # Secure login form with CSRF and credential verification
│   ├── logout.php            # Secure logout and session destruction
│   └── forbidden.php         # HTTP 403 Forbidden page for unauthorized actions
├── employees/
│   ├── index.php             # Employee directory with search, filter, pagination
│   ├── create.php            # Form to register new employee
│   ├── view.php              # Comprehensive employee profile
│   ├── edit.php              # Edit employee details and update status/password
│   ├── delete.php            # Secure POST delete action handler
│   └── export.php            # Export filtered employee data to CSV
├── roles/
│   ├── index.php             # Roles directory with employee and permission counts
│   ├── create.php            # Create custom role with permission matrix
│   ├── edit.php              # Edit role details and modify assigned permissions
│   └── delete.php            # Delete role with dependency safeguards
├── dashboard.php             # Main executive telemetry and metrics dashboard
├── index.php                 # Root router (redirects to dashboard or login)
├── install.php               # One-click web installer & database setup wizard
└── README.md                 # Complete documentation and setup guide
```

---

## Installation & Setup Instructions

### Option A: Using XAMPP / WAMP / MAMP (Recommended)

1. **Copy Files to Web Directory:**
   Copy the `employee-management-system` folder into your local web root:
   - **XAMPP (Windows):** `C:\xampp\htdocs\employee-management-system`
   - **XAMPP (macOS):** `/Applications/XAMPP/htdocs/employee-management-system`
   - **WAMP (Windows):** `C:\wamp64\www\employee-management-system`
   - **LAMP (Linux):** `/var/www/html/employee-management-system`

2. **Start Services:**
   Open XAMPP/WAMP Control Panel and start **Apache** and **MySQL**.

3. **Database Setup (Choose 1 of 2 ways):**
   - **Way 1 (One-Click Web Installer):**
     Open your browser and navigate to:
     `http://localhost/employee-management-system/install.php`
     Click **"Initialize Database & Install Now"**. The installer will create the database and seed all tables automatically!
   - **Way 2 (phpMyAdmin or MySQL CLI):**
     Create a database named `employee_management_db` in phpMyAdmin (`http://localhost/phpmyadmin/`), then import:
     1. `database/schema.sql`
     2. `database/seed.sql`

4. **Verify Database Credentials:**
   If your MySQL password is not blank, edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'employee_management_db');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Set your MySQL password here
   ```

5. **Launch Application:**
   Navigate to:
   `http://localhost/employee-management-system/`

---

### Option B: Using PHP's Built-in Development Server

If you have PHP (>= 7.4) and MySQL installed on your machine:

1. **Import Database:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE employee_management_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p employee_management_db < database/schema.sql
   mysql -u root -p employee_management_db < database/seed.sql
   ```

2. **Start PHP Built-in Server:**
   Navigate into the project directory and run:
   ```bash
   cd employee-management-system
   php -S localhost:8000
   ```

3. **Access the Portal:**
   Open `http://localhost:8000/` in your browser.

---

## Default Login Credentials

| Role | Email Address | Password | Permissions Scope |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@company.com` | `AdminPassword123!` | Full unrestricted access to all modules and system actions |
| **HR Manager** | `hr@company.com` | `Employee123!` | View Dashboard, Manage Employees (Add/Edit/Delete/Export), View Roles |
| **Manager** | `marcus.chen@company.com` | `Employee123!` | View Dashboard, View Employees Directory, Export CSV |
| **Employee** | `elena.rostova@company.com` | `Employee123!` | View Personal Dashboard & Profile |

---

## Role-Based Access Control (RBAC) Permissions Matrix

| Permission Name | Module | Admin | HR Manager | Manager | Employee |
| :--- | :--- | :---: | :---: | :---: | :---: |
| `view_dashboard` | Dashboard | Yes | Yes | Yes | Yes |
| `view_employees` | Employees | Yes | Yes | Yes | No |
| `create_employees` | Employees | Yes | Yes | No | No |
| `edit_employees` | Employees | Yes | Yes | No | No |
| `delete_employees` | Employees | Yes | Yes | No | No |
| `export_employees` | Employees | Yes | Yes | Yes | No |
| `view_roles` | Roles | Yes | Yes | No | No |
| `create_roles` | Roles | Yes | No | No | No |
| `edit_roles` | Roles | Yes | No | No | No |
| `delete_roles` | Roles | Yes | No | No | No |

---

## Security Architecture

1. **Prepared SQL Statements (PDO):** Parameterized queries prevent SQL Injection vulnerabilities across all search, filter, insert, and update operations.
2. **Password Cryptography:** Secure bcrypt hashing with high work factor (`PASSWORD_BCRYPT`).
3. **Cross-Site Request Forgery (CSRF):** Unique cryptographic tokens are required on all POST requests.
4. **Cross-Site Scripting (XSS) Prevention:** Contextual escaping (`e()` helper) on all user-submitted text output.
5. **Session Fixation Prevention:** Session IDs are regenerated immediately upon successful user authentication.
6. **Self-Deletion Protection:** Administrators cannot delete their own active account, ensuring system administration continuity.
#   P C A - 2 - 5 t h - S e m - E R P -  
 