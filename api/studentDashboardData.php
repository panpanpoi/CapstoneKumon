<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";

// Set Timezone to ensure "Today" is correct (crucial for scheduling)
date_default_timezone_set('Asia/Manila');

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['error'] = "Student not logged in.";
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// -----------------------------------------------------------------
// 1. Fetch student information
// -----------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT s.student_id, s.Firstname, s.Lastname, cs.class_id, cs.level
    FROM students s
    LEFT JOIN class_students cs ON s.student_id = cs.student_id
    WHERE s.student_id = ?
    LIMIT 1
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$current_level = $student['level'] ?? null;

// -----------------------------------------------------------------
// 2. Get latest payment details
// -----------------------------------------------------------------
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
// 3. Get next due payment date
// -----------------------------------------------------------------
$next_due = null; 

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
        $date = date_create_from_format('F Y', $p['tf_month_covered']);
        if ($date) {
            $covered_dates[] = $date->modify('first day of this month');
        }
    }

    if (count($covered_dates) > 0) {
        $latest_covered_date = max($covered_dates);
        $latest_covered_date->modify('+1 month');
        $next_due = $latest_covered_date->format('F j, Y');
    }
}

// --- Strategy 2: Fallback (Future due dates) ---
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

// --- Strategy 3: Default Fallback ---
if ($next_due === null) {
    $next_due = date("F 1, Y", strtotime("first day of next month"));
}

// -----------------------------------------------------------------
// 4. Get upcoming PTC meeting
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
// 5. Get today's class schedule (FIXED LOGIC)
// -----------------------------------------------------------------

$today = date('l'); // Gets current day name (e.g., "Wednesday")

// This query matches your working Student Schedule page exactly
$stmt = $pdo->prepare("
    SELECT 
        cs.schedule_day, 
        cs.start_time, 
        cs.end_time, 
        cs.class_id,
        u.subject,
        u.Name AS teacher_name,
        u.Surname AS teacher_surname
    FROM class_students cst
    JOIN class_schedules cs ON cst.class_id = cs.class_id
    JOIN teachers t ON cst.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.user_id
    WHERE cst.student_id = ?
    ORDER BY cs.start_time ASC
");

$stmt->execute([$student_id]);
$all_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today_schedule = [];

// Filter through all classes to find only the ones for today
foreach ($all_schedules as $sched) {
    // trim() ensures no hidden spaces cause a mismatch
    if (trim($sched['schedule_day']) === $today) {
        
        // Format times for the dashboard display
        if (!empty($sched['start_time'])) {
            $sched['formatted_start'] = date("g:i A", strtotime($sched['start_time']));
        }
        if (!empty($sched['end_time'])) {
            $sched['formatted_end'] = date("g:i A", strtotime($sched['end_time']));
        }
        
        $today_schedule[] = $sched;
    }
}

// -----------------------------------------------------------------
// Return all data
// -----------------------------------------------------------------
return [
    'student' => $student,
    'current_level' => $current_level,
    'latest_payment' => $latest_payment,
    'next_due' => $next_due,
    'upcoming_ptc' => $upcoming_ptc,
    'today_schedule' => $today_schedule
];