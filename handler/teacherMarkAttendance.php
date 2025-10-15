<?php
require_once "../database.php";
session_start();

// ✅ Ensure teacher is logged in
$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

// ✅ Get POST data
$date = $_POST['date'] ?? null;
$attendanceData = $_POST['attendance'] ?? null;

header('Content-Type: application/json');

if (!$date || !$attendanceData) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing date or attendance data.'
    ]);
    exit;
}

// Decode JSON array
$attendanceArray = json_decode($attendanceData, true);
if (!is_array($attendanceArray)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid attendance data format.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO attendance (student_id, class_id, status, attendance_date, confirmed_by, confirmed_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            confirmed_by = VALUES(confirmed_by),
            confirmed_at = VALUES(confirmed_at)
    ");

    foreach ($attendanceArray as $att) {
        $student_id = $att['student_id'] ?? null;
        $class_id   = $att['class_id'] ?? null;
        $status     = $att['status'] ?? null;

        if (!$student_id || !$class_id || !$status) continue;

        $stmt->execute([$student_id, $class_id, $status, $date, $teacher_id]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Attendance successfully saved.'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
