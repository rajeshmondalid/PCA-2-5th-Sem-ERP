<?php
/**
 * Leave Management & Applications Portal
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = getDBConnection();
$pageTitle = 'Leave Management';

$tab = $_GET['tab'] ?? 'all';
$canManageLeaves = hasRole('Admin') || hasRole('HR Manager') || hasPermission('manage_leaves');

// Query leaves
$where = [];
$params = [];

if ($tab === 'pending') {
    $where[] = "l.status = 'Pending'";
} elseif ($tab === 'approved') {
    $where[] = "l.status = 'Approved'";
} elseif ($tab === 'rejected') {
    $where[] = "l.status = 'Rejected'";
}

// If regular employee without manage permission, only show self leaves
if (!$canManageLeaves) {
    $where[] = "l.employee_id = :current_user_id";
    $params['current_user_id'] = $currentUser['id'];
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT l.*, lt.name as leave_type_name, lt.color as leave_type_color,
           e.first_name, e.last_name, e.employee_id as emp_code, e.department,
           u.first_name as reviewer_first_name, u.last_name as reviewer_last_name
    FROM leaves l
    INNER JOIN leave_types lt ON l.leave_type_id = lt.id
    INNER JOIN employees e ON l.employee_id = e.id
    LEFT JOIN employees u ON l.approved_by = u.id
    $whereClause
    ORDER BY l.id DESC
");
$stmt->execute($params);
$leavesList = $stmt->fetchAll();

// Counts for tabs
$countQuery = $canManageLeaves ? "" : "WHERE employee_id = " . (int)$currentUser['id'];
$counts = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as count_pending,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as count_approved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as count_rejected
    FROM leaves
    $countQuery
")->fetch();

// Fetch leave types & entitlement overview
$leaveTypes = $db->query("SELECT * FROM leave_types ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <?php require_once __DIR__ . '/../includes/flash.php'; ?>

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-calendar2-check text-primary me-2"></i>Leave & Absence Management</h4>
                <p class="text-muted small mb-0">Track time-off allowances, vacation approvals, and medical leaves across teams.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/leaves/apply.php" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill"></i> Apply for Leave
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">All Applications</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= (int)$counts['total'] ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-calendar3"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Pending Review</div>
                            <h3 class="fw-bold mb-0 text-warning"><?= (int)$counts['count_pending'] ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-hourglass-top"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Approved Leaves</div>
                            <h3 class="fw-bold mb-0 text-success"><?= (int)$counts['count_approved'] ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Declined / Rejected</div>
                            <h3 class="fw-bold mb-0 text-danger"><?= (int)$counts['count_rejected'] ?></h3>
                        </div>
                        <div class="stat-icon bg-danger-subtle text-danger">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4 gap-2">
            <li class="nav-item">
                <a class="nav-link <?= ($tab === 'all') ? 'active' : 'bg-white border text-secondary' ?>" href="index.php?tab=all">
                    All Requests <span class="badge bg-secondary ms-1"><?= (int)$counts['total'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($tab === 'pending') ? 'active bg-warning text-dark' : 'bg-white border text-secondary' ?>" href="index.php?tab=pending">
                    Pending Approval <span class="badge bg-dark ms-1"><?= (int)$counts['count_pending'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($tab === 'approved') ? 'active bg-success' : 'bg-white border text-secondary' ?>" href="index.php?tab=approved">
                    Approved <span class="badge bg-light text-dark ms-1"><?= (int)$counts['count_approved'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($tab === 'rejected') ? 'active bg-danger' : 'bg-white border text-secondary' ?>" href="index.php?tab=rejected">
                    Rejected <span class="badge bg-light text-dark ms-1"><?= (int)$counts['count_rejected'] ?></span>
                </a>
            </li>
        </ul>

        <!-- Leave Requests Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase fw-semibold">
                        <tr>
                            <th>Applicant</th>
                            <th>Leave Category</th>
                            <th>Dates & Duration</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Reviewed By</th>
                            <?php if ($canManageLeaves): ?><th class="text-end">Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leavesList)): ?>
                            <tr>
                                <td colspan="<?= $canManageLeaves ? '7' : '6' ?>" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No leave applications found in this category.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leavesList as $l): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle-sm bg-primary text-white fw-bold">
                                                <?= strtoupper(substr($l['first_name'], 0, 1) . substr($l['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark"><?= e($l['first_name'] . ' ' . $l['last_name']) ?></div>
                                                <small class="text-muted"><?= e($l['emp_code']) ?> &bull; <?= e($l['department']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= e($l['leave_type_color']) ?>-subtle text-<?= e($l['leave_type_color']) ?> border border-<?= e($l['leave_type_color']) ?>-subtle px-2 py-1">
                                            <?= e($l['leave_type_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small text-nowrap">
                                            <?= date('M d', strtotime($l['start_date'])) ?> &rarr; <?= date('M d, Y', strtotime($l['end_date'])) ?>
                                        </div>
                                        <span class="badge bg-light text-secondary border font-monospace mt-1"><?= (int)$l['total_days'] ?>d duration</span>
                                    </td>
                                    <td>
                                        <div class="text-truncate text-secondary small" style="max-width: 160px;" title="<?= e($l['reason']) ?>">
                                            <?= e($l['reason']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($l['status'] === 'Approved'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 text-nowrap">
                                                <i class="bi bi-check-circle-fill me-1"></i>Approved
                                            </span>
                                        <?php elseif ($l['status'] === 'Rejected'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-nowrap">
                                                <i class="bi bi-x-circle-fill me-1"></i>Rejected
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 text-nowrap">
                                                <i class="bi bi-hourglass-split me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($l['reviewer_first_name'])): ?>
                                            <div class="small fw-semibold text-dark text-nowrap"><?= e($l['reviewer_first_name'] . ' ' . $l['reviewer_last_name']) ?></div>
                                            <?php if (!empty($l['admin_notes'])): ?>
                                                <small class="text-muted d-block text-truncate" style="max-width: 120px;" title="<?= e($l['admin_notes']) ?>"><?= e($l['admin_notes']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($canManageLeaves): ?>
                                    <td class="text-end text-nowrap">
                                        <?php if ($l['status'] === 'Pending'): ?>
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 leave-action-trigger" 
                                                        data-id="<?= (int)$l['id'] ?>" 
                                                        data-name="<?= e($l['first_name'] . ' ' . $l['last_name']) ?>" 
                                                        data-action="Approved">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 leave-action-trigger" 
                                                        data-id="<?= (int)$l['id'] ?>" 
                                                        data-name="<?= e($l['first_name'] . ' ' . $l['last_name']) ?>" 
                                                        data-action="Rejected">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">Decided</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leave Types Allowance Legend -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Corporate Leave Entitlement & Policy</h6>
            <div class="row g-3">
                <?php foreach ($leaveTypes as $t): ?>
                    <div class="col-12 col-md-4 col-lg">
                        <div class="p-3 rounded-3 border bg-light">
                            <span class="badge bg-<?= e($t['color']) ?>-subtle text-<?= e($t['color']) ?> mb-2"><?= e($t['name']) ?></span>
                            <div class="h4 fw-bold mb-1 text-dark"><?= (int)$t['days_allowed'] ?> <small class="text-muted fs-6">days/yr</small></div>
                            <small class="text-muted d-block"><?= e($t['description']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

<!-- Modal for Leave Review / Decision -->
<?php if ($canManageLeaves): ?>
<div class="modal fade" id="leaveActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold" id="leaveModalTitle">Review Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/leaves/action.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                <input type="hidden" name="leave_id" id="modalLeaveId" value="">
                <input type="hidden" name="action" id="modalLeaveAction" value="">
                <div class="modal-body p-4">
                    <p class="text-muted small">
                        Are you sure you want to mark the leave request for <strong id="modalApplicantName" class="text-dark"></strong> as <strong id="modalActionBadge"></strong>?
                    </p>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Reviewer Comments / Authorization Notes</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Optional comments for applicant..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Confirm Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('leaveActionModal');
    const bsModal = new bootstrap.Modal(modalEl);

    document.querySelectorAll('.leave-action-trigger').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const action = this.getAttribute('data-action');

            document.getElementById('modalLeaveId').value = id;
            document.getElementById('modalLeaveAction').value = action;
            document.getElementById('modalApplicantName').textContent = name;
            
            const badge = document.getElementById('modalActionBadge');
            badge.textContent = action;
            badge.className = (action === 'Approved') ? 'text-success' : 'text-danger';

            const submitBtn = document.getElementById('modalSubmitBtn');
            submitBtn.textContent = 'Confirm ' + action;
            submitBtn.className = (action === 'Approved') ? 'btn btn-success' : 'btn btn-danger';

            bsModal.show();
        });
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
