<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";
require_once "../handler/auth.php";

if ($_SESSION['account_type'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch Active Schedules
try {
    $stmt = $pdo->prepare("
        SELECT s.schedule_id, s.date, s.start_time, s.end_time,
               b.student_id, CONCAT(st.Firstname,' ',st.Lastname) AS studentName,
               CASE WHEN b.status='booked' THEN 'Booked' ELSE 'Open' END AS status
        FROM ptc_schedules s
        LEFT JOIN ptc_bookings b ON s.schedule_id = b.schedule_id
        LEFT JOIN students st ON b.student_id = st.student_id
        WHERE s.teacher_id = ?
        ORDER BY s.date, s.start_time
    ");
    $stmt->execute([$teacher_id]);
    $activeSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activeSchedules = [];
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
<link rel="stylesheet" href="../styles/teacherPtcScheduler.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        <li><a href="kumonTeacher.php"><i class="fa fa-home"></i> Home</a></li>
        <li><a href="kumonClass.php"><i class="fa fa-users"></i> My Class</a></li>
        <li><a href="teacherPtcScheduler.php" class="active"><i class="fa fa-calendar"></i> PTC Schedule</a></li>
        <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>

<!-- MAIN CONTENT -->
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
                    <?php foreach($activeSchedules as $s): ?>
                    <tr>
                        <td><?= date("F j, Y", strtotime($s['date'])) ?></td>
                        <td><?= date("g:i A", strtotime($s['start_time'])) ?> - <?= date("g:i A", strtotime($s['end_time'])) ?></td>
                        <td><?= htmlspecialchars($s['status']) ?></td>
                        <td><?= htmlspecialchars($s['studentName'] ?? '-') ?></td>
                        <td>
                            <?php if($s['status'] === 'Open'): ?>
                                <!-- Delete button only for open schedules -->
                                <button class="btn-delete" data-schedule-id="<?= $s['schedule_id'] ?>"><i class="fa fa-trash"></i> Delete</button>
                            <?php else: ?>
                                <button class="btn-delete disabled" disabled title="Cannot delete booked schedule">
                                    <i class="fa fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($activeSchedules)): ?>
                    <tr><td colspan="5" style="text-align:center;">No active schedules found.</td></tr>
                    <?php endif; ?>
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
                    <!-- Populate via JS or PHP similar to above -->
                </tbody>
            </table>
        </div>

    </div>
</main>
</div>

<!-- ===== JS ===== -->
<script src="../scr/teacherPtcScheduler.js" defer></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".btn-delete").forEach(btn => {
        btn.addEventListener("click", async () => {
            const scheduleId = btn.dataset.id;
            if(!scheduleId) return;

            if(!confirm("Are you sure you want to delete this schedule?")) return;

            try {
                const resp = await fetch("../handler/ptcSchedule.php?delete=" + scheduleId, {
                    method: "GET"
                });
                // Reload page after deletion
                location.reload();
            } catch(err) {
                alert("Error deleting schedule.");
                console.error(err);
            }
        });
    });
});
</script>
</body>
</html>
