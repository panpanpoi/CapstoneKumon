<?php
// 1. Setup and Configuration
require_once "../database.php";
if (!isset($_SESSION)) session_start();
header('Content-Type: application/json');

// Set Timezone to Philippines
date_default_timezone_set('Asia/Manila');

// 2. Security: Only allow Teachers
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Teachers only.']);
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$student_id = $_POST['student_id'] ?? null;
$date       = $_POST['date'] ?? date('Y-m-d'); // Default to today
$currentTime = date('H:i:s'); // Current Manila time

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID missing.']);
    exit;
}

try {
    // 3. GET CLASS ID
    // Find the class assigned to this student and this teacher.
    $stmtClass = $pdo->prepare("
        SELECT class_id 
        FROM class_students 
        WHERE student_id = ? AND teacher_id = ? 
        LIMIT 1
    ");
    $stmtClass->execute([$student_id, $teacher_id]);
    $class_id = $stmtClass->fetchColumn();

    // Safety Check: If we can't find a class ID, we cannot create an attendance record
    if (!$class_id) {
        echo json_encode(['success' => false, 'message' => 'Error: Student is not linked to a class under your account.']);
        exit;
    }

    // 4. Check if attendance exists
    $stmtCheck = $pdo->prepare("
        SELECT attendance_id, status
        FROM attendance 
        WHERE student_id = ? AND attendance_date = ?
    ");
    $stmtCheck->execute([$student_id, $date]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // UPDATE existing record (Overwrites 'Absent' or 'Pending')
        // We perform the update regardless of current status to ensure 'time_in' is set to now
        $stmt = $pdo->prepare("
            UPDATE attendance 
            SET status = 'Present', time_in = ? 
            WHERE attendance_id = ?
        ");
        $stmt->execute([$currentTime, $existing['attendance_id']]);
    } else {
        // INSERT new record
        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, class_id, status, attendance_date, time_in) 
            VALUES (?, ?, 'Present', ?, ?)
        ");
        $stmt->execute([$student_id, $class_id, $date, $currentTime]);
    }

    echo json_encode(['success' => true, 'message' => 'Marked Present successfully.']);

} catch (PDOException $e) {
    // Log the actual error to the server error log for debugging
    error_log("Teacher Mark Attendance Error: " . $e->getMessage());
    
    // Return a generic error to the user
    echo json_encode(['success' => false, 'message' => 'Database Error: Could not save attendance.']);
}
?>