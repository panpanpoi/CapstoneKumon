<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    jsonResponse(["success" => false, "message" => "Unauthorized."]);
}

try {
    // ✅ FIX: JOIN logic now accepts 'booked' OR 'approved'
    $sql = "
        SELECT 
            ptc.schedule_id, 
            ptc.date, 
            ptc.startTime, 
            ptc.endTime,
            -- Get the status from the booking if it exists, otherwise use schedule status
            COALESCE(pb.status, ptc.status) as real_status,
            s.Firstname AS student_fname,
            s.Lastname AS student_lname
        FROM ptc_schedules ptc
        -- Join bookings: Check for BOTH statuses
        LEFT JOIN ptc_bookings pb ON ptc.schedule_id = pb.schedule_id 
                                  AND pb.status IN ('booked', 'approved')
        LEFT JOIN students s ON pb.student_id = s.student_id
        WHERE ptc.teacher_id = ? 
        AND ptc.date >= CURDATE() 
        AND ptc.status = 'booked' -- The slot is occupied in the schedule table
        ORDER BY ptc.date ASC, ptc.startTime ASC
        LIMIT 5
    ";

    if ($stmt = $pdo->prepare($sql)) {
        $stmt->execute([$teacher_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $upcomingPtc = [];
        
        foreach ($results as $row) {
            $status = $row['real_status']; // 'booked' or 'approved'
            $name = "Booked Slot"; 

            if (!empty($row['student_fname'])) {
                $name = trim($row['student_fname'] . ' ' . $row['student_lname']);
            }
            
            // Format date and time
            $dateFormatted = date("M d", strtotime($row['date'])); 
            $timeFormatted = date("g:i A", strtotime($row['startTime'])); 
            
            $upcomingPtc[] = [
                "id" => $row['schedule_id'],
                "name" => $name,
                "date" => $dateFormatted,
                "time" => $timeFormatted,
                "status" => $status, // Passing 'approved' or 'booked' to JS
                "date_raw" => $row['date']
            ];
        }
        
        jsonResponse(["success" => true, "data" => $upcomingPtc]);
    } else {
         jsonResponse(["success" => false, "message" => "Query error."]);
    }

} catch (Exception $e) {
    jsonResponse(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>