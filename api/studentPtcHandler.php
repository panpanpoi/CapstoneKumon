<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/auth.php';

if (($_SESSION['account_type'] ?? '') !== 'student') { header("Location: ../login.php"); exit; }
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) { header("Location: ../login.php"); exit; }

$filter_year = isset($_GET['filter_year']) ? (int)$_GET['filter_year'] : (int)date('Y');
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : (int)date('n');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        if (isset($_POST['book'], $_POST['schedule_id'])) {
            $schedule_id = (int) $_POST['schedule_id'];

            // Check for active bookings (booked OR approved)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM ptc_bookings pb
                JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
                WHERE pb.student_id = ? 
                  AND pb.status IN ('booked', 'approved') 
                  AND (ps.date > CURDATE() OR (ps.date = CURDATE() AND ps.endTime > TIME(NOW())))
            ");
            $stmt->execute([$student_id]);
            if ($stmt->fetchColumn() > 0) throw new Exception("You already have an upcoming active booking.");

            $stmt = $pdo->prepare("SELECT * FROM ptc_schedules WHERE schedule_id = ? AND status = 'open'");
            $stmt->execute([$schedule_id]);
            $schedule = $stmt->fetch();
            if (!$schedule) throw new Exception("Unavailable schedule.");

            $pdo->prepare("INSERT INTO ptc_bookings (student_id, schedule_id, status) VALUES (?, ?, 'booked')")->execute([$student_id, $schedule_id]);
            $pdo->prepare("UPDATE ptc_schedules SET status = 'booked' WHERE schedule_id = ?")->execute([$schedule_id]);
            $_SESSION['success'] = "PTC booking confirmed.";
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: ../pages/studentPtc.php?filter_year=$filter_year&filter_month=$filter_month");
    exit;
}

// FETCH CURRENT BOOKING (booked OR approved)
$currentBooking = null;
$stmt = $pdo->prepare("
    SELECT pb.booking_id, ps.schedule_id, ps.date, ps.startTime, ps.endTime, pb.status,
           CONCAT(u.Name,' ',u.Surname) AS teacherName
    FROM ptc_bookings pb
    JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
    JOIN teachers t ON ps.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.user_id
    WHERE pb.student_id = ? AND pb.status IN ('booked', 'approved')
      AND (ps.date > CURDATE() OR (ps.date = CURDATE() AND ps.startTime >= TIME(NOW())))
    ORDER BY ps.date, ps.startTime LIMIT 1
");
$stmt->execute([$student_id]);
$currentBooking = $stmt->fetch(PDO::FETCH_ASSOC);

// FETCH AVAILABLE
$availableSchedules = [];
if (!$currentBooking) {
    $stmt = $pdo->prepare("
        SELECT ps.schedule_id, ps.date, ps.startTime, ps.endTime, CONCAT(u.Name,' ',u.Surname) AS teacherName
        FROM ptc_schedules ps
        JOIN teachers t ON ps.teacher_id = t.teacher_id
        JOIN users u ON t.user_id = u.user_id
        JOIN class_students cs ON ps.teacher_id = cs.teacher_id
        WHERE cs.student_id = ? AND ps.status = 'open'
          AND (ps.date > CURDATE() OR (ps.date = CURDATE() AND ps.startTime >= TIME(NOW())))
        ORDER BY ps.date, ps.startTime
    ");
    $stmt->execute([$student_id]);
    $availableSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// FETCH DONE
$doneBookings = [];
$sql = "SELECT pb.booking_id, ps.schedule_id, ps.date, CONCAT(u.Name,' ',u.Surname) AS teacherName
    FROM ptc_bookings pb
    JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
    JOIN teachers t ON ps.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.user_id
    WHERE pb.student_id = ? AND pb.status = 'done' AND YEAR(ps.date) = ?";
$params = [$student_id, $filter_year];
if ($filter_month !== 'all' && $filter_month !== '') { $sql .= " AND MONTH(ps.date) = ?"; $params[] = (int)$filter_month; }
$sql .= " ORDER BY ps.date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($bookings as $b) {
    $stmtN = $pdo->prepare("SELECT note, created_at FROM ptc_notes WHERE schedule_id = ? ORDER BY created_at DESC");
    $stmtN->execute([$b['schedule_id']]);
    $b['notes'] = $stmtN->fetchAll(PDO::FETCH_ASSOC);
    $doneBookings[] = $b;
}
?>