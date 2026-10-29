<?php
/**
 * Delete Custom Role (POST Handler)
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requirePermission('delete_roles');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

enforceCsrf();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlash('danger', 'Invalid role identifier.');
    redirect('roles/index.php');
}

try {
    $db = getDBConnection();

    // Fetch role details
    $stmt = $db->prepare("SELECT * FROM roles WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $role = $stmt->fetch();

    if (!$role) {
        setFlash('warning', 'Role not found or already deleted.');
        redirect('roles/index.php');
    }

    // Protection 1: System roles cannot be deleted
    if ($role['is_system']) {
        setFlash('danger', 'Security Restriction: System roles cannot be deleted.');
        redirect('roles/index.php');
    }

    // Protection 2: Cannot delete role if assigned to any employees
    $countStmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE role_id = :id");
    $countStmt->execute(['id' => $id]);
    $assignedCount = (int)$countStmt->fetchColumn();

    if ($assignedCount > 0) {
        setFlash('danger', 'Cannot delete role "' . $role['name'] . '" because ' . $assignedCount . ' employee(s) are currently assigned to it. Please reassign them to another role first.');
        redirect('roles/index.php');
    }

    // Delete role (cascade deletes role_permissions)
    $delStmt = $db->prepare("DELETE FROM roles WHERE id = :id");
    $delStmt->execute(['id' => $id]);

    setFlash('success', 'Role "' . $role['name'] . '" was successfully removed.');
} catch (Exception $e) {
    setFlash('danger', 'Failed to delete role: ' . $e->getMessage());
}

redirect('roles/index.php');
