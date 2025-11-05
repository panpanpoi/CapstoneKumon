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
  <title>Kumon Admin - Payments List</title>
  <link rel="icon" type="image/png" href="../styles/kumonIcon.png">

  <!-- Global + Admin Styles -->
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/kumonAdmin.css">
  <link rel="stylesheet" href="../styles/viewPayment.css">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="logo">
    <a href="kumonAdmin.php"><img src="../styles/kumonLogoBlue.png" alt="KUMON Logo" style="height:55px;"></a>
    <p>Practice Makes Possibilities</p>
  </div>

  <div class="user-profile">
    <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
    <div class="user-details">
      <div class="username"><?= htmlspecialchars($username) ?></div>
      <div class="user-role"><?= htmlspecialchars($userRole) ?></div>
    </div>
  </div>

  <nav>
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
        <button class="subnavbtn active"><i class="fa fa-credit-card"></i> Payment Management <i class="fa fa-caret-down caret-icon"></i></button>
        <ul class="subnav-content show">
          <li><a href="recordPayment.php"><i class="fa fa-edit"></i> Record Payment</a></li>
          <li><a href="viewPayment.php" class="active"><i class="fa fa-list"></i> Payments List</a></li>
        </ul>
      </li>

      <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </nav>
</aside>

<!-- MAIN CONTENT -->
<main class="main-content">
  <!-- Header -->
  <header class="header">
    <div class="header-right">
      <div class="user-info">
        <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
      </div>
    </div>
  </header>

  <!-- Dashboard Content -->
  <section class="dashboard-content">
    <h2>Payments List</h2>

    <!-- Action Buttons -->
    <div class="action-bar">
      <!-- Import Payments -->
      <form action="../handler/importPayments.php" method="POST" enctype="multipart/form-data" class="import-form">
        <label><i class="fa fa-file-import"></i> Import CSV:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit" class="btn-import">
          <i class="fa fa-upload"></i> Import
        </button>
      </form>

      <!-- Export Payments -->
      <form action="../handler/exportPayments.php" method="GET" class="export-form">
        <label for="month"><i class="fa fa-calendar"></i> Export Month:</label>
        <input type="month" id="month" name="month" value="<?= date('Y-m') ?>" required>
        <button type="submit" class="btn-export">
          <i class="fa fa-file-export"></i> Export CSV
        </button>
      </form>
    </div>


    <!-- Payments Table -->
    <div id="paymentsContainer" class="table-container">
      <table id="paymentsTable">
        <thead>
        <tr>
          <th>ID</th>
          <th>Student</th>
          <th>Code</th>
          <th>Amount</th>
          <th>Payment Date</th>
          <th>Method</th>
          <th>Reference #</th>
          <th>TF-Month Covered</th>
          <th>Due Date</th> <!-- ✅ Added -->
          <th>Status</th>
          <th>Receipt</th>
          <th>Actions</th>
        </tr>
      </thead>

        <tbody id="paymentsBody">
          <!-- Populated dynamically by viewPayment.js -->
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="paginationContainer">
      <!-- Buttons inserted by JS -->
    </div>

    <!-- Flash Messages -->
    <div id="flashMessage" class="alert"></div>
  </section>
</main>

  <div id="verifyModal" class="modal">
  <div class="modal-content">
    <span id="closeModal" class="close">&times;</span>
    <h2>Verify Payment</h2>

    <form id="verifyForm">
      <input type="hidden" name="payment_id" id="paymentId">
      <input type="hidden" name="action" id="verifyAction" value="approve">

      <div class="form-group">
        <label>Receipt:</label>
        <div id="receiptPreview" class="receipt-preview"></div>
      </div>

      <div class="form-group">
        <label for="reference_number">Reference Number:</label>
        <input type="text" id="reference_number" name="reference_number" required>
      </div>

      <div class="form-group">
        <label for="remarks">Remarks (optional):</label>
        <textarea id="remarks" name="remarks" placeholder="Add remarks..."></textarea>
      </div>

      <div class="form-actions">
        <button type="submit" id="verifyBtn" class="btn btn-success">Approve</button>
        <button type="button" id="rejectBtn" class="btn btn-danger">Reject</button>
        <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>



<!-- Scripts -->
<script src="../scr/viewPayment.js"></script>
<script src="../scr/adminSidebar.js"></script>
</body>
</html>
