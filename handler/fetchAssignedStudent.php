<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

header('Content-Type: application/json; charset=utf-8');

// ✅ Only allow teachers
$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

// Optional day filter
$filter_day = $_GET['day'] ?? 'all';

try {
    // 🎯 Fetch all assigned students for this teacher
    $stmt = $pdo->prepare("
        SELECT cs.class_id, s.student_id, s.studentCode, s.Firstname, s.Lastname, cs.level
        FROM class_students cs
        JOIN students s ON s.student_id = cs.student_id
        WHERE cs.teacher_id = ?
        ORDER BY s.Firstname ASC
    ");
    $stmt->execute([$teacher_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $assigned = [];

    foreach ($students as $st) {
        // 📅 Fetch their schedules
        $schedStmt = $pdo->prepare("
            SELECT schedule_day, start_time, end_time 
            FROM class_schedules 
            WHERE class_id = ?
            ORDER BY schedule_id ASC
        ");
        $schedStmt->execute([$st['class_id']]);
        $schedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

        // 🕒 Format each schedule
        $schedFormatted = [];
        $days = [];

        foreach ($schedules as $sch) {
            $day = $sch['schedule_day'];
            $start = date("h:i A", strtotime($sch['start_time']));
            $end   = date("h:i A", strtotime($sch['end_time']));
            $schedFormatted[] = "{$day} {$start}–{$end}";
            $days[] = strtolower($day);
        }

        // 🧭 Apply filter if needed
        if ($filter_day !== 'all' && !in_array(strtolower($filter_day), $days)) {
            continue;
        }

        $assigned[] = [
            "class_id"   => $st['class_id'],
            "student_id" => $st['student_id'],
            "studentCode"=> $st['studentCode'],
            "full_name"  => "{$st['Firstname']} {$st['Lastname']}",
            "level"      => $st['level'],
            "schedules"  => implode(", ", $schedFormatted)
        ];
    }

    echo json_encode(["success" => true, "data" => $assigned], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>
