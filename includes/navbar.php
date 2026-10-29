<?php
/**
 * Top Navbar Component
 * StaffPulse ERP System
 */
$currentUser = getCurrentUser();
$todayAtt = null;
$unreadNoticesCount = 0;
if (isLoggedIn()) {
    $db = getDBConnection();
    $attStmt = $db->prepare("SELECT * FROM attendance WHERE employee_id = :uid AND date = CURRENT_DATE() LIMIT 1");
    $attStmt->execute(['uid' => $currentUser['id']]);
    $todayAtt = $attStmt->fetch();
    $unreadNoticesCount = (int)$db->query("SELECT COUNT(*) FROM notices WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
}
?>
<header class="navbar navbar-expand bg-white border-bottom shadow-sm px-3 sticky-top">
    <div class="d-flex align-items-center gap-3">
        <!-- Sidebar Toggle Mobile -->
        <button class="btn btn-light border d-lg-none" id="sidebarToggleBtn" aria-label="Toggle navigation">
            <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Current Location Indicator -->
        <div class="d-none d-md-block">
            <span class="text-muted small">Location:</span>
            <span class="fw-semibold text-dark small ms-1"><?= isset($pageTitle) ? e($pageTitle) : 'Dashboard' ?></span>
        </div>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2 gap-sm-3">
        <!-- Quick Attendance Clock In / Clock Out Form -->
        <div class="d-none d-sm-block">
            <?php if (!$todayAtt || empty($todayAtt['clock_in'])): ?>
                <form method="POST" action="<?= BASE_URL ?>/attendance/punch.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="clock_in">
                    <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                    <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-1 shadow-sm px-2 py-1">
                        <i class="bi bi-box-arrow-in-right"></i> <span>Clock In</span>
                    </button>
                </form>
            <?php elseif (empty($todayAtt['clock_out'])): ?>
                <form method="POST" action="<?= BASE_URL ?>/attendance/punch.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="clock_out">
                    <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 px-2 py-1" title="Clocked in at <?= date('h:i A', strtotime($todayAtt['clock_in'])) ?>">
                        <span class="badge-dot bg-success d-inline-block me-1"></span>
                        <i class="bi bi-box-arrow-left"></i> <span>Clock Out</span>
                    </button>
                </form>
            <?php else: ?>
                <span class="badge bg-light text-secondary border py-2 px-2" style="font-size: 0.75rem;">
                    <i class="bi bi-check2-all text-success me-1"></i>Shift Done (<?= (float)$todayAtt['total_hours'] ?>h)
                </span>
            <?php endif; ?>
        </div>

        <!-- Notices Bell Icon -->
        <a href="<?= BASE_URL ?>/notices/index.php" class="btn btn-light position-relative p-2 border-0 rounded-circle" title="Company Notices">
            <i class="bi bi-bell text-secondary fs-6"></i>
            <?php if ($unreadNoticesCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                    <?= $unreadNoticesCount ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- Role Badge -->
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 d-none d-md-inline-block">
            <i class="bi bi-shield-check me-1"></i><?= e($currentUser['role_name'] ?? 'Staff') ?>
        </span>

        <!-- User Dropdown Menu -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar-circle-sm bg-primary text-white fw-bold">
                    <?= strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1) . substr($currentUser['last_name'] ?? 'S', 0, 1)) ?>
                </span>
                <span class="d-none d-sm-inline fw-medium text-dark small"><?= e($currentUser['first_name'] ?? 'User') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                <li class="dropdown-header">
                    <div class="fw-bold text-dark"><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                    <small class="text-muted"><?= e($currentUser['email'] ?? '') ?></small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="<?= BASE_URL ?>/employees/view.php?id=<?= (int)$currentUser['id'] ?>">
                        <i class="bi bi-person text-secondary"></i> My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="<?= BASE_URL ?>/employees/index.php">
                        <i class="bi bi-people text-secondary"></i> Employee Directory
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="<?= BASE_URL ?>/auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Sign Out
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
