<?php
/**
 * Create Custom Role with Granular Permissions
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requirePermission('create_roles');

$pageTitle = 'Create New Role';
$db = getDBConnection();

// Fetch all available permissions grouped by module
$allPerms = $db->query("SELECT * FROM permissions ORDER BY module ASC, id ASC")->fetchAll();
$groupedPermissions = [];
foreach ($allPerms as $p) {
    $groupedPermissions[$p['module']][] = $p;
}

$errors = [];
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$selectedPermissions = $_POST['permissions'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforceCsrf();

    if (empty(trim($name))) {
        $errors['name'] = 'Role name is required.';
    } else {
        $checkStmt = $db->prepare("SELECT id FROM roles WHERE name = :name");
        $checkStmt->execute(['name' => trim($name)]);
        if ($checkStmt->fetch()) {
            $errors['name'] = 'A role with this name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert role
            $stmt = $db->prepare("INSERT INTO roles (name, description, is_system) VALUES (:name, :description, 0)");
            $stmt->execute([
                'name'        => trim($name),
                'description' => trim($description)
            ]);
            $roleId = (int)$db->lastInsertId();

            // Insert assigned permissions
            if (!empty($selectedPermissions) && is_array($selectedPermissions)) {
                $permStmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
                foreach ($selectedPermissions as $pId) {
                    $permStmt->execute([
                        'role_id'       => $roleId,
                        'permission_id' => (int)$pId
                    ]);
                }
            }

            $db->commit();

            setFlash('success', 'Role "' . $name . '" created successfully with ' . count($selectedPermissions) . ' permissions.');
            redirect('roles/index.php');
        } catch (Exception $e) {
            $db->rollBack();
            $errors['general'] = 'Failed to create role: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/roles/index.php" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left"></i> Back to Roles Matrix
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-plus me-2 text-primary"></i>Create Role & Assign Permissions</h5>
                <small class="text-muted">Define a new workforce access role and configure module-level capabilities.</small>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger"><?= e($errors['general']) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e($name) ?>" placeholder="e.g. Operations Supervisor" required>
                            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Role Description</label>
                            <input type="text" name="description" class="form-control" value="<?= e($description) ?>" placeholder="Summarize role purpose and scope...">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-check2-square text-primary me-2"></i>Module Permissions Matrix</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleAllPermsBtn">Toggle Select All</button>
                        </div>

                        <div class="row g-4">
                            <?php foreach ($groupedPermissions as $module => $perms): ?>
                            <div class="col-12 col-md-4">
                                <div class="card h-100 border bg-light">
                                    <div class="card-header bg-white fw-bold small text-uppercase text-secondary py-2">
                                        <?= e($module) ?> Module
                                    </div>
                                    <div class="card-body p-3">
                                        <?php foreach ($perms as $p): 
                                            $checked = in_array((string)$p['id'], (array)$selectedPermissions, true) ? 'checked' : '';
                                        ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>" <?= $checked ?>>
                                            <label class="form-check-label small" for="perm_<?= $p['id'] ?>">
                                                <div class="fw-semibold text-dark"><?= e($p['name']) ?></div>
                                                <div class="text-muted" style="font-size: 0.72rem;"><?= e($p['description']) ?></div>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="<?= BASE_URL ?>/roles/index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Role</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleAllPermsBtn');
        const checkboxes = document.querySelectorAll('.perm-checkbox');
        let allChecked = false;

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                allChecked = !allChecked;
                checkboxes.forEach(cb => cb.checked = allChecked);
                toggleBtn.textContent = allChecked ? 'Deselect All' : 'Select All';
            });
        }
    });
    </script>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
