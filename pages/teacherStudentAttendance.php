<?php
require_once "../handler/auth.php";
require_once "../database.php";

// ✅ Only allow teachers
if ($_SESSION['account_type'] !== 'teacher') {
    header("Location: loginform.php");
    exit;
}

$teacher_id  = $_SESSION['teacher_id'];
$username    = $_SESSION['username'];
$userRole    = ucfirst($_SESSION['account_type']);
$initials    = $_SESSION['initials'];
$currentPage = basename(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Attendance</title>
  <link rel="icon" type="image/png" href="../styles/kumonIcon.png">

  <!-- Styles -->
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/kumonTeacher.css">
  <link rel="stylesheet" href="../styles/kumonClass.css">
  <link rel="stylesheet" href="../styles/teacherStudentAttendance.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
  <div class="main-content">
    <header class="header">
      <h1 class="section-header"><i class="fa fa-clipboard-list"></i> Student Attendance</h1>
    </header>

    <main class="main_panel">
      <section class="attendance-section">

        <div class="top-controls">
          <button class="btn-back" onclick="window.location.href='kumonClass.php'">
            <i class="fa fa-arrow-left"></i> Back to My Class
          </button>
        </div>

        <div class="section-header">
          <h2><i class="fa fa-calendar-check"></i> Manage Attendance</h2>
          <div class="filter-actions">
            <input type="date" id="attendanceDate">
            <button id="loadAttendanceBtn" class="btn btn-primary">
              <i class="fa fa-filter"></i> Load Students
            </button>
          </div>
        </div>

        <div class="assigned-table-container">
          <table class="assigned-table">
            <thead>
              <tr>
                <th>Student Code</th>
                <th>Name</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody id="attendanceTableBody">
              <tr><td colspan="4" class="no-data">Select a date to load students.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <!-- JS -->
  <script src="../scr/teacherStudentAttendance.js"></script>
</body>
</html>
