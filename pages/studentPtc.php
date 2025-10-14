<?php
// pages/studentPtc.php
// Ensure session is started to access session variables.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../handler/studentPtcHandler.php';

// Check for student_id in the session. Redirect to login if not found.
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    $_SESSION['error'] = "You must be logged in to view this page.";
    header("Location: ../login.php");
    exit;
}

// --- 1. Fetch Student Information for Sidebar ---
try {
    $stmt = $pdo->prepare("SELECT Firstname, Lastname FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        session_destroy();
        $_SESSION['error'] = "Student profile not found. Please log in again.";
        header("Location: ../login.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database error: Could not fetch student data.");
}

// --- 2. Format Name and Avatar ---
function sentence_case($string) {
    return ucfirst(strtolower($string));
}
$fullName = sentence_case($student['Firstname']) . " " . sentence_case($student['Lastname']);
$avatarInitials = strtoupper(substr($student['Firstname'], 0, 1) . substr($student['Lastname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTC Meeting - Kumon</title>
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonStudent.css">
    <!-- Your new PTC-specific styles -->
    <link rel="stylesheet" href="../styles/studentptc.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../styles/sidebarToggle.css">

    <!-- Icons via Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="student-ptc">
<div class="dashboard">
    <!-- Sidebar Toggle Button (for mobile) -->
    <button id="sidebarToggle" class="sidebar-toggle">
        <div class="bar"></div>
    </button>
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="logo">
            <a href="kumonStudent.php">
                <img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:55px;">
            </a>
            <p>Practice Makes Possibilities</p>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars(substr($avatarInitials, 0, 2)) ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>
        <ul class="nav-menu">
            <li><a href="kumonStudent.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="studentSchedules.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="studentPayments.php"><i class="fas fa-money-bill-wave"></i> Balance</a></li>
            <li><a href="studentPtc.php" class="active"><i class="fas fa-comments"></i> PTC Meeting</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Header -->
        <div class="header-card">
            <h2><i class="fas fa-comments"></i> Parent-Teacher Conference</h2>
        </div>

        <!-- Flash Messages (these will now be styled by your new CSS) -->
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

        <!-- Current Booking Section -->
        <!-- MODIFIED: Wrapped with the new .section-card class -->
        <section class="section-card current-booking">
            <h3><i class="fas fa-check-circle"></i> Your Current Booking</h3>
            <?php if ($currentBooking): ?>
                <p>
                    <strong>Date:</strong> <?= htmlspecialchars(date("F j, Y", strtotime($currentBooking['date']))) ?><br>
                    <strong>Time:</strong> <?= htmlspecialchars(date("g:i A", strtotime($currentBooking['startTime']))) ?> - <?= htmlspecialchars(date("g:i A", strtotime($currentBooking['endTime']))) ?><br>
                    <strong>Teacher:</strong> <?= htmlspecialchars($currentBooking['teacherName']) ?>
                </p>
                <form method="POST" action="../handler/studentPtcHandler.php" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($currentBooking['booking_id']) ?>">
                    <button type="submit" name="cancel" class="cancel-btn">
                        <i class="fas fa-times"></i> Cancel Booking
                    </button>
                </form>
            <?php else: ?>
                <p>You have no scheduled PTC meetings.</p>
            <?php endif; ?>
        </section>

        <!-- Available Slots Section -->
        <!-- MODIFIED: Wrapped with the new .section-card class -->
        <section class="section-card available-slots">
            <h3><i class="fas fa-calendar-alt"></i> Available Slots</h3>
            <?php if (!empty($availableSchedules)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Teacher</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availableSchedules as $slot): ?>
                            <tr>
                                <!-- NOTE: data-label attributes are removed as they are not needed for this design's mobile view -->
                                <td><?= htmlspecialchars(date("F j, Y", strtotime($slot['date']))) ?></td>
                                <td><?= htmlspecialchars(date("g:i A", strtotime($slot['startTime']))) ?> - <?= htmlspecialchars(date("g:i A", strtotime($slot['endTime']))) ?></td>
                                <td><?= htmlspecialchars($slot['teacherName']) ?></td>
                                <td>
                                    <form method="POST" action="../handler/studentPtcHandler.php">
                                        <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($slot['schedule_id']) ?>">
                                        <button type="submit" name="book" class="btn" <?= $currentBooking ? 'disabled' : '' ?> title="<?= $currentBooking ? 'You must cancel your current booking first.' : 'Book this slot' ?>">
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
                    <h4>No Schedules Available</h4>
                    <p>There are currently no PTC slots available.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Use setTimeout to hide the alert after the delay
        const autoHide = setTimeout(() => hideAlert(alert), 5000);
        const closeBtn = alert.querySelector('.alert-close');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                clearTimeout(autoHide); // Stop the auto-hide if closed manually
                hideAlert(alert);
            });
        }
    });
});

// Function to smoothly hide alerts
function hideAlert(alert) {
    alert.style.opacity = '0';
    alert.style.transform = 'translate(-50%, -15px)'; // Match animation exit
    setTimeout(() => alert.remove(), 300);
}
</script>
<script src="../scr/sidebarToggle.js"></script>

</body>
</html>