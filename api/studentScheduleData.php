<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";

$student_id = $_SESSION['student_id'] ?? null;

if (!$student_id) {
    // Optional safety fallback
    die(" Error: Student ID not found in session.");
}

try {
    // Fetch full weekly schedule for the logged-in student
    $stmt = $pdo->prepare("
        SELECT 
            cs.schedule_day, 
            cs.start_time, 
            cs.end_time, 
            u.subject, 
            u.Name AS teacher_name, 
            u.Surname AS teacher_surname
        FROM class_students cst
        JOIN class_schedules cs ON cst.class_id = cs.class_id
        JOIN teachers t ON cst.teacher_id = t.teacher_id
        JOIN users u ON t.user_id = u.user_id
        WHERE cst.student_id = ?
        ORDER BY 
            FIELD(cs.schedule_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
            cs.start_time ASC
    ");
    $stmt->execute([$student_id]);
    $all_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group schedules by day for easy rendering in HTML
    $weekly_schedule = [];
    foreach ($all_schedules as $sched) {
        $day = $sched['schedule_day'];
        if (!isset($weekly_schedule[$day])) {
            $weekly_schedule[$day] = [];
        }
        $weekly_schedule[$day][] = $sched;
    }

} catch (PDOException $e) {
    //  Error logging (useful for debugging)
    error_log("Error fetching schedule: " . $e->getMessage());
    $weekly_schedule = [];
}


