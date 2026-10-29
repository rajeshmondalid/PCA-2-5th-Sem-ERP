<?php
/**
 * Company Notice Board & Enterprise Bulletins
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = getDBConnection();
$pageTitle = 'Company Notice Board';

$canManageNotices = hasRole('Admin') || hasRole('HR Manager') || hasPermission('manage_notices');

// Handle Publish Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_notice']) && $canManageNotices) {
    enforceCsrf();
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $priority = in_array($_POST['priority'] ?? '', ['Normal', 'High', 'Urgent']) ? $_POST['priority'] : 'Normal';
    $targetAudience = trim($_POST['target_audience'] ?? 'All Departments');
    $isPinned = !empty($_POST['is_pinned']) ? 1 : 0;

    if (empty($title) || empty($content)) {
        setFlash('danger', 'Notice title and bulletin content are required.');
    } else {
        $stmt = $db->prepare("
            INSERT INTO notices (title, content, priority, target_audience, created_by, is_pinned)
            VALUES (:title, :content, :priority, :target, :author, :pinned)
        ");
        $stmt->execute([
            'title'    => $title,
            'content'  => $content,
            'priority' => $priority,
            'target'   => $targetAudience,
            'author'   => $currentUser['id'],
            'pinned'   => $isPinned
        ]);
        setFlash('success', 'Corporate announcement broadcasted successfully.');
        redirect('notices/index.php');
    }
}

// Handle Delete Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notice']) && $canManageNotices) {
    enforceCsrf();
    $noticeId = (int)($_POST['notice_id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM notices WHERE id = :id");
    $stmt->execute(['id' => $noticeId]);
    setFlash('success', 'Announcement removed from notice board.');
    redirect('notices/index.php');
}

// Fetch all notices (Pinned first, then newest)
$notices = $db->query("
    SELECT n.*, e.first_name, e.last_name, e.department as author_department
    FROM notices n
    INNER JOIN employees e ON n.created_by = e.id
    ORDER BY n.is_pinned DESC, n.created_at DESC
")->fetchAll();

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
                <h4 class="fw-bold mb-1"><i class="bi bi-megaphone-fill text-primary me-2"></i>Company Bulletin & Notice Board</h4>
                <p class="text-muted small mb-0">Official enterprise announcements, town hall meetings, and policy updates.</p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($canManageNotices): ?>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#publishNoticeModal">
                    <i class="bi bi-plus-circle-fill"></i> Broadcast New Notice
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notices Stream -->
        <div class="d-flex flex-column gap-3 mb-4">
            <?php if (empty($notices)): ?>
                <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                    <i class="bi bi-bell-slash fs-1 text-muted mb-2"></i>
                    <h6 class="fw-bold text-dark">No Announcements Active</h6>
                    <p class="text-muted small mb-0">All corporate bulletins and town hall circulars will be displayed here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notices as $n): ?>
                    <?php
                    $borderClass = 'notice-normal';
                    $badgeBg = 'bg-primary-subtle text-primary border-primary-subtle';
                    if ($n['priority'] === 'Urgent') {
                        $borderClass = 'notice-urgent';
                        $badgeBg = 'bg-danger-subtle text-danger border-danger-subtle';
                    } elseif ($n['priority'] === 'High') {
                        $borderClass = 'notice-high';
                        $badgeBg = 'bg-warning-subtle text-warning border-warning-subtle';
                    }
                    ?>
                    <div class="card border-0 shadow-sm rounded-4 p-4 notice-card <?= $borderClass ?> position-relative">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <?php if ($n['is_pinned']): ?>
                                    <span class="badge bg-danger text-white px-2 py-1">
                                        <i class="bi bi-pin-angle-fill me-1"></i> Pinned Bulletin
                                    </span>
                                <?php endif; ?>
                                <span class="badge <?= $badgeBg ?> border px-2 py-1">
                                    <i class="bi bi-flag-fill me-1"></i> <?= e($n['priority']) ?> Priority
                                </span>
                                <span class="badge bg-light text-secondary border px-2 py-1">
                                    <i class="bi bi-people-fill me-1"></i> <?= e($n['target_audience']) ?>
                                </span>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-clock me-1"></i> <?= date('M d, Y &bull; h:i A', strtotime($n['created_at'])) ?>
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark mb-2"><?= e($n['title']) ?></h5>
                        <p class="text-secondary small mb-3" style="line-height: 1.6; white-space: pre-line;">
                            <?= e($n['content']) ?>
                        </p>

                        <div class="d-flex align-items-center justify-content-between border-top pt-3 mt-1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle-sm bg-primary text-white fw-bold" style="font-size: 0.75rem;">
                                    <?= strtoupper(substr($n['first_name'], 0, 1) . substr($n['last_name'], 0, 1)) ?>
                                </div>
                                <small class="text-muted">
                                    Broadcasted by <strong><?= e($n['first_name'] . ' ' . $n['last_name']) ?></strong> (<?= e($n['author_department']) ?>)
                                </small>
                            </div>

                            <?php if ($canManageNotices): ?>
                            <form method="POST" action="" onsubmit="return confirm('Remove this notice?');" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                                <input type="hidden" name="delete_notice" value="1">
                                <input type="hidden" name="notice_id" value="<?= (int)$n['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

<!-- Publish Notice Modal -->
<?php if ($canManageNotices): ?>
<div class="modal fade" id="publishNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-megaphone me-2 text-primary"></i>Broadcast New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
                <input type="hidden" name="publish_notice" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Bulletin Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Q4 Company All-Hands Town Hall" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Priority Level</label>
                            <select name="priority" class="form-select">
                                <option value="Normal">Normal</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Target Audience</label>
                            <input type="text" name="target_audience" class="form-control" value="All Departments">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Announcement Message & Details <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="4" placeholder="Detail the circular, meeting agenda, or policy modification..." required></textarea>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinnedCheck" value="1">
                        <label class="form-check-label small fw-semibold text-secondary" for="isPinnedCheck">
                            Pin this announcement to top of dashboard & notice board
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Broadcast Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
