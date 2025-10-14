<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";
require_once "auth.php";

// ✅ Ensure only teacher
if (($_SESSION['account_type'] ?? '') !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// ===============================
// ✅ MARK schedule as DONE
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_done'])) {
    $schedule_id = intval($_POST['schedule_id']);
    
    try {
        // Update schedule status to 'done'
        $stmt = $pdo->prepare("UPDATE ptc_schedules SET status='done' WHERE schedule_id=? AND teacher_id=?");
        $stmt->execute([$schedule_id, $teacher_id]);

        // Insert attendance for booked students
        $stmt = $pdo->prepare("
            SELECT pb.student_id, ps.date, ps.startTime, ps.endTime
            FROM ptc_bookings pb
            JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
            WHERE pb.schedule_id = ? AND pb.status='booked'
        ");
        $stmt->execute([$schedule_id]);
        $bookedStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($bookedStudents as $b) {
            $insert = $pdo->prepare("
                INSERT INTO attendance (student_id, teacher_id, date, type, status)
                VALUES (?, ?, ?, 'PTC', 'Present')
            ");
            $insert->execute([$b['student_id'], $teacher_id, $b['date']]);
        }

        $_SESSION['success'] = "✅ Schedule marked as done.";
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ Failed to mark schedule as done: " . $e->getMessage();
    }

    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

// ===============================
// 📌 CREATE schedule
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $date  = $_POST['date'] ?? null;
    $start = $_POST['start_time'] ?? null;
    $end   = $_POST['end_time'] ?? null;

    if ($date && $start && $end) {
        $today = date("Y-m-d");
        if ($date < $today) {
            $_SESSION['error'] = "⚠️ Cannot set schedules in the past. Today is {$today}, you selected {$date}.";
        } else {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM ptc_schedules 
                WHERE teacher_id=? AND date=? 
                AND (startTime < ? AND endTime > ?)
            ");
            $stmt->execute([$teacher_id, $date, $end, $start]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "⚠️ Schedule overlaps with an existing slot.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO ptc_schedules (teacher_id, `date`, startTime, endTime, status) 
                    VALUES (?, ?, ?, ?, 'open')
                ");
                $stmt->execute([$teacher_id, $date, $start, $end]);
                $_SESSION['success'] = "✅ Schedule created successfully.";
            }
        }
    }
    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

// ===============================
// ✏️ UPDATE schedule
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id    = $_POST['schedule_id'] ?? null;
    $date  = $_POST['date'] ?? null;
    $start = $_POST['start_time'] ?? null;
    $end   = $_POST['end_time'] ?? null;

    if ($id && $date && $start && $end) {
        $today = date("Y-m-d");
        if ($date < $today) {
            $_SESSION['error'] = "⚠️ Cannot update schedule to a past date. Today is {$today}, you selected {$date}.";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_bookings WHERE schedule_id=? AND status='booked'");
            $stmt->execute([$id]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "⚠️ Cannot edit. This schedule is already booked.";
            } else {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM ptc_schedules 
                    WHERE teacher_id=? AND date=? AND schedule_id<>? 
                    AND (startTime < ? AND endTime > ?)
                ");
                $stmt->execute([$teacher_id, $date, $id, $end, $start]);

                if ($stmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "⚠️ Updated schedule conflicts with an existing slot.";
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE ptc_schedules 
                        SET `date`=?, startTime=?, endTime=? 
                        WHERE schedule_id=? AND teacher_id=?
                    ");
                    $stmt->execute([$date, $start, $end, $id, $teacher_id]);
                    $_SESSION['success'] = "✅ Schedule updated successfully.";
                }
            }
        }
    }
    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

// ===============================
// ❌ DELETE schedule
// ===============================
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_bookings WHERE schedule_id=? AND status='booked'");
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "⚠️ Cannot delete. This schedule is already booked.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM ptc_bookings WHERE schedule_id=?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM ptc_schedules WHERE schedule_id=? AND teacher_id=?");
        $stmt->execute([$id, $teacher_id]);

        $_SESSION['success'] = "✅ Schedule deleted.";
    }

    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

// ===============================
// 📝 ADD PTC NOTE
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $note = trim($_POST['note'] ?? '');

    if ($schedule_id && $note) {
        $stmt = $pdo->prepare("
            SELECT pb.student_id 
            FROM ptc_bookings pb
            WHERE pb.schedule_id=? AND pb.status='booked' LIMIT 1
        ");
        $stmt->execute([$schedule_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $stmt = $pdo->prepare("
                INSERT INTO ptc_notes (schedule_id, teacher_id, student_id, note)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$schedule_id, $teacher_id, $student['student_id'], $note]);
            $_SESSION['success'] = "✅ Note added successfully.";
        } else {
            $_SESSION['error'] = "⚠️ Cannot add note. No student booked for this schedule.";
        }
    }
    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

// ===============================
// 📋 FETCH teacher schedules
// ===============================
$stmt = $pdo->prepare("
    SELECT ps.schedule_id, ps.date, 
           TIME_FORMAT(ps.startTime, '%h:%i %p') AS startTime, 
           TIME_FORMAT(ps.endTime, '%h:%i %p') AS endTime, 
           ps.status,
           (SELECT pb.status 
              FROM ptc_bookings pb 
             WHERE pb.schedule_id = ps.schedule_id 
             ORDER BY pb.booking_id DESC LIMIT 1) AS booking_status,
           (SELECT CONCAT(u.Name, ' ', u.Surname) 
              FROM ptc_bookings pb 
              JOIN students s ON pb.student_id = s.student_id
              JOIN users u ON s.user_id = u.user_id
             WHERE pb.schedule_id = ps.schedule_id 
               AND pb.status = 'booked'
             ORDER BY pb.booking_id DESC LIMIT 1) AS student_name
    FROM ptc_schedules ps
    WHERE ps.teacher_id = ?
    ORDER BY ps.date, ps.startTime
");
$stmt->execute([$teacher_id]);
$teacherSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
