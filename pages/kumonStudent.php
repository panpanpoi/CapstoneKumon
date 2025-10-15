<?php
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "../handler/auth.php";

// ✅ Ensure student session ID exists
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    $_SESSION['error'] = "Student session not found.";
    header("Location: ../loginform.php");
    exit;
}

// Load dashboard data
$dashboardData = require "../handler/studentDashboardData.php";

$student         = $dashboardData['student'] ?? [];
$current_level   = $dashboardData['current_level'] ?? 'N/A';
$latest_payment  = $dashboardData['latest_payment'] ?? null;
$next_due        = $dashboardData['next_due'] ?? null;
$upcoming_ptc    = $dashboardData['upcoming_ptc'] ?? null;
$today_schedule  = $dashboardData['today_schedule'] ?? [];

// Helper function
function sentence_case($string) {
    return ucfirst(strtolower($string));
}

// Build student name safely
$firstName = $student['Firstname'] ?? '';
$lastName  = $student['Lastname'] ?? '';
$fullName  = sentence_case($firstName) . " " . sentence_case($lastName);

// Avatar initials (fallback: session initials)
$avatarInitials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
if (trim($avatarInitials) === '') {
    $avatarInitials = strtoupper($_SESSION['initials'] ?? 'ST');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="../styles/kumonIcon.png">
<link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
<link rel="stylesheet" href="../styles/kumonStudent.css">
<link rel="stylesheet" href="../styles/studentHome.css">
<link rel="stylesheet" href="../styles/sidebarToggle.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<title>KUMON Student Dashboard</title>
<style>
    .btn-present {
        background: #3498db;
        border: none;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: 0.2s;
        margin-left: 10px;
        font-size: 0.9em;
    }
    .btn-present:hover {
        background: #2c80b4;
    }
    .present-marked {
        background: #2ecc71 !important;
    }
</style>
</head>
<body>
<div class="dashboard">
    <!-- Toggle Button -->
    <button id="sidebarToggle" class="sidebar-toggle">
        <div class="bar"></div>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <a href="kumonStudent.php">
                <img src="../styles/kumonLogo.png" alt="KUMON Logo">
            </a>
            <p>Practice Makes Possibilities</p>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($avatarInitials) ?></div>
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

    <div class="overlay" id="overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <header class="main-header">
            <h1>Student Dashboard</h1>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar"><?= htmlspecialchars($avatarInitials) ?></div>
                </div>
            </div>
        </header>

        <!-- Dashboard Cards -->
        <section class="card-container">
            <div class="card-row">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-title">Current Level</div>
                    <div class="card-value"><?= htmlspecialchars($current_level) ?></div>
                    <div class="card-subtitle">
                        <?= !empty($today_schedule) ? htmlspecialchars($today_schedule[0]['subject'] ?? '') : '' ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon"><i class="fas fa-money-check-alt"></i></div>
                    <div class="card-title">Next Due Date</div>
                    <?php if ($next_due): ?>
                        <div class="card-value"><?= htmlspecialchars($next_due) ?></div>
                        <div class="card-subtitle">
                            Latest Payment:
                            <?= htmlspecialchars($latest_payment['amount'] ?? '0.00') ?>
                            (<?= htmlspecialchars($latest_payment['formatted_payment_date'] ?? 'N/A') ?>)
                        </div>
                    <?php else: ?>
                        <div class="card-value">No payments found</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-row">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="card-title">Next PTC Meeting</div>
                    <?php if ($upcoming_ptc): ?>
                        <div class="card-value">
                            <?= htmlspecialchars($upcoming_ptc['formatted_date'] ?? 'N/A') ?>
                            (<?= htmlspecialchars($upcoming_ptc['formatted_start'] ?? '') ?> - <?= htmlspecialchars($upcoming_ptc['formatted_end'] ?? '') ?>)
                        </div>
                        <div class="card-subtitle">
                            <span class="status-badge">
                                <?= htmlspecialchars($upcoming_ptc['status'] ?? 'N/A') ?>
                            </span>
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
                        <ul class="today-schedule-list">
                            <?php foreach ($today_schedule as $sched): ?>
                                <li>
                                    <?= htmlspecialchars($sched['formatted_start'] ?? '') ?> -
                                    <?= htmlspecialchars($sched['formatted_end'] ?? '') ?>
                                    <button 
                                        class="btn-present" 
                                        data-class-id="<?= htmlspecialchars($sched['class_id'] ?? '') ?>"
                                        data-student-id="<?= htmlspecialchars($student_id) ?>"
                                    >
                                        <i class="fa fa-user-check"></i> Mark Present
                                    </button>
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

<!-- JS -->
<script src="../scr/sidebarToggle.js"></script>
<script src="../scr/studentAttendance.js"></script>
</body>
</html>
