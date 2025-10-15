<?php
require_once "../database.php";
session_start();

// Check if student is logged in
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

$student_id = $_POST['student_id'] ?? null;
$class_id   = $_POST['class_id'] ?? null;

if (!$student_id || !$class_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing student or class information.'
    ]);
    exit;
}

try {
    // Check if attendance already exists for this student and class today
    $stmtCheck = $pdo->prepare("
        SELECT attendance_id, status 
        FROM attendance 
        WHERE student_id = ? 
          AND class_id = ? 
          AND attendance_date = CURDATE()
    ");
    $stmtCheck->execute([$student_id, $class_id]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['status'] === 'Pending' || $existing['status'] === 'Present') {
            echo json_encode([
                'success' => false,
                'message' => 'Attendance already recorded.'
            ]);
            exit;
        }

        // Update existing attendance record to Pending
        $stmt = $pdo->prepare("
            UPDATE attendance
            SET status = 'Pending'
            WHERE attendance_id = ?
        ");
        $stmt->execute([$existing['attendance_id']]);
    } else {
        // Insert new attendance record with Pending status
        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, class_id, status, attendance_date)
            VALUES (?, ?, 'Pending', CURDATE())
        ");
        $stmt->execute([$student_id, $class_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Attendance marked as Pending.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
