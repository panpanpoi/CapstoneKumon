<?php
require_once "../api/auth.php";
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

  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
 <link rel="stylesheet" href="../styles/kumonAdmin.css">
 <link rel="stylesheet" href="../styles/viewPayment.css">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
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
   <li><a href="accountList.php"><i class="fa fa-list"></i> Account List</a></li>
   <li><a href="createAccount.php"><i class="fa fa-user-plus"></i> Create Account</a></li>
   <li><a href="recordPayment.php"><i class="fa fa-pen-to-square"></i> Record Payment</a></li>
   <li><a href="viewPayment.php" class="active"><i class="fa fa-list"></i> Payments List</a></li>
   <li><a href="adminStudentDelegation.php"><i class="fa fa-user-tag"></i> Student Delegation</a></li>
   <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
  </ul>
 </nav>
</aside>

<main class="main-content">
  <header class="header">
  <div class="header-right">
   <div class="user-info">
    <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
   </div>
  </div>
 </header>

  <section class="dashboard-content">
  <h2>Payments List</h2>

    <div class="action-bar">
      <!-- Left: Export -->
      <form action="../api/exportPayments.php" method="GET" class="export-form">
        <label for="month"><i class="fa fa-calendar"></i> Month:</label>
        <input type="month" id="month" name="month" value="<?= date('Y-m') ?>" required>
        <button type="submit" class="btn-export">
          <i class="fa fa-file-export"></i> Export CSV
        </button>
      </form>

      <!-- Right: Search & Filter -->
      <div class="search-filter-group">
        <!-- ✅ NEW: Status Filter -->
        <select id="statusFilter" class="status-select">
            <option value="">All Statuses</option>
            <option value="verified">Verified</option>
            <option value="unverified">Unverified</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
        </select>

        <!-- Search Input -->
        <div class="search-box">
            <i class="fa fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, code...">
        </div>
      </div>
  </div>


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
     <th>Status</th>
     <th>Receipt</th>
     <th>Actions</th>
    </tr>
   </thead>
    <tbody id="paymentsBody">
        <!-- Rows will be populated by JS -->
    </tbody>
   </table>
  </div>

    <div class="pagination" id="paginationContainer">
      <!-- Pagination buttons -->
     </div>

    <div id="flashMessage" class="alert"></div>
 </section>
</main>

 <!-- Verification Modal -->
 <div id="verifyModal" class="modal">
 <div class="modal-content">
  <span id="closeModal" class="close">&times;</span>
  <h2>Verify Payment</h2>

      <form id="verifyForm" enctype="multipart/form-data">
   <input type="hidden" name="payment_id" id="paymentId">
    <input type="hidden" name="action" id="verifyAction" value="approve">

   <div class="form-group">
    <label>Receipt:</label>
    <div id="receiptPreview" class="receipt-preview"></div>
   </div>

      <div id="noReceiptAlert" class="alert-warning" style="display: none; margin-bottom: 15px;">
        <strong>Note:</strong> No receipt was submitted. Please upload one to verify this payment.
      </div>

      <div class="form-group" id="adminUploadGroup" style="display: none;">
        <label for="admin_receipt">Upload Receipt (Admin):</label>
        <input type="file" id="admin_receipt" name="admin_receipt" accept="image/*,application/pdf">
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

<script src="../scr/viewPayment.js"></script>
<script src="../scr/adminSidebar.js"></script>
</body>
</html>