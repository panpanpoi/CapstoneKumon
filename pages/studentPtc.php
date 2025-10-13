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
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonStudent.css">
    <link rel="stylesheet" href="../styles/studentptc.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo">
            <h1>
                <a href="kumonStudent.php">
                    <img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:55px;">
                </a>
            </h1>
            <p>Practice Makes Possibilities</p>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars(substr($studentName, 0, 2)) ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($studentName) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>

        <ul class="nav-menu">
            <li><a href="kumonStudent.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="studentSchedules.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="studentPayments.php"><i class="fas fa-money-bill-wave"></i> Balance</a></li>
            <li><a href="studentPtc.php" class="active"><i class="fas fa-comments"></i> PTC Meeting</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2><i class="fas fa-comments"></i> Parent-Teacher Conference</h2>

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Current Booking -->
        <section class="current-booking">
            <h3><i class="fas fa-check-circle"></i> Your Current Booking</h3>
            <?php if ($currentBooking): ?>
                <p>
                    <strong>Date:</strong> <?= htmlspecialchars($currentBooking['date']) ?><br>
                    <strong>Time:</strong> <?= htmlspecialchars($currentBooking['startTime']) ?> - <?= htmlspecialchars($currentBooking['endTime']) ?><br>
                    <strong>Teacher:</strong> <?= htmlspecialchars($currentBooking['teacherName']) ?>
                </p>
                <form method="POST" action="../handler/studentPtcHandler.php">
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($currentBooking['booking_id']) ?>">
                    <button type="submit" name="cancel" class="cancel-btn">
                        <i class="fas fa-times"></i> Cancel Booking
                    </button>
                </form>
            <?php else: ?>
                <p>No active booking yet.</p>
            <?php endif; ?>
        </section>

        <!-- Available Slots -->
        <section class="available-slots">
            <h3><i class="fas fa-calendar-alt"></i> Available Slots</h3>
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
                                        <button type="submit" name="book" class="btn">
                                            <i class="fas fa-bookmark"></i> Book
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h4>No available slots</h4>
                    <p>No available slots from your assigned teacher(s).</p>
                </div>
            <?php endif; ?>
        </section>
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
