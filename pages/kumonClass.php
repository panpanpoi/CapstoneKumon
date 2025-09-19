<?php
require_once "../database.php"; 
require_once "../handler/auth.php";
require_once "../handler/fetchAssignedStudent.php"; 
require_once "../handler/fetchAllStudent.php"; 

if (!isset($_SESSION)) session_start();

// Define levels once
$levels = ['7A','6A','5A','4A','3A','2A','A','B','C','D','E','F','G','H',
           'I','J','K','L','M','N','O'];

// Current class ID (replace with dynamic value if needed)
$currentClassId = 1; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Class - KUMON</title>
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/kumonClassStyle.css">
</head>
<body>
  <!-- Sidebar --> 
  <div class="sidebar">
    <div class="logo">
      <h1><a href="kumonTeacher.php">KUMON</a></h1>
      <p>Practice Makes Possibilities</p>
    </div>
    <ul class="nav--menu">
      <li><a href="kumonTeacher.php">Home</a></li>
      <li><a href="kumonClass.php" class="active">My Class</a></li>
      <li><a href="ptcSchedule.php">PTC Schedule</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h2>📚 My Class</h2>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <p class="success-msg"><?= htmlspecialchars($_SESSION['success']); ?></p>
      <?php unset($_SESSION['success']); ?>
    <?php elseif (isset($_SESSION['error'])): ?>
      <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); ?></p>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Assigned Students Table -->
    <table class="class-table">
      <thead>
        <tr>
          <th>Student Code</th>
          <th>Student Name</th>
          <th>Schedule</th>
          <th>Level</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($assignedStudents)): ?>
          <?php foreach ($assignedStudents as $student): ?>
            <tr>
              <td><?= htmlspecialchars($student['studentCode']) ?></td>
              <td><?= htmlspecialchars($student['Firstname'] . " " . $student['Lastname']) ?></td>
              <td><?= $student['schedules'] ? htmlspecialchars($student['schedules']) : 'Not set'; ?></td>
              <td>
                <form action="../handler/classStudentHandler.php" method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="update_level">
                  <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                  <select name="level" onchange="this.form.submit()">
                    <?php foreach ($levels as $lvl): ?>
                      <option value="<?= $lvl ?>" <?= ($student['level'] === $lvl) ? "selected" : "" ?>>
                        <?= $lvl ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td>
                <form action="../handler/classStudentHandler.php" method="POST" style="display:inline;"
                      onsubmit="return confirm('Remove this student?');">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="class_id" value="<?= $student['class_id'] ?>">
                  <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                  <button type="submit" class="btn btn-danger">❌ Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5">No students in your class yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Add Student Form -->
    <div class="form-container">
      <h3>Add Student to Class</h3>
      <form action="../handler/classStudentHandler.php" method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="class_id" value="<?= $currentClassId ?>">

        <label for="student_id">Select Student:</label>
        <select name="student_id" id="student_id" required>
          <option value="">-- Choose a Student --</option>
          <?php foreach ($allStudents as $student): ?>
            <option value="<?= $student['student_id'] ?>" data-level="<?= $student['level'] ?>">
              <?= htmlspecialchars($student['Firstname'] . " " . $student['Lastname']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="level">Set Level:</label>
        <select name="level" id="level" required>
          <option value="">-- Choose Level --</option>
          <?php foreach ($levels as $lvl): ?>
            <option value="<?= $lvl ?>"><?= $lvl ?></option>
          <?php endforeach; ?>
        </select>

        <div class="schedule-group">
          <label>Schedule 1:</label>
          <select name="schedule_day1" required>
            <option value="">Day</option>
            <option>Monday</option><option>Tuesday</option>
            <option>Wednesday</option><option>Thursday</option>
            <option>Friday</option><option>Saturday</option>
          </select>
          <label for="start_time1">Start Time:</label>
          <input type="time" name="start_time1" required>
          <label for="end_time1">End Time:</label>
          <input type="time" name="end_time1" required>
        </div>

        <div class="schedule-group">
          <label>Schedule 2:</label>
          <select name="schedule_day2" required>
            <option value="">Day</option>
            <option>Monday</option><option>Tuesday</option>
            <option>Wednesday</option><option>Thursday</option>
            <option>Friday</option><option>Saturday</option>
          </select>
          <label for="start_time2">Start Time:</label>
          <input type="time" name="start_time2" required>
          <label for="end_time2">End Time:</label>
          <input type="time" name="end_time2" required>
        </div>

        <button type="submit" class="btn btn-primary">➕ Add Student</button>
      </form>
    </div>
  </div>

  <script>
    // Auto-fill level when selecting student
    const studentSelect = document.getElementById('student_id');
    const levelSelect = document.getElementById('level');
    studentSelect.addEventListener('change', function() {
      const selectedOption = this.selectedOptions[0];
      levelSelect.value = selectedOption.dataset.level || '';
    });
  </script>
</body>
</html>
