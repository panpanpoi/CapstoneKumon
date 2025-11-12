<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";
require_once "../api/auth.php";

if ($_SESSION['account_type'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Kumon Teacher PTC Scheduler</title>
<link rel="icon" type="image/png" href="../styles/kumonIcon.png">
<link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
<link rel="stylesheet" href="../styles/kumonTeacher.css">
<link rel="stylesheet" href="../styles/teacherPtcScheduler.css?v=<?= time(); ?>">

<!-- YOUR FIX: Load Icons from CDNJS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- === THIS IS THE FIX === -->
<!-- This file was missing. It provides the STYLES for the drawer -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@awesome.me/webawesome@3.0.0/dist-cdn/styles/webawesome.css">
<!-- === END OF FIX === -->

<!-- Web Awesome SCRIPT (for <wa-drawer> component) -->
<script data-fa-kit-code="38c11e3f20" type="module" src="https://cdn.jsdelivr.net/npm/@awesome.me/webawesome@3.0.0/dist-cdn/webawesome.loader.js"></script>


</head>
<body>
<div class="dashboard">

<!-- SIDEBAR -->
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
        <li><a href="kumonTeacher.php"><i class="fa-solid fa-home"></i> Home</a></li>
        <li><a href="kumonClass.php"><i class="fa-solid fa-users"></i> My Class</a></li>
        <li><a href="teacherPtcScheduler.php" class="active"><i class="fa-solid fa-calendar"></i> PTC Schedule</a></li>
        <li><a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>

<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="header">
        <h1><i class="fa-solid fa-calendar"></i> PTC Schedule Management</h1>
    </div>

    <div class="content">

        <!-- ===== PHP Alert Box ===== -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <!-- ===== End Alert Box ===== -->

        <!-- ===== Add New Schedule Form ===== -->
        <form method="POST" action="../api/ptcSchedule.php" class="create-form">
            <h3><i class="fa-solid fa-plus"></i> Add New Schedule</h3>
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
                    <button type="submit" name="create" class="btn-create"><i class="fa-solid fa-plus"></i> Add Schedule</button>
                </div>
            </div>
        </form>

        <!-- ===== Active Schedules ===== -->
        <div class="schedule-section">
            <h3><i class="fa-solid fa-list"></i> Your Active Schedules</h3>
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
                <tbody id="active-schedules-body">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>

        <!-- ===== Done PTC Table ===== -->
        <div class="schedule-section">
            <div class="done-ptc-header">
                <h3><i class="fa-solid fa-calendar-check"></i> Done PTC</h3>
                
                <!-- Button to open drawer -->
                <button id="openHistoryBtn" class="btn-create btn-history"><i class="fa-solid fa-search"></i> Search Student</button>
            </div>
            
            <table class="schedule-table done-bookings">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Student</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody id="done-schedules-body">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>

    </div> <!-- .content -->

    <!-- ===== Web Awesome Drawer (v3.0 Syntax) ===== -->
    <wa-drawer id="studentHistoryDrawer" label="Student Done PTC" placement="bottom" style="--wa-drawer-height: 50%;">
        
        <!-- Content area -->
        <div id="drawerContent" class="drawer-content">
            
            <!-- Student Search Bar (MOVED HERE) -->
            <div class="student-search-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="studentSearchInput" placeholder="Search by student name or code...">
                <div id="studentSearchResults" class="search-results-dropdown"></div>
            </div>

            <!-- This is where the student's history will be shown -->
            <div id="studentHistoryContent">
                <p class="history-placeholder">Search for a student to see their completed PTC history.</p>
            </div>

        </div>

        <!-- Footer close button -->
        <wa-button slot="footer" variant="brand" data-drawer="close">Close</wa-button>

    </wa-drawer>
    <!-- ===== End Drawer ===== -->

</main> <!-- .main-content -->
</div> <!-- .dashboard -->

<!-- ===== JS ===== -->
<!-- This script tag is at the end of BODY and uses the cache-buster -->
<script src="../scr/teacherPtcScheduler.js?v=<?= time(); ?>"></script>

</body>
</html>