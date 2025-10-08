<?php
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "../handler/auth.php";
require_once "../handler/studentScheduleData.php"; // this should set $weekly_schedule

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['error'] = "Please log in first.";
    header("Location: ../login.php");
    exit;
}

// Helper for avatar initials
$student_name = $_SESSION['student_name'] ?? "Student";
$avatarInitials = strtoupper(substr($student_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Schedule - KUMON</title>
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonStudent.css">
    <link rel="stylesheet" href="../styles/studentSchedule.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h1>KUMON</h1>
            <p>Practice Makes Possibilities</p>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><?= $avatarInitials ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($student_name) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>
        <ul class="nav-menu">
            <li><i class="fas fa-home"></i><a href="kumonStudent.php">Home</a></li>
            <li><i class="fas fa-calendar-alt"></i><a href="studentSchedules.php" class="active">Schedule</a></li>
            <li><i class="fas fa-money-bill-wave"></i><a href="studentPayments.php">Balance</a></li>
            <li><i class="fas fa-comments"></i><a href="studentPtc.php">PTC Meeting</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <h2>📅 Weekly Schedule</h2>
        </header>

        <section class="schedule-container">
            <?php
            $daysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            foreach ($daysOfWeek as $day):
                $schedules = $weekly_schedule[$day] ?? [];
            ?>
                <div class="day-card">
                    <h3><?= $day ?></h3>
                    <?php if (!empty($schedules)): ?>
                        <ul>
                            <?php foreach ($schedules as $sched): ?>
                                <li>
                                    <strong><?= htmlspecialchars($sched['subject']) ?></strong> 
                                    (<?= htmlspecialchars($sched['start_time'] . " - " . $sched['end_time']) ?>) 
                                    with <?= htmlspecialchars($sched['teacher_name'] . " " . $sched['teacher_surname']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No schedule</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    </main>
</div>
</body>
</html>