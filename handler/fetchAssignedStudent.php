<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

$teacher_id = $_SESSION['teacher_id'] ?? null;
$assignedStudents = [];

if ($teacher_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                cs.student_id,
                s.studentCode,
                s.Firstname,
                s.Lastname,
                cs.level,
                cs.class_id,
                GROUP_CONCAT(
                    CONCAT(csch.schedule_day, ' ', 
                           TIME_FORMAT(csch.start_time, '%H:%i'), '-', 
                           TIME_FORMAT(csch.end_time, '%H:%i')
                    ) SEPARATOR ', '
                ) AS schedules
            FROM class_students cs
            JOIN students s ON cs.student_id = s.student_id
            LEFT JOIN class_schedules csch ON cs.class_id = csch.class_id
            WHERE cs.teacher_id = ?
            GROUP BY cs.student_id, cs.class_id, cs.level, s.studentCode, s.Firstname, s.Lastname
            ORDER BY s.Firstname, s.Lastname
        ");
        $stmt->execute([$teacher_id]);
        $assignedStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>
