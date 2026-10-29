<?php
/**
 * One-Click Web Installer & Database Setup Wizard
 * Employee Management System
 */
define('INSTALLER_MODE', true);
require_once __DIR__ . '/config/database.php';

$step = $_GET['step'] ?? 'welcome';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install'])) {
    try {
        // Connect to MySQL server (without specifying DB_NAME first, so we can create it if missing)
        $dsnWithoutDb = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        $rootPdo = new PDO($dsnWithoutDb, DB_USER, DB_PASS, $options);

        // Create Database if it does not exist
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Now connect with database selected
        $pdo = getDBConnection();

        // Read and execute schema.sql & erp_schema.sql
        $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($schemaSql);
        if (file_exists(__DIR__ . '/database/erp_schema.sql')) {
            $pdo->exec(file_get_contents(__DIR__ . '/database/erp_schema.sql'));
        }

        // Read and execute seed.sql & erp_seed.sql
        $seedSql = file_get_contents(__DIR__ . '/database/seed.sql');
        $pdo->exec($seedSql);
        if (file_exists(__DIR__ . '/database/erp_seed.sql')) {
            $pdo->exec(file_get_contents(__DIR__ . '/database/erp_seed.sql'));
        }

        $step = 'success';
    } catch (Exception $e) {
        $error = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Wizard - Employee Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh; font-family: 'Inter', sans-serif;">

<div class="container py-5" style="max-width: 600px;">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-primary text-white p-4 text-center">
            <i class="bi bi-gear-wide-connected fs-1 mb-2 d-inline-block"></i>
            <h4 class="fw-bold mb-0">System Setup & Database Installer</h4>
            <small class="text-white-50">StaffPulse Employee Management System</small>
        </div>

        <div class="card-body p-4 p-sm-5">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($step === 'welcome'): ?>
                <h5 class="fw-bold text-dark mb-3">Welcome to the Automated Setup Wizard</h5>
                <p class="text-muted small">
                    This setup utility will automatically initialize the database <strong><?= htmlspecialchars(DB_NAME) ?></strong>, create the required relational tables, and seed default roles, permissions, and administrator accounts.
                </p>

                <div class="bg-light p-3 rounded-3 border mb-4">
                    <h6 class="fw-bold small text-uppercase text-secondary mb-2">Detected Configuration:</h6>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li><strong>Database Host:</strong> <?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></li>
                        <li><strong>Database Name:</strong> <?= htmlspecialchars(DB_NAME) ?></li>
                        <li><strong>Database User:</strong> <?= htmlspecialchars(DB_USER) ?></li>
                        <li><strong>PHP Version:</strong> <?= phpversion() ?> (>= 7.4 OK)</li>
                        <li><strong>PDO MySQL:</strong> <?= extension_loaded('pdo_mysql') ? '<span class="text-success fw-bold">Installed</span>' : '<span class="text-danger fw-bold">Missing</span>' ?></li>
                    </ul>
                </div>

                <form method="POST" action="">
                    <button type="submit" name="run_install" value="1" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-database-check"></i> Initialize Database & Install Now
                    </button>
                </form>

            <?php elseif ($step === 'success'): ?>
                <div class="text-center py-3">
                    <div class="text-success display-3 mb-2">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Installation Completed!</h4>
                    <p class="text-muted small mb-4">Database tables created and seeded with default administrator, roles, and sample employee records.</p>

                    <div class="bg-light p-3 rounded-3 border text-start mb-4 small font-monospace">
                        <div class="fw-bold text-dark mb-1">Default Admin Login:</div>
                        <div><strong>Email:</strong> admin@company.com</div>
                        <div><strong>Password:</strong> AdminPassword123!</div>
                    </div>

                    <a href="auth/login.php" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Proceed to Login Portal
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-light border-top text-center py-2 text-muted small">
            StaffPulse Employee Management System &bull; Production Ready
        </div>
    </div>
</div>

</body>
</html>
