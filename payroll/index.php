<?php
/**
 * Monthly Payroll Registry & Compensation History
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = getDBConnection();
$pageTitle = 'Payroll & Compensation';

$canManagePayroll = hasRole('Admin') || hasRole('HR Manager') || hasPermission('manage_payroll');

// Month filter
$availableMonths = $db->query("SELECT DISTINCT month FROM payroll ORDER BY month DESC")->fetchAll(PDO::FETCH_COLUMN);
$currentMonth = date('Y-m');
$selectedMonth = $_GET['month'] ?? (!empty($availableMonths[0]) ? $availableMonths[0] : $currentMonth);

// Build query
$where = ['p.month = :month'];
$params = ['month' => $selectedMonth];

// If regular staff, only show their own payroll
if (!$canManagePayroll) {
    $where[] = 'p.employee_id = :current_emp_id';
    $params['current_emp_id'] = $currentUser['id'];
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT p.*, e.first_name, e.last_name, e.employee_id as emp_code, e.department, e.designation
    FROM payroll p
    INNER JOIN employees e ON p.employee_id = e.id
    $whereClause
    ORDER BY p.net_salary DESC
");
$stmt->execute($params);
$payrollList = $stmt->fetchAll();

// Month summary statistics
$statQuery = $canManagePayroll ? "WHERE month = :month" : "WHERE month = :month AND employee_id = " . (int)$currentUser['id'];
$statStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_slips,
        COALESCE(SUM(basic_salary), 0) as total_basic,
        COALESCE(SUM(allowances), 0) as total_allowances,
        COALESCE(SUM(deductions), 0) as total_deductions,
        COALESCE(SUM(net_salary), 0) as total_disbursed,
        COALESCE(AVG(net_salary), 0) as avg_net
    FROM payroll
    $statQuery
");
$statStmt->execute(['month' => $selectedMonth]);
$stats = $statStmt->fetch();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <?php require_once __DIR__ . '/../includes/flash.php'; ?>

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-wallet2 text-success me-2"></i>Corporate Payroll & Compensation</h4>
                <p class="text-muted small mb-0">Monthly remuneration cycles, electronic payslip issuance, and tax withholding ledger.</p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($canManagePayroll): ?>
                <a href="<?= BASE_URL ?>/payroll/generate.php" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill"></i> Run / Process Payroll
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Total Net Disbursed</div>
                            <h3 class="fw-bold mb-0 text-success"><?= formatCurrency($stats['total_disbursed']) ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Issued Payslips</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= (int)$stats['total_slips'] ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Gross Allowances</div>
                            <h3 class="fw-bold mb-0 text-info"><?= formatCurrency($stats['total_allowances']) ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="bi bi-arrow-up-right-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Tax & Deductions</div>
                            <h3 class="fw-bold mb-0 text-danger"><?= formatCurrency($stats['total_deductions']) ?></h3>
                        </div>
                        <div class="stat-icon bg-danger-subtle text-danger">
                            <i class="bi bi-arrow-down-left-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Month Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="" class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label small fw-semibold text-secondary mb-0 text-nowrap">Pay Period Month:</label>
                        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 220px;">
                            <?php if (!in_array($currentMonth, $availableMonths, true)): ?>
                                <option value="<?= e($currentMonth) ?>" <?= ($selectedMonth === $currentMonth) ? 'selected' : '' ?>>
                                    <?= date('F Y', strtotime($currentMonth . '-01')) ?> (Current)
                                </option>
                            <?php endif; ?>
                            <?php foreach ($availableMonths as $m): ?>
                                <option value="<?= e($m) ?>" <?= ($selectedMonth === $m) ? 'selected' : '' ?>>
                                    <?= date('F Y', strtotime($m . '-01')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-muted small">
                        Showing salary disbursements for <strong><?= date('F Y', strtotime($selectedMonth . '-01')) ?></strong>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payroll Records Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Salary Register & Payment Ledger</h6>
                <span class="badge bg-light text-dark border"><?= count($payrollList) ?> Employees</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase fw-semibold">
                        <tr>
                            <th>Staff Member</th>
                            <th>Department</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status & Method</th>
                            <th class="text-end">Payslip</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payrollList)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-wallet2 fs-2 d-block mb-2 text-secondary"></i>
                                    No payroll records generated for <?= date('F Y', strtotime($selectedMonth . '-01')) ?>.
                                    <?php if ($canManagePayroll): ?>
                                        <div class="mt-2">
                                            <a href="generate.php" class="btn btn-sm btn-primary">Process Month Payroll Now</a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payrollList as $p): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle-sm bg-primary text-white fw-bold">
                                                <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= e($p['emp_code']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border"><?= e($p['department']) ?></span></td>
                                    <td class="font-monospace small"><?= formatCurrency($p['basic_salary']) ?></td>
                                    <td class="font-monospace small text-success">+<?= formatCurrency($p['allowances']) ?></td>
                                    <td class="font-monospace small text-danger">&minus;<?= formatCurrency($p['deductions']) ?></td>
                                    <td>
                                        <span class="fw-bold text-success font-monospace fs-6"><?= formatCurrency($p['net_salary']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i><?= e($p['status']) ?>
                                        </span>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;"><?= e($p['payment_method']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/payroll/payslip.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-file-earmark-text"></i> View Slip
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
