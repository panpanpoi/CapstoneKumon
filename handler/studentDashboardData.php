<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['error'] = "Student not logged in.";
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// =======================
// 🎓 1. Fetch Student Info
// =======================
$stmt = $pdo->prepare("
    SELECT s.student_id, s.Firstname, s.Lastname, cs.class_id, cs.level
    FROM students s
    LEFT JOIN class_students cs ON s.student_id = cs.student_id
    WHERE s.student_id = ?
    LIMIT 1
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$class_id = $student['class_id'] ?? null;
$current_level = $student['level'] ?? null;

// =======================
// 💰 2. Latest Payment
// =======================
$stmt = $pdo->prepare("
    SELECT amount, payment_date
    FROM payments
    WHERE student_id = ?
    ORDER BY payment_date DESC
    LIMIT 1
");
$stmt->execute([$student_id]);
$latest_payment = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Format payment date
if ($latest_payment && !empty($latest_payment['payment_date'])) {
    $latest_payment['formatted_payment_date'] = date("F j, Y", strtotime($latest_payment['payment_date']));
}

// =======================
// 📅 3. Next Due Payment
// =======================
$stmt = $pdo->prepare("
    SELECT due_date
    FROM payments
    WHERE student_id = ? AND due_date >= CURDATE()
    ORDER BY due_date ASC
    LIMIT 1
");
$stmt->execute([$student_id]);
$next_due_raw = $stmt->fetchColumn();

$next_due = $next_due_raw 
    ? date("F j, Y", strtotime($next_due_raw)) 
    : null;

// =======================
// 🧑‍🏫 4. Upcoming PTC Meeting
// =======================
$stmt = $pdo->prepare("
    SELECT ps.date, ps.startTime, ps.endTime, ps.status
    FROM ptc_bookings pb
    INNER JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
    WHERE pb.student_id = ? AND ps.date >= CURDATE()
    ORDER BY ps.date ASC
    LIMIT 1
");
$stmt->execute([$student_id]);
$upcoming_ptc = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Format PTC meeting date and time
if ($upcoming_ptc) {
    if (!empty($upcoming_ptc['date'])) {
        $upcoming_ptc['formatted_date'] = date("F j, Y", strtotime($upcoming_ptc['date']));
    }
    if (!empty($upcoming_ptc['startTime'])) {
        $upcoming_ptc['formatted_start'] = date("g:i A", strtotime($upcoming_ptc['startTime']));
    }
    if (!empty($upcoming_ptc['endTime'])) {
        $upcoming_ptc['formatted_end'] = date("g:i A", strtotime($upcoming_ptc['endTime']));
    }
}

// =======================
// 🗓️ 5. Today's Schedule
// =======================
$today = date('l'); // e.g. Monday, Tuesday

$today_schedule = [];
if ($class_id) {
    $stmt = $pdo->prepare("
        SELECT cs.schedule_day, cs.start_time, cs.end_time, cs.class_id
        FROM class_schedules cs
        WHERE cs.class_id = ? AND cs.schedule_day = ?
        ORDER BY cs.start_time ASC
    ");
    $stmt->execute([$class_id, $today]);
    $today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Format times for each schedule
    foreach ($today_schedule as &$sched) {
        if (!empty($sched['start_time'])) {
            $sched['formatted_start'] = date("g:i A", strtotime($sched['start_time']));
        }
        if (!empty($sched['end_time'])) {
            $sched['formatted_end'] = date("g:i A", strtotime($sched['end_time']));
        }
    }
    unset($sched); // break reference
}

// ✅ Return all data
return [
    'student' => $student,
    'current_level' => $current_level,
    'latest_payment' => $latest_payment,
    'next_due' => $next_due,
    'upcoming_ptc' => $upcoming_ptc,
    'today_schedule' => $today_schedule
];
?>
