<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";

// Make sure student is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['error'] = "Student not logged in.";
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch student info
$stmt = $pdo->prepare("
    SELECT s.student_id, s.studentCode, s.Firstname, s.Lastname, cs.level, cs.class_id
    FROM students s
    LEFT JOIN class_students cs ON s.student_id = cs.student_id
    WHERE s.student_id = ?
    LIMIT 1
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch latest payment
$stmt = $pdo->prepare("
    SELECT *
    FROM payments
    WHERE student_id = ?
    ORDER BY payment_date DESC
    LIMIT 1
");
$stmt->execute([$student_id]);
$latest_payment = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch next due payment
$stmt = $pdo->prepare("
    SELECT *
    FROM payments
    WHERE student_id = ? AND due_date >= CURDATE()
    ORDER BY due_date ASC
    LIMIT 1
");
$stmt->execute([$student_id]);
$next_due = $stmt->fetch(PDO::FETCH_ASSOC);


