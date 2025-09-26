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
            <li><i class="fas fa-comments"></i><a href="studentPtc.php">PTC Meeting</a></li>
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
                <script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.3.0/firebase-app.js";
  import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/12.3.0/firebase-messaging.js";

  const firebaseConfig = {
    apiKey: "AIzaSyAcnlBtzPF9z6ahVDlPAjXOpgHlr2BIapc",
    authDomain: "kumonnotification-53518.firebaseapp.com",
    projectId: "kumonnotification-53518",
    storageBucket: "kumonnotification-53518.appspot.com",
    messagingSenderId: "346058075092",
    appId: "1:346058075092:web:20e02a2cf742e7d3f84441",
    measurementId: "G-YETFSFV1NL"
  };

  // Initialize Firebase
  const app = initializeApp(firebaseConfig);
  const messaging = getMessaging(app);

  // Ask permission
  Notification.requestPermission().then(permission => {
    if (permission === "granted") {
      console.log("Notification permission granted.");
      getToken(messaging, { vapidKey: "BEN2whWisY6Dfm-uQ0oFKcDbJlsWYoPkLIKq7Wi_RzqwQr9rIFgLPY7ma0Oz6hLw9obMLArD_8cECoDW0ZvH2tU" })
        .then((token) => {
          console.log("FCM Token:", token);
          // Send token to backend
          fetch("../handler/saveFCMToken.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ token })
          });
        });
    } else {
      console.log("Notification permission denied.");
    }
  });

  // Foreground messages
  onMessage(messaging, (payload) => {
    console.log("Message received: ", payload);
    alert(payload.notification.title + ": " + payload.notification.body);
  });
</script>
</body>
</html>
