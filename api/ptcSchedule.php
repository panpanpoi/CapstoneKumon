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

// Helper function to check if request expects JSON
function isJsonRequest() {
    return isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
}

// MARK SCHEDULE AS DONE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mark_done"])) {
    header("Content-Type: application/json");
    $schedule_id = intval($_POST["schedule_id"]);

    try {
        // Get latest booked student for this schedule
        $stmt = $pdo->prepare("
            SELECT pb.student_id, s.Firstname, s.Lastname, ps.date, ps.startTime, ps.endTime
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

        // Update schedule status to done
        $pdo->prepare("UPDATE ptc_schedules SET status='done', student_id=? WHERE schedule_id=? AND teacher_id=?")
            ->execute([$student_id, $schedule_id, $teacher_id]);

        // Update booking status to done
        if ($student_id) {
            $pdo->prepare("UPDATE ptc_bookings SET status='done' WHERE schedule_id=? AND student_id=?")
                ->execute([$schedule_id, $student_id]);
        }

        echo json_encode([
            "status"=>"success",
            "schedule_id"=>$schedule_id,
            "date"=>$student['date'] ?? date("Y-m-d"),
            "startTime"=>date("g:i A", strtotime($student['startTime'] ?? '00:00')),
            "endTime"=>date("g:i A", strtotime($student['endTime'] ?? '00:00')),
            "student_name"=>$student_name,
            "student_id"=>$student_id
        ]);
    } catch (Exception $e) {
        echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
    }
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

    try {
        // Get student_id from schedule or latest booking
        $stmt = $pdo->prepare("SELECT student_id FROM ptc_schedules WHERE schedule_id=?");
        $stmt->execute([$schedule_id]);
        $student_id = $stmt->fetchColumn();

        if (!$student_id) {
            $stmt = $pdo->prepare("SELECT student_id FROM ptc_bookings WHERE schedule_id=? ORDER BY booking_id DESC LIMIT 1");
            $stmt->execute([$schedule_id]);
            $student_id = $stmt->fetchColumn();
        }

        if (!$student_id) {
            echo json_encode(["status"=>"error","message"=>"No student found for this schedule."]);
            exit;
        }

        // Insert note into ptc_notes
        $pdo->prepare("INSERT INTO ptc_notes (schedule_id, teacher_id, student_id, note) VALUES (?,?,?,?)")
            ->execute([$schedule_id, $teacher_id, $student_id, $note]);

        echo json_encode(["status"=>"success","message"=>"Note added successfully."]);
    } catch (Exception $e) {
        echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
    }
    exit;
}

// CREATE NEW SCHEDULE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create"])) {
    $date = $_POST["date"] ?? null;
    $start = $_POST["start_time"] ?? null;
    $end = $_POST["end_time"] ?? null;

    if (!$date || !$start || !$end) {
        $_SESSION["error"] = "Missing fields.";
        header("Location: ../pages/teacherPtcScheduler.php");
        exit;
    }

    try {
        if ($date < date("Y-m-d")) {
            $_SESSION["error"] = "Cannot set schedules in the past.";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_schedules WHERE teacher_id=? AND date=? AND (startTime < ? AND endTime > ?)");
            $stmt->execute([$teacher_id, $date, $end, $start]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION["error"] = "Overlapping schedule detected.";
            } else {
                $pdo->prepare("INSERT INTO ptc_schedules (teacher_id, date, startTime, endTime, status) VALUES (?,?,?,?, 'open')")
                    ->execute([$teacher_id, $date, $start, $end]);
                $_SESSION["success"] = "Schedule created successfully.";
            }
        }
    } catch (Exception $e) {
        $_SESSION["error"] = "Database error: ".$e->getMessage();
    }

    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

// UPDATE EXISTING SCHEDULE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update"])) {
    $id = intval($_POST["schedule_id"] ?? 0);
    $date = $_POST["date"] ?? null;
    $start = $_POST["start_time"] ?? null;
    $end = $_POST["end_time"] ?? null;

    if (!$id || !$date || !$start || !$end) {
        $_SESSION["error"] = "Missing fields.";
        header("Location: ../pages/teacherPtcScheduler.php");
        exit;
    }

    try {
        if ($date < date("Y-m-d")) {
            $_SESSION["error"] = "Cannot update to a past date.";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_bookings WHERE schedule_id=? AND status='booked'");
            $stmt->execute([$id]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION["error"] = "Cannot edit a booked schedule.";
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_schedules WHERE teacher_id=? AND date=? AND schedule_id<>? AND (startTime < ? AND endTime > ?)");
                $stmt->execute([$teacher_id, $date, $id, $end, $start]);

                if ($stmt->fetchColumn() > 0) {
                    $_SESSION["error"] = "Overlaps with another slot.";
                } else {
                    $pdo->prepare("UPDATE ptc_schedules SET date=?, startTime=?, endTime=? WHERE schedule_id=? AND teacher_id=?")
                        ->execute([$date, $start, $end, $id, $teacher_id]);
                    $_SESSION["success"] = "Schedule updated successfully.";
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION["error"] = "Error: ".$e->getMessage();
    }

    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

// DELETE SCHEDULE
if (isset($_POST['delete_schedule']) && isset($_POST['schedule_id'])) {
    $schedule_id = intval($_POST['schedule_id']);

    try {
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

        // Delete related bookings first
        $pdo->prepare("DELETE FROM ptc_bookings WHERE schedule_id=?")->execute([$schedule_id]);

        // Delete the schedule
        $pdo->prepare("DELETE FROM ptc_schedules WHERE schedule_id=? AND teacher_id=?")->execute([$schedule_id, $teacher_id]);

        echo json_encode(["status" => "success", "message" => "Schedule deleted successfully."]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        exit;
    }
}

// FETCH ALL SCHEDULES FOR TEACHER (JSON)
$stmt = $pdo->prepare("
    SELECT 
        ps.schedule_id, 
        ps.date, 
        TIME_FORMAT(ps.startTime, '%H:%i') AS startTime,
        TIME_FORMAT(ps.endTime, '%H:%i') AS endTime,
        ps.status,
        pb.status AS booking_status,
        CONCAT(s.Firstname,' ',s.Lastname) AS student_name
    FROM ptc_schedules ps
    LEFT JOIN ptc_bookings pb 
        ON ps.schedule_id = pb.schedule_id AND pb.status IN ('booked','done')
    LEFT JOIN students s 
        ON pb.student_id = s.student_id
    WHERE ps.teacher_id = ?
    ORDER BY ps.date ASC, ps.startTime ASC
");
$stmt->execute([$teacher_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For each schedule, fetch notes
foreach ($schedules as &$s) {
    $noteStmt = $pdo->prepare("SELECT note, created_at FROM ptc_notes WHERE schedule_id=? ORDER BY created_at DESC");
    $noteStmt->execute([$s['schedule_id']]);
    $s['notes'] = $noteStmt->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'schedules' => $schedules
]);


