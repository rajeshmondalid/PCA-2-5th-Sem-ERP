<?php
/**
 * Flash Notification Component
 * Renders user alerts (success, error, warning, info)
 */
$flash = getFlash();
if ($flash):
    $alertType = $flash['type'] ?? 'info';
    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info'    => 'bi-info-circle-fill',
    ];
    $icon = $iconMap[$alertType] ?? 'bi-bell-fill';
?>
<div class="alert alert-<?= e($alertType) ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm mb-4" role="alert">
    <i class="bi <?= $icon ?> fs-5"></i>
    <div class="flex-grow-1">
        <?= e($flash['message']) ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
