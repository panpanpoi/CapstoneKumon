<?php
require_once "../database.php";
session_start();

// Ensure teacher is logged in
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$attendance_id = $_POST['attendance_id'] ?? null;
$status = $_POST['status'] ?? 'Present'; // Default to Present
$teacher_id = $_SESSION['teacher_id'] ?? null;

if (!$attendance_id || !$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required information.']);
    exit;
}

try {
    //  Update attendance record to Present and record teacher
    $stmt = $pdo->prepare("
        UPDATE attendance
        SET status = ?, marked_by = ?, attendance_date = CURDATE()
        WHERE attendance_id = ?
    ");
    $stmt->execute([$status, $teacher_id, $attendance_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Attendance confirmed successfully.',
        'confirmed_at' => date('Y-m-d') // send the date to JS
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>


