<?php
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "../handler/auth.php";
require_once "../handler/studentData.php";
require_once "../handler/studentScheduleData.php";

// ✅ Ensure student is logged in
if ($_SESSION['account_type'] !== 'student') {
    header("Location: loginform.php");
    exit;
}

$username   = $_SESSION['username'];   
$userRole   = ucfirst($_SESSION['account_type']); 
$initials   = $_SESSION['initials'];   

// ✅ Avatar initials
$avatarInitials = strtoupper(
    substr($student['Firstname'], 0, 1) . substr($student['Lastname'], 0, 1)
);

// ✅ Student current level
$current_level = $class_student['level'] ?? null;
$class_id      = $class_student['class_id'] ?? null;

// ✅ Schedule for today
$today_schedule = $today_schedule ?? [];

// ✅ Helper function
function sentence_case($string) {
    $string = strtolower($string);
    return ucfirst($string);
}

$fullName = sentence_case($student['Firstname']) . " " . sentence_case($student['Lastname']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">

    <!-- CSS -->
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css"> 
    <link rel="stylesheet" href="../styles/kumonStudent.css">
    <link rel="stylesheet" href="../styles/studentHome.css">
    <link rel="stylesheet" href="../styles/notification_styles.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <title>KUMON Student Dashboard</title>
</head>
<body>
<div class="dashboard">

    <!-- ✅ Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <a href="kumonStudent.php">
                <img src="../styles/kumonLogo.png" alt="KUMON Logo">
            </a>
            <p>Practice Makes Possibilities</p>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?= $avatarInitials ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>

        <ul class="nav-menu">
            <li><a href="kumonStudent.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="studentSchedules.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="studentPayments.php"><i class="fas fa-money-bill-wave"></i> Balance</a></li>
            <li><a href="studentPTC.php"><i class="fas fa-comments"></i> PTC Meeting</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- ✅ Main Content -->
    <main class="main-content">
        <!-- 🔔 Header -->
        <header class="main-header">
            <h1>Student Dashboard</h1>
            <div class="header-right">
                <!-- User Info -->
                <div class="user-info">
                    <div class="user-avatar"><?= $avatarInitials ?></div>
                </div>
            </div>
        </header>

        <!-- ✅ Dashboard Cards -->
        <section class="card-container">
            <!-- Row 1 -->
            <div class="card-row">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-title">Current Level</div>
                    <div class="card-value"><?= htmlspecialchars($current_level ?? 'N/A') ?></div>
                    <div class="card-subtitle">
                        <?= !empty($today_schedule) ? htmlspecialchars($today_schedule[0]['subject']) : '' ?>
                    </div>
                </div>

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
            </div>

            <!-- Row 2 -->
            <div class="card-row">
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
            </div>
        </section>
    </main>
</div>

<

<!-- ✅ JS -->
<script src="../scr/studentHome.js"></script>
<script src="../scr/sse_notifications.js"></script>
</body>
</html>
