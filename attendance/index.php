<?php
/**
 * Daily Attendance & Punctuality Logs
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = getDBConnection();
$pageTitle = 'Attendance & Time Logs';

// Filters
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterDept = trim($_GET['department'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// Handle manual attendance adjustment (Admin / HR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_entry'])) {
    enforceCsrf();
    if (hasRole('Admin') || hasRole('HR Manager')) {
        $empId = (int)($_POST['employee_id'] ?? 0);
        $attDate = $_POST['date'] ?? date('Y-m-d');
        $clockIn = !empty($_POST['clock_in']) ? $_POST['clock_in'] : null;
        $clockOut = !empty($_POST['clock_out']) ? $_POST['clock_out'] : null;
        $status = $_POST['status'] ?? 'Present';
        $notes = trim($_POST['notes'] ?? 'Manual HR adjustment');

        $totalHours = 0.00;
        if ($clockIn && $clockOut) {
            $diff = max(0, strtotime($clockOut) - strtotime($clockIn));
            $totalHours = round($diff / 3600, 2);
        }

        // Check if record exists
        $chk = $db->prepare("SELECT id FROM attendance WHERE employee_id = :emp_id AND date = :date");
        $chk->execute(['emp_id' => $empId, 'date' => $attDate]);
        $existing = $chk->fetch();

        if ($existing) {
            $up = $db->prepare("
                UPDATE attendance 
                SET clock_in = :cin, clock_out = :cout, total_hours = :hrs, status = :status, notes = :notes 
                WHERE id = :id
            ");
            $up->execute([
                'cin' => $clockIn, 'cout' => $clockOut, 'hrs' => $totalHours, 
                'status' => $status, 'notes' => $notes, 'id' => $existing['id']
            ]);
            setFlash('success', 'Attendance record updated successfully.');
        } else {
            $ins = $db->prepare("
                INSERT INTO attendance (employee_id, date, clock_in, clock_out, total_hours, status, notes)
                VALUES (:emp_id, :date, :cin, :cout, :hrs, :status, :notes)
            ");
            $ins->execute([
                'emp_id' => $empId, 'date' => $attDate, 'cin' => $clockIn, 
                'cout' => $clockOut, 'hrs' => $totalHours, 'status' => $status, 'notes' => $notes
            ]);
            setFlash('success', 'New attendance record created successfully.');
        }
        redirect('attendance/index.php?date=' . urlencode($attDate));
    }
}

// Build query conditions
$where = ['a.date = :filter_date'];
$params = ['filter_date' => $filterDate];

if (!empty($filterDept)) {
    $where[] = 'e.department = :department';
    $params['department'] = $filterDept;
}
if (!empty($filterStatus)) {
    $where[] = 'a.status = :status';
    $params['status'] = $filterStatus;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Fetch records
$query = "
    SELECT a.*, e.employee_id as emp_code, e.first_name, e.last_name, e.department, e.designation
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    $whereClause
    ORDER BY a.clock_in ASC, e.first_name ASC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$attendanceLogs = $stmt->fetchAll();

// Statistics for selected date
$statStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_logged,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as count_present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as count_late,
        SUM(CASE WHEN status = 'Half Day' THEN 1 ELSE 0 END) as count_halfday,
        COALESCE(AVG(CASE WHEN total_hours > 0 THEN total_hours END), 0) as avg_hours
    FROM attendance 
    WHERE date = :stat_date
");
$statStmt->execute(['stat_date' => $filterDate]);
$stats = $statStmt->fetch();

// Departments for filter
$deptList = $db->query("SELECT DISTINCT department FROM employees ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
// Employees for manual entry modal
$empList = $db->query("SELECT id, employee_id, first_name, last_name, department FROM employees WHERE status = 'Active' ORDER BY first_name ASC")->fetchAll();

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
                <h4 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Attendance & Time Tracking</h4>
                <p class="text-muted small mb-0">Daily punch logs, biometric/web clock-in timestamps, and shift punctuality.</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (hasRole('Admin') || hasRole('HR Manager')): ?>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#manualAttendanceModal">
                    <i class="bi bi-plus-circle-fill"></i> Manual Time Entry
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
                            <div class="text-muted small fw-medium">Logged Staff</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= (int)$stats['total_logged'] ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">On-Time Present</div>
                            <h3 class="fw-bold mb-0 text-success"><?= (int)$stats['count_present'] ?></h3>
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
                            <div class="text-muted small fw-medium">Late Arrivals</div>
                            <h3 class="fw-bold mb-0 text-warning"><?= (int)$stats['count_late'] ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-alarm-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-medium">Avg Work Hours</div>
                            <h3 class="fw-bold mb-0 text-info"><?= number_format((float)$stats['avg_hours'], 1) ?>h</h3>
                        </div>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Select Date</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= e($filterDate) ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Department</label>
                        <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php foreach ($deptList as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($filterDept === $d) ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="Present" <?= ($filterStatus === 'Present') ? 'selected' : '' ?>>Present</option>
                            <option value="Late" <?= ($filterStatus === 'Late') ? 'selected' : '' ?>>Late</option>
                            <option value="Half Day" <?= ($filterStatus === 'Half Day') ? 'selected' : '' ?>>Half Day</option>
                            <option value="Absent" <?= ($filterStatus === 'Absent') ? 'selected' : '' ?>>Absent</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end gap-2 pt-md-4">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                        <a href="index.php" class="btn btn-sm btn-light border w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Logs Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Logs for <?= date('F j, Y', strtotime($filterDate)) ?></h6>
                <span class="badge bg-light text-dark border"><?= count($attendanceLogs) ?> Records</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase fw-semibold">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Total Time</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceLogs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary"></i>
                                    No attendance records logged for <?= htmlspecialchars($filterDate) ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceLogs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle-sm bg-primary text-white fw-bold">
                                                <?= strtoupper(substr($log['first_name'], 0, 1) . substr($log['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark"><?= e($log['first_name'] . ' ' . $log['last_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= e($log['emp_code']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border"><?= e($log['department']) ?></span></td>
                                    <td>
                                        <?php if (!empty($log['clock_in'])): ?>
                                            <span class="fw-medium text-dark"><i class="bi bi-box-arrow-in-right text-success me-1"></i><?= date('h:i A', strtotime($log['clock_in'])) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['clock_out'])): ?>
                                            <span class="fw-medium text-dark"><i class="bi bi-box-arrow-left text-danger me-1"></i><?= date('h:i A', strtotime($log['clock_out'])) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Working...</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((float)$log['total_hours'] > 0): ?>
                                            <span class="fw-bold text-dark"><?= number_format((float)$log['total_hours'], 2) ?> hrs</span>
                                        <?php else: ?>
                                            <span class="text-muted">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = 'badge-status-present';
                                        if ($log['status'] === 'Late') $statusClass = 'badge-status-late';
                                        elseif ($log['status'] === 'Half Day') $statusClass = 'badge-status-halfday';
                                        elseif ($log['status'] === 'Absent') $statusClass = 'badge-status-absent';
                                        ?>
                                        <span class="badge-status <?= $statusClass ?>">
                                            <span class="badge-dot"></span>
                                            <?= e($log['status']) ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?= e($log['notes'] ?? 'Regular shift') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<!-- Manual Entry Modal -->
<?php if (hasRole('Admin') || hasRole('HR Manager')): ?>
<div class="modal fade" id="manualAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock me-2 text-primary"></i>Manual Time Entry / Override</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                <input type="hidden" name="manual_entry" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee...</option>
                            <?php foreach ($empList as $emp): ?>
                                <option value="<?= (int)$emp['id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['employee_id']) ?> - <?= e($emp['department']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Attendance Date</label>
                        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Clock In Time</label>
                            <input type="time" name="clock_in" class="form-control" value="09:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Clock Out Time</label>
                            <input type="time" name="clock_out" class="form-control" value="17:30">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Status</label>
                        <select name="status" class="form-select">
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Half Day">Half Day</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Adjustment Reason / Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Biometric system sync / Verified by Manager">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Save Attendance Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
