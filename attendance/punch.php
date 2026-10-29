<?php
/**
 * Attendance Punch (Clock In / Clock Out) Handler
 * StaffPulse ERP System
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforceCsrf();

    $currentUser = getCurrentUser();
    $userId = (int)$currentUser['id'];
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $redirectUrl = $_POST['redirect_to'] ?? BASE_URL . '/dashboard.php';

    $db = getDBConnection();
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');

    // Check existing attendance record for today
    $checkStmt = $db->prepare("SELECT * FROM attendance WHERE employee_id = :emp_id AND date = :today LIMIT 1");
    $checkStmt->execute(['emp_id' => $userId, 'today' => $today]);
    $record = $checkStmt->fetch();

    if ($action === 'clock_in') {
        if ($record && !empty($record['clock_in'])) {
            setFlash('warning', 'You have already clocked in today at ' . date('h:i A', strtotime($record['clock_in'])));
        } else {
            // Determine punctuality (e.g., standard start at 09:15 AM)
            $isLate = (strtotime($currentTime) > strtotime('09:15:00'));
            $status = $isLate ? 'Late' : 'Present';

            if ($record) {
                // Update record if created as absent or blank
                $updateStmt = $db->prepare("
                    UPDATE attendance 
                    SET clock_in = :clock_in, status = :status, notes = COALESCE(:notes, notes)
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    'clock_in' => $currentTime,
                    'status'   => $status,
                    'notes'    => !empty($notes) ? $notes : null,
                    'id'       => $record['id']
                ]);
            } else {
                $insertStmt = $db->prepare("
                    INSERT INTO attendance (employee_id, date, clock_in, status, notes)
                    VALUES (:emp_id, :date, :clock_in, :status, :notes)
                ");
                $insertStmt->execute([
                    'emp_id'   => $userId,
                    'date'     => $today,
                    'clock_in' => $currentTime,
                    'status'   => $status,
                    'notes'    => !empty($notes) ? $notes : ($isLate ? 'Late check-in' : 'Punctual check-in')
                ]);
            }
            setFlash('success', 'Clock-in recorded at ' . date('h:i A') . ($isLate ? ' (Marked Late)' : ' (On Time)!'));
        }
    } elseif ($action === 'clock_out') {
        if (!$record || empty($record['clock_in'])) {
            setFlash('danger', 'You must clock in before clocking out.');
        } elseif (!empty($record['clock_out'])) {
            setFlash('warning', 'You have already clocked out today at ' . date('h:i A', strtotime($record['clock_out'])));
        } else {
            $clockInTime = strtotime($record['clock_in']);
            $clockOutTime = strtotime($currentTime);
            $secondsDiff = max(0, $clockOutTime - $clockInTime);
            $totalHours = round($secondsDiff / 3600, 2);

            $status = $record['status'];
            if ($totalHours < 4.5 && $status !== 'Late') {
                $status = 'Half Day';
            }

            $updateStmt = $db->prepare("
                UPDATE attendance 
                SET clock_out = :clock_out, total_hours = :hours, status = :status
                WHERE id = :id
            ");
            $updateStmt->execute([
                'clock_out' => $currentTime,
                'hours'     => $totalHours,
                'status'    => $status,
                'id'        => $record['id']
            ]);

            setFlash('success', 'Clock-out recorded at ' . date('h:i A') . '. Total work time: ' . $totalHours . ' hrs.');
        }
    }
    header('Location: ' . $redirectUrl);
    exit;
}

redirect('dashboard.php');
