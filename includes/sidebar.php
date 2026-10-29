<?php
/**
 * Sidebar Navigation Component
 * Employee Management System
 */
$currentUser = getCurrentUser();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
?>
<!-- Sidebar Overlay for mobile -->
<div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

<aside class="sidebar bg-dark text-white d-flex flex-column" id="appSidebar">
    <!-- Brand -->
    <div class="sidebar-header p-3 border-bottom border-secondary d-flex align-items-center justify-content-between">
        <a href="<?= BASE_URL ?>/dashboard.php" class="text-white text-decoration-none d-flex align-items-center gap-2">
            <span class="brand-icon bg-primary text-white rounded d-flex align-items-center justify-content-center">
                <i class="bi bi-person-badge-fill fs-5"></i>
            </span>
            <div>
                <h6 class="mb-0 fw-bold tracking-tight"><?= APP_NAME ?></h6>
                <small class="text-secondary" style="font-size: 0.72rem;">HR & WORKFORCE PORTAL</small>
            </div>
        </a>
        <button class="btn btn-sm btn-link text-white d-lg-none p-0" id="sidebarCloseBtn">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <!-- Navigation Links -->
    <div class="sidebar-body flex-grow-1 overflow-auto py-3">
        <ul class="nav nav-pills flex-column px-2 gap-1">
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link text-white-50 <?= ($currentScript === 'dashboard.php') ? 'active text-white' : '' ?>">
                    <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard
                </a>
            </li>

            <!-- Workforce Management -->
            <li class="nav-header text-uppercase text-secondary px-3 mt-3 mb-1" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;">
                Workforce
            </li>
            <?php if (hasPermission('view_employees')): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/employees/index.php" class="nav-link text-white-50 <?= ($currentDir === 'employees' && $currentScript === 'index.php') ? 'active text-white' : '' ?>">
                    <i class="bi bi-people-fill me-2"></i> All Employees
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('create_employees')): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/employees/create.php" class="nav-link text-white-50 <?= ($currentDir === 'employees' && $currentScript === 'create.php') ? 'active text-white' : '' ?>">
                    <i class="bi bi-person-plus-fill me-2"></i> Add Employee
                </a>
            </li>
            <?php endif; ?>

            <!-- Time & Attendance -->
            <li class="nav-header text-uppercase text-secondary px-3 mt-3 mb-1" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;">
                Time & Attendance
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/attendance/index.php" class="nav-link text-white-50 <?= ($currentDir === 'attendance') ? 'active text-white' : '' ?>">
                    <i class="bi bi-clock-history me-2"></i> Daily Attendance
                </a>
            </li>

            <!-- Leave Management -->
            <li class="nav-header text-uppercase text-secondary px-3 mt-3 mb-1" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;">
                Leave & Absences
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/leaves/index.php" class="nav-link text-white-50 <?= ($currentDir === 'leaves' && $currentScript === 'index.php') ? 'active text-white' : '' ?>">
                    <i class="bi bi-calendar2-check-fill me-2"></i> Leave Portal
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/leaves/apply.php" class="nav-link text-white-50 <?= ($currentDir === 'leaves' && $currentScript === 'apply.php') ? 'active text-white' : '' ?>">
                    <i class="bi bi-calendar-plus me-2"></i> Apply for Leave
                </a>
            </li>

            <!-- Payroll & Finance -->
            <li class="nav-header text-uppercase text-secondary px-3 mt-3 mb-1" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;">
                Payroll & Compensation
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/payroll/index.php" class="nav-link text-white-50 <?= ($currentDir === 'payroll' && $currentScript === 'index.php') ? 'active text-white' : '' ?>">
                    <i class="bi bi-wallet2 me-2"></i> Monthly Payroll
                </a>
            </li>
            <?php if (hasRole('Admin') || hasRole('HR Manager')): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/payroll/generate.php" class="nav-link text-white-50 <?= ($currentDir === 'payroll' && $currentScript === 'generate.php') ? 'active text-white' : '' ?>">
                    <i class="bi bi-cash-coin me-2"></i> Process Payroll
                </a>
            </li>
            <?php endif; ?>

            <!-- Organization & Masters -->
            <li class="nav-header text-uppercase text-secondary px-3 mt-3 mb-1" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;">
                Organization
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/departments/index.php" class="nav-link text-white-50 <?= ($currentDir === 'departments') ? 'active text-white' : '' ?>">
                    <i class="bi bi-diagram-3-fill me-2"></i> Departments
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/notices/index.php" class="nav-link text-white-50 <?= ($currentDir === 'notices') ? 'active text-white' : '' ?>">
                    <i class="bi bi-megaphone-fill me-2"></i> Notice Board
                </a>
            </li>

            <!-- System Administration -->
            <li class="nav-header text-uppercase text-secondary px-3 mt-3 mb-1" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;">
                Administration
            </li>
            <?php if (hasPermission('view_roles')): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/roles/index.php" class="nav-link text-white-50 <?= ($currentDir === 'roles') ? 'active text-white' : '' ?>">
                    <i class="bi bi-shield-lock-fill me-2"></i> Roles & RBAC
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- User Profile & Logout -->
    <div class="sidebar-footer p-3 border-top border-secondary bg-black bg-opacity-25">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="avatar-circle bg-primary text-white fw-bold">
                <?= strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1) . substr($currentUser['last_name'] ?? 'S', 0, 1)) ?>
            </div>
            <div class="overflow-hidden flex-grow-1">
                <div class="text-truncate text-white fw-semibold small"><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                <div class="text-truncate text-secondary" style="font-size: 0.75rem;"><?= e($currentUser['role_name']) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2 mt-2">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</aside>
