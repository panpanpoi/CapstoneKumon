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

// ===============================
// 📋 Fetch all schedules for this teacher
// ===============================
$stmt = $pdo->prepare("
    SELECT ps.schedule_id, ps.date, ps.startTime, ps.endTime, ps.status,
           (SELECT pb.status 
              FROM ptc_bookings pb 
             WHERE pb.schedule_id = ps.schedule_id 
             ORDER BY pb.booking_id DESC LIMIT 1) AS booking_status,
           (SELECT CONCAT(u.Name,' ',u.Surname)
              FROM ptc_bookings pb
              JOIN students s ON pb.student_id = s.student_id
              JOIN users u ON s.user_id = u.user_id
             WHERE pb.schedule_id = ps.schedule_id
               AND pb.status='booked'
             ORDER BY pb.booking_id DESC LIMIT 1) AS student_name
    FROM ptc_schedules ps
    WHERE ps.teacher_id = ?
    ORDER BY ps.date ASC, ps.startTime ASC
");
$stmt->execute([$teacher_id]);
$teacherSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

<!-- ================= FLASH MESSAGES ================= -->
<?php foreach (['success','error'] as $type): ?>
    <?php if (isset($_SESSION[$type])): ?>
        <div class="alert <?= $type ?>">
            <i class="fa <?= $type==='success' ? 'fa-check-circle':'fa-exclamation-circle' ?>"></i>
            <span><?= $_SESSION[$type]; unset($_SESSION[$type]); ?></span>
            <button class="alert-close">&times;</button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

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

        <!-- ===== Active / Booked / Open Schedules ===== -->
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
                    <?php foreach($teacherSchedules as $s): ?>
                        <?php if($s['status'] !== 'done'): ?>
                            <?php
                                $status = $s['booking_status']==='booked' ? 'booked' : 'open';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($s['date']) ?></td>
                                <td><?= htmlspecialchars($s['startTime']) ?> - <?= htmlspecialchars($s['endTime']) ?></td>
                                <td><span class="status-<?= $status ?>"><?= ucfirst($status) ?></span></td>
                                <td><?= $status==='booked' ? htmlspecialchars($s['student_name'] ?? '-') : '-' ?></td>
                                <td class="actions-cell">
                                    <?php if($status==='booked'): ?>
                                        <button class="btn-done" data-schedule-id="<?= $s['schedule_id'] ?>">
                                            <i class="fa fa-check"></i> Done
                                        </button>
                                    <?php else: ?>
                                        <!-- Edit form -->
                                        <form method="POST" action="../handler/ptcSchedule.php" class="inline-edit-form">
                                            <input type="hidden" name="schedule_id" value="<?= $s['schedule_id'] ?>">
                                            <input type="date" name="date" value="<?= $s['date'] ?>" required>
                                            <input type="time" name="start_time" value="<?= $s['startTime'] ?>" required>
                                            <input type="time" name="end_time" value="<?= $s['endTime'] ?>" required>
                                            <button type="submit" name="update" class="btn-edit"><i class="fa fa-edit"></i> Update</button>
                                        </form>
                                        <a href="../handler/ptcSchedule.php?delete=<?= $s['schedule_id'] ?>" onclick="return confirm('Delete this schedule?')" class="btn-delete">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

                <!-- ===== Done PTC Table ===== -->
        <div class="schedule-section">
            <h3><i class="fa fa-calendar-check"></i> Done PTC</h3>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Student</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($teacherSchedules as $s): ?>
                        <?php if($s['status'] === 'done'): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['date']) ?></td>
                                <td>
                                    <?php 
                                        // Convert 24-hour to 12-hour format
                                        $start = date("g:i A", strtotime($s['startTime']));
                                        $end   = date("g:i A", strtotime($s['endTime']));
                                        echo "$start - $end";
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($s['student_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                        // Fetch notes
                                        $stmt = $pdo->prepare("SELECT note, created_at FROM ptc_notes WHERE schedule_id=? ORDER BY created_at DESC");
                                        $stmt->execute([$s['schedule_id']]);
                                        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <ul>
                                        <?php foreach($notes as $note): ?>
                                            <li><?= htmlspecialchars($note['note']) ?> <small>(<?= $note['created_at'] ?>)</small></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <!-- Add note form -->
                                    <form method="POST" action="../handler/ptcSchedule.php" class="inline-note-form">
                                        <input type="hidden" name="schedule_id" value="<?= $s['schedule_id'] ?>">
                                        <input type="text" name="note" placeholder="Add note..." required>
                                        <button type="submit" name="add_note" class="btn-note"></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>
</div>

<script src="../scr/teacherPtcScheduler.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const autoHide = setTimeout(() => hideAlert(alert), 5000);
        const closeBtn = alert.querySelector('.alert-close');
        if(closeBtn){
            closeBtn.addEventListener('click', () => {
                clearTimeout(autoHide);
                hideAlert(alert);
            });
        }
    });
});

function hideAlert(alert){
    alert.style.opacity = '0';
    alert.style.transform = 'translateX(-50%) translateY(-20px)';
    setTimeout(() => alert.remove(), 300);
}
</script>
</body>
</html>
