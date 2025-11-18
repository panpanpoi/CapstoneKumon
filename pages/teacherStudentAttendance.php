<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

// 1. Security: Only allow teachers
$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    header("Location: ../pages/loginform.php");
    exit;
}

// 2. Date Logic
// Get the date from URL or default to today
$selected_date = $_GET['date'] ?? date('Y-m-d');
// Calculate the Day of the Week (e.g., "Monday", "Wednesday") to fetch the correct schedule
$day_of_week = date('l', strtotime($selected_date));

// 3. Fetch Data
// We JOIN 'class_schedules' to get the time specifically for this Day of the Week
$stmt = $pdo->prepare("
  SELECT 
    s.student_id,
    s.studentCode,
    s.Firstname,
    s.Lastname,
    a.attendance_id,
    a.status,
    a.attendance_date,
    sched.start_time,
    sched.end_time
  FROM class_students cs
  JOIN students s ON cs.student_id = s.student_id
  
  -- Get the schedule time for the specific Day of Week
  LEFT JOIN class_schedules sched 
    ON cs.class_id = sched.class_id 
    AND sched.schedule_day = ?

  -- Get existing attendance status for the specific Date
  LEFT JOIN attendance a 
    ON s.student_id = a.student_id 
    AND a.attendance_date = ?

  WHERE cs.teacher_id = ?
  ORDER BY sched.start_time ASC, s.Lastname ASC
");

$stmt->execute([$day_of_week, $selected_date, $teacher_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Attendance | Teacher View</title>
  <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/teacherStudentAttendance.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="attendance-container">
  <header class="attendance-header">
    <button class="back-btn" onclick="window.location.href='kumonClass.php'">
      <i class="fas fa-arrow-left"></i> Back
    </button>
    <h1><i class="fas fa-user-check"></i> Student Attendance</h1>
    
    <div class="date-controls">
      <label for="attendanceDate">Date: </label>
      <input type="date" id="attendanceDate" value="<?= htmlspecialchars($selected_date) ?>">
      <button id="loadDateBtn">Load</button>
      <span style="margin-left: 8px; font-weight: 500; opacity: 0.9;">(<?= $day_of_week ?>)</span>
    </div>
  </header>

  <section class="attendance-table-section">
    <table class="attendance-table">
      <thead>
        <tr>
          <th>Student Code</th>
          <th>Name</th>
          <th>Class Schedule</th> <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($students) > 0): ?>
            <?php foreach ($students as $st): ?>
              <tr class="<?= ($st['status'] === 'Pending') ? 'pending-row' : '' ?>">
                
                <td><?= htmlspecialchars($st['studentCode']) ?></td>
                <td><?= htmlspecialchars($st['Firstname'] . ' ' . $st['Lastname']) ?></td>
                
                <td style="font-weight: 500; color: #444;">
                    <?php if (!empty($st['start_time']) && !empty($st['end_time'])): ?>
                        <?= date("g:i A", strtotime($st['start_time'])) ?> - 
                        <?= date("g:i A", strtotime($st['end_time'])) ?>
                    <?php else: ?>
                        <span style="color: #999; font-style: italic; font-size: 0.85em;">No Class Today</span>
                    <?php endif; ?>
                </td>

                <td>
                  <?php if ($st['status'] === 'Pending'): ?>
                    <span class="status pending">Pending</span>
                  <?php elseif ($st['status'] === 'Present'): ?>
                    <span class="status present">Present</span>
                  <?php elseif ($st['status'] === 'Absent'): ?>
                    <span class="status absent">Absent</span>
                  <?php else: ?>
                    <span class="status none">Not Marked</span>
                  <?php endif; ?>
                </td>
                
                <td>
                  <?php if ($st['status'] === 'Pending'): ?>
                    <button class="confirmBtn" data-attendance-id="<?= $st['attendance_id'] ?>">
                      <i class="fas fa-check"></i> Confirm
                    </button>

                  <?php elseif ($st['status'] === 'Present' || $st['status'] === 'Absent'): ?>
                    <button class="disabledBtn" disabled>
                        <i class="fas fa-check"></i> Done
                    </button>

                  <?php else: ?>
                    <div style="display: flex; gap: 8px;">
                        <button class="markPresentBtn" 
                                title="Mark Present"
                                data-student-id="<?= $st['student_id'] ?>"
                                data-date="<?= $selected_date ?>">
                          <i class="fas fa-check"></i>
                        </button>

                        <button class="markAbsentBtn" 
                                title="Mark Absent"
                                data-student-id="<?= $st['student_id'] ?>"
                                data-date="<?= $selected_date ?>">
                          <i class="fas fa-times"></i>
                        </button>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align:center; padding: 30px; color: #666;">
                    No students found assigned to you.
                </td>
            </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
</div>

<script src="../scr/teacherStudentAttendance.js"></script>

<script>
  // Simple reload when date changes
  document.getElementById('loadDateBtn').addEventListener('click', () => {
    const date = document.getElementById('attendanceDate').value;
    if (date) window.location.href = `teacherStudentAttendance.php?date=${date}`;
  });
</script>

</body>
</html>