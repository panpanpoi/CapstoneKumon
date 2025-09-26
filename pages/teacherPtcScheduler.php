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
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/teacherPTCStyle.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: center; }
        th { background: #f9f9f9; }
        .status-open { color: green; font-weight: bold; }
        .status-booked { color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h1>KUMON</h1>
            <p>Practice Makes Possibilities</p>
        </div>
        <ul class="nav-menu">
            <li><a href="kumonTeacher.php">🏠 Home</a></li>
            <li><a href="kumonClass.php">📅 My Class</a></li>
            <li><a href="teacherPtcScheduler.php" class="active">👨‍👩‍👦 PTC Scheduling</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Main -->
    <main class="main-content">
        <h2>📅 Manage PTC Schedules</h2>

        <!-- Create form -->
        <form method="POST" action="../handler/ptcSchedule.php" class="create-form">
            <input type="date" name="date" required>
            <input type="time" name="start_time" required>
            <input type="time" name="end_time" required>
            <button type="submit" name="create">➕ Add Schedule</button>
        </form>

        <!-- Schedule list -->
        <h3>Your Schedules</h3>
        <table>
            <tr>
                <th>Date</th>
                <th>Time Range</th>
                <th>Status</th>
                <th>Student</th>
                <th>Actions</th>
            </tr>
            <?php if ($schedules): ?>
                <?php foreach ($schedules as $s): ?>
                    <?php
                        // derive display status
                        $status = ($s['bookingStatus'] === 'booked') ? 'booked' : 'open';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($s['date']) ?></td>
                        <td><?= htmlspecialchars($s['startTime']) ?> - <?= htmlspecialchars($s['endTime']) ?></td>
                        <td class="<?= $status === 'open' ? 'status-open' : 'status-booked' ?>">
                            <?= ucfirst($status) ?>
                        </td>
                        <td>
                            <?= $status === 'booked' && $s['studentName'] 
                                ? htmlspecialchars($s['studentName']) 
                                : '-' ?>
                        </td>
                        <td>
                            <?php if ($status === 'booked'): ?>
                                🔒 Locked (Booked)
                            <?php else: ?>
                                <!-- Edit -->
                                <form method="POST" action="../handler/ptcSchedule.php" style="display:inline-block;">
                                    <input type="hidden" name="schedule_id" value="<?= $s['schedule_id'] ?>">
                                    <input type="date" name="date" value="<?= $s['date'] ?>" required>
                                    <input type="time" name="start_time" value="<?= $s['startTime'] ?>" required>
                                    <input type="time" name="end_time" value="<?= $s['endTime'] ?>" required>
                                    <button type="submit" name="update">✏️ Update</button>
                                </form>
                                <!-- Delete -->
                                <a href="../handler/ptcSchedule.php?delete=<?= $s['schedule_id'] ?>" 
                                   onclick="return confirm('Delete this schedule?')">❌ Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No schedules yet.</td></tr>
            <?php endif; ?>
        </table>
    </main>
</div>
</body>
</html>
