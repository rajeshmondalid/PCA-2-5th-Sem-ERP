<?php
/**
 * Employee Directory - List, Search, Filter & Paginate
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requirePermission('view_employees');

$pageTitle = 'Employee Directory';
$db = getDBConnection();

// Fetch filter parameters
$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');
$roleId = trim($_GET['role_id'] ?? '');

// Pagination settings
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Build dynamic WHERE clause
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = '(e.first_name LIKE :search OR e.last_name LIKE :search OR e.employee_id LIKE :search OR e.email LIKE :search OR e.designation LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if (!empty($department)) {
    $whereConditions[] = 'e.department = :department';
    $params['department'] = $department;
}

if (!empty($status) && in_array($status, ['Active', 'Inactive'], true)) {
    $whereConditions[] = 'e.status = :status';
    $params['status'] = $status;
}

if (!empty($roleId) && is_numeric($roleId)) {
    $whereConditions[] = 'e.role_id = :role_id';
    $params['role_id'] = (int)$roleId;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Count total matching records
$countSql = "SELECT COUNT(*) FROM employees e $whereClause";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit) ?: 1;

// Fetch paginated records
$dataSql = "
    SELECT e.*, r.name as role_name
    FROM employees e
    INNER JOIN roles r ON e.role_id = r.id
    $whereClause
    ORDER BY e.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($dataSql);
foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll();

// Fetch departments for filter dropdown
$departments = $db->query("SELECT DISTINCT department FROM employees ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch roles for filter dropdown
$roles = $db->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();

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
                <h4 class="fw-bold mb-1">Employee Directory</h4>
                <p class="text-muted small mb-0">Manage workforce records, view profiles, and update role assignments.</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (hasPermission('export_employees')): ?>
                <a href="<?= BASE_URL ?>/employees/export.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export Filtered CSV
                </a>
                <?php endif; ?>
                <?php if (hasPermission('create_employees')): ?>
                <a href="<?= BASE_URL ?>/employees/create.php" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus-fill"></i> Add New Employee
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter & Search Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, ID, email, title..." value="<?= e($search) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= e($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="role_id" class="form-select">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $roleId == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="<?= BASE_URL ?>/employees/index.php" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employees Table Card -->
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">Staff Records (<?= number_format($totalRecords) ?> total)</h6>
                <span class="text-muted small">Page <?= $page ?> of <?= $totalPages ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted text-uppercase">
                        <tr>
                            <th>Employee</th>
                            <th>Contact</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle-sm bg-primary text-white fw-bold">
                                            <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <a href="<?= BASE_URL ?>/employees/view.php?id=<?= $emp['id'] ?>" class="text-decoration-none fw-semibold text-dark">
                                                <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                            </a>
                                            <div class="text-muted small">
                                                <span class="badge bg-light text-secondary border"><?= e($emp['employee_id']) ?></span> &bull; <?= e($emp['designation']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-dark"><?= e($emp['email']) ?></div>
                                    <div class="small text-muted"><?= e($emp['phone']) ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($emp['department']) ?></span></td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($emp['role_name']) ?></span></td>
                                <td class="fw-medium text-dark small"><?= formatCurrency($emp['salary']) ?></td>
                                <td>
                                    <span class="badge-status <?= $emp['status'] === 'Active' ? 'badge-status-active' : 'badge-status-inactive' ?>">
                                        <span class="badge-dot"></span> <?= e($emp['status']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= formatDate($emp['joining_date']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/employees/view.php?id=<?= $emp['id'] ?>" class="btn btn-light border" title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('edit_employees')): ?>
                                        <a href="<?= BASE_URL ?>/employees/edit.php?id=<?= $emp['id'] ?>" class="btn btn-light border text-primary" title="Edit Employee">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('delete_employees')): ?>
                                            <?php if ($emp['id'] != getCurrentUser()['id']): ?>
                                            <button type="button" class="btn btn-light border text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteConfirmModal" 
                                                    data-id="<?= $emp['id'] ?>" 
                                                    data-name="<?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['employee_id']) ?>)" 
                                                    data-action="<?= BASE_URL ?>/employees/delete.php" 
                                                    title="Delete Employee">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php else: ?>
                                            <button type="button" class="btn btn-light border text-muted" disabled title="You cannot delete yourself">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    No employee records matched your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white d-flex align-items-center justify-content-between py-3">
                <span class="small text-muted">Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> employees</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Previous Link -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                        </li>

                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Link -->
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
