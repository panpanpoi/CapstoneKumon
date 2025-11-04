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
  <link rel="stylesheet" href="../styles/paymentDrawer.css">

  <!-- WebAwesome (for icons & drawer/buttons) -->
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
      <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
      <div class="user-details">
        <div class="username"><?php echo htmlspecialchars($username); ?></div>
        <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
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
          <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
          <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
        </div>
      </div>
    </header>

    <section class="dashboard-content">
      <div class="panel payment-panel">
        <div class="panel-header">
          <h2><i class="fa-solid fa-credit-card"></i> Record a Payment</h2>
        </div>

        <div class="panel-body">
          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
              <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
              <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
          <?php endif; ?>

          <form id="paymentForm" action="../handler/addPayment.php" method="POST">
            <input type="hidden" id="student_id" name="student_id" required>

            <!-- DRAWER TRIGGER -->
            <wa-button id="openDrawerBtn" variant="brand" size="small">
              <i class="fa fa-search"></i> Search Student
            </wa-button>

            <div id="selectedStudent" class="selected-student"></div>
            <wa-button id="changeStudentBtn" variant="neutral" size="small" style="display:none;">Change Student</wa-button>

            <label for="amount">Amount:</label>
            <input type="number" step="0.01" id="amount" name="amount" placeholder="Enter amount" required>
            <div class="form-group">
              <label for="tfMonthCovered">TF Month Covered</label>
              <input type="text" id="tfMonthCovered" name="tfMonthCovered" readonly>
            </div>

            <label for="payment_date">Payment Date:</label>
            <input type="date" id="payment_date" name="payment_date" required>

            <label for="payment_method">Payment Method:</label>
            <select id="payment_method" name="payment_method" required>
              <option value="Cash">Cash</option>
              <option value="GCash">GCash</option>
              <option value="Bank">Bank</option>
            </select>

            <div class="reference-group">
              <label for="reference_number">Reference Number:</label>
              <input type="text" id="reference_number" name="reference_number" placeholder="Enter reference number (if any)">
            </div>

            <label for="remarks">Notes / Remarks:</label>
            <textarea id="remarks" name="remarks" placeholder="Add any remarks here..."></textarea>

            <button type="submit" id="submitBtn" disabled>
              <i class="fa-solid fa-floppy-disk"></i> Save Payment
            </button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <!-- STUDENT SEARCH DRAWER -->
  <wa-drawer label="Search Student"  class="custom-drawer">
    <div class="drawer-inner">
      <div class="drawer-header">
        <h3>Find Student</h3>
      </div>

      <input id="studentSearchInput" type="text" placeholder="Search by name or student code" class="drawer-input">

      <div id="studentList" class="drawer-list">
        <p style="text-align:center; color:#666; margin:10px 0;">Start typing to search...</p>
      </div>

      <!-- Student Ledger -->
      <div id="studentLedger" style="display:none;">
        <h4>Payment Ledger</h4>
        <div id="ledgerContent">Select a student to view details.</div>
      </div>

      <!-- Payment Summary -->
      <div class="payment-summary" id="paymentSummary" style="display:none;">
        <div class="info">
          <strong>Status:</strong> <span id="paymentStatus">Pending</span>
        </div>
      </div>

      <div class="drawer-footer">
        <button id="confirmStudentBtn" disabled>
          <i class="fa fa-check"></i> Select Student
        </button>
      </div>
    </div>
  </wa-drawer>

  <!-- Scripts -->
  <script src="../scr/adminSidebar.js"></script>
  <script src="../scr/recordPayment.js"></script>
</body>
</html>
