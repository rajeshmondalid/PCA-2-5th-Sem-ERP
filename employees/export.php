<?php
/**
 * Export Employees to CSV
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';
requirePermission('export_employees');

$db = getDBConnection();

// Read active filters from query parameters
$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');
$roleId = trim($_GET['role_id'] ?? '');

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = '(e.first_name LIKE :search OR e.last_name LIKE :search OR e.employee_id LIKE :search OR e.email LIKE :search OR e.designation LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if (!empty($department)) {
    $whereConditions[] = 'e.department = :department';
    $params['department'] = $department;
}

if (!empty($status) && in_array($status, ['Active', 'Inactive'], true)) {
    $whereConditions[] = 'e.status = :status';
    $params['status'] = $status;
}

if (!empty($roleId) && is_numeric($roleId)) {
    $whereConditions[] = 'e.role_id = :role_id';
    $params['role_id'] = (int)$roleId;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$sql = "
    SELECT e.employee_id, e.first_name, e.last_name, e.email, e.phone,
           e.department, e.designation, r.name as role_name, e.salary,
           e.status, e.joining_date, e.created_at
    FROM employees e
    INNER JOIN roles r ON e.role_id = r.id
    $whereClause
    ORDER BY e.id ASC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Send CSV download headers
$filename = 'employees_export_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fputs($output, "ï»¿");

// Header row
fputcsv($output, [
    'Employee ID',
    'First Name',
    'Last Name',
    'Email Address',
    'Phone',
    'Department',
    'Designation',
    'Role',
    'Annual Salary',
    'Status',
    'Joining Date',
    'Record Created At'
]);

foreach ($rows as $row) {
    fputcsv($output, [
        $row['employee_id'],
        $row['first_name'],
        $row['last_name'],
        $row['email'],
        $row['phone'],
        $row['department'],
        $row['designation'],
        $row['role_name'],
        $row['salary'],
        $row['status'],
        $row['joining_date'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
