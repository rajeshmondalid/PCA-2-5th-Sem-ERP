<?php
/**
 * Enterprise Executive ERP Dashboard
 * StaffPulse ERP System
 */
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Enterprise ERP Dashboard';
$db = getDBConnection();
$currentUser = getCurrentUser();

// 1. Core Workforce Metrics
$totalEmployees = (int)$db->query('SELECT COUNT(*) FROM employees')->fetchColumn();
$activeEmployees = (int)$db->query("SELECT COUNT(*) FROM employees WHERE status = 'Active'")->fetchColumn();
$inactiveEmployees = (int)$db->query("SELECT COUNT(*) FROM employees WHERE status = 'Inactive'")->fetchColumn();
$totalRoles = (int)$db->query('SELECT COUNT(*) FROM roles')->fetchColumn();

// 2. Today's Attendance Metrics
$todayDate = date('Y-m-d');
$attStats = $db->query("
    SELECT 
        COUNT(*) as total_logged,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as count_present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as count_late,
        SUM(CASE WHEN status = 'Half Day' THEN 1 ELSE 0 END) as count_halfday
    FROM attendance 
    WHERE date = CURRENT_DATE()
")->fetch();

// 3. Pending Leaves Count & List
$pendingLeavesCount = (int)$db->query("SELECT COUNT(*) FROM leaves WHERE status = 'Pending'")->fetchColumn();
$pendingLeaves = $db->query("
    SELECT l.*, lt.name as leave_type_name, lt.color as leave_type_color,
           e.first_name, e.last_name, e.employee_id as emp_code, e.department
    FROM leaves l
    INNER JOIN leave_types lt ON l.leave_type_id = lt.id
    INNER JOIN employees e ON l.employee_id = e.id
    WHERE l.status = 'Pending'
    ORDER BY l.applied_at DESC
    LIMIT 4
")->fetchAll();

// 4. Payroll Total (Current or Most Recent Month)
$payrollMonth = date('Y-m');
$payrollStats = $db->query("
    SELECT 
        COALESCE(SUM(net_salary), 0) as monthly_disbursed,
        COUNT(*) as slips_issued
    FROM payroll 
    WHERE month = '$payrollMonth'
")->fetch();

if ((float)$payrollStats['monthly_disbursed'] == 0) {
    // Check previous month if current month not yet run
    $prevMonth = date('Y-m', strtotime('-1 month'));
    $payrollStats = $db->query("
        SELECT 
            COALESCE(SUM(net_salary), 0) as monthly_disbursed,
            COUNT(*) as slips_issued
        FROM payroll 
        WHERE month = '$prevMonth'
    ")->fetch();
    $payrollMonth = $prevMonth;
}

// 5. Active Pinned or Urgent Notices
$notices = $db->query("
    SELECT n.*, e.first_name, e.last_name, e.department as author_department
    FROM notices n
    INNER JOIN employees e ON n.created_by = e.id
    ORDER BY n.is_pinned DESC, n.created_at DESC
    LIMIT 3
")->fetchAll();

// 6. Current User Today Attendance
$myTodayAtt = $db->prepare("SELECT * FROM attendance WHERE employee_id = :uid AND date = CURRENT_DATE() LIMIT 1");
$myTodayAtt->execute(['uid' => $currentUser['id']]);
$myAttendance = $myTodayAtt->fetch();

// 7. Today's Live Attendance Feed (Recent 5 Punches)
$todayPunches = $db->query("
    SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, e.department
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.date = CURRENT_DATE()
    ORDER BY a.clock_in DESC
    LIMIT 5
")->fetchAll();

// 8. Recent Onboarded Employees
$recentEmployees = $db->query("
    SELECT e.*, r.name as role_name
    FROM employees e
    INNER JOIN roles r ON e.role_id = r.id
    ORDER BY e.created_at DESC
    LIMIT 5
")->fetchAll();

// 9. Department Telemetry
$departmentStats = $db->query("
    SELECT department, COUNT(*) as count, 
           SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_count
    FROM employees
    GROUP BY department
    ORDER BY count DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <?php require_once __DIR__ . '/includes/flash.php'; ?>

        <!-- Welcome Banner & ERP Executive Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary text-white text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.08em;">Enterprise ERP</span>
                    <span class="text-muted small">&bull; <?= date('l, F j, Y') ?></span>
                </div>
                <h4 class="fw-bold mb-0 text-dark">Executive Command Center</h4>
                <p class="text-secondary small mb-0">Unified workforce, timekeeping, payroll, and departmental operations.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>/attendance/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-clock-history"></i> Attendance
                </a>
                <a href="<?= BASE_URL ?>/leaves/apply.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-calendar-plus"></i> Request Leave
                </a>
                <?php if (hasRole('Admin') || hasRole('HR Manager')): ?>
                <a href="<?= BASE_URL ?>/payroll/generate.php" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-cash-coin"></i> Process Payroll
                </a>
                <a href="<?= BASE_URL ?>/employees/create.php" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-person-plus-fill"></i> Add Staff
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pinned Corporate Announcement Broadcast -->
        <?php if (!empty($notices)): ?>
            <?php $topNotice = $notices[0]; ?>
            <div class="alert alert-light border shadow-sm rounded-4 p-3 mb-4 d-flex align-items-start gap-3 notice-card <?= $topNotice['priority'] === 'Urgent' ? 'notice-urgent' : 'notice-normal' ?>">
                <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small px-2 py-0"><?= e($topNotice['priority']) ?> Bulletin</span>
                        <strong class="text-dark small text-truncate"><?= e($topNotice['title']) ?></strong>
                        <span class="text-muted small">&bull; <?= date('M d', strtotime($topNotice['created_at'])) ?></span>
                    </div>
                    <p class="text-secondary small mb-0 text-truncate" style="max-width: 900px;">
                        <?= e($topNotice['content']) ?>
                    </p>
                </div>
                <a href="<?= BASE_URL ?>/notices/index.php" class="btn btn-sm btn-outline-primary text-nowrap flex-shrink-0 align-self-center">
                    Notice Board &rarr;
                </a>
            </div>
        <?php endif; ?>

        <!-- 5 Core ERP KPI Stat Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Employees -->
            <div class="col-12 col-sm-6 col-xl">
                <div class="card stat-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium text-uppercase">Workforce</span>
                            <h3 class="fw-bold my-1 text-dark"><?= number_format($totalEmployees) ?></h3>
                            <span class="small text-success fw-medium">
                                <i class="bi bi-check-circle me-1"></i><?= $totalEmployees > 0 ? round(($activeEmployees / $totalEmployees) * 100) : 0 ?>% Active
                            </span>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Present Today -->
            <div class="col-12 col-sm-6 col-xl">
                <div class="card stat-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium text-uppercase">Present Today</span>
                            <h3 class="fw-bold my-1 text-success"><?= (int)$attStats['count_present'] ?></h3>
                            <span class="small text-muted">
                                <i class="bi bi-alarm text-warning me-1"></i><?= (int)$attStats['count_late'] ?> Late arrivals
                            </span>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Leaves -->
            <div class="col-12 col-sm-6 col-xl">
                <div class="card stat-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium text-uppercase">Pending Leaves</span>
                            <h3 class="fw-bold my-1 text-warning"><?= $pendingLeavesCount ?></h3>
                            <a href="<?= BASE_URL ?>/leaves/index.php?tab=pending" class="small text-decoration-none fw-semibold">
                                Review Queue &rarr;
                            </a>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-calendar2-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Payroll Spend -->
            <div class="col-12 col-sm-6 col-xl">
                <div class="card stat-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium text-uppercase">Monthly Payroll</span>
                            <h3 class="fw-bold my-1 text-dark font-monospace"><?= formatCurrency($payrollStats['monthly_disbursed']) ?></h3>
                            <span class="small text-muted">
                                <?= date('M Y', strtotime($payrollMonth . '-01')) ?> &bull; <?= (int)$payrollStats['slips_issued'] ?> Slips
                            </span>
                        </div>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Count -->
            <div class="col-12 col-sm-6 col-xl">
                <div class="card stat-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium text-uppercase">Cost Centers</span>
                            <h3 class="fw-bold my-1 text-dark"><?= count($departmentStats) ?></h3>
                            <a href="<?= BASE_URL ?>/departments/index.php" class="small text-decoration-none fw-semibold">
                                Org Master &rarr;
                            </a>
                        </div>
                        <div class="stat-icon bg-secondary-subtle text-secondary">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main ERP Content Grid -->
        <div class="row g-4 mb-4">
            <!-- Left Column: Operations & Approvals -->
            <div class="col-12 col-xl-8">
                
                <!-- Pending Leaves Approvals Card -->
                <?php if (!empty($pendingLeaves)): ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 border-start border-4 border-warning">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Action Required: Pending Leave Applications
                            </h6>
                            <small class="text-muted">Review time-off authorizations submitted by staff</small>
                        </div>
                        <a href="<?= BASE_URL ?>/leaves/index.php?tab=pending" class="small fw-semibold text-decoration-none">Open Portal &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-secondary">
                                <tr>
                                    <th>Applicant</th>
                                    <th>Category</th>
                                    <th>Duration</th>
                                    <th>Reason</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingLeaves as $pl): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle-sm bg-primary text-white fw-bold">
                                                <?= strtoupper(substr($pl['first_name'], 0, 1) . substr($pl['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark small"><?= e($pl['first_name'] . ' ' . $pl['last_name']) ?></div>
                                                <small class="text-muted"><?= e($pl['department']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= e($pl['leave_type_color']) ?>-subtle text-<?= e($pl['leave_type_color']) ?> border small">
                                            <?= e($pl['leave_type_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= (int)$pl['total_days'] ?> day(s)</div>
                                        <small class="text-muted"><?= date('M d', strtotime($pl['start_date'])) ?> &rarr; <?= date('M d', strtotime($pl['end_date'])) ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted text-truncate d-block" style="max-width: 180px;" title="<?= e($pl['reason']) ?>">
                                            <?= e($pl['reason']) ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="<?= BASE_URL ?>/leaves/action.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                            <input type="hidden" name="leave_id" value="<?= (int)$pl['id'] ?>">
                                            <input type="hidden" name="action" value="Approved">
                                            <button type="submit" class="btn btn-xs btn-success py-1 px-2" style="font-size: 0.75rem;" title="Approve Request">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= BASE_URL ?>/leaves/action.php" class="d-inline ms-1">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                            <input type="hidden" name="leave_id" value="<?= (int)$pl['id'] ?>">
                                            <input type="hidden" name="action" value="Rejected">
                                            <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size: 0.75rem;" title="Reject Request">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Today's Attendance Real-Time Punch Stream -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-clock-history me-2 text-primary"></i>Today's Attendance Punch Stream
                            </h6>
                            <small class="text-muted">Real-time shift log & punctuality telemetry</small>
                        </div>
                        <a href="<?= BASE_URL ?>/attendance/index.php" class="small fw-semibold text-decoration-none">Full Roster &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-secondary">
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Department</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todayPunches)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted small">No check-ins logged yet today.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($todayPunches as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-circle-sm bg-primary text-white fw-bold">
                                                    <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark small"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></div>
                                                    <small class="text-muted font-monospace"><?= e($p['emp_code']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-secondary border"><?= e($p['department']) ?></span></td>
                                        <td>
                                            <span class="fw-medium text-dark small">
                                                <i class="bi bi-box-arrow-in-right text-success me-1"></i><?= date('h:i A', strtotime($p['clock_in'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($p['clock_out'])): ?>
                                                <span class="fw-medium text-dark small">
                                                    <i class="bi bi-box-arrow-left text-danger me-1"></i><?= date('h:i A', strtotime($p['clock_out'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.72rem;">On Shift</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ((float)$p['total_hours'] > 0): ?>
                                                <span class="fw-bold text-dark small font-monospace"><?= number_format((float)$p['total_hours'], 1) ?>h</span>
                                            <?php else: ?>
                                                <span class="text-muted">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stClass = 'badge-status-present';
                                            if ($p['status'] === 'Late') $stClass = 'badge-status-late';
                                            elseif ($p['status'] === 'Half Day') $stClass = 'badge-status-halfday';
                                            ?>
                                            <span class="badge-status <?= $stClass ?>">
                                                <span class="badge-dot"></span>
                                                <?= e($p['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recently Added Employees -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-person-lines-fill me-2 text-primary"></i>Workforce Directory
                        </h6>
                        <a href="<?= BASE_URL ?>/employees/index.php" class="small fw-semibold text-decoration-none">Directory &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-secondary">
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Clearance</th>
                                    <th>Salary</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEmployees as $emp): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle-sm bg-primary text-white fw-bold">
                                                <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <a href="<?= BASE_URL ?>/employees/view.php?id=<?= (int)$emp['id'] ?>" class="text-decoration-none fw-semibold text-dark small">
                                                    <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                                </a>
                                                <div class="text-muted" style="font-size: 0.72rem;"><?= e($emp['designation']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border"><?= e($emp['department']) ?></span></td>
                                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($emp['role_name']) ?></span></td>
                                    <td class="font-monospace small"><?= formatCurrency($emp['salary']) ?></td>
                                    <td>
                                        <span class="badge-status <?= $emp['status'] === 'Active' ? 'badge-status-active' : 'badge-status-inactive' ?>">
                                            <span class="badge-dot"></span> <?= e($emp['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Personal Shift Punch Widget & Department Breakdowns -->
            <div class="col-12 col-xl-4">
                
                <!-- Interactive Punch Widget -->
                <div class="punch-widget p-4 mb-4 text-center">
                    <span class="badge bg-white bg-opacity-25 text-white mb-2" style="font-size: 0.72rem;">Biometric & Web Attendance</span>
                    <div class="h2 fw-bold mb-1 font-monospace" id="liveClockDisplay">--:--:--</div>
                    <div class="text-white-50 small mb-3"><?= date('l') ?> &bull; <?= date('F j, Y') ?></div>

                    <div class="bg-white bg-opacity-10 p-3 rounded-3 mb-3 text-start">
                        <div class="d-flex justify-content-between small text-white-50 mb-1">
                            <span>Logged In:</span>
                            <span class="text-white fw-semibold"><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-white-50 mb-1">
                            <span>Today's Status:</span>
                            <span class="text-white fw-bold">
                                <?= $myAttendance ? e($myAttendance['status']) : 'Not Checked In' ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between small text-white-50">
                            <span>Clock-in Time:</span>
                            <span class="text-white font-monospace">
                                <?= (!empty($myAttendance['clock_in'])) ? date('h:i A', strtotime($myAttendance['clock_in'])) : '--:--' ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!$myAttendance || empty($myAttendance['clock_in'])): ?>
                        <form method="POST" action="<?= BASE_URL ?>/attendance/punch.php">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="clock_in">
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 rounded-3 shadow">
                                <i class="bi bi-box-arrow-in-right"></i> Punch In (Start Shift)
                            </button>
                        </form>
                    <?php elseif (empty($myAttendance['clock_out'])): ?>
                        <form method="POST" action="<?= BASE_URL ?>/attendance/punch.php">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="clock_out">
                            <button type="submit" class="btn btn-danger w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 rounded-3 shadow">
                                <i class="bi bi-box-arrow-left"></i> Punch Out (End Shift)
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success bg-opacity-25 border-0 text-white small mb-0 py-2">
                            <i class="bi bi-check-circle-fill me-1"></i> Shift completed for today! (<?= (float)$myAttendance['total_hours'] ?> hrs)
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Department Cost Centers Progress Bars -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Department Allocation
                        </h6>
                        <a href="<?= BASE_URL ?>/departments/index.php" class="small fw-semibold text-decoration-none">Manage &rarr;</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($departmentStats as $dept): 
                            $pct = $totalEmployees > 0 ? round(($dept['count'] / $totalEmployees) * 100) : 0;
                        ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold text-dark"><?= e($dept['department']) ?></span>
                                    <span class="badge bg-light text-dark border"><?= $dept['count'] ?> Staff (<?= $pct ?>%)</span>
                                </div>
                                <div class="progress" style="height: 7px; border-radius: 4px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick ERP System Links -->
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-grid-fill me-2 text-primary"></i>Quick ERP Modules</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/payroll/index.php" class="btn btn-light border text-start d-flex align-items-center justify-content-between p-2 rounded-3">
                            <span class="small fw-semibold"><i class="bi bi-wallet2 text-success me-2"></i>Corporate Payroll</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/leaves/index.php" class="btn btn-light border text-start d-flex align-items-center justify-content-between p-2 rounded-3">
                            <span class="small fw-semibold"><i class="bi bi-calendar2-check text-warning me-2"></i>Leave Portal</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/attendance/index.php" class="btn btn-light border text-start d-flex align-items-center justify-content-between p-2 rounded-3">
                            <span class="small fw-semibold"><i class="bi bi-clock-history text-info me-2"></i>Daily Attendance</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/departments/index.php" class="btn btn-light border text-start d-flex align-items-center justify-content-between p-2 rounded-3">
                            <span class="small fw-semibold"><i class="bi bi-diagram-3 text-primary me-2"></i>Departments Master</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/notices/index.php" class="btn btn-light border text-start d-flex align-items-center justify-content-between p-2 rounded-3">
                            <span class="small fw-semibold"><i class="bi bi-megaphone text-danger me-2"></i>Notice Board</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </main>

<script>
// Live Real-Time Digital Clock for Punch Widget
function updateLiveClock() {
    const el = document.getElementById('liveClockDisplay');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
setInterval(updateLiveClock, 1000);
updateLiveClock();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
