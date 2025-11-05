<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

header('Content-Type: application/json; charset=utf-8');

// Helper to always return valid JSON
function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Only allow teachers
$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    jsonResponse(["success" => false, "message" => "Unauthorized access. Please log in again."]);
}

$filter_day = $_GET['day'] ?? 'all';

try {
    // Fetch all assigned students for this teacher
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
        // Fetch their schedules
        $schedStmt = $pdo->prepare("
            SELECT schedule_day, start_time, end_time 
            FROM class_schedules 
            WHERE class_id = ?
            ORDER BY schedule_id ASC
            LIMIT 2 -- Your modal only supports 2 schedules
        ");
        $schedStmt->execute([$st['class_id']]);
        $schedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

        // --- MODIFIED LOGIC ---
        
        $schedFormatted = [];
        $days = [];

        // Initialize raw fields for the "Edit" modal
        $schedule1_day = null;
        $schedule1_start = null;
        $schedule1_end = null;
        $schedule2_day = null;
        $schedule2_start = null;
        $schedule2_end = null;

        foreach ($schedules as $index => $sch) {
            $day = $sch['schedule_day'];
            
            // Raw times from DB (e.g., "13:00:00")
            $start_raw = $sch['start_time'];
            $end_raw = $sch['end_time'];
            
            // 1. Format for display (e.g., "01:00 PM")
            $start_display = date("h:i A", strtotime($start_raw));
            $end_display = date("h:i A", strtotime($end_raw));
            $schedFormatted[] = "{$day} {$start_display}–{$end_display}";
            $days[] = strtolower($day);

            // 2. Format for <input type="time"> (e.g., "13:00")
            $start_input = date("H:i", strtotime($start_raw));
            $end_input = date("H:i", strtotime($end_raw));

            // Assign to the correct schedule slot
            if ($index == 0) { // First schedule
                $schedule1_day = $day;
                $schedule1_start = $start_input;
                $schedule1_end = $end_input;
            } else if ($index == 1) { // Second schedule
                $schedule2_day = $day;
                $schedule2_start = $start_input;
                $schedule2_end = $end_input;
            }
        }
        // --- END MODIFIED LOGIC ---

        // Apply filter if needed
        if ($filter_day !== 'all' && !empty($days) && !in_array(strtolower($filter_day), $days)) {
            continue;
        }
        
        // Add all fields to the response
        $assigned[] = [
            "class_id"   => $st['class_id'],
            "student_id" => $st['student_id'],
            "studentCode"=> $st['studentCode'],
            "full_name"  => "{$st['Firstname']} {$st['Lastname']}",
            "level"      => $st['level'],
            "schedules"  => implode(", ", $schedFormatted), // For table display

            // NEW: Raw data for the "Edit" modal
            "schedule1_day"   => $schedule1_day,
            "schedule1_start" => $schedule1_start,
            "schedule1_end"   => $schedule1_end,
            "schedule2_day"   => $schedule2_day,
            "schedule2_start" => $schedule2_start,
            "schedule2_end"   => $schedule2_end
        ];
    }

    jsonResponse(["success" => true, "data" => $assigned]);

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Database error: " . $e->getMessage()]);
} catch (Throwable $e) {
    jsonResponse(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>