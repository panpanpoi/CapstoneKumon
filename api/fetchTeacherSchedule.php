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

$currentDay = date('l'); 
$todaysClasses = [];

try {
    // FINAL, CORRECTED SQL QUERY 
    $sql = "
        SELECT 
            s.Firstname, s.Lastname, cs.level,
            sch.start_time, sch.end_time 
        FROM class_schedules sch
        JOIN class_students cs ON sch.class_id = cs.class_id
        JOIN students s ON cs.student_id = s.student_id
        WHERE cs.teacher_id = ? 
        AND sch.schedule_day = ? 
        ORDER BY sch.start_time ASC
    ";

    if ($stmt = $pdo->prepare($sql)) {
        // Bind parameters: teacherId (int), currentDay (string)
        $stmt->execute([$teacher_id, $currentDay]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $row) {
            // Format time to readable 12-hour format
            $row['time_start'] = date("g:i A", strtotime($row['start_time']));
            $row['time_end'] = date("g:i A", strtotime($row['end_time']));
            
            // ✅ FIX: Combine name fields into a single 'fullName' field
            $row['fullName'] = trim($row['Firstname'] . ' ' . $row['Lastname']);
            
            // Remove raw time and separate name fields
            unset($row['start_time'], $row['end_time'], $row['Firstname'], $row['Lastname'], $row['Lastname']);
            
            $todaysClasses[] = $row;
        }
        
        jsonResponse([
            "success" => true, 
            "data" => $todaysClasses,
            "day" => $currentDay,
            "date" => date('F j, Y')
        ]);
    } else {
         jsonResponse(["success" => false, "message" => "Failed to prepare SQL statement."]);
    }

} catch (PDOException $e) {
    jsonResponse(["success" => false, "message" => "Database error: " . $e->getMessage()]);
} catch (Throwable $e) {
    jsonResponse(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>