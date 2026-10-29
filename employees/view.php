<?php
/**
 * Detailed Employee Profile View
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requirePermission('view_employees');

$empId = (int)($_GET['id'] ?? getCurrentUser()['id']);
$db = getDBConnection();

$stmt = $db->prepare('
    SELECT e.*, r.name as role_name
    FROM employees e
    INNER JOIN roles r ON e.role_id = r.id
    WHERE e.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $empId]);
$emp = $stmt->fetch();

if (!$emp) {
    setFlash('danger', 'Employee record not found.');
    redirect('employees/index.php');
}

$pageTitle = $emp['first_name'] . ' ' . $emp['last_name'] . ' - Profile';

// Compute tenure
$joiningDateTime = new DateTime($emp['joining_date']);
$now = new DateTime();
$interval = $joiningDateTime->diff($now);
$tenure = sprintf('%d yrs, %d mos', $interval->y, $interval->m);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="<?= BASE_URL ?>/employees/index.php" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left"></i> Back to Employee Directory
            </a>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-printer"></i> Print Profile
                </button>
                <?php if (hasPermission('edit_employees')): ?>
                <a href="<?= BASE_URL ?>/employees/edit.php?id=<?= $emp['id'] ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-pencil-square"></i> Edit Record
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Hero Card -->
        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
            <div class="bg-primary p-4 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                    <div class="avatar-circle-lg bg-white text-primary shadow">
                        <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h3 class="fw-bold mb-0"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></h3>
                            <span class="badge bg-light text-dark fw-bold"><?= e($emp['employee_id']) ?></span>
                            <span class="badge-status <?= $emp['status'] === 'Active' ? 'badge-status-active' : 'badge-status-inactive' ?> bg-white">
                                <span class="badge-dot"></span> <?= e($emp['status']) ?>
                            </span>
                        </div>
                        <p class="text-white-50 mb-1 mt-1 fs-6"><?= e($emp['designation']) ?> &bull; <?= e($emp['department']) ?></p>
                        <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25">
                            <i class="bi bi-shield-lock me-1"></i> Role: <?= e($emp['role_name']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Employment & Role Information -->
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-briefcase-fill text-primary me-2"></i>Employment Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted py-2" style="width: 40%;">Employee ID</td>
                                    <td class="fw-semibold py-2"><?= e($emp['employee_id']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Department</td>
                                    <td class="fw-semibold py-2"><span class="badge bg-light text-dark border"><?= e($emp['department']) ?></span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Designation</td>
                                    <td class="fw-semibold py-2"><?= e($emp['designation']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Assigned Role</td>
                                    <td class="fw-semibold py-2"><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($emp['role_name']) ?></span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Annual Salary</td>
                                    <td class="fw-bold text-success py-2"><?= formatCurrency($emp['salary']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Joining Date</td>
                                    <td class="fw-semibold py-2"><?= formatDate($emp['joining_date']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Tenure</td>
                                    <td class="fw-semibold py-2"><?= e($tenure) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Contact & Account Audit -->
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i>Contact & Audit Telemetry</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted py-2" style="width: 40%;">Work Email</td>
                                    <td class="fw-semibold py-2"><a href="mailto:<?= e($emp['email']) ?>"><?= e($emp['email']) ?></a></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Phone Number</td>
                                    <td class="fw-semibold py-2"><a href="tel:<?= e($emp['phone']) ?>"><?= e($emp['phone']) ?></a></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Account Status</td>
                                    <td class="py-2">
                                        <span class="badge-status <?= $emp['status'] === 'Active' ? 'badge-status-active' : 'badge-status-inactive' ?>">
                                            <span class="badge-dot"></span> <?= e($emp['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Record Created</td>
                                    <td class="fw-semibold py-2"><?= date('M d, Y H:i', strtotime($emp['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-2">Last Updated</td>
                                    <td class="fw-semibold py-2"><?= date('M d, Y H:i', strtotime($emp['updated_at'])) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
