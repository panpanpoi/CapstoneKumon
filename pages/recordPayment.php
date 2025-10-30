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

  <!-- Styles -->
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/kumonAdmin.css">
  <link rel="stylesheet" href="../styles/recordPayment.css">

    <script 
      data-fa-kit-code="38c11e3f20" 
      type="module" 
      src="https://cdn.jsdelivr.net/npm/@awesome.me/webawesome@3.0.0/dist-cdn/webawesome.loader.js">
    </script>
    <link 
      rel="stylesheet" 
      href="https://cdn.jsdelivr.net/npm/@awesome.me/webawesome@3.0.0/dist-cdn/styles/webawesome.css">

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
        <button class="subnavbtn"><i class="fa fa-users"></i> User Management <i class="fa fa-caret-down caret-icon"></i></button>
        <ul class="subnav-content">
          <li><a href="accountList.php"><i class="fa fa-users"></i> Account List</a></li>
          <li><a href="createAccount.php"><i class="fa fa-user-plus"></i> Create Account</a></li>
        </ul>
      </li>
      <li class="subnav">
        <button class="subnavbtn"><i class="fa fa-credit-card"></i> Payment Management <i class="fa fa-caret-down caret-icon"></i></button>
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
    <header class="header">
      <div class="header-right">
        <div class="user-info">
          <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
          <div class="user-name"><?= htmlspecialchars($username) ?></div>
        </div>
      </div>
    </header>

    <section class="dashboard-content">
      <div class="payment-form-container">
        <h2><i class="fa-solid fa-credit-card"></i> Record a Payment</h2>

        <form id="paymentForm" action="../handler/addPayment.php" method="POST">
          <input type="hidden" id="student_id" name="student_id" required>

          <!-- DRAWER TRIGGER -->
          <wa-button id="openDrawerBtn" variant="brand">
            <i class="fa fa-search"></i> Search Student
          </wa-button>

          <div id="selectedStudent" class="selected-student"></div>
          <wa-button id="changeStudentBtn" variant="neutral" style="display:none;">Change Student</wa-button>

          <label for="amount">Amount:</label>
          <input type="number" step="0.01" id="amount" name="amount" placeholder="Enter amount" required>

          <label for="payment_date">Payment Date:</label>
          <input type="date" id="payment_date" name="payment_date" required>

          <label for="payment_method">Payment Method:</label>
          <select id="payment_method" name="payment_method" required>
            <option value="Cash">Cash</option>
            <option value="GCash">GCash</option>
            <option value="Bank">Bank</option>
          </select>

          <label for="reference_number">Reference Number:</label>
          <input type="text" id="reference_number" name="reference_number" placeholder="Enter reference number (if any)">

          <label for="remarks">Notes / Remarks:</label>
          <textarea id="remarks" name="remarks" placeholder="Add any remarks here..."></textarea>

          <button type="submit" id="submitBtn" disabled>
            <i class="fa-solid fa-floppy-disk"></i> Save Payment
          </button>
        </form>
      </div>
    </section>
  </main>

  <!-- STUDENT SEARCH DRAWER -->
  <wa-drawer label="Search Student" placement="bottom" class="drawer-placement-bottom">
    <div class="drawer-content" style="padding: 15px; max-height: 60vh; overflow-y: auto;">
      <h3 style="margin-bottom: 10px;">Find Student</h3>
      <wa-input id="studentSearchInput" placeholder="Search by name or student code" clearable></wa-input>
      <div id="studentList" style="margin-top: 15px; border: 1px solid #ddd; border-radius: 8px; overflow-y: auto; max-height: 250px; padding: 5px;">
        <p style="text-align:center; color:#666; margin:10px 0;">Start typing to search...</p>
      </div>
      <div id="studentLedger" style="margin-top: 20px;">
        <h4>Student Ledger</h4>
        <div id="ledgerContent" style="font-size: 14px; color: #333;">Select a student to view their details.</div>
      </div>
    </div>
    <wa-button slot="footer" variant="neutral" data-drawer="close">
      <i class="fa fa-times"></i> Close
    </wa-button>
  </wa-drawer>

  <!-- Scripts -->
  <script src="../scr/adminSidebar.js"></script>
  <script src="../scr/recordPayment.js"></script>
</body>
</html>
