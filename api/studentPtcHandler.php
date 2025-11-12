<?php
if (!isset($_SESSION)) session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/auth.php'; // ensures logged-in + sets session ids

// Restrict to students only
if (($_SESSION['account_type'] ?? '') !== 'student') {
    header("Location: ../login.php");
    exit;
}

// --- [START OF FIX] ---
// Define $student_id from the session
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    // This should be caught by auth.php, but as a fallback:
    $_SESSION['error'] = "You must be logged in.";
    header("Location: ../login.php");
    exit;
}
// --- [END OF FIX] ---


// --- [MODIFIED] GET YEAR AND MONTH FILTER ---
// Get the year from the URL (e.g., studentPtc.php?filter_year=2025)
// Default to the current year if not set
$filter_year = isset($_GET['filter_year']) ? (int)$_GET['filter_year'] : (int)date('Y');
// Get month. Default to the CURRENT MONTH if not set.
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : (int)date('n'); // 'n' = current month (1-12)


// Handle Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // --- BOOK ---
        if (isset($_POST['book'], $_POST['schedule_id'])) {
            $schedule_id = (int) $_POST['schedule_id'];

            // Check if already has a booking
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_bookings WHERE student_id = ? AND status = 'booked'");
            $stmt->execute([$student_id]);
            if ($stmt->fetchColumn() > 0) throw new Exception("You already have an active booking.");

            // Validate schedule
            $stmt = $pdo->prepare("
                SELECT ps.schedule_id, ps.date, ps.startTime
                FROM ptc_schedules ps
                JOIN class_students cs ON ps.teacher_id = cs.teacher_id
                WHERE ps.schedule_id = ? AND cs.student_id = ? AND ps.status = 'open'
            ");
            $stmt->execute([$schedule_id, $student_id]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$schedule) throw new Exception("Invalid or unavailable schedule.");

            // Prevent past bookings
            $scheduleTime = new DateTime($schedule['date'] . ' ' . $schedule['startTime']);
            if ($scheduleTime < new DateTime()) throw new Exception("Cannot book a past schedule.");

            // Insert booking
            $pdo->prepare("INSERT INTO ptc_bookings (student_id, schedule_id, status) VALUES (?, ?, 'booked')")
                ->execute([$student_id, $schedule_id]);
            $pdo->prepare("UPDATE ptc_schedules SET status = 'booked' WHERE schedule_id = ?")
                ->execute([$schedule_id]);

            $_SESSION['success'] = "PTC booking confirmed.";
        }

        // --- OLD CANCEL LOGIC IS REMOVED ---
        // This is now handled by api/cancelBooking.php

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }

    // Redirect back to the page, preserving the filter
    header("Location: ../pages/studentPtc.php?filter_year=" . $filter_year . "&filter_month=" . $filter_month);
    exit;
}


// Fetch Current Booking

$currentBooking = null;
$stmt = $pdo->prepare("
    SELECT pb.booking_id, ps.schedule_id, ps.date, ps.startTime, ps.endTime,
           CONCAT(u.Name,' ',u.Surname) AS teacherName
    FROM ptc_bookings pb
    JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
    JOIN teachers t ON ps.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.user_id
    WHERE pb.student_id = ? AND pb.status = 'booked'
      AND (ps.date > CURDATE() OR (ps.date = CURDATE() AND ps.startTime >= TIME(NOW())))
    ORDER BY ps.date, ps.startTime
    LIMIT 1
");
$stmt->execute([$student_id]);
$currentBooking = $stmt->fetch(PDO::FETCH_ASSOC);


// Fetch Available Slots (only if no current booking)

$availableSchedules = [];
if (!$currentBooking) {
    // [START OF FIX]
    // The query logic was missing here
    $stmt = $pdo->prepare("
        SELECT ps.schedule_id, ps.date, ps.startTime, ps.endTime,
               CONCAT(u.Name,' ',u.Surname) AS teacherName
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
    // [END OF FIX]
}

// Fetch Completed PTCs with Notes
$doneBookings = [];

// [MODIFIED] Build query dynamically
$sql = "
    SELECT pb.booking_id, ps.schedule_id, ps.date, CONCAT(u.Name,' ',u.Surname) AS teacherName
    FROM ptc_bookings pb
    JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
    JOIN teachers t ON ps.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.user_id
    WHERE pb.student_id = ? 
      AND pb.status = 'done'
      AND YEAR(ps.date) = ?
";

$params = [$student_id, $filter_year];

// [NEW] Add month filter if it's not 'all' or empty
if ($filter_month !== 'all' && $filter_month !== '') {
    $sql .= " AND MONTH(ps.date) = ?";
    $params[] = (int)$filter_month;
}       

$sql .= " ORDER BY ps.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params); // Execute with dynamic params
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($bookings as $b) {
    // Fetch notes for this schedule
    $stmtNotes = $pdo->prepare("SELECT note, created_at FROM ptc_notes WHERE schedule_id = ? ORDER BY created_at DESC");
    $stmtNotes->execute([$b['schedule_id']]);
    $b['notes'] = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);
    $doneBookings[] = $b;
}
?>