<?php
require_once "../database.php";
session_start();

// ✅ Ensure teacher is logged in
$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    header("Location: ../pages/loginform.php");
    exit;
}

// Use selected date if provided, otherwise default to today
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Fetch students under this teacher and their attendance status for the selected date
$stmt = $pdo->prepare("
  SELECT 
    s.student_id,
    s.studentCode,
    s.Firstname,
    s.Lastname,
    a.attendance_id,
    a.status,
    a.attendance_date
  FROM class_students cs
  JOIN students s ON cs.student_id = s.student_id
  LEFT JOIN attendance a 
    ON s.student_id = a.student_id 
    AND a.attendance_date = ?
  WHERE cs.teacher_id = ?
  ORDER BY s.Lastname, s.Firstname
");
$stmt->execute([$selected_date, $teacher_id]);
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
  <style>
    .pending-row { background: #fff3cd; }
    .status { padding: 4px 8px; border-radius: 5px; font-weight: bold; }
    .status.pending { background: #ffc107; color: #212529; }
    .status.present { background: #28a745; color: #fff; }
    .status.absent  { background: #dc3545; color: #fff; }
    .status.none    { background: #6c757d; color: #fff; }
    .confirmBtn { background: #007bff; color: #fff; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; }
    .confirmBtn:hover { background: #0056b3; }
    .disabledBtn { background: #ccc; color: #666; border: none; padding: 5px 10px; border-radius: 6px; }
    .attendance-header input[type="date"] { padding: 4px 8px; border-radius: 5px; border: 1px solid #ccc; }
  </style>
</head>
<body>

<div class="attendance-container">
  <!-- Header -->
  <header class="attendance-header">
    <button class="back-btn" onclick="window.location.href='kumonClass.php'">
      <i class="fas fa-arrow-left"></i> Back
    </button>
    <h1><i class="fas fa-user-check"></i> Student Attendance</h1>
    <p class="today-date">
      Date: <input type="date" id="attendanceDate" value="<?= htmlspecialchars($selected_date) ?>">
      <button id="loadDateBtn">Load</button>
    </p>
  </header>

  <!-- Table Section -->
  <section class="attendance-table-section">
    <table class="attendance-table">
      <thead>
        <tr>
          <th>Student Code</th>
          <th>Name</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $st): ?>
          <tr class="<?= ($st['status'] === 'Pending') ? 'pending-row' : '' ?>" data-attendance-id="<?= $st['attendance_id'] ?? '' ?>">
            <td><?= htmlspecialchars($st['studentCode']) ?></td>
            <td><?= htmlspecialchars($st['Firstname'] . ' ' . $st['Lastname']) ?></td>
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
            <td><?= htmlspecialchars($st['attendance_date'] ?? $selected_date) ?></td>
            <td>
              <?php if (($st['status'] ?? '') === 'Pending'): ?>
                <button class="confirmBtn" data-attendance-id="<?= $st['attendance_id'] ?>" >
                  <i class="fas fa-check"></i> Confirm
                </button>
              <?php else: ?>
                <button class="disabledBtn" disabled>—</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>

<script src="../scr/teacherStudentAttendance.js"></script>
<script>
  // Reload page when date is changed
  document.getElementById('loadDateBtn').addEventListener('click', () => {
    const date = document.getElementById('attendanceDate').value;
    if (date) window.location.href = `teacherStudentAttendance.php?date=${date}`;
  });
</script>
</body>
</html>
