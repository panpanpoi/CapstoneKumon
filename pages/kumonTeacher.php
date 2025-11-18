<?php 
require_once "../api/auth.php"; 

// Only allow teachers
if ($_SESSION['account_type'] !== 'teacher') {
    header("Location: loginform.php");
    exit;
}

// ✅ FIX: Set Timezone to Philippines/Manila so the day matches local time
date_default_timezone_set('Asia/Manila');

// Variables from session/auth
$username   = $_SESSION['username']; 
$userRole   = ucfirst($_SESSION['account_type']); 
$initials   = $_SESSION['initials'] ?? ''; 
$currentPage = basename(__FILE__);

// Fallback for user details 
$teacherName = $user['Name'] ?? $_SESSION['name'] ?? $username; 
$teacherSurname = $user['Surname'] ?? $_SESSION['surname'] ?? ''; 

$currentDay = date('l'); 

// Cache-busting version
$js_version = time(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>KUMON Teacher Account</title>
  <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
  
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/kumonTeacher.css"> 
  
  <!-- ✅ Dashboard Layout and Widget Styles -->
  <link rel="stylesheet" href="../styles/kumonTeacherDashboard.css">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    
<button id="sidebarToggle" class="sidebar-toggle">
    <div class="bar"></div>
</button>

<aside class="sidebar">
    <div class="logo">
        <h1>
            <a href="kumonTeacher.php">
                <img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:55px; vertical-align:middle; margin-right:6px;">
            </a>
        </h1>
        <p>Practice Makes Possibilities</p>
    </div>

    <div class="user-profile"> 
        <div class="user-avatar">
            <?= strtoupper(substr($teacherName,0,1)) . strtoupper(substr($teacherSurname,0,1)) ?>
        </div>
        <div class="user-details">
            <div class="username"><?= htmlspecialchars($teacherName . " " . $teacherSurname); ?></div>
            <div class="user-role"><?= $userRole; ?></div>
        </div>
    </div>

    <ul class="nav-menu">
        <li><a href="kumonTeacher.php" class="active"><i class="fa fa-home"></i> Home</a></li>
        <li><a href="kumonClass.php"><i class="fa fa-users"></i> My Class</a></li>
        <li><a href="teacherPtcScheduler.php"><i class="fa fa-calendar"></i> PTC Schedule</a></li>
        <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>
<div class="overlay" id="overlay"></div>

<!-- main-content margin-left is handled by kumonTeacher.css -->
<div class="main-content">
     <!-- 🔹 Flash success message -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <p style="color:green; font-weight:bold;">
            <?= htmlspecialchars($_SESSION['flash_success']); ?>
        </p>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- HEADER -->
    <header class="header">
        <div class="header-left">
            <h1 class="section-header"><i class="fa-solid fa-home"></i> Home</h1>
        </div>
    </header>

    <!-- MAIN PANEL -->
    <main class="main_panel">
        <!-- 🔹 DASHBOARD GRID -->
        <div class="dashboard-grid">
            
            <!-- WIDGET 1: CLASS SCHEDULE -->
            <div class="schedule-card">
                <div class="schedule-header">
                    <h3><i class="fa fa-clock" style="color:#74C0FC; margin-right:8px;"></i> Today's Classes (<span id="currentDayDisplayWidget"><?= $currentDay ?></span>)</h3>
                    <a href="kumonClass.php" class="view-all-link">View All <i class="fa fa-arrow-right"></i></a>
                </div>

                <!-- Content populated by scr/kumonTeacherDashboard.js -->
                <div id="scheduleContent" class="class-list">
                    <div class="no-classes">
                        <i class="fa fa-sync fa-spin" style="font-size: 2rem; color: #74C0FC; margin-bottom: 10px; display:block;"></i>
                        Loading today's schedule...
                    </div>
                </div>
            </div>
            
            <!-- WIDGET 2: PTC SCHEDULE BOOKING -->
            <div class="schedule-card ptc-card">
                <div class="schedule-header">
                    <h3><i class="fa fa-handshake" style="color:#FFC300; margin-right:8px;"></i> Upcoming PTC Bookings</h3>
                    <!-- View All link for PTC Scheduler -->
                    <a href="teacherPtcScheduler.php" class="view-all-link">View All <i class="fa fa-arrow-right"></i></a>
                </div>

                <!-- Content populated by scr/kumonTeacherDashboard.js -->
                <div class="class-list" id="ptcScheduleContent">
                    <div class="no-classes">
                        <i class="fa fa-sync fa-spin" style="font-size: 2rem; color: #FFC300; margin-bottom: 10px; display:block;"></i>
                        Loading upcoming slots...
                    </div>
                </div>
            </div>
            <!-- END WIDGET 2 -->
        </div>
    </main>
</div>

<script src="../scr/sidebarToggle.js"></script>
<!-- Linking the external JavaScript file with cache busting -->
<script src="../scr/kumonTeacherDashboard.js?v=<?= $js_version ?>"></script>
</body>
</html>