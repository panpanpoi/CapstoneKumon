<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['error'] = "Student not logged in.";
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// 1. Fetch student information
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

// 2. Get latest payment details
$stmt = $pdo->prepare("
    SELECT amount, payment_date
    FROM payments
    WHERE student_id = ?
    ORDER BY payment_date DESC
    LIMIT 1
");
$stmt->execute([$student_id]);
$latest_payment = $stmt->fetch(PDO::FETCH_ASSOC);

// Format payment date
if ($latest_payment && !empty($latest_payment['payment_date'])) {
    $latest_payment['formatted_payment_date'] = date("F j, Y", strtotime($latest_payment['payment_date']));
}

// -----------------------------------------------------------------
// 3. Get next due payment date (REVISED LOGIC)
// -----------------------------------------------------------------

$next_due = null; // Default

// --- Strategy 1: Find latest VERIFIED payment and add 1 month ---
$stmt = $pdo->prepare("
    SELECT tf_month_covered 
    FROM payments 
    WHERE student_id = ? AND payment_status = 'verified'
");
$stmt->execute([$student_id]);
$verified_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($verified_payments) > 0) {
    $covered_dates = [];
    foreach ($verified_payments as $p) {
        // Convert "Month YYYY" string (e.g., "March 2026") to a valid DateTime object
        $date = date_create_from_format('F Y', $p['tf_month_covered']);
        if ($date) {
            $covered_dates[] = $date->modify('first day of this month');
        }
    }

    if (count($covered_dates) > 0) {
        // Find the latest (max) date from the verified payments
        $latest_covered_date = max($covered_dates);
        // Add 1 month to get the next due date
        $latest_covered_date->modify('+1 month');
        // Format as "F j, Y" (e.g., April 1, 2026)
        $next_due = $latest_covered_date->format('F j, Y');
    }
}

// --- Strategy 2: If no verified payment, find earliest FUTURE due date (for new students) ---
if ($next_due === null) {
    $stmt = $pdo->prepare("
        SELECT due_date
        FROM payments
        WHERE student_id = ? AND due_date >= CURDATE()
        ORDER BY due_date ASC
        LIMIT 1
    ");
    $stmt->execute([$student_id]);
    $next_due_raw = $stmt->fetchColumn();

    if ($next_due_raw) {
         $next_due = date("F j, Y", strtotime($next_due_raw));
    }
}

// --- Strategy 3: Final fallback if still nothing ---
if ($next_due === null) {
    // Defaults to the first of next month
    $next_due = date("F 1, Y", strtotime("first day of next month"));
}

// -----------------------------------------------------------------
// (End of Next Due Logic)
// -----------------------------------------------------------------


// -----------------------------------------------------------------
// 4. Get upcoming PTC meeting (REVISED LOGIC)
// -----------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT ps.date, ps.startTime, ps.endTime, pb.status
    FROM ptc_bookings pb
    INNER JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
    WHERE pb.student_id = ? 
      AND ps.date >= CURDATE()
      AND pb.status != 'canceled'
      AND pb.status != 'done'
    ORDER BY ps.date ASC
    LIMIT 1
");
$stmt->execute([$student_id]);
$upcoming_ptc = $stmt->fetch(PDO::FETCH_ASSOC);

// Format PTC meeting date and time
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

// -----------------------------------------------------------------
// (End of PTC Logic)
// -----------------------------------------------------------------


// 5. Get today's class schedule
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

    // Format times for each schedule
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

// Return all data
return [
    'student' => $student,
    'current_level' => $current_level,
    'latest_payment' => $latest_payment,
    'next_due' => $next_due,
    'upcoming_ptc' => $upcoming_ptc,
    'today_schedule' => $today_schedule
];