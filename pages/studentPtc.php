<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../api/auth.php'; // ensures logged-in + sets session ids
require_once __DIR__ . '/../api/studentPtcHandler.php'; // Logic for bookings

// 1. Fetch Student Information for Sidebar 
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    $_SESSION['error'] = "You must be logged in to view this page.";
    header("Location: ../login.php");
    exit;
}

try {
    // ✅ RESTORED: Fetch actual student name from DB
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

// 2. Format Name and Avatar 
function sentence_case($string) {
    return ucfirst(strtolower($string));
}
$fullName = sentence_case($student['Firstname']) . ' ' . sentence_case($student['Lastname']);

function initials($name) {
    $parts = explode(' ', $name);
    $ini = '';
    foreach($parts as $p) $ini .= strtoupper($p[0] ?? '');
    return $ini;
}
$avatarInitials = initials($fullName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PTC Meeting - Kumon</title>
<link rel="icon" type="image/png" href="../styles/kumonIcon.png">
<link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
<link rel="stylesheet" href="../styles/kumonStudent.css">
<link rel="stylesheet" href="../styles/studentptc.css?v=<?= time() ?>">
<link rel="stylesheet" href="../styles/sidebarToggle.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="student-ptc">
<div class="dashboard">
    <!-- Sidebar Toggle -->
    <button id="sidebarToggle" class="sidebar-toggle"><div class="bar"></div></button>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <a href="kumonStudent.php"><img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:55px;"></a>
            <p>Practice Makes Possibilities</p>
        </div>
        <div class="user-profile">
            <!-- Display dynamic initials and name -->
            <div class="user-avatar"><?= htmlspecialchars($avatarInitials) ?></div>
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

    <div class="overlay" id="overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-card">
            <h2><i class="fas fa-comments"></i> Parent-Teacher Conference</h2>
        </div>

        <!-- Flash Messages -->
        <?php foreach(['success','error'] as $type): if(!empty($_SESSION[$type])): ?>
            <div class="alert alert-<?= $type ?>">
                <i class="fas <?= $type==='success'?'fa-check-circle':'fa-exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($_SESSION[$type]); unset($_SESSION[$type]); ?></span>
                <button class="alert-close">&times;</button>
            </div>
        <?php endif; endforeach; ?>

        <!-- Current Booking -->
        <section class="section-card current-booking">
            <h3><i class="fas fa-check-circle"></i> Your Current Booking</h3>
            <?php if ($currentBooking): ?>
                <p>
                    <strong>Date:</strong> <?= date("F j, Y", strtotime($currentBooking['date'])) ?><br>
                    <strong>Time:</strong> <?= date("g:i A", strtotime($currentBooking['startTime'])) ?> - <?= date("g:i A", strtotime($currentBooking['endTime'])) ?><br>
                    <strong>Teacher:</strong> <?= htmlspecialchars($currentBooking['teacherName']) ?>
                </p>
                
                <!-- Logic for Approved vs Pending status -->
                <?php if ($currentBooking['status'] === 'approved'): ?>
                    <!-- APPROVED STATE -->
                    <div style="margin-top: 15px;">
                        <span style="background: #28a745; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; margin-right: 10px;">
                            <i class="fas fa-check-double"></i> Approved
                        </span>
                        <button type="button" class="cancel-btn" disabled style="opacity: 0.6; cursor: not-allowed; background: #999;">
                            <i class="fas fa-lock"></i> Booking Approved (Cannot Cancel)
                        </button>
                    </div>
                <?php else: ?>
                    <!-- PENDING STATE -->
                    <button type="button" class="cancel-btn cancel-booking-btn" data-booking-id="<?= htmlspecialchars($currentBooking['booking_id']) ?>">
                        <i class="fas fa-times"></i> Cancel Booking
                    </button>
                <?php endif; ?>
                
            <?php else: ?>
                <p>No scheduled PTC meetings.</p>
            <?php endif; ?>
        </section>

        <!-- Available Slots -->
        <section class="section-card available-slots">
            <h3><i class="fas fa-calendar-alt"></i> Available Slots</h3>
            <?php if (!empty($availableSchedules)): ?>
                <table>
                    <thead>
                        <tr><th>Date</th><th>Time</th><th>Teacher</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($availableSchedules as $slot): ?>
                            <tr>
                                <td><?= date("F j, Y", strtotime($slot['date'])) ?></td>
                                <td><?= date("g:i A", strtotime($slot['startTime'])) ?> - <?= date("g:i A", strtotime($slot['endTime'])) ?></td>
                                <td><?= htmlspecialchars($slot['teacherName']) ?></td>
                                <td>
                                    <form method="POST" action="../api/studentPtcHandler.php?filter_year=<?= $filter_year ?>&filter_month=<?= $filter_month ?>">
                                        <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($slot['schedule_id']) ?>">
                                        <button type="submit" name="book" class="btn" <?= $currentBooking?'disabled':'' ?>>Book</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No available PTC slots.</p>
            <?php endif; ?>
        </section>

        <!-- Completed PTC Meetings -->
        <section class="section-card done-bookings">
            
            <!-- Filter Row -->
            <div class="card-header-row">
                <h3><i class="fas fa-calendar-check"></i> Completed PTC Meetings</h3>
                <form action="studentPtc.php" method="GET" class="filter-form">
                    <label for="filter_month">Filter by:</label>
                    <select name="filter_month" id="filter_month" onchange="this.form.submit()">
                        <option value="all" <?= ($filter_month == 'all') ? 'selected' : '' ?>>All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): 
                            $month_name = date('F', mktime(0, 0, 0, $m, 10));
                        ?>
                            <option value="<?= $m ?>" <?= ($filter_month == $m) ? 'selected' : '' ?>>
                                <?= $month_name ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <select name="filter_year" id="filter_year" onchange="this.form.submit()">
                        <?php 
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 4; $y--): 
                        ?>
                            <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <?php if(!empty($doneBookings)): ?>
                <div class="table-wrapper">
                    <table class="ptc-table">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Teacher</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($doneBookings as $b): ?>
                            <tr>
                                <td><?= date("F j, Y", strtotime($b['date'])) ?></td>
                                <td><?= htmlspecialchars($b['teacherName']) ?></td>
                                <td>
                                    <button class="btn-view-notes" data-schedule-id="<?= $b['schedule_id'] ?>">
                                        <i class="fas fa-eye"></i> View Notes
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">
                    No completed PTC meetings found for 
                    <?php 
                        if ($filter_month !== 'all' && $filter_month !== '') {
                            echo date('F', mktime(0, 0, 0, $filter_month, 10)) . " ";
                        }
                        echo $filter_year;
                    ?>.
                </p>
            <?php endif; ?>
        </section>
    </main>
</div>

<!-- Hidden divs for notes content -->
<div class="notes-storage" style="display: none;">
    <?php foreach ($doneBookings as $b): ?>
        <div id="notes-content-<?= $b['schedule_id'] ?>">
            <div class="notes-box">
                <p><strong>Teacher:</strong> <?= htmlspecialchars($b['teacherName']) ?></p>
                <p><strong>Date:</strong> <?= date("F j, Y", strtotime($b['date'])) ?></p>
                <hr style="border:0; border-top: 1px solid #eee; margin: 10px 0;">
                
                <?php if (empty($b['notes'])): ?>
                    <p>No notes were added for this meeting.</p>
                <?php else: ?>
                    <ul class="notes-list">
                    <?php foreach($b['notes'] as $note): ?>
                        <li>
                            <p><?= htmlspecialchars($note['note']) ?></p>
                            <small>(<?= date("M j, Y, g:i A", strtotime($note['created_at'])) ?>)</small>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="../scr/sidebarToggle.js"></script>
<script src="../scr/studentPtc.js"></script> 
</body>
</html>