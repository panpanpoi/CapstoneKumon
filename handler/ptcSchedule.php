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
// 📌 CREATE schedule
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $date  = $_POST['date'] ?? null;
    $start = $_POST['start_time'] ?? null;
    $end   = $_POST['end_time'] ?? null;

    if ($date && $start && $end) {
        if ($date < date("Y-m-d")) {
            $_SESSION['error'] = "⚠️ Cannot set schedules in the past.";
        } else {
            // ⛔ Prevent overlaps
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
        if ($date < date("Y-m-d")) {
            $_SESSION['error'] = "⚠️ Cannot update schedules in the past.";
        } else {
            // ❌ Block if still booked
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_bookings WHERE schedule_id=? AND status='booked'");
            $stmt->execute([$id]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "⚠️ Cannot edit. This schedule is already booked.";
            } else {
                // ⛔ Prevent overlap (exclude itself)
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

    // ❌ Block delete if still actively booked
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_bookings WHERE schedule_id=? AND status='booked'");
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "⚠️ Cannot delete. This schedule is already booked.";
    } else {
        // ✅ Clean up cancelled/old bookings first
        $stmt = $pdo->prepare("DELETE FROM ptc_bookings WHERE schedule_id=?");
        $stmt->execute([$id]);

        // ✅ Then delete the schedule
        $stmt = $pdo->prepare("DELETE FROM ptc_schedules WHERE schedule_id=? AND teacher_id=?");
        $stmt->execute([$id, $teacher_id]);

        $_SESSION['success'] = "✅ Schedule deleted.";
    }

    header("Location: ../pages/teacherPtcScheduler.php");
    exit;
}

// ===============================
// 📋 FETCH teacher schedules
// ===============================
$stmt = $pdo->prepare("
    SELECT ps.schedule_id, ps.date, ps.startTime, ps.endTime, ps.status,
           -- latest booking info (if any)
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
