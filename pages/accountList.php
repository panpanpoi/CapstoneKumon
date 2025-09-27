<?php
require_once "../handler/auth.php"; 

// ✅ Only allow admins
if ($_SESSION['account_type'] !== 'admin') {
    header("Location: ../loginform.php");
    exit;
}

$username   = $_SESSION['username'];   // Full name (from auth.php)
$userRole   = ucfirst($_SESSION['account_type']); // Admin
$initials   = $_SESSION['initials'];   // LR
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account List</title>
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/kumonAdmin.css">
  <link rel="stylesheet" href="../styles/accountList.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
      <div class="logo">
          <h1>
            <a href="kumonAdmin.php">
                <img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:80px; vertical-align:middle; margin-right:6px;">
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
                  <li><a href="accountList.php" class="active"><i class="fa fa-users"></i> Account List</a></li>
                  <li><a href="createAccount.php"><i class="fa fa-user-plus"></i> Create Account</a></li>
              </ul>
          </li>
          <li class="subnav">
              <button class="subnavbtn">
                  <i class="fa fa-credit-card"></i> Payment Management 
                  <i class="fa fa-caret-down caret-icon"></i>
              </button>
              <ul class="subnav-content">
                  <li><a href="recordPayment.php"><i class="fa fa-edit"></i> Record Payment</a></li>
                  <li><a href="viewPayment.php"><i class="fa fa-list"></i> Payments List</a></li>
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

    <div class="dashboard-content" style="padding: 20px;">
      <h2>User List</h2>

      <!-- Flash message area -->
      <div id="flashMessage"></div>
      
      <!-- Filter buttons -->
      <div class="filter-buttons">
        <button id="showActive" class="btn-filter active">Active Accounts</button>
        <button id="showArchived" class="btn-filter">Archived Accounts</button>
      </div>

      <!-- Search -->
      <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
        <input type="text" id="searchInput" placeholder="Search users...">
      </div>
  
      <!-- User table -->
      <table id="userTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Surname</th>
            <th>Address</th>
            <th>Contact Number</th>
            <th>Account</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <!-- External JS -->
  <script src="../scr/accountList.js"></script>
</body>
</html>
