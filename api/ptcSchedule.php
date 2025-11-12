<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";

// AUTH CHECK: Only allow teachers
if (($_SESSION['account_type'] ?? '') !== 'teacher') {
    header("Content-Type: application/json");
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized or session expired."]);
    exit;
}

$teacher_id = $_SESSION["teacher_id"] ?? null;
if (!$teacher_id) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "No teacher session found."]);
    exit;
}

// --- POST/GET ACTIONS ---

try {
    // MARK SCHEDULE AS DONE
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mark_done"])) {
        header("Content-Type: application/json");
        $schedule_id = intval($_POST["schedule_id"]);

        $pdo->beginTransaction();
        
        // Get latest booked student for this schedule
        $stmt = $pdo->prepare("
            SELECT pb.student_id, s.Firstname, s.Lastname, s.studentCode,
                   ps.date, ps.startTime, ps.endTime
            FROM ptc_bookings pb
            JOIN students s ON pb.student_id = s.student_id
            JOIN ptc_schedules ps ON ps.schedule_id = pb.schedule_id
            WHERE pb.schedule_id=? AND pb.status='booked'
            ORDER BY pb.booking_id DESC LIMIT 1
        ");
        $stmt->execute([$schedule_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        $student_name = $student ? $student['Firstname'].' '.$student['Lastname'] : '-';
        $student_id = $student['student_id'] ?? null;
        $student_code = $student['studentCode'] ?? null; // <-- ADDED

        // Update schedule status to done
        $pdo->prepare("UPDATE ptc_schedules SET status='done', student_id=? WHERE schedule_id=? AND teacher_id=?")
            ->execute([$student_id, $schedule_id, $teacher_id]);

        // Update booking status to done
        if ($student_id) {
            $pdo->prepare("UPDATE ptc_bookings SET status='done' WHERE schedule_id=? AND student_id=?")
                ->execute([$schedule_id, $student_id]);
        }
        
        $pdo->commit();
        
        $startTime = $student['startTime'] ?? '00:00:00';
        $endTime = $student['endTime'] ?? '00:00:00';

        echo json_encode([
            "status"=>"success",
            "schedule_id"=>$schedule_id,
            "date"=>$student['date'] ?? date("Y-m-d"),
            "rawStartTime"=>date("H:i", strtotime($startTime)), // Raw for JS
            "rawEndTime"=>date("H:i", strtotime($endTime)),     // Raw for JS
            "student_name"=>$student_name,
            "studentCode"=>$student_code, // <-- ADDED
            "student_id"=>$student_id
        ]);
        exit;
    }

    // ADD NOTE TO SCHEDULE
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_note"])) {
        header("Content-Type: application/json");
        $schedule_id = intval($_POST["schedule_id"] ?? 0);
        $note = trim($_POST["note"] ?? "");

        if (!$schedule_id || $note === "") {
            echo json_encode(["status"=>"error","message"=>"Missing schedule or note."]);
            exit;
        }

        $pdo->beginTransaction();
        
        // Get student_id from schedule
        $stmt = $pdo->prepare("SELECT student_id FROM ptc_schedules WHERE schedule_id=? AND teacher_id=?");
        $stmt->execute([$schedule_id, $teacher_id]);
        $student_id = $stmt->fetchColumn();

        if (!$student_id) {
            // Fallback for older schedules, get from booking
            $stmt = $pdo->prepare("SELECT student_id FROM ptc_bookings WHERE schedule_id=? ORDER BY booking_id DESC LIMIT 1");
            $stmt->execute([$schedule_id]);
            $student_id = $stmt->fetchColumn();
        }

        if (!$student_id) {
            $pdo->rollBack();
            echo json_encode(["status"=>"error","message"=>"No student found for this schedule."]);
            exit;
        }

        // Insert note into ptc_notes
        $stmt = $pdo->prepare("INSERT INTO ptc_notes (schedule_id, teacher_id, student_id, note) VALUES (?,?,?,?)");
        $stmt->execute([$schedule_id, $teacher_id, $student_id, $note]);
        $new_note_id = $pdo->lastInsertId();
        
        $pdo->commit();

        echo json_encode([
            "status"=>"success",
            "message"=>"Note added successfully.",
            // Return the new note so it can be added to the UI
            "new_note" => [
                "note_id" => $new_note_id,
                "note" => $note,
                "created_at" => date("Y-m-d H:i:s") // Or "just now"
            ]
        ]);
        exit;
    }

    // UPDATE NOTE
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_note"])) {
        header("Content-Type: application/json");
        $note_id = intval($_POST["note_id"] ?? 0);
        $note = trim($_POST["note"] ?? "");

        if (!$note_id || $note === "") {
            echo json_encode(["status"=>"error","message"=>"Missing note ID or text."]);
            exit;
        }

        // Update the note, ensuring it belongs to the logged-in teacher
        $stmt = $pdo->prepare("UPDATE ptc_notes SET note=? WHERE note_id=? AND teacher_id=?");
        $stmt->execute([$note, $note_id, $teacher_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status"=>"success", "message"=>"Note updated."]);
        } else {
            echo json_encode(["status"=>"error", "message"=>"Note not found or no permission."]);
        }
        exit;
    }

    // DELETE NOTE
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_note"])) {
        header("Content-Type: application/json");
        $note_id = intval($_POST["note_id"] ?? 0);

        if (!$note_id) {
            echo json_encode(["status"=>"error","message"=>"Missing note ID."]);
            exit;
        }

        // Delete the note, ensuring it belongs to the logged-in teacher
        $stmt = $pdo->prepare("DELETE FROM ptc_notes WHERE note_id=? AND teacher_id=?");
        $stmt->execute([$note_id, $teacher_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status"=>"success", "message"=>"Note deleted."]);
        } else {
            echo json_encode(["status"=>"error", "message"=>"Note not found or no permission."]);
        }
        exit;
    }

    // CREATE NEW SCHEDULE (Form submission, not JSON)
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create"])) {
        $date = $_POST["date"] ?? null;
        $start = $_POST["start_time"] ?? null;
        $end = $_POST["end_time"] ?? null;

        if (!$date || !$start || !$end) {
            $_SESSION["error"] = "Missing fields.";
        } else if (strtotime($start) >= strtotime($end)) {
             $_SESSION["error"] = "Start time must be before end time.";
        } else if ($date < date("Y-m-d")) {
            $_SESSION["error"] = "Cannot set schedules in the past.";
        } else {
            // Check for overlap
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_schedules WHERE teacher_id=? AND date=? AND (startTime < ? AND endTime > ?)");
            $stmt->execute([$teacher_id, $date, $end, $start]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION["error"] = "Overlapping schedule detected.";
            } else {
                // No overlap, create schedule
                $pdo->prepare("INSERT INTO ptc_schedules (teacher_id, date, startTime, endTime, status) VALUES (?,?,?,?, 'open')")
                    ->execute([$teacher_id, $date, $start, $end]);
                $_SESSION["success"] = "Schedule created successfully.";
            }
        }
        
        // Redirect back to the page
        header("Location: ../pages/teacherPtcScheduler.php");
        exit;
    }

    // DELETE SCHEDULE (JSON request)
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_schedule'])) {
        header("Content-Type: application/json");
        $schedule_id = intval($_POST['schedule_id']);

        // Check if schedule exists, belongs to this teacher, and is open
        $stmt = $pdo->prepare("SELECT status FROM ptc_schedules WHERE schedule_id=? AND teacher_id=?");
        $stmt->execute([$schedule_id, $teacher_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            echo json_encode(["status" => "error", "message" => "Schedule not found."]);
            exit;
        }
        if ($schedule['status'] !== "open") {
            echo json_encode(["status" => "error", "message" => "Only open schedules can be deleted."]);
            exit;
        }

        $pdo->beginTransaction();
        // Delete related bookings first (if any)
        $pdo->prepare("DELETE FROM ptc_bookings WHERE schedule_id=?")->execute([$schedule_id]);
        // Delete related notes (if any)
        $pdo->prepare("DELETE FROM ptc_notes WHERE schedule_id=?")->execute([$schedule_id]);
        // Delete the schedule
        $pdo->prepare("DELETE FROM ptc_schedules WHERE schedule_id=? AND teacher_id=?")->execute([$schedule_id, $teacher_id]);
        $pdo->commit();

        echo json_encode(["status" => "success", "message" => "Schedule deleted successfully."]);
        exit;
    }

    // ===== DEFAULT ACTION: FETCH ALL SCHEDULES (JSON) =====
    // This is what the page calls on load
    
    // This query is now more robust. It gets student info for 'booked' and 'done' schedules.
    $stmt = $pdo->prepare("
        SELECT 
            ps.schedule_id, 
            ps.date, 
            TIME_FORMAT(ps.startTime, '%H:%i') AS startTime,
            TIME_FORMAT(ps.endTime, '%H:%i') AS endTime,
            ps.status,
            s.student_id,
            s.studentCode, -- <-- ADDED
            CONCAT(s.Firstname,' ',s.Lastname) AS student_name,
            -- Find out the booking status separately
            (SELECT pb.status 
             FROM ptc_bookings pb 
             WHERE pb.schedule_id = ps.schedule_id 
             ORDER BY pb.booking_id DESC LIMIT 1) AS booking_status
        FROM ptc_schedules ps
        -- LEFT JOIN for student info, based on the student_id stored on the SCHEDULE
        LEFT JOIN students s ON ps.student_id = s.student_id
        WHERE ps.teacher_id = ?
        ORDER BY ps.date ASC, ps.startTime ASC
    ");
    $stmt->execute([$teacher_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each schedule, fetch notes
    $noteStmt = $pdo->prepare("SELECT note_id, note, created_at FROM ptc_notes WHERE schedule_id=? ORDER BY created_at ASC");
    foreach ($schedules as &$s) {
        $noteStmt->execute([$s['schedule_id']]);
        $s['notes'] = $noteStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'schedules' => $schedules
    ]);

} catch (Exception $e) {
    // Catch any uncaught errors
    if (isset($pdo) && $pdo->inTransaction()) {
        // Only rollback if a transaction is active and failed
        $pdo->rollBack();
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>$e->getMessage(), "trace" => $e->getTraceAsString()]);
}
exit;
?>