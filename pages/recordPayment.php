<?php
require_once "../handler/auth.php"; 

// Only allow admins
if ($_SESSION['account_type'] !== 'admin') {
    header("Location: ../loginform.php");
    exit;
}

$username = $_SESSION['username'];  
$userRole = ucfirst($_SESSION['account_type']); 
$initials = $_SESSION['initials'];   
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kumon Admin - Record Payment</title>
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">

    <!-- Global & Page Styles -->
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonAdmin.css">
    <link rel="stylesheet" href="../styles/recordPayment.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <a href="kumonAdmin.php">
                <img src="../styles/kumonLogoBlue.png" alt="KUMON Logo" style="height:55px; margin-right:6px;">
            </a>
            <p>Practice Makes Possibilities</p>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-details">
                <div class="username"><?= htmlspecialchars($username) ?></div>
                <div class="user-role"><?= htmlspecialchars($userRole) ?></div>
            </div>
        </div>

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
                <button class="subnavbtn">
                    <i class="fa fa-credit-card"></i> Payment Management
                    <i class="fa fa-caret-down caret-icon"></i>
                </button>
                <ul class="subnav-content">
                    <li><a href="recordPayment.php" class="active"><i class="fa fa-edit"></i> Record Payment</a></li>
                    <li><a href="viewPayment.php"><i class="fa fa-list"></i> Payments List</a></li>
                </ul>
            </li>

            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

   <!-- MAIN CONTENT -->
<main class="main-content">
  <!-- Header -->
  <header class="header">
    <div class="header-right">
      <div class="user-info">
        <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="user-name"><?= htmlspecialchars($username) ?></div>
      </div>
    </div>
  </header>

  <!-- Dashboard / Page Content -->
  <section class="dashboard-content">
    <div class="payment-form-container">
      <h2><i class="fa-solid fa-credit-card"></i> Record a Payment</h2>

      <form id="paymentForm" action="../handler/addPayment.php" method="POST">
        <!-- Student Selection -->
        <input type="hidden" id="student_id" name="student_id" required>

        <label for="student_search">Search Student:</label>
        <input 
          type="text" 
          id="student_search" 
          placeholder="Enter student ID (e.g., KSTU2025000)" 
          required
        >
        <div id="results" class="search-results"></div>
        <div id="selectedStudent" class="selected-student"></div>
        <button type="button" id="changeStudentBtn" class="change-btn">Change Student</button>

        <!-- Payment Details -->
        <label for="amount">Amount:</label>
        <input 
          type="number" 
          step="0.01" 
          id="amount" 
          name="amount" 
          placeholder="Enter amount" 
          required
        >

        <label for="payment_date">Payment Date:</label>
        <input 
          type="date" 
          id="payment_date" 
          name="payment_date" 
          required
        >

        <label for="payment_method">Payment Method:</label>
        <select id="payment_method" name="payment_method" required>
          <option value="Cash">Cash</option>
          <option value="GCash">GCash</option>
          <option value="Bank">Bank</option>
        </select>

        <label for="reference_number">Reference Number:</label>
        <input 
          type="text" 
          id="reference_number" 
          name="reference_number" 
          placeholder="Enter reference number (if any)"
        >

        <label for="remarks">Notes / Remarks:</label>
        <textarea 
          id="remarks" 
          name="remarks" 
          placeholder="Add any remarks here..."
        ></textarea>

        <button type="submit" id="submitBtn" disabled>
          <i class="fa-solid fa-floppy-disk"></i> Save Payment
        </button>
      </form>
    </div>
  </section>
</main>


    
    <script src="../scr/recordPayment.js"></script>
    <script src="../scr/adminSidebar.js"></script>
</body>
</html>
