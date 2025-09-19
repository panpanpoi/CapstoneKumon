<?php
require_once "../database.php";
session_start();

if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST['student_id'] ?? null;
    $meetingDate = $_POST['meetingDate'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    if (!$student_id || !$meetingDate || !$start_time || !$end_time) {
        header("Location: ../pages/kumonTeacher.php?error=Missing data");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO ptc_meetings (student_id, teacher_id, meetingDate, start_time, end_time, status) 
        VALUES (?, ?, ?, ?, ?, 'scheduled')
    ");
    $stmt->execute([$student_id, $_SESSION['teacher_id'], $meetingDate, $start_time, $end_time]);

    header("Location: ../pages/kumonTeacher.php?success=PTC scheduled");
    exit;
}
?>
