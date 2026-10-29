<?php
/**
 * Payroll Generation & Salary Adjustment
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

if (!hasRole('Admin') && !hasRole('HR Manager') && !hasPermission('manage_payroll')) {
    setFlash('danger', 'You do not have permission to manage corporate payroll.');
    redirect('payroll/index.php');
}

$currentUser = getCurrentUser();
$db = getDBConnection();
$pageTitle = 'Generate Monthly Payroll';

$errors = [];
$month = $_POST['month'] ?? date('Y-m');
$empId = (int)($_POST['employee_id'] ?? 0);
$basicSalary = (float)($_POST['basic_salary'] ?? 0.00);
$allowances = (float)($_POST['allowances'] ?? 0.00);
$deductions = (float)($_POST['deductions'] ?? 0.00);
$paymentMethod = $_POST['payment_method'] ?? 'Bank Transfer';
$paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
$remarks = trim($_POST['remarks'] ?? 'Regular monthly salary disbursement');

// Handle Auto-Bulk Generation for all Active Employees for selected month
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_generate'])) {
    enforceCsrf();
    $targetMonth = $_POST['bulk_month'] ?? date('Y-m');

    $activeEmployees = $db->query("SELECT id, salary FROM employees WHERE status = 'Active'")->fetchAll();
    $generatedCount = 0;

    foreach ($activeEmployees as $emp) {
        $monthlyBase = round((float)$emp['salary'] / 12, 2);
        // Standard formula: 10% allowance, 8% deduction
        $allow = round($monthlyBase * 0.10, 2);
        $deduct = round($monthlyBase * 0.08, 2);
        $net = round($monthlyBase + $allow - $deduct, 2);

        $stmt = $db->prepare("
            INSERT INTO payroll (employee_id, month, basic_salary, allowances, deductions, net_salary, payment_method, payment_date, status, remarks)
            VALUES (:emp_id, :month, :base, :allow, :deduct, :net, 'Bank Transfer', :pdate, 'Paid', 'Automated monthly payroll run')
            ON DUPLICATE KEY UPDATE 
                basic_salary = VALUES(basic_salary),
                allowances = VALUES(allowances),
                deductions = VALUES(deductions),
                net_salary = VALUES(net_salary)
        ");
        $stmt->execute([
            'emp_id' => $emp['id'],
            'month'  => $targetMonth,
            'base'   => $monthlyBase,
            'allow'  => $allow,
            'deduct' => $deduct,
            'net'    => $net,
            'pdate'  => date('Y-m-d')
        ]);
        $generatedCount++;
    }

    setFlash('success', "Bulk payroll successfully generated for $generatedCount active staff members for " . date('F Y', strtotime($targetMonth . '-01')));
    redirect('payroll/index.php?month=' . urlencode($targetMonth));
}

// Single employee payroll submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['single_payroll'])) {
    enforceCsrf();

    if ($empId <= 0) {
        $errors['emp'] = 'Please select a valid employee.';
    }
    if ($basicSalary <= 0) {
        $errors['basic'] = 'Basic salary must be greater than zero.';
    }

    if (empty($errors)) {
        $netSalary = round($basicSalary + $allowances - $deductions, 2);

        $stmt = $db->prepare("
            INSERT INTO payroll (employee_id, month, basic_salary, allowances, deductions, net_salary, payment_method, payment_date, status, remarks)
            VALUES (:emp_id, :month, :base, :allow, :deduct, :net, :method, :pdate, 'Paid', :remarks)
            ON DUPLICATE KEY UPDATE 
                basic_salary = VALUES(basic_salary),
                allowances = VALUES(allowances),
                deductions = VALUES(deductions),
                net_salary = VALUES(net_salary),
                payment_method = VALUES(payment_method),
                payment_date = VALUES(payment_date),
                remarks = VALUES(remarks)
        ");
        $stmt->execute([
            'emp_id'  => $empId,
            'month'   => $month,
            'base'    => $basicSalary,
            'allow'   => $allowances,
            'deduct'  => $deductions,
            'net'     => $netSalary,
            'method'  => $paymentMethod,
            'pdate'   => $paymentDate,
            'remarks' => $remarks
        ]);

        setFlash('success', 'Payroll slip generated and recorded successfully.');
        redirect('payroll/index.php?month=' . urlencode($month));
    }
}

// Fetch active employees with annual salaries
$employees = $db->query("SELECT id, employee_id, first_name, last_name, department, designation, salary FROM employees WHERE status = 'Active' ORDER BY first_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <?php require_once __DIR__ . '/../includes/flash.php'; ?>

        <div class="mb-4">
            <a href="<?= BASE_URL ?>/payroll/index.php" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left"></i> Back to Payroll Registry
            </a>
            <h4 class="fw-bold mb-1"><i class="bi bi-cash-stack text-success me-2"></i>Generate Monthly Salary Slip</h4>
            <p class="text-muted small">Calculate monthly base compensation, statutory deductions, and tax withholdings.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger small py-2 mb-4">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= e($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Individual Payslip Processing</h6>
                <span class="badge bg-light text-dark border">Single Record</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                    <input type="hidden" name="single_payroll" value="1">

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Target Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="empSelect" class="form-select" required>
                                <option value="">Select Employee...</option>
                                <?php foreach ($employees as $e): ?>
                                    <option value="<?= (int)$e['id'] ?>" data-annual="<?= (float)$e['salary'] ?>">
                                        <?= e($e['first_name'] . ' ' . $e['last_name']) ?> (<?= e($e['employee_id']) ?> - <?= formatCurrency($e['salary']) ?>/yr)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Pay Period Month <span class="text-danger">*</span></label>
                            <input type="month" name="month" class="form-control" value="<?= e($month) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Monthly Basic Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="basic_salary" id="basicSalary" class="form-control" value="0.00" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Total Allowances (₹)</label>
                            <input type="number" step="0.01" name="allowances" id="allowances" class="form-control" value="0.00" placeholder="HRA, Travel, Bonus">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Total Deductions (₹)</label>
                            <input type="number" step="0.01" name="deductions" id="deductions" class="form-control" value="0.00" placeholder="Tax TDS, Insurance, PF">
                        </div>
                    </div>

                    <!-- Computed Net Pay Box -->
                    <div class="p-3 rounded-3 bg-light border mb-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">Net Pay Amount:</span>
                            <div class="h3 fw-bold text-success mb-0" id="netPayDisplay">₹0.00</div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Formula: Basic + Allowances &minus; Deductions</span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Disbursement Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="Bank Transfer">Direct Bank Transfer</option>
                                <option value="Cheque">Corporate Cheque</option>
                                <option value="Direct Deposit">ACH Direct Deposit</option>
                                <option value="Cash">Cash Voucher</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Payment / Effective Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Notes & Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Regular monthly disbursement via Chase Bank">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Save & Issue Payslip
                        </button>
                        <a href="<?= BASE_URL ?>/payroll/index.php" class="btn btn-light border px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Automated Bulk Run Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 border-start border-4 border-primary">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h6 class="fw-bold mb-1"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Automated One-Click Corporate Payroll Run</h6>
                    <p class="text-muted small mb-0">Compute and disburse monthly compensation across all active employees simultaneously using their recorded annual contract salaries.</p>
                </div>
                <form method="POST" action="" onsubmit="return confirm('Generate monthly payroll for all active employees?');">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                    <input type="hidden" name="bulk_generate" value="1">
                    <input type="hidden" name="bulk_month" value="<?= date('Y-m') ?>">
                    <button type="submit" class="btn btn-dark text-nowrap d-flex align-items-center gap-2">
                        <i class="bi bi-gear-fill"></i> Run All Active Staff
                    </button>
                </form>
            </div>
        </div>
    </main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const empSelect = document.getElementById('empSelect');
    const basicInput = document.getElementById('basicSalary');
    const allowInput = document.getElementById('allowances');
    const deductInput = document.getElementById('deductions');
    const netDisplay = document.getElementById('netPayDisplay');

    function calculateNet() {
        const base = parseFloat(basicInput.value) || 0;
        const allow = parseFloat(allowInput.value) || 0;
        const deduct = parseFloat(deductInput.value) || 0;
        const net = Math.max(0, base + allow - deduct);
        netDisplay.textContent = '₹' + net.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    empSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const annual = parseFloat(opt.getAttribute('data-annual')) || 0;
        if (annual > 0) {
            const monthly = (annual / 12);
            basicInput.value = monthly.toFixed(2);
            allowInput.value = (monthly * 0.10).toFixed(2);
            deductInput.value = (monthly * 0.08).toFixed(2);
            calculateNet();
        }
    });

    basicInput.addEventListener('input', calculateNet);
    allowInput.addEventListener('input', calculateNet);
    deductInput.addEventListener('input', calculateNet);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
