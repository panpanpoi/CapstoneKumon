<?php
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "../handler/auth.php";
require_once "../handler/studentScheduleData.php"; // sets $weekly_schedule

$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    $_SESSION['error'] = "Student session not found.";
    header("Location: ../login.php");
    exit;
}

// 1️⃣ Fetch student info
$stmt = $pdo->prepare("SELECT Firstname, Lastname FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// 2️⃣ Full name & avatar initials
function sentence_case($string) {
    return ucfirst(strtolower($string));
}
$fullName = sentence_case($student['Firstname']) . " " . sentence_case($student['Lastname']);
$avatarInitials = strtoupper(substr($student['Firstname'], 0, 1) . substr($student['Lastname'], 0, 1));

$daysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
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
      <li><a href="studentSchedules.php" class="active"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
      <li><a href="studentPayments.php"><i class="fas fa-money-bill-wave"></i> Balance</a></li>
      <li><a href="studentPTC.php"><i class="fas fa-comments"></i> PTC Meeting</a></li>
      <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <header class="header">
      <h2>Weekly Schedule</h2>
      <div class="filter-buttons">
        <button id="filter-week" class="filter-btn active">Entire Week</button>
        <button id="filter-today" class="filter-btn">Today</button>
      </div>
    </header>

    <section class="schedule-container" id="schedule-container">
      <div class="day-card" data-day="">
        

        <?php foreach ($daysOfWeek as $day): ?>
          <div class="inner-day" data-day="<?= $day ?>">
            <h4><?= $day ?></h4>
            <?php if (!empty($weekly_schedule[$day])): ?>
              <ul>
                <?php foreach ($weekly_schedule[$day] as $sched): ?>
                  <li>
                    <strong><?= htmlspecialchars($sched['subject']) ?></strong> with 
                    <?= htmlspecialchars($sched['teacher_name'] . ' ' . $sched['teacher_surname']) ?><br>
                    <?= date('g:i A', strtotime($sched['start_time'])) ?> - 
                    <?= date('g:i A', strtotime($sched['end_time'])) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="no-class-msg">No classes this day</p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

      </div>
    </section>
  </main>
</div>

<script src="../scr/studentSchedule.js"></script>
</body>
</html>
