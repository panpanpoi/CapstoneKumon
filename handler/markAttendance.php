<?php
require_once "../database.php";
session_start();

$teacher_id = $_SESSION['teacher_id'] ?? null;
$date = $_POST['date'] ?? null;

if (!$teacher_id || !$date) {
  exit("Missing required fields");
}

// Fetch all students assigned under this teacher
$stmt = $pdo->prepare("
  SELECT cs.student_id, cs.class_id
  FROM class_students cs
  JOIN class_schedules c ON cs.class_id = c.class_id
  WHERE c.teacher_id = ?
");
$stmt->execute([$teacher_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($students)) exit("No assigned students found.");

// Insert or update attendance
foreach ($students as $s) {
  $insert = $pdo->prepare("
    INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by)
    VALUES (?, ?, ?, 'Present', ?)
    ON DUPLICATE KEY UPDATE status = 'Present'
  ");
  $insert->execute([$s['student_id'], $s['class_id'], $date, $teacher_id]);
}

echo "✅ Attendance marked successfully for {$date}";
?>
