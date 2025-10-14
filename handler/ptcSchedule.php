<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";

// -------------------------
// AUTH CHECK
// -------------------------
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

// -------------------------
// HELPER: detect JSON/AJAX request
// -------------------------
function isJsonRequest() {
    return isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
}

// =======================
// MARK SCHEDULE AS DONE
// =======================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mark_done"])) {
    header("Content-Type: application/json");
    $schedule_id = intval($_POST["schedule_id"]);

    try {
        // Get schedule + booked student
        $stmt = $pdo->prepare("
            SELECT ps.date, ps.startTime, ps.endTime, pb.student_id,
                   CONCAT(s.Firstname, ' ', s.Lastname) AS student_name
            FROM ptc_schedules ps
            LEFT JOIN ptc_bookings pb 
                ON ps.schedule_id = pb.schedule_id AND pb.status='booked'
            LEFT JOIN students s 
                ON pb.student_id = s.student_id
            WHERE ps.schedule_id=? AND ps.teacher_id=?
            LIMIT 1
        ");
        $stmt->execute([$schedule_id, $teacher_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            echo json_encode(["status"=>"error","message"=>"Schedule not found."]);
            exit;
        }

        // Mark schedule as done
        $pdo->prepare("UPDATE ptc_schedules SET status='done' WHERE schedule_id=? AND teacher_id=?")
            ->execute([$schedule_id, $teacher_id]);

        // Mark booking as done instead of deleting
        if ($schedule['student_id']) {
            $pdo->prepare("UPDATE ptc_bookings SET status='done' WHERE schedule_id=? AND student_id=?")
                ->execute([$schedule_id, $schedule['student_id']]);
        }

        echo json_encode([
            "status" => "success",
            "schedule_id" => $schedule_id,
            "date" => $schedule['date'],
            "startTime" => date("g:i A", strtotime($schedule['startTime'])),
            "endTime" => date("g:i A", strtotime($schedule['endTime'])),
            "student_name" => $schedule['student_name'] ?? "-",
            "student_id" => $schedule['student_id'] ?? null
        ]);

    } catch (Exception $e) {
        echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
    }
    exit;
}

// =======================
// ADD NOTE (AJAX)
// =======================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_note"])) {
    header("Content-Type: application/json");
    $schedule_id = intval($_POST["schedule_id"] ?? 0);
    $note = trim($_POST["note"] ?? "");

    if (!$schedule_id || $note === "") {
        echo json_encode(["status"=>"error","message"=>"Missing schedule or note."]);
        exit;
    }

    try {
        // Get student_id from booking or done schedule
        $stmt = $pdo->prepare("
            SELECT pb.student_id
            FROM ptc_bookings pb
            WHERE pb.schedule_id=? 
            UNION
            SELECT ps.schedule_id
            FROM ptc_schedules ps
            WHERE ps.schedule_id=? AND ps.status='done'
            LIMIT 1
        ");
        $stmt->execute([$schedule_id, $schedule_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $student_id = $student['student_id'] ?? null;

        if (!$student_id) {
            echo json_encode(["status"=>"error","message"=>"No student found for this schedule."]);
            exit;
        }

        $insert = $pdo->prepare("
            INSERT INTO ptc_notes (schedule_id, teacher_id, student_id, note)
            VALUES (?, ?, ?, ?)
        ");
        $insert->execute([$schedule_id, $teacher_id, $student_id, $note]);

        echo json_encode(["status"=>"success","message"=>"Note added successfully."]);

    } catch (Exception $e) {
        echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
    }
    exit;
}


/* ===========================================================
   3️⃣ CREATE SCHEDULE
=========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create"])) {
    $date = $_POST["date"] ?? null;
    $start = $_POST["start_time"] ?? null;
    $end = $_POST["end_time"] ?? null;

    if (!$date || !$start || !$end) {
        $_SESSION["error"] = "⚠️ Missing fields.";
        header("Location: ../pages/teacherPtcScheduler.php");
        exit;
    }

    try {
        $today = date("Y-m-d");
        if ($date < $today) {
            $_SESSION["error"] = "⚠️ Cannot set schedules in the past.";
        } else {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM ptc_schedules 
                WHERE teacher_id=? AND date=? 
                AND (startTime < ? AND endTime > ?)
            ");
            $stmt->execute([$teacher_id, $date, $end, $start]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION["error"] = "⚠️ Overlapping schedule detected.";
            } else {
                $pdo->prepare("INSERT INTO ptc_schedules (teacher_id, date, startTime, endTime, status) VALUES (?, ?, ?, ?, 'open')")
                    ->execute([$teacher_id, $date, $start, $end]);
                $_SESSION["success"] = "✅ Schedule created successfully.";
            }
        }
    } catch (Exception $e) {
        $_SESSION["error"] = "❌ Database error: " . $e->getMessage();
    }

    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

/* ===========================================================
   4️⃣ UPDATE SCHEDULE
=========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update"])) {
    $id = intval($_POST["schedule_id"] ?? 0);
    $date = $_POST["date"] ?? null;
    $start = $_POST["start_time"] ?? null;
    $end = $_POST["end_time"] ?? null;

    if (!$id || !$date || !$start || !$end) {
        $_SESSION["error"] = "⚠️ Missing fields.";
        header("Location: ../pages/teacherPtcScheduler.php");
        exit;
    }

    try {
        $today = date("Y-m-d");
        if ($date < $today) {
            $_SESSION["error"] = "⚠️ Cannot update to a past date.";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_bookings WHERE schedule_id=? AND status='booked'");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION["error"] = "⚠️ Cannot edit a booked schedule.";
            } else {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM ptc_schedules
                    WHERE teacher_id=? AND date=? AND schedule_id<>?
                    AND (startTime < ? AND endTime > ?)
                ");
                $stmt->execute([$teacher_id, $date, $id, $end, $start]);

                if ($stmt->fetchColumn() > 0) {
                    $_SESSION["error"] = "⚠️ Overlaps with another slot.";
                } else {
                    $pdo->prepare("UPDATE ptc_schedules SET date=?, startTime=?, endTime=? WHERE schedule_id=? AND teacher_id=?")
                        ->execute([$date, $start, $end, $id, $teacher_id]);
                    $_SESSION["success"] = "✅ Schedule updated successfully.";
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION["error"] = "❌ Error: " . $e->getMessage();
    }

    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

/* ===========================================================
   5️⃣ DELETE SCHEDULE
=========================================================== */
if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_bookings WHERE schedule_id=? AND status='booked'");
        $stmt->execute([$id]);

        if ($stmt->fetchColumn() > 0) {
            $_SESSION["error"] = "⚠️ Cannot delete. Schedule is booked.";
        } else {
            $pdo->prepare("DELETE FROM ptc_bookings WHERE schedule_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM ptc_schedules WHERE schedule_id=? AND teacher_id=?")->execute([$id, $teacher_id]);
            $_SESSION["success"] = "✅ Schedule deleted successfully.";
        }
    } catch (Exception $e) {
        $_SESSION["error"] = "❌ Database error: " . $e->getMessage();
    }

    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

/* ===========================================================
   📋 FETCH TEACHER SCHEDULES (DISPLAY)
   =========================================================== */
$stmt = $pdo->prepare("
    SELECT 
        ps.schedule_id, 
        ps.date, 
        TIME_FORMAT(ps.startTime, '%h:%i %p') AS startTime,
        TIME_FORMAT(ps.endTime, '%h:%i %p') AS endTime,
        ps.status,
        (
            SELECT CONCAT(u.Name,' ',u.Surname)
            FROM ptc_bookings pb
            JOIN students s ON pb.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            WHERE pb.schedule_id = ps.schedule_id
              AND pb.status='done'
            LIMIT 1
        ) AS student_name
    FROM ptc_schedules ps
    WHERE ps.teacher_id = ?
    ORDER BY ps.date, ps.startTime
");
$stmt->execute([$teacher_id]);
$teacherSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
