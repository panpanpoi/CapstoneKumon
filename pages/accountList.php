<?php
require_once "../api/auth.php"; 

// ✅ Only allow admins
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
    <title>Kumon Admin - Account List</title>
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonAdmin.css">
    <link rel="stylesheet" href="../styles/accountList.css">
    <link rel="stylesheet" href="../styles/editPanel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
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
            <li><a href="accountList.php" class="active"><i class="fa fa-list"></i> Account List</a></li>
            <li><a href="createAccount.php"><i class="fa fa-user-plus"></i> Create Account</a></li>
            <li><a href="recordPayment.php"><i class="fa fa-edit"></i> Record Payment</a></li>
            <li><a href="viewPayment.php"><i class="fa fa-list"></i> Payments List</a></li>
            <li><a href="adminStudentDelegation.php"><i class="fa fa-user-tag"></i> Student Delegation</a></li>
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
            <form id="editUserForm">
                <input type="hidden" name="user_id">

                <h3 class="panel-title">Edit User Account</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="Name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="Surname" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="Address">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input type="text" name="mobileNumber">
                    </div>
                    <div class="form-group full-width">
                        <label>Account Type</label>
                        <select name="account_type" required>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <hr>

                <!-- Password Section -->
                <div class="password-section">
                    <label class="checkbox-inline small-checkbox">
                        <input type="checkbox" id="change_password" name="change_password">
                        Change Password
                    </label>

                    <div class="password-fields" style="display:none;">
                        <div class="form-row">
                            <div class="form-group password-group">
                                <label>New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="new_password" id="new_password">
                                    <i class="fa fa-eye toggle-password" data-target="new_password"></i>
                                </div>
                            </div>
                            <div class="form-group password-group">
                                <label>Confirm Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" id="confirm_password">
                                    <i class="fa fa-eye toggle-password" data-target="confirm_password"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                     <div class="form-actions">
                         <button type="submit" id="saveUserButton" class="btn-primary">Save Changes</button>
                     </div>
                 </form>
     
                 <!-- FA Close Button positioned at top right -->
                 <button class="close-btn"><i class="fa-solid fa-xmark"></i></button>
        </div>
    </div>

    <script src="../scr/adminSidebar.js"></script>
    <script src="../scr/accountList.js"></script>
</body>
</html>


