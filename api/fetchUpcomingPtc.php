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

try {
    // ✅ FIX: Added filter "AND ptc.status = 'booked'"
    // Now only schedules that are actually booked by a student will appear.
    $sql = "
        SELECT 
            ptc.schedule_id, 
            ptc.date, 
            ptc.startTime, 
            ptc.endTime,
            ptc.status,
            s.Firstname AS student_fname,
            s.Lastname AS student_lname
        FROM ptc_schedules ptc
        -- Join bookings to find who booked it
        LEFT JOIN ptc_bookings pb ON ptc.schedule_id = pb.schedule_id AND pb.status = 'booked'
        -- Join students to get the name
        LEFT JOIN students s ON pb.student_id = s.student_id
        WHERE ptc.teacher_id = ? 
        AND ptc.date >= CURDATE() 
        AND ptc.status = 'booked' -- <--- THIS LINE FILTERS OUT OPEN SLOTS
        ORDER BY ptc.date ASC, ptc.startTime ASC
        LIMIT 5
    ";

    if ($stmt = $pdo->prepare($sql)) {
        $stmt->execute([$teacher_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $upcomingPtc = [];
        
        foreach ($results as $row) {
            $status = $row['status'];
            $name = "Booked (Unknown)"; 

            // Format the name cleanly
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
                "status" => $status,
                "date_raw" => $row['date']
            ];
        }
        
        jsonResponse(["success" => true, "data" => $upcomingPtc]);
    } else {
         jsonResponse(["success" => false, "message" => "Failed to prepare SQL statement."]);
    }

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Database error: " . $e->getMessage()]);
} catch (Throwable $e) {
    jsonResponse(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>