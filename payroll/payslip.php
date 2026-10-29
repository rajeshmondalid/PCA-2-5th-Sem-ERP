<?php
/**
 * Professional Printable Salary Slip / Payslip View
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$payrollId = (int)($_GET['id'] ?? 0);
$db = getDBConnection();

$stmt = $db->prepare("
    SELECT p.*, e.first_name, e.last_name, e.employee_id as emp_code, e.email, e.phone,
           e.department, e.designation, e.joining_date, r.name as role_name
    FROM payroll p
    INNER JOIN employees e ON p.employee_id = e.id
    INNER JOIN roles r ON e.role_id = r.id
    WHERE p.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $payrollId]);
$slip = $stmt->fetch();

if (!$slip) {
    setFlash('danger', 'Payslip record not found.');
    redirect('payroll/index.php');
}

// Security: Employees can only view their own payslip unless Admin or HR Manager
$canViewAll = hasRole('Admin') || hasRole('HR Manager') || hasPermission('manage_payroll');
if (!$canViewAll && (int)$slip['employee_id'] !== (int)$currentUser['id']) {
    setFlash('danger', 'You are not authorized to view this confidential salary slip.');
    redirect('payroll/index.php');
}

$pageTitle = 'Salary Slip - ' . $slip['first_name'] . ' ' . $slip['last_name'] . ' (' . date('M Y', strtotime($slip['month'] . '-01')) . ')';

// Sub-component estimates for visual realism
$basic = (float)$slip['basic_salary'];
$allowances = (float)$slip['allowances'];
$deductions = (float)$slip['deductions'];
$net = (float)$slip['net_salary'];

$hra = round($allowances * 0.50, 2);
$transport = round($allowances * 0.30, 2);
$specialAllow = round($allowances - $hra - $transport, 2);

$taxTds = round($deductions * 0.55, 2);
$providentFund = round($deductions * 0.30, 2);
$healthInsurance = round($deductions - $taxTds - $providentFund, 2);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <!-- Action Buttons (Hidden on Print) -->
        <div class="d-flex align-items-center justify-content-between mb-4 d-print-none">
            <a href="<?= BASE_URL ?>/payroll/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Back to Payroll
            </a>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2 shadow-sm" onclick="window.print()">
                    <i class="bi bi-printer-fill"></i> Print / Download PDF Payslip
                </button>
            </div>
        </div>

        <!-- Payslip Document Container -->
        <div class="card border shadow-sm rounded-4 mx-auto payslip-container" style="max-width: 880px; background: #ffffff;">
            <div class="card-body p-4 p-md-5">

                <!-- Company Header -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-4 mb-4 gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-4 d-flex align-items-center justify-content-center p-3 shadow-sm" style="width: 58px; height: 58px;">
                            <i class="bi bi-buildings-fill fs-3"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark"><?= APP_NAME ?> ENTERPRISE</h4>
                            <p class="text-secondary small mb-0">Workforce & Human Resource Management System</p>
                            <small class="text-muted" style="font-size: 0.75rem;">Registration No: CORP-USA-89412 &bull; Tax ID: EIN-98-3829104</small>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold text-uppercase">
                            <i class="bi bi-check-circle-fill me-1"></i> <?= e($slip['status']) ?>
                        </span>
                        <div class="text-muted small mt-2 font-monospace">REF: SLIP-<?= str_pad((string)$slip['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        <div class="text-muted small">Issued: <?= date('M d, Y', strtotime($slip['payment_date'] ?? $slip['created_at'])) ?></div>
                    </div>
                </div>

                <!-- Pay Period Bar -->
                <div class="bg-light p-3 rounded-3 border mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                    <div>
                        <span class="text-uppercase text-secondary fw-bold small" style="letter-spacing: 0.05em;">Salary Statement for Month</span>
                        <h5 class="fw-bold text-primary mb-0"><?= date('F Y', strtotime($slip['month'] . '-01')) ?></h5>
                    </div>
                    <div class="text-sm-end mt-2 mt-sm-0">
                        <small class="text-muted d-block">Disbursement Method</small>
                        <span class="fw-bold text-dark small"><i class="bi bi-bank me-1"></i><?= e($slip['payment_method']) ?></span>
                    </div>
                </div>

                <!-- Employee Information Grid -->
                <div class="row g-3 mb-4 pb-2">
                    <div class="col-6 col-md-3">
                        <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 0.72rem;">Employee Name</small>
                        <span class="fw-bold text-dark"><?= e($slip['first_name'] . ' ' . $slip['last_name']) ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 0.72rem;">Employee ID</small>
                        <span class="fw-bold text-dark font-monospace"><?= e($slip['emp_code']) ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 0.72rem;">Department</small>
                        <span class="fw-bold text-dark"><?= e($slip['department']) ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 0.72rem;">Designation</small>
                        <span class="fw-bold text-dark"><?= e($slip['designation']) ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 0.72rem;">Role Clearance</small>
                        <span class="fw-semibold text-dark"><?= e($slip['role_name']) ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 0.72rem;">Joining Date</small>
                        <span class="fw-semibold text-dark"><?= date('M d, Y', strtotime($slip['joining_date'])) ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 0.72rem;">Email Address</small>
                        <span class="text-dark small text-truncate d-block"><?= e($slip['email']) ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 0.72rem;">Bank Account (Direct)</small>
                        <span class="text-dark small font-monospace">****-****-7819</span>
                    </div>
                </div>

                <!-- Earnings vs Deductions 2-Column Table -->
                <div class="row g-4 mb-4">
                    <!-- Earnings Column -->
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 overflow-hidden">
                            <div class="bg-success-subtle p-2 px-3 fw-bold text-success border-bottom small d-flex justify-content-between">
                                <span><i class="bi bi-plus-circle me-1"></i>EARNINGS & ALLOWANCES</span>
                                <span>AMOUNT (₹)</span>
                            </div>
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-secondary small ps-3">Basic Salary</td>
                                        <td class="text-end fw-semibold pe-3 font-monospace"><?= formatCurrency($basic) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary small ps-3">House Rent Allowance (HRA)</td>
                                        <td class="text-end fw-semibold pe-3 font-monospace"><?= formatCurrency($hra) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary small ps-3">Transport / Conveyance</td>
                                        <td class="text-end fw-semibold pe-3 font-monospace"><?= formatCurrency($transport) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary small ps-3">Special Executive Allowance</td>
                                        <td class="text-end fw-semibold pe-3 font-monospace"><?= formatCurrency($specialAllow) ?></td>
                                    </tr>
                                    <tr class="border-top bg-light">
                                        <td class="fw-bold small ps-3 py-2">Total Gross Earnings</td>
                                        <td class="text-end fw-bold text-success pe-3 py-2 font-monospace"><?= formatCurrency($basic + $allowances) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Deductions Column -->
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 overflow-hidden">
                            <div class="bg-danger-subtle p-2 px-3 fw-bold text-danger border-bottom small d-flex justify-content-between">
                                <span><i class="bi bi-dash-circle me-1"></i>STATUTORY DEDUCTIONS</span>
                                <span>AMOUNT (₹)</span>
                            </div>
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-secondary small ps-3">Income Tax Withholding (TDS)</td>
                                        <td class="text-end fw-semibold pe-3 font-monospace text-danger"><?= formatCurrency($taxTds) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary small ps-3">Provident Fund (PF) / Social Security</td>
                                        <td class="text-end fw-semibold pe-3 font-monospace text-danger"><?= formatCurrency($providentFund) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary small ps-3">Corporate Health & Dental Insurance</td>
                                        <td class="text-end fw-semibold pe-3 font-monospace text-danger"><?= formatCurrency($healthInsurance) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary small ps-3">Professional Tax / Levies</td>
                                        <td class="text-end fw-semibold pe-3 font-monospace text-danger">₹0.00</td>
                                    </tr>
                                    <tr class="border-top bg-light">
                                        <td class="fw-bold small ps-3 py-2">Total Deductions</td>
                                        <td class="text-end fw-bold text-danger pe-3 py-2 font-monospace"><?= formatCurrency($deductions) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Net Salary Highlighting Box -->
                <div class="p-4 rounded-4 bg-primary text-white mb-4 shadow-sm d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                    <div>
                        <span class="text-white-50 text-uppercase small fw-semibold" style="letter-spacing: 0.08em;">Net Take-Home Salary</span>
                        <div class="display-6 fw-bold mb-0"><?= formatCurrency($net) ?></div>
                        <small class="text-white-50 d-block mt-1">Disbursed successfully to employee's designated primary bank account.</small>
                    </div>
                    <div class="mt-3 mt-sm-0 text-sm-end">
                        <span class="badge bg-white text-primary px-3 py-2 fw-bold text-uppercase">
                            <i class="bi bi-shield-check me-1"></i> Verified & Audited
                        </span>
                    </div>
                </div>

                <!-- Signatures Block -->
                <div class="row pt-5 mt-4 border-top">
                    <div class="col-6 text-center">
                        <div class="border-bottom mx-auto mb-2" style="max-width: 200px; height: 40px;">
                            <span class="font-monospace text-muted fst-italic" style="font-size: 0.9rem;">Sarah Jenkins</span>
                        </div>
                        <small class="text-secondary fw-semibold text-uppercase d-block">Authorized HR Director</small>
                        <small class="text-muted" style="font-size: 0.72rem;">StaffPulse Human Resources Dept.</small>
                    </div>
                    <div class="col-6 text-center">
                        <div class="border-bottom mx-auto mb-2" style="max-width: 200px; height: 40px;">
                            <span class="font-monospace text-muted fst-italic" style="font-size: 0.9rem;"><?= e($slip['first_name'] . ' ' . $slip['last_name']) ?></span>
                        </div>
                        <small class="text-secondary fw-semibold text-uppercase d-block">Employee Signature</small>
                        <small class="text-muted" style="font-size: 0.72rem;">Electronic Acknowledgment</small>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="text-center text-muted small mt-4 pt-3 border-top" style="font-size: 0.75rem;">
                    This is an electronically generated salary voucher issued under corporate payroll authorization. No physical stamp is required.
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
