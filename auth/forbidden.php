<?php
/**
 * HTTP 403 Forbidden Access Page
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle = 'Access Denied';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="card shadow-sm border-0 text-center py-5 mx-auto" style="max-width: 600px;">
            <div class="card-body p-4">
                <div class="display-1 text-danger mb-3">
                    <i class="bi bi-shield-slash"></i>
                </div>
                <h3 class="fw-bold text-dark">403 - Access Denied</h3>
                <p class="text-muted mb-4">
                    Your current role (<strong><?= e(getCurrentUser()['role_name']) ?></strong>) does not have the required permissions to perform this action or view this page.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Go Back
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary">
                        <i class="bi bi-grid-1x2 me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
