<?php
/**
 * Submit Leave Application Form
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = getDBConnection();
$pageTitle = 'Apply for Leave';

$errors = [];
$leaveTypeId = $_POST['leave_type_id'] ?? '';
$startDate = $_POST['start_date'] ?? date('Y-m-d');
$endDate = $_POST['end_date'] ?? date('Y-m-d');
$reason = trim($_POST['reason'] ?? '');
$targetEmployeeId = (hasRole('Admin') || hasRole('HR Manager')) ? ($_POST['target_employee_id'] ?? $currentUser['id']) : $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforceCsrf();

    if (empty($leaveTypeId)) {
        $errors['leave_type_id'] = 'Please select a leave type.';
    }
    if (empty($startDate) || empty($endDate)) {
        $errors['dates'] = 'Both start date and end date are required.';
    } elseif (strtotime($startDate) > strtotime($endDate)) {
        $errors['dates'] = 'Start date cannot be after end date.';
    }
    if (empty($reason)) {
        $errors['reason'] = 'Please provide a justification or reason for this leave request.';
    }

    if (empty($errors)) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        // Include both boundary days (+1)
        $totalDays = $start->diff($end)->days + 1;

        $insertStmt = $db->prepare("
            INSERT INTO leaves (employee_id, leave_type_id, start_date, end_date, total_days, reason, status)
            VALUES (:emp_id, :type_id, :start_date, :end_date, :total_days, :reason, 'Pending')
        ");
        $insertStmt->execute([
            'emp_id'      => (int)$targetEmployeeId,
            'type_id'     => (int)$leaveTypeId,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'total_days'  => $totalDays,
            'reason'      => $reason
        ]);

        setFlash('success', "Leave application for $totalDays day(s) submitted successfully. Pending manager review.");
        redirect('leaves/index.php');
    }
}

// Fetch available leave types
$leaveTypes = $db->query("SELECT * FROM leave_types ORDER BY id ASC")->fetchAll();
// Fetch employees if admin/HR
$employees = (hasRole('Admin') || hasRole('HR Manager')) ? $db->query("SELECT id, employee_id, first_name, last_name, department FROM employees WHERE status = 'Active' ORDER BY first_name ASC")->fetchAll() : [];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <?php require_once __DIR__ . '/../includes/flash.php'; ?>

        <div class="mb-4">
            <a href="<?= BASE_URL ?>/leaves/index.php" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left"></i> Back to Leave Portal
            </a>
            <h4 class="fw-bold mb-1"><i class="bi bi-calendar-plus text-primary me-2"></i>Apply for Leave of Absence</h4>
            <p class="text-muted small">Submit your time-off request for departmental review and HR authorization.</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0">Leave Request Form</h6>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger small py-2">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">

                    <?php if (hasRole('Admin') || hasRole('HR Manager')): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Applicant Employee</label>
                        <select name="target_employee_id" class="form-select">
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= (int)$emp['id'] ?>" <?= ((int)$targetEmployeeId === (int)$emp['id']) ? 'selected' : '' ?>>
                                    <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['employee_id']) ?> - <?= e($emp['department']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">As an administrator, you can log leaves on behalf of team members.</small>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Leave Category / Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <?php foreach ($leaveTypes as $lt): ?>
                            <div class="col-12 col-md-6">
                                <label class="card h-100 p-3 border rounded-3 cursor-pointer hover-shadow" style="cursor: pointer;">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="radio" name="leave_type_id" value="<?= (int)$lt['id'] ?>" <?= ($leaveTypeId == $lt['id']) ? 'checked' : '' ?> required class="form-check-input mt-0">
                                        <div>
                                            <div class="fw-bold small text-dark"><?= e($lt['name']) ?></div>
                                            <small class="text-muted"><?= e($lt['days_allowed']) ?> days allowed &bull; <?= e($lt['description']) ?></small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="startDate" class="form-control" value="<?= e($startDate) ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="endDate" class="form-control" value="<?= e($endDate) ?>" required>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted d-flex align-items-center gap-2 py-2 mb-3">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <span>Estimated Duration: <strong id="calculatedDaysBadge" class="text-primary">1 day</strong> (working calendar duration)</span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Reason / Remarks <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="3" class="form-control" placeholder="Please elaborate briefly on the purpose of your leave..." required><?= e($reason) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold d-flex align-items-center gap-2">
                            <i class="bi bi-send-fill"></i> Submit Leave Request
                        </button>
                        <a href="<?= BASE_URL ?>/leaves/index.php" class="btn btn-light border px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const startInput = document.getElementById('startDate');
    const endInput = document.getElementById('endDate');
    const badge = document.getElementById('calculatedDaysBadge');

    function updateDays() {
        if (startInput.value && endInput.value) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            if (end >= start) {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                badge.textContent = diffDays + ' day' + (diffDays > 1 ? 's' : '');
            } else {
                badge.textContent = 'Invalid date range';
            }
        }
    }

    startInput.addEventListener('change', updateDays);
    endInput.addEventListener('change', updateDays);
    updateDays();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
