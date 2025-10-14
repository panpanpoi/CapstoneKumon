<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";
require_once "../handler/auth.php";

// ✅ Only teachers allowed
if ($_SESSION['account_type'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher PTC Scheduler</title>
<link rel="icon" type="image/png" href="../styles/kumonIcon.png">
<link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
<link rel="stylesheet" href="../styles/kumonTeacher.css">
<link rel="stylesheet" href="../styles/teacherPtcScheduler.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="dashboard">

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">
    <div class="logo">
        <h1><a href="kumonTeacher.php">
            <img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:55px;">
        </a></h1>
        <p>Practice Makes Possibilities</p>
    </div>
    <div class="user-profile">
        <div class="user-avatar"><?= htmlspecialchars($_SESSION['initials'] ?? 'T') ?></div>
        <div class="user-details">
            <div class="username"><?= htmlspecialchars($_SESSION['username'] ?? 'Teacher') ?></div>
            <div class="user-role"><?= htmlspecialchars(ucfirst($_SESSION['account_type'] ?? 'Teacher')) ?></div>
        </div>
    </div>
    <ul class="nav-menu">
        <li><a href="kumonTeacher.php"><i class="fa fa-home"></i> Home</a></li>
        <li><a href="kumonClass.php"><i class="fa fa-users"></i> My Class</a></li>
        <li><a href="teacherPtcScheduler.php" class="active"><i class="fa fa-calendar"></i> PTC Schedule</a></li>
        <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>

<!-- ================= MAIN CONTENT ================= -->
<main class="main-content">
    <div class="header">
        <h1><i class="fa fa-calendar"></i> PTC Schedule Management</h1>
    </div>

    <div class="content">

        <!-- ===== Add New Schedule Form ===== -->
        <form method="POST" action="../handler/ptcSchedule.php" class="create-form">
            <h3><i class="fa fa-plus"></i> Add New Schedule</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" required>
                </div>
                <div class="form-group">
                    <label for="start_time">Start Time</label>
                    <input type="time" name="start_time" id="start_time" required>
                </div>
                <div class="form-group">
                    <label for="end_time">End Time</label>
                    <input type="time" name="end_time" id="end_time" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="create" class="btn-create"><i class="fa fa-plus"></i> Add Schedule</button>
                </div>
            </div>
        </form>

        <!-- ===== Active Schedules ===== -->
        <div class="schedule-section">
            <h3><i class="fa fa-list"></i> Your Active Schedules</h3>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Student</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Active schedules will be populated via JS -->
                </tbody>
            </table>
        </div>

        <!-- ===== Done PTC Table ===== -->
        <div class="schedule-section">
            <h3><i class="fa fa-calendar-check"></i> Done PTC</h3>
            <table class="schedule-table done-bookings">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Student</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Done schedules will be populated via JS -->
                </tbody>
            </table>
        </div>

    </div>
</main>
</div>

<!-- ===== JS ===== -->
<script src="../scr/teacherPtcScheduler.js" defer></script>
</body>
</html>
