<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";
require_once "../handler/auth.php";

// ✅ Ensure teacher account
if ($_SESSION['account_type'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// 🔹 Fetch schedules with only ACTIVE bookings
$stmt = $pdo->prepare("
    SELECT 
        s.schedule_id, s.date, s.startTime, s.endTime, s.status AS scheduleStatus,
        pb.booking_id, pb.status AS bookingStatus,
        CONCAT(u.Name, ' ', u.Surname) AS studentName
    FROM ptc_schedules s
    LEFT JOIN ptc_bookings pb 
        ON s.schedule_id = pb.schedule_id 
       AND pb.status = 'booked' -- ✅ only include active bookings
    LEFT JOIN students st ON pb.student_id = st.student_id
    LEFT JOIN users u ON st.user_id = u.user_id
    WHERE s.teacher_id = ?
    ORDER BY s.date ASC, s.startTime ASC
");
$stmt->execute([$teacher_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher PTC Schedule</title>
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonTeacher.css">
    <link rel="stylesheet" href="../styles/teacherPtcScheduler.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h1>
                <a href="kumonTeacher.php">
                    <img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:55px;">
                </a>
            </h1>
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

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success">
            <i class="fa fa-check-circle"></i>
            <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error">
            <i class="fa fa-exclamation-circle"></i>
            <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Main -->
    <main class="main-content">
        <div class="header">
            <div class="header-left">
                <h1><i class="fa fa-calendar"></i> PTC Schedule Management</h1>
            </div>
        </div>

        <div class="content">
            <!-- Create form -->
            <form method="POST" action="../handler/ptcSchedule.php" class="create-form">
                <h3><i class="fa fa-plus"></i> Add New Schedule</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="create" class="btn-create">
                            <i class="fa fa-plus"></i> Add Schedule
                        </button>
                    </div>
                </div>
            </form>

            <!-- Schedule list -->
            <div class="schedule-section">
                <h3><i class="fa fa-list"></i> Your Schedules</h3>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time Range</th>
                            <th>Status</th>
                            <th>Student</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($schedules): ?>
                            <?php foreach ($schedules as $s): ?>
                                <?php
                                    // derive display status
                                    $status = ($s['bookingStatus'] === 'booked') ? 'booked' : 'open';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['date']) ?></td>
                                    <td><?= htmlspecialchars($s['startTime']) ?> - <?= htmlspecialchars($s['endTime']) ?></td>
                                    <td>
                                        <span class="<?= $status === 'open' ? 'status-open' : 'status-booked' ?>">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $status === 'booked' && $s['studentName'] 
                                            ? htmlspecialchars($s['studentName']) 
                                            : '-' ?>
                                    </td>
                                    <td class="actions-cell">
                                        <?php if ($status === 'booked'): ?>
                                            <span class="locked-status">
                                                <i class="fa fa-lock"></i> Locked (Booked)
                                            </span>
                                        <?php else: ?>
                                            <!-- Edit -->
                                            <form method="POST" action="../handler/ptcSchedule.php" class="inline-edit-form">
                                                <input type="hidden" name="schedule_id" value="<?= $s['schedule_id'] ?>">
                                                <input type="date" name="date" value="<?= $s['date'] ?>" required>
                                                <input type="time" name="start_time" value="<?= $s['startTime'] ?>" required>
                                                <input type="time" name="end_time" value="<?= $s['endTime'] ?>" required>
                                                <button type="submit" name="update" class="btn-edit">
                                                    <i class="fa fa-edit"></i> Update
                                                </button>
                                            </form>
                                            <!-- Delete -->
                                            <a href="../handler/ptcSchedule.php?delete=<?= $s['schedule_id'] ?>" 
                                               onclick="return confirm('Delete this schedule?')" class="btn-delete">
                                                <i class="fa fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <h4>No schedules yet</h4>
                                    <p>Create your first PTC schedule using the form above.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Auto-hide after 5 seconds
        const autoHide = setTimeout(() => {
            hideAlert(alert);
        }, 5000);
        
        // Stop auto-hide if user manually closes
        const closeBtn = alert.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                clearTimeout(autoHide);
                hideAlert(alert);
            });
        }
    });
});

function hideAlert(alert) {
    alert.style.opacity = '0';
    alert.style.transform = 'translateX(-50%) translateY(-20px)';
    setTimeout(() => {
        alert.remove();
    }, 300);
}
</script>
</body>
</html>
