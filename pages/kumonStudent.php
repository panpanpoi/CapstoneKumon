<?php
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "../handler/auth.php";
require_once "../handler/studentData.php";
require_once "../handler/ptcData.php";
require_once "../handler/studentScheduleData.php";

// Ensure student is logged in
if (empty($student)) {
    $_SESSION['error'] = "Student profile not found.";
    header("Location: ../login.php");
    exit;
}

// Avatar initials
$avatarInitials = strtoupper(substr($student['Firstname'], 0, 1) . substr($student['Lastname'], 0, 1));

// Current level
$current_level = $class_student['level'] ?? null;
$class_id = $class_student['class_id'] ?? null;

// Schedule for today
$today_schedule = $today_schedule ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KUMON Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonStudent.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h1>
                <a href="kumonStudent.php">
                    <img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:75px; vertical-align:middle; margin-right:6px;">
                </a>
            </h1>
            <p>Practice Makes Possibilities</p>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><?= $avatarInitials ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($student['Firstname'] . " " . $student['Lastname']) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>
        <ul class="nav-menu">
            <li><i class="fas fa-home"></i><a href="kumonStudent.php">Home</a></li>
            <li><i class="fas fa-calendar-alt"></i><a href="studentSchedules.php">Schedule</a></li>
            <li><i class="fas fa-money-bill-wave"></i><a href="studentPayments.php">Balance</a></li>
            <li><i class="fas fa-comments"></i><a href="studentPTC.php">PTC Meeting</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar"><?= $avatarInitials ?></div>
                    <div class="user-name"><?= htmlspecialchars($student['Firstname']) ?></div>
                </div>
            </div>
        </header>

        <section class="card-container">
            <!-- Current Level -->
            <div class="card">
                <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                <div class="card-title">Current Level</div>
                <div class="card-value"><?= htmlspecialchars($current_level ?? 'N/A') ?></div>
                <div class="card-subtitle">
                    <?= !empty($today_schedule) ? htmlspecialchars($today_schedule[0]['subject']) : '' ?>
                </div>
            </div>

            <!-- Next Payment Due -->
            <div class="card">
                <div class="card-icon"><i class="fas fa-money-check-alt"></i></div>
                <div class="card-title">Next Due Date</div>
                <?php if ($next_due): ?>
                    <div class="card-value"><?= htmlspecialchars($next_due) ?></div>
                    <div class="card-subtitle">
                        Latest Payment: <?= htmlspecialchars($latest_payment['amount'] ?? '0.00') ?>
                        (<?= htmlspecialchars($latest_payment['payment_date'] ?? 'N/A') ?>)
                    </div>
                <?php else: ?>
                    <div class="card-value">No payments found</div>
                <?php endif; ?>
            </div>

            <!-- Upcoming PTC -->
            <div class="card">
                <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="card-title">Next PTC Meeting</div>
                <?php if ($upcoming_ptc): ?>
                    <div class="card-value">
                        <?= htmlspecialchars($upcoming_ptc['meetingDate']) ?>
                        (<?= htmlspecialchars($upcoming_ptc['start_time'] . " - " . $upcoming_ptc['end_time']) ?>)
                    </div>
                    <div class="card-subtitle">
                        Status: <?= htmlspecialchars($upcoming_ptc['status']) ?>
                    </div>
                <?php else: ?>
                    <div class="card-value">No scheduled PTC</div>
                <?php endif; ?>
            </div>

            <!-- Today's Schedule -->
            <div class="card">
                <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="card-title">Today's Schedule</div>
                <?php if (!empty($today_schedule)): ?>
                    <ul>
                        <?php foreach ($today_schedule as $sched): ?>
                            <li>
                                <?= htmlspecialchars($sched['subject']) ?>
                                <?= htmlspecialchars($sched['start_time'] . " - " . $sched['end_time']) ?>
                                with <?= htmlspecialchars($sched['teacher_name'] . " " . $sched['teacher_surname']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="card-value">No schedule today</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>
