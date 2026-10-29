<?php
/**
 * Create New Employee
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requirePermission('create_employees');

$pageTitle = 'Add New Employee';
$db = getDBConnection();

$errors = [];
$roles = $db->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();

// Auto-suggest next employee ID (e.g. EMP-008)
$maxIdStmt = $db->query("SELECT employee_id FROM employees WHERE employee_id LIKE 'EMP-%' ORDER BY id DESC LIMIT 1");
$lastEmpId = $maxIdStmt->fetchColumn();
$suggestedId = 'EMP-001';
if ($lastEmpId && preg_match('/EMP-(\d+)/', $lastEmpId, $m)) {
    $suggestedId = sprintf('EMP-%03d', (int)$m[1] + 1);
}

// Form state
$employee_id = $_POST['employee_id'] ?? $suggestedId;
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$department = $_POST['department'] ?? '';
$designation = $_POST['designation'] ?? '';
$joining_date = $_POST['joining_date'] ?? date('Y-m-d');
$salary = $_POST['salary'] ?? '';
$status = $_POST['status'] ?? 'Active';
$role_id = $_POST['role_id'] ?? '';
$password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforceCsrf();

    // Validations
    if (empty(trim($employee_id))) $errors['employee_id'] = 'Employee ID is required.';
    if (empty(trim($first_name))) $errors['first_name'] = 'First name is required.';
    if (empty(trim($last_name))) $errors['last_name'] = 'Last name is required.';
    if (empty(trim($email)) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'A valid email address is required.';
    if (empty(trim($phone))) $errors['phone'] = 'Phone number is required.';
    if (empty(trim($department))) $errors['department'] = 'Department is required.';
    if (empty(trim($designation))) $errors['designation'] = 'Designation is required.';
    if (empty(trim($joining_date))) $errors['joining_date'] = 'Joining date is required.';
    if ($salary === '' || !is_numeric($salary) || $salary < 0) $errors['salary'] = 'Please enter a valid non-negative salary.';
    if (empty($role_id)) $errors['role_id'] = 'Please assign a role.';
    if (empty($password)) $errors['password'] = 'An initial password is required.';

    // Check duplicate employee_id
    if (empty($errors['employee_id'])) {
        $checkStmt = $db->prepare("SELECT id FROM employees WHERE employee_id = :id");
        $checkStmt->execute(['id' => trim($employee_id)]);
        if ($checkStmt->fetch()) {
            $errors['employee_id'] = 'This Employee ID is already assigned to another employee.';
        }
    }

    // Check duplicate email
    if (empty($errors['email'])) {
        $checkStmt = $db->prepare("SELECT id FROM employees WHERE email = :email");
        $checkStmt->execute(['email' => trim($email)]);
        if ($checkStmt->fetch()) {
            $errors['email'] = 'This email address is already registered in the system.';
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $insertSql = "
                INSERT INTO employees (
                    employee_id, first_name, last_name, email, password,
                    phone, department, designation, joining_date, salary,
                    status, role_id
                ) VALUES (
                    :employee_id, :first_name, :last_name, :email, :password,
                    :phone, :department, :designation, :joining_date, :salary,
                    :status, :role_id
                )
            ";
            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                'employee_id'  => trim($employee_id),
                'first_name'   => trim($first_name),
                'last_name'    => trim($last_name),
                'email'        => trim($email),
                'password'     => $passwordHash,
                'phone'        => trim($phone),
                'department'   => trim($department),
                'designation'  => trim($designation),
                'joining_date' => $joining_date,
                'salary'       => (float)$salary,
                'status'       => $status,
                'role_id'      => (int)$role_id
            ]);

            setFlash('success', 'Employee ' . $first_name . ' ' . $last_name . ' (' . $employee_id . ') added successfully!');
            redirect('employees/index.php');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to create employee record: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container-fluid p-4">
        <!-- Breadcrumb / Back -->
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/employees/index.php" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left"></i> Back to Employee Directory
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Register New Employee</h5>
                <small class="text-muted">Enter employee identity, department assignment, job title, and login credentials.</small>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger"><?= e($errors['general']) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-12"><h6 class="fw-bold text-secondary text-uppercase small border-bottom pb-2">1. Identity & System Identifiers</h6></div>
                        
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" name="employee_id" class="form-control <?= isset($errors['employee_id']) ? 'is-invalid' : '' ?>" value="<?= e($employee_id) ?>" placeholder="e.g. EMP-008" required>
                            <?php if (isset($errors['employee_id'])): ?><div class="invalid-feedback"><?= e($errors['employee_id']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" value="<?= e($first_name) ?>" required>
                            <?php if (isset($errors['first_name'])): ?><div class="invalid-feedback"><?= e($errors['first_name']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" value="<?= e($last_name) ?>" required>
                            <?php if (isset($errors['last_name'])): ?><div class="invalid-feedback"><?= e($errors['last_name']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12"><h6 class="fw-bold text-secondary text-uppercase small border-bottom pb-2">2. Contact Information</h6></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address (Login Username) <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e($email) ?>" placeholder="employee@company.com" required>
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= e($phone) ?>" placeholder="+1 (555) 000-0000" required>
                            <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12"><h6 class="fw-bold text-secondary text-uppercase small border-bottom pb-2">3. Job & Compensation Details</h6></div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Department <span class="text-danger">*</span></label>
                            <input type="text" name="department" class="form-control <?= isset($errors['department']) ? 'is-invalid' : '' ?>" list="departmentList" value="<?= e($department) ?>" placeholder="Select or type department" required>
                            <datalist id="departmentList">
                                <option value="Engineering">
                                <option value="Human Resources">
                                <option value="Finance">
                                <option value="Marketing">
                                <option value="Operations">
                                <option value="Sales">
                                <option value="Legal">
                                <option value="Executive Management">
                            </datalist>
                            <?php if (isset($errors['department'])): ?><div class="invalid-feedback"><?= e($errors['department']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Designation / Title <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control <?= isset($errors['designation']) ? 'is-invalid' : '' ?>" value="<?= e($designation) ?>" placeholder="e.g. Senior Software Engineer" required>
                            <?php if (isset($errors['designation'])): ?><div class="invalid-feedback"><?= e($errors['designation']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Annual Salary (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" name="salary" class="form-control <?= isset($errors['salary']) ? 'is-invalid' : '' ?>" value="<?= e($salary) ?>" placeholder="85000.00" required>
                                <?php if (isset($errors['salary'])): ?><div class="invalid-feedback"><?= e($errors['salary']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" name="joining_date" class="form-control <?= isset($errors['joining_date']) ? 'is-invalid' : '' ?>" value="<?= e($joining_date) ?>" required>
                            <?php if (isset($errors['joining_date'])): ?><div class="invalid-feedback"><?= e($errors['joining_date']) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Employment Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Assigned Role (RBAC) <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Choose Role --</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $role_id == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['role_id'])): ?><div class="invalid-feedback"><?= e($errors['role_id']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12"><h6 class="fw-bold text-secondary text-uppercase small border-bottom pb-2">4. Portal Authentication</h6></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Initial Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="Minimum 8 characters" required>
                            <small class="text-muted">The employee will use this password to sign into the system.</small>
                            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="<?= BASE_URL ?>/employees/index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
