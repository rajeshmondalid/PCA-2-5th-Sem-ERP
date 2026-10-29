<?php
/**
 * Roles & Permissions Management Directory
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requirePermission('view_roles');

$pageTitle = 'Role-Based Access Control (RBAC)';
$db = getDBConnection();

// Fetch all roles with employee count and permission count
$sql = "
    SELECT r.*,
           COUNT(DISTINCT e.id) as employee_count,
           COUNT(DISTINCT rp.permission_id) as permission_count
    FROM roles r
    LEFT JOIN employees e ON r.id = e.role_id
    LEFT JOIN role_permissions rp ON r.id = rp.role_id
    GROUP BY r.id
    ORDER BY r.id ASC
";
$roles = $db->query($sql)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <?php require_once __DIR__ . '/../includes/flash.php'; ?>

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Roles & Permissions (RBAC)</h4>
                <p class="text-muted small mb-0">Configure role privilege matrices and granular page/action capabilities.</p>
            </div>
            <?php if (hasPermission('create_roles')): ?>
            <a href="<?= BASE_URL ?>/roles/create.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-shield-plus"></i> Create New Role
            </a>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Configured Roles (<?= count($roles) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted text-uppercase">
                        <tr>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Assigned Workforce</th>
                            <th>Active Permissions</th>
                            <th>Type</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-primary-subtle text-primary" style="width: 36px; height: 36px; font-size: 1rem;">
                                        <i class="bi bi-shield-lock-fill"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($role['name']) ?></div>
                                        <small class="text-muted">ID: #<?= (int)$role['id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-secondary small" style="max-width: 300px;"><?= e($role['description'] ?: 'No description provided.') ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/employees/index.php?role_id=<?= $role['id'] ?>" class="badge bg-light text-dark border text-decoration-none">
                                    <i class="bi bi-people me-1"></i> <?= $role['employee_count'] ?> Employees
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="bi bi-key me-1"></i> <?= $role['permission_count'] ?> Granted
                                </span>
                            </td>
                            <td>
                                <?php if ($role['is_system']): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-lock-fill me-1"></i> System Role
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">Custom Role</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if (hasPermission('edit_roles')): ?>
                                    <a href="<?= BASE_URL ?>/roles/edit.php?id=<?= $role['id'] ?>" class="btn btn-light border text-primary" title="Edit Role & Permissions">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <?php endif; ?>

                                    <?php if (hasPermission('delete_roles')): ?>
                                        <?php if ($role['is_system']): ?>
                                            <button type="button" class="btn btn-light border text-muted" disabled title="System roles cannot be deleted.">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php elseif ($role['employee_count'] > 0): ?>
                                            <button type="button" class="btn btn-light border text-muted" disabled title="Cannot delete: <?= $role['employee_count'] ?> employee(s) assigned. Reassign first.">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-light border text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteConfirmModal" 
                                                    data-id="<?= $role['id'] ?>" 
                                                    data-name="Role '<?= e($role['name']) ?>'" 
                                                    data-action="<?= BASE_URL ?>/roles/delete.php" 
                                                    title="Delete Role">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
