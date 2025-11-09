<?php
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "../api/auth.php"; // ensures valid student session

$student_id = $_SESSION['student_id'] ?? null;

if (!$student_id) {
    $_SESSION['error'] = "Student session not found.";
    header("Location: ../login.php");
    exit;
}

// Fetch student info
$stmt = $pdo->prepare("SELECT Firstname, Lastname FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: ../login.php");
    exit;
}

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
<title>KUMON Student Payments</title>
<link rel="icon" type="image/png" href="../styles/kumonIcon.png">

<!-- Fonts & Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
<link rel="stylesheet" href="../styles/kumonStudent.css">
<link rel="stylesheet" href="../styles/sidebarToggle.css">
<link rel="stylesheet" href="../styles/studentPayment.css">
</head>

<body>
<div class="dashboard">
    <button id="sidebarToggle" class="sidebar-toggle"><div class="bar"></div></button>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <a href="kumonStudent.php"><img src="../styles/kumonLogo.png" alt="KUMON Logo"></a>
            <p>Practice Makes Possibilities</p>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($avatarInitials) ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>

        <ul class="nav-menu">
            <li><a href="kumonStudent.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="studentSchedules.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li class="active"><a href="studentPayments.php"><i class="fas fa-money-bill-wave"></i> Balance</a></li>
            <li><a href="studentPtc.php"><i class="fas fa-comments"></i> PTC Meeting</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <div class="overlay" id="overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- 🔹 Hidden input for student ID (used by JS) -->
        <input type="hidden" id="student_id" value="<?= htmlspecialchars($student_id) ?>">

        <header class="main-header">
            <h1 id="paymentHeader">Payment History</h1>
        </header>

        <div class="header-summary">
            <div class="balance-info">
                <div class="balance-card">
                    <div class="label">Remaining Balance</div>
                    <div class="value" id="remainingBalance">₱0.00</div>
                </div>
                <div class="balance-card">
                    <div class="label">Next Due</div>
                    <div class="value" id="nextDue">N/A</div>
                </div>
            </div>

            <!-- Month Filter -->
            <div class="filter-bar">
                <label for="monthPicker">Filter by Month:</label>
                <input type="month" id="monthPicker">
            </div>
        </div>

        <!-- Dynamic Payment History Section -->
        <section class="payments-history-panel">
            <div id="paymentContainer" class="payment-container">
                <p class="loading-text">Loading payment records...</p>
            </div>
        </section>
    </main>
</div>

<!-- Scripts -->
<script src="../scr/sidebarToggle.js"></script>
<script src="../scr/studentPayment.js"></script>
</body>
</html>


