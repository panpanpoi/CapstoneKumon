<?php
require_once "../handler/auth.php"; 

// ✅ Only allow admins
if ($_SESSION['account_type'] !== 'admin') {
    header("Location: ../loginform.php");
    exit;
}

$username = $_SESSION['username'];   // Full name
$userRole = ucfirst($_SESSION['account_type']); // Admin
$initials = $_SESSION['initials'];   // Initials
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kumon Admin - Account List</title>
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonAdmin.css">
    <link rel="stylesheet" href="../styles/accountList.css">
    <link rel="stylesheet" href="../styles/editPanel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar  -->
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
                <div class="user-info">
                    <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="user-name"><?= htmlspecialchars($username) ?></div>
                </div>
            </div>
        </div>

        <div class="dashboard-content" style="padding: 20px;">
            <h2>User List</h2>

            <!-- Flash messages -->
            <div id="flashMessage"></div>

            <!-- Filters -->
            <div class="filter-buttons">
                <button id="showActive" class="btn-filter active">Active Accounts</button>
                <button id="showArchived" class="btn-filter">Archived Accounts</button>
            </div>

            <!-- Search -->
            <div style="margin: 15px 0; display:flex; gap:10px; align-items:center;">
                <input type="text" id="searchInput" placeholder="Search users...">
            </div>

            <!-- User Table -->
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

            <!-- Pagination -->
            <div id="pagination" style="margin-top:10px; display:flex; gap:5px;"></div>
        </div>
    </div>

    <!-- Floating Edit Panel -->
    <div id="editPanel" class="edit-panel">
        <div class="edit-panel-content">
            <span class="close-btn">&times;</span>
            <h2>Edit User</h2>

            <form id="editUserForm">
                <input type="hidden" id="edit_user_id" name="user_id">

                <label>Name:</label>
                <input type="text" id="edit_name" name="Name" required>

                <label>Surname:</label>
                <input type="text" id="edit_surname" name="Surname" required>

                <label>Address:</label>
                <input type="text" id="edit_address" name="Address">

                <label>Mobile Number:</label>
                <input type="text" id="edit_mobile" name="mobileNumber">

                <label>Account Type:</label>
                <select id="edit_account_type" name="account_type" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>

                <div class="password-section">
                    <label>Change Password:</label>
                    <div class="password-toggle">
                        <input type="checkbox" id="change_password" name="change_password">
                        <label for="change_password">Enable password change</label>
                    </div>
                    <div class="password-fields" style="display: none;">
                        <input type="password" id="new_password" name="new_password" placeholder="New Password" minlength="6">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" minlength="6">
                        <small class="password-help">Password must be at least 6 characters long.</small>
                    </div>
                </div>

                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    
    <script src="../scr/accountList.js"></script>
</body>
</html>
