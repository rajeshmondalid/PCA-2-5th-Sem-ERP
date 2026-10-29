<?php
/**
 * Delete Employee Record (POST Handler)
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requirePermission('delete_employees');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

enforceCsrf();

$id = (int)($_POST['id'] ?? 0);
$currentUser = getCurrentUser();

if ($id <= 0) {
    setFlash('danger', 'Invalid employee identifier.');
    redirect('employees/index.php');
}

// Self-deletion safeguard
if ($id === (int)$currentUser['id']) {
    setFlash('danger', 'Security Restriction: You cannot delete your own account.');
    redirect('employees/index.php');
}

try {
    $db = getDBConnection();

    // Fetch employee name for flash feedback
    $nameStmt = $db->prepare("SELECT first_name, last_name, employee_id FROM employees WHERE id = :id");
    $nameStmt->execute(['id' => $id]);
    $emp = $nameStmt->fetch();

    if ($emp) {
        $delStmt = $db->prepare("DELETE FROM employees WHERE id = :id");
        $delStmt->execute(['id' => $id]);
        setFlash('success', 'Employee ' . $emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ') was permanently deleted.');
    } else {
        setFlash('warning', 'Employee record was not found or has already been removed.');
    }
} catch (Exception $e) {
    setFlash('danger', 'Failed to delete employee: ' . $e->getMessage());
}

redirect('employees/index.php');
