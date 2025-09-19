<?php
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "../handler/auth.php";
require_once "../handler/studentPaymentData.php"; // sets $payments, $total_paid, $remaining_balance, $last_payment

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['error'] = "Please log in first.";
    header("Location: ../login.php");
    exit;
}

// Helper for avatar initials
$student_name = $_SESSION['student_name'] ?? "Student";
$avatarInitials = strtoupper(substr($student_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Payments - KUMON</title>
<link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
<link rel="stylesheet" href="../styles/studentPaymentStyle.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h1>KUMON</h1>
            <p>Practice Makes Possibilities</p>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><?= $avatarInitials ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($student_name) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>
        <ul class="nav-menu">
            <li><i class="fas fa-home"></i><a href="kumonStudent.php">Home</a></li>
            <li><i class="fas fa-calendar-alt"></i><a href="studentSchedules.php">Schedule</a></li>
            <li><i class="fas fa-money-bill-wave"></i><a href="studentPayments.php" class="active">Balance</a></li>
            <li><i class="fas fa-comments"></i><a href="studentPTC.php">PTC Meeting</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <h2>💰 Payment Overview</h2>
        </header>

        <!-- Summary Cards -->
        <section class="summary-cards">
            <div class="card">
                <div class="card-title">Total Paid</div>
                <div class="card-value">₱<?= number_format($total_paid, 2) ?></div>
            </div>
            <div class="card">
                <div class="card-title">Remaining Balance</div>
                <div class="card-value">₱<?= number_format($remaining_balance, 2) ?></div>
            </div>
            <div class="card">
                <div class="card-title">Last Payment</div>
                <div class="card-value">
                    <?php if ($last_payment): ?>
                        ₱<?= number_format($last_payment['amount'], 2) ?> 
                        (<?= htmlspecialchars($last_payment['payment_date']) ?>)
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Payments History -->
        <section class="payments-history">
            <h3>Payment History</h3>
            <?php if (!empty($payments)): ?>
                <div class="payment-list">
                    <?php foreach ($payments as $pay): ?>
                        <div class="payment-card">
                            <div><strong>Amount:</strong> ₱<?= number_format($pay['amount'], 2) ?></div>
                            <div><strong>Due Date:</strong> <?= htmlspecialchars($pay['due_date']) ?></div>
                            <div><strong>Method:</strong> <?= htmlspecialchars($pay['payment_method']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No payment history found.</p>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
