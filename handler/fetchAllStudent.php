<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

$teacher_id = $_SESSION['teacher_id'] ?? null;
$allStudents = [];

if ($teacher_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.student_id, s.studentCode, s.Firstname, s.Lastname, s.level
            FROM students s
            WHERE s.student_id NOT IN (
                SELECT student_id FROM class_students WHERE teacher_id = ?
            )
            ORDER BY s.Firstname, s.Lastname
        ");
        $stmt->execute([$teacher_id]);
        $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $allStudents = [];
    }
}
?>
