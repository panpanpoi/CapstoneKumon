<?php //final version
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "../handler/auth.php"; // ensures valid student session

$student_id = $_SESSION['student_id'] ?? null;

if (!$student_id) {
    $_SESSION['error'] = "Student session not found.";
    header("Location: ../login.php");
    exit;
}

// 1️⃣ Fetch student info
$stmt = $pdo->prepare("SELECT Firstname, Lastname FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// 2️⃣ Full name & avatar initials
function sentence_case($string) {
    return ucfirst(strtolower($string));
}
$fullName = sentence_case($student['Firstname']) . " " . sentence_case($student['Lastname']);
$avatarInitials = strtoupper(substr($student['Firstname'], 0, 1) . substr($student['Lastname'], 0, 1));

// 3️⃣ Load current month payment data
$paymentData = include "../handler/studentPaymentData.php";
$payments = $paymentData['payments'] ?? [];
$total_paid = $paymentData['total_paid'] ?? 0;
$remaining_balance = $paymentData['remaining_balance'] ?? 0;
$next_due = $paymentData['next_due'] ?? date('F j, Y');
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
<link rel="stylesheet" href="../styles/studentPaymentStyle.css">
</head>
<body>
<div class="dashboard">
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
            <li><a href="studentPTC.php"><i class="fas fa-comments"></i> PTC Meeting</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="main-header">
            <h1>Payment History</h1>
            <div class="header-right">
                <div class="balance-panel">
                    <div class="label">Remaining Balance:</div>
                    <div class="amount">₱<?= number_format($remaining_balance, 2) ?></div>
                    <div class="label">Next Due:</div>
                    <div class="amount"><?= htmlspecialchars($next_due) ?></div>
                </div>
            </div>
        </header>

        <section class="payments-history-panel">
            <?php if (!empty($payments)): ?>
                <div class="payment-list">
                    <?php foreach ($payments as $pay): ?>
                        <div class="payment-card">
                            <div class="payment-card-row">
                                <strong>Amount:</strong> 
                                <span>₱<?= number_format($pay['amount'], 2) ?></span>
                                <?php
                                    $status = strtolower($pay['payment_status'] ?? 'unverified');
                                    $badge_class = $status === 'verified' ? 'verified-badge' : 'unverified-badge';
                                ?>
                                <span class="status-badge <?= $badge_class ?>"><?= ucfirst($status) ?></span>
                            </div>
                            <div class="payment-card-row">
                                <strong>Payment Date:</strong> <span><?= htmlspecialchars($pay['payment_date']) ?></span>
                            </div>
                            <div class="payment-card-row">
                                <strong>Method:</strong> <span><?= htmlspecialchars($pay['payment_method']) ?></span>
                            </div>
                            <?php if (!empty($pay['remarks'])): ?>
                            <div class="payment-card-row">
                                <strong>Remarks:</strong> <span><?= htmlspecialchars($pay['remarks']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($pay['receipt_path'])): ?>
                            <div class="payment-card-row">
                                <a href="../<?= htmlspecialchars($pay['receipt_path']) ?>" target="_blank">View Receipt</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No payment history found for this month.</p>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
