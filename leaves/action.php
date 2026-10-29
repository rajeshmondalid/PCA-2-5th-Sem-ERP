<?php
/**
 * Leave Approval / Rejection Action Handler
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

// Restricted to Admin and HR Manager
if (!hasRole('Admin') && !hasRole('HR Manager') && !hasPermission('manage_leaves')) {
    setFlash('danger', 'You do not have permission to review leave requests.');
    redirect('leaves/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforceCsrf();

    $leaveId = (int)($_POST['leave_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    $currentUser = getCurrentUser();

    if (!in_array($action, ['Approved', 'Rejected'], true)) {
        setFlash('danger', 'Invalid leave action.');
        redirect('leaves/index.php');
    }

    $db = getDBConnection();
    $stmt = $db->prepare("
        UPDATE leaves 
        SET status = :status, admin_notes = :notes, approved_by = :reviewer
        WHERE id = :id
    ");
    $stmt->execute([
        'status'   => $action,
        'notes'    => !empty($adminNotes) ? $adminNotes : ($action === 'Approved' ? 'Leave approved by HR.' : 'Leave request declined.'),
        'reviewer' => $currentUser['id'],
        'id'       => $leaveId
    ]);

    setFlash('success', 'Leave request has been marked as ' . $action . '.');
}

redirect('leaves/index.php');
