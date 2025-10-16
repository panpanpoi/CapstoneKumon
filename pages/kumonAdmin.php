<?php
require_once "../handler/auth.php"; 

// 🔒 Admin-only access
if ($_SESSION['account_type'] !== 'admin') {
    header("Location: loginform.php");
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
  <title>Kumon Admin Dashboard</title>
  <link rel="icon" type="image/png" href="../styles/kumonIcon.png" style="height:55px; margin-right:6px;">

  <!-- Styles -->
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/kumonAdmin.css">
  <link rel="stylesheet" href="../styles/kumonAdminHome.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <!--SIDEBAR -->
  <aside class="sidebar">
    <div class="logo">
      <a href="kumonAdmin.php">
        <img src="../styles/kumonLogoBlue.png" alt="Kumon Logo" class="logo-img">
      </a>
      <p class="tagline">Practice Makes Possibilities</p>
    </div>

    <div class="user-profile">
      <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="user-details">
        <span class="username"><?= htmlspecialchars($username) ?></span>
        <span class="user-role"><?= htmlspecialchars($userRole) ?></span>
      </div>
    </div>

    <ul class="nav-menu">
      <li><a href="kumonAdmin.php" class="active"><i class="fa fa-home"></i> <span>Home</span></a></li>

      <li class="subnav">
        <button class="subnavbtn">
          <i class="fa fa-users"></i> <span>User Management</span>
          <i class="fa fa-caret-down caret-icon"></i>
        </button>
        <ul class="subnav-content">
          <li><a href="accountList.php"><i class="fa fa-list"></i> Account List</a></li>
          <li><a href="createAccount.php"><i class="fa fa-user-plus"></i> Create Account</a></li>
        </ul>
      </li>

      <li class="subnav">
        <button class="subnavbtn">
          <i class="fa fa-credit-card"></i> <span>Payment Management</span>
          <i class="fa fa-caret-down caret-icon"></i>
        </button>
        <ul class="subnav-content">
          <li><a href="recordPayment.php"><i class="fa fa-pen-to-square"></i> Record Payment</a></li>
          <li><a href="viewPayment.php"><i class="fa fa-list"></i> Payments List</a></li>
        </ul>
      </li>

      <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <header class="header">
      <div class="header-right">
        </div>
        <div class="user-info">
          <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
          <div class="user-name"><?= htmlspecialchars($username) ?></div>
        </div>
      </div>
    </header>

    <section class="dashboard-content">
      <div class="stats-section">
        <div class="section-header">
          <h3><i class="fa fa-chart-line"></i> Payment Overview</h3>
          <small id="last-updated">Last updated: —</small>
        </div>

        <div class="stats-grid">
          <!-- Total Payments -->
          <div class="stat-card total">
            <i class="fa-solid fa-sack-dollar icon"></i>
            <div>
              <h4>This Month’s Total</h4>
              <p id="total-payments">₱0.00</p>
            </div>
          </div>

          <!-- Cash -->
          <div class="stat-card cash">
            <i class="fa-solid fa-money-bill-wave icon"></i>
            <div>
              <h4>Cash Payments</h4>
              <p id="cash-payments">₱0.00</p>
            </div>
          </div>

          <!-- GCash -->
          <div class="stat-card gcash">
            <i class="fa-solid fa-mobile-screen icon"></i>
            <div>
              <h4>GCash Payments</h4>
              <p id="gcash-payments">₱0.00</p>
            </div>
          </div>

          <!-- Bank -->
          <div class="stat-card bank">
            <i class="fa-solid fa-building-columns icon"></i>
            <div>
              <h4>Bank Payments</h4>
              <p id="bank-payments">₱0.00</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- SCRIPTS -->
  <script src="../scr/kumonAdmin.js"></script>
</body>
</html>
