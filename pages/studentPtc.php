<?php
// pages/studentPtc.php
require_once __DIR__ . '/../handler/studentPtcHandler.php';

// at this point $studentName, $currentBooking, $availableSchedules are available
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student PTC</title>
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/studentptc.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo">
            <h1>KUMON</h1>
            <p>Practice Makes Possibilities</p>
            <p><?= htmlspecialchars($studentName) ?></p>
            <p>Student</p>
        </div>
        <ul class="nav-menu">
            <li><i class="fas fa-home"></i><a href="kumonStudent.php">Home</a></li>
            <li><i class="fas fa-calendar-alt"></i><a href="studentSchedules.php">Schedule</a></li>
            <li><i class="fas fa-money-bill-wave"></i><a href="studentPayments.php">Balance</a></li>
            <li><i class="fas fa-comments"></i><a href="studentPtc.php" class="active">PTC Meeting</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>👨‍👩‍👦 Parent-Teacher Conference</h2>

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Current Booking -->
        <section class="current-booking">
            <h3>✅ Your Current Booking</h3>
            <?php if ($currentBooking): ?>
                <p>
                    <strong>Date:</strong> <?= htmlspecialchars($currentBooking['date']) ?><br>
                    <strong>Time:</strong> <?= htmlspecialchars($currentBooking['startTime']) ?> - <?= htmlspecialchars($currentBooking['endTime']) ?><br>
                    <strong>Teacher:</strong> <?= htmlspecialchars($currentBooking['teacherName']) ?>
                </p>
                <form method="POST" action="../handler/studentPtcHandler.php">
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($currentBooking['booking_id']) ?>">
                    <button type="submit" name="cancel" class="cancel-btn">❌ Cancel Booking</button>
                </form>
            <?php else: ?>
                <p>No active booking yet.</p>
            <?php endif; ?>
        </section>

        <!-- Available Slots -->
        <section class="available-slots">
            <h3>📅 Available Slots</h3>
            <?php if (!empty($availableSchedules)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time Range</th>
                            <th>Teacher</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availableSchedules as $slot): ?>
                            <tr>
                                <td><?= htmlspecialchars($slot['date']) ?></td>
                                <td><?= htmlspecialchars($slot['startTime']) ?> - <?= htmlspecialchars($slot['endTime']) ?></td>
                                <td><?= htmlspecialchars($slot['teacherName']) ?></td>
                                <td>
                                    <form method="POST" action="../handler/studentPtcHandler.php">
                                        <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($slot['schedule_id']) ?>">
                                        <button type="submit" name="book" class="btn">📌 Book</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No available slots from your assigned teacher(s).</p>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
