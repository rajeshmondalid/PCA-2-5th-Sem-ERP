<?php
/**
 * Department Master Management
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = getDBConnection();
$pageTitle = 'Department Master';

$canManageDept = hasRole('Admin') || hasRole('HR Manager') || hasPermission('manage_departments');

// Handle Department Creation via Modal / Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_department']) && $canManageDept) {
    enforceCsrf();
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $description = trim($_POST['description'] ?? '');
    $headId = !empty($_POST['head_id']) ? (int)$_POST['head_id'] : null;
    $budget = (float)($_POST['budget'] ?? 0.00);
    $status = in_array($_POST['status'] ?? '', ['Active', 'Inactive']) ? $_POST['status'] : 'Active';

    if (empty($name) || empty($code)) {
        setFlash('danger', 'Department name and department code are required.');
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO departments (name, code, description, head_id, budget, status)
                VALUES (:name, :code, :desc, :head_id, :budget, :status)
            ");
            $stmt->execute([
                'name'    => $name,
                'code'    => $code,
                'desc'    => $description,
                'head_id' => $headId,
                'budget'  => $budget,
                'status'  => $status
            ]);
            setFlash('success', "Department '{$name}' created successfully.");
            redirect('departments/index.php');
        } catch (PDOException $e) {
            setFlash('danger', 'Error creating department: Name or Code may already exist.');
        }
    }
}

// Fetch all departments with head name & employee count
$deptQuery = "
    SELECT d.*, 
           CONCAT(e.first_name, ' ', e.last_name) as head_name,
           e.employee_id as head_code,
           COUNT(emp.id) as staff_count
    FROM departments d
    LEFT JOIN employees e ON d.head_id = e.id
    LEFT JOIN employees emp ON d.name = emp.department
    GROUP BY d.id
    ORDER BY d.id ASC
";
$departments = $db->query($deptQuery)->fetchAll();

// Total Budget across all departments
$totalBudget = array_sum(array_column($departments, 'budget'));
$activeDeptCount = count(array_filter($departments, fn($d) => $d['status'] === 'Active'));

// Active employees for Department Head selector
$employees = $db->query("SELECT id, employee_id, first_name, last_name, department FROM employees WHERE status = 'Active' ORDER BY first_name ASC")->fetchAll();

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
                <h4 class="fw-bold mb-1"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Department Master Directory</h4>
                <p class="text-muted small mb-0">Organizational units, cost centers, leadership assignments, and headcount capacities.</p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($canManageDept): ?>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newDeptModal">
                    <i class="bi bi-plus-circle-fill"></i> Add Department
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Total Departments</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= count($departments) ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Active Cost Centers</div>
                            <h3 class="fw-bold mb-0 text-success"><?= $activeDeptCount ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Operational Budget</div>
                            <h3 class="fw-bold mb-0 text-info"><?= formatCurrency($totalBudget) ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Avg Headcount</div>
                            <h3 class="fw-bold mb-0 text-warning">
                                <?= count($departments) ? round(array_sum(array_column($departments, 'staff_count')) / count($departments), 1) : 0 ?>
                            </h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departments Grid Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Organizational Units</h6>
                <span class="badge bg-light text-dark border"><?= count($departments) ?> Records</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase fw-semibold">
                        <tr>
                            <th>Code</th>
                            <th>Department Name</th>
                            <th>Department Head (Lead)</th>
                            <th>Staff Headcount</th>
                            <th>Allocated Budget</th>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                        <?= e($d['code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($d['name']) ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($d['head_name'])): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle-sm bg-secondary text-white fw-semibold" style="font-size: 0.72rem;">
                                                <?= strtoupper(substr($d['head_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark small"><?= e($d['head_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= e($d['head_code']) ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">Vacant / Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-person-fill text-secondary me-1"></i><?= (int)$d['staff_count'] ?> Staff
                                    </span>
                                </td>
                                <td class="font-monospace fw-semibold text-dark">
                                    <?= formatCurrency($d['budget']) ?>
                                </td>
                                <td>
                                    <span class="badge-status badge-status-<?= strtolower($d['status']) ?>">
                                        <span class="badge-dot"></span>
                                        <?= e($d['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="<?= e($d['description']) ?>">
                                        <?= e($d['description'] ?? 'No description provided') ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<!-- New Department Modal -->
<?php if ($canManageDept): ?>
<div class="modal fade" id="newDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3 me-2 text-primary"></i>Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                <input type="hidden" name="save_department" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Department Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Quality Assurance & Testing" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Department Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. QAT" maxlength="10" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Annual Budget (₹)</label>
                            <input type="number" step="0.01" name="budget" class="form-control" value="100000.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Department Head (Lead)</label>
                        <select name="head_id" class="form-select">
                            <option value="">Select Department Head (Optional)...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= (int)$emp['id'] ?>">
                                    <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['employee_id']) ?> - <?= e($emp['department']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Description & Purpose</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief mandate and scope of this business unit..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
