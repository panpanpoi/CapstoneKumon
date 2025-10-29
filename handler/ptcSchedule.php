<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";
require_once "../handler/auth.php";

/**
 * Helper: Return JSON response
 */
function jsonResponse($success, $message = "", $data = null, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

// Ensure only teacher accounts access
if ($_SESSION['account_type'] !== 'teacher') {
    jsonResponse(false, "Unauthorized access", null, 403);
}

$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    jsonResponse(false, "Teacher ID missing from session", null, 400);
}

// Route requests
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === "GET") {
        // Fetch active schedules
        $stmt = $pdo->prepare("
            SELECT 
                s.schedule_id,
                s.date,
                s.startTime AS start_time,
                s.endTime AS end_time,
                s.status AS schedule_status,
                b.student_id,
                b.status AS booking_status,
                CONCAT(st.Firstname, ' ', st.Lastname) AS studentName
            FROM ptc_schedules s
            LEFT JOIN ptc_bookings b ON s.schedule_id = b.schedule_id
            LEFT JOIN students st ON b.student_id = st.student_id
            WHERE s.teacher_id = ?
            ORDER BY s.date, s.startTime
        ");
        $stmt->execute([$teacher_id]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(true, "Fetched active schedules", $schedules);

    } elseif ($method === "POST") {
        // Add new schedule
        $input = json_decode(file_get_contents("php://input"), true);
        $date = $input['date'] ?? null;
        $start_time = $input['start_time'] ?? null;
        $end_time = $input['end_time'] ?? null;

        if (!$date || !$start_time || !$end_time) {
            jsonResponse(false, "Missing required fields", null, 400);
        }

        // Insert schedule
        $stmt = $pdo->prepare("
            INSERT INTO ptc_schedules (teacher_id, date, startTime, endTime, status)
            VALUES (?, ?, ?, ?, 'open')
        ");
        $stmt->execute([$teacher_id, $date, $start_time, $end_time]);

        jsonResponse(true, "Schedule added successfully", [
            "schedule_id" => $pdo->lastInsertId(),
            "date" => $date,
            "start_time" => $start_time,
            "end_time" => $end_time
        ]);

    } elseif ($method === "DELETE") {
        // Delete schedule
        $input = json_decode(file_get_contents("php://input"), true);
        $schedule_id = $input['schedule_id'] ?? null;

        if (!$schedule_id) {
            jsonResponse(false, "Missing schedule ID", null, 400);
        }

        // Check if schedule is booked
        $stmt = $pdo->prepare("
            SELECT status FROM ptc_schedules WHERE schedule_id = ? AND teacher_id = ?
        ");
        $stmt->execute([$schedule_id, $teacher_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            jsonResponse(false, "Schedule not found", null, 404);
        }

        if ($schedule['status'] !== 'open') {
            jsonResponse(false, "Cannot delete booked or done schedules", null, 400);
        }

        $stmt = $pdo->prepare("DELETE FROM ptc_schedules WHERE schedule_id = ? AND teacher_id = ?");
        $stmt->execute([$schedule_id, $teacher_id]);

        jsonResponse(true, "Schedule deleted successfully");

    } elseif ($method === "PATCH") {
        // Mark schedule as done
        $input = json_decode(file_get_contents("php://input"), true);
        $schedule_id = $input['schedule_id'] ?? null;
        $note = $input['note'] ?? "";

        if (!$schedule_id) {
            jsonResponse(false, "Missing schedule ID", null, 400);
        }

        $pdo->beginTransaction();

        // Update schedule status
        $stmt = $pdo->prepare("
            UPDATE ptc_schedules SET status = 'done' WHERE schedule_id = ? AND teacher_id = ?
        ");
        $stmt->execute([$schedule_id, $teacher_id]);

        // Optional: Insert teacher note
        if (!empty($note)) {
            $stmt = $pdo->prepare("
                INSERT INTO ptc_notes (schedule_id, teacher_id, student_id, note)
                SELECT s.schedule_id, s.teacher_id, b.student_id, ?
                FROM ptc_schedules s
                LEFT JOIN ptc_bookings b ON s.schedule_id = b.schedule_id
                WHERE s.schedule_id = ?
            ");
            $stmt->execute([$note, $schedule_id]);
        }

        $pdo->commit();
        jsonResponse(true, "Schedule marked as done");

    } else {
        jsonResponse(false, "Unsupported request method", null, 405);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    jsonResponse(false, "Database error: " . $e->getMessage(), null, 500);
}
