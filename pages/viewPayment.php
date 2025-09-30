<?php
require_once "../handler/auth.php"; 

// ✅ Only allow admins
if ($_SESSION['account_type'] !== 'admin') {
    header("Location: loginform.php");
    exit;
}

$username   = $_SESSION['username'];   
$userRole   = ucfirst($_SESSION['account_type']);
$initials   = $_SESSION['initials'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kumon Admin - View Payments</title>
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonAdmin.css">
    <link rel="stylesheet" href="../styles/viewPayments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h1>
                <a href="kumonAdmin.php">
                    <img src="../styles/kumonLogoBlue.png" alt="KUMON Logo" style="height:55px; vertical-align:middle; margin-right:6px;">
                </a>
            </h1>
            <p>Practice Makes Possibilities</p>
        </div>

        <!-- User Profile -->
        <div class="user-profile"> 
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-details">
                <div class="username"><?= htmlspecialchars($username) ?></div>
                <div class="user-role"><?= htmlspecialchars($userRole) ?></div>
            </div>
        </div>

        <!-- Navigation -->
        <ul class="nav-menu">
            <li><a href="kumonAdmin.php"><i class="fa fa-home"></i> Home</a></li>
            <li class="subnav">
                <button class="subnavbtn">
                    <i class="fa fa-users"></i> User Management
                    <i class="fa fa-caret-down caret-icon"></i>
                </button>
                <ul class="subnav-content">
                    <li><a href="accountList.php"><i class="fa fa-users"></i> Account List</a></li>
                    <li><a href="createAccount.php"><i class="fa fa-user-plus"></i> Create Account</a></li>
                </ul>
            </li>
            <li class="subnav">
                <button class="subnavbtn active">
                    <i class="fa fa-credit-card"></i> Payment Management 
                    <i class="fa fa-caret-down caret-icon"></i>
                </button>
                <ul class="subnav-content">
                    <li><a href="recordPayment.php"><i class="fa fa-edit"></i> Record Payment</a></li>
                    <li><a href="viewPayment.php" class="active"><i class="fa fa-list"></i> Payments List</a></li>
                </ul>
            </li>
            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="header-right">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                <div class="user-info">
                    <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="user-name"><?= htmlspecialchars($username) ?></div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <h2>Payments List</h2>

        <!-- Filter -->
        <div class="filter-buttons">
            <button id="showActive" class="btn-filter active">Active Payments</button>
            <button id="showArchived" class="btn-filter">Archived Payments</button>
        </div>

        <!-- Container where payments table will be injected -->
        <div id="paymentsContainer"></div>

        <!-- Flash Message -->
        <div id="flashMessage" class="alert"></div>
    </div>

    <!-- 📌 Verify Modal -->
    <div id="verifyModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeModal">&times;</span>
            <h3>Verify Payment</h3>
            <form id="verifyForm" enctype="multipart/form-data">
                <input type="hidden" name="payment_id" id="paymentId">
                <div class="form-group">
                    <label for="receipt">Upload Receipt (optional):</label>
                    <input type="file" name="receipt" id="receipt" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="remarks">Remarks:</label>
                    <textarea name="remarks" id="remarks" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Verify</button>
            </form>
        </div>
    </div>

    <script src="../scr/viewPayment.js"></script>
    <script src="../scr/adminSidebar.js"></script>
</body>
</html>
