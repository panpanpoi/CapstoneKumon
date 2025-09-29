<?php
require_once "../handler/auth.php"; 

// ✅ Only allow admins
if ($_SESSION['account_type'] !== 'admin') {
    header("Location: loginform.php");
    exit;
}

// ✅ Fetch user info from session
$username   = $_SESSION['username'];              // e.g., "Luke Reyes"
$userRole   = ucfirst($_SESSION['account_type']); // "Admin"
$initials   = $_SESSION['initials'];              // e.g., "LR"
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KUMON Account Creation</title>
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonAdmin.css">
    <link rel="stylesheet" href="../styles/createAccount.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- ✅ Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <a href="kumonAdmin.php">
                    <img src="../styles/kumonLogoBlue.png" alt="KUMON Logo" style="height:55px; vertical-align:middle; margin-right:6px;">
                </a>
            <p>Practice Makes Possibilities</p>
        </div>

        <!-- Dynamic user profile -->
        <div class="user-profile"> 
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-details">
                <div class="username"><?= htmlspecialchars($username) ?></div>
                <div class="user-role"><?= htmlspecialchars($userRole) ?></div>
            </div>
        </div>

        <!-- ✅ Sidebar Nav -->
        <ul class="nav-menu">
            <li><a href="kumonAdmin.php" class="active"><i class="fa fa-home"></i> Home</a></li>

            <li class="subnav">
                <button class="subnavbtn">
                    <i class="fa fa-users"></i> User Management
                    <i class="fa fa-caret-down caret-icon"></i>
                </button>
                <ul class="subnav-content">
                    <li><a href="accountList.php"><i class="fa fa-users"></i> Account List</a></li>
                    <li><a href="createAccount.php" class="active"><i class="fa fa-user-plus"></i> Create Account</a></li>
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

    <!-- ✅ Main Content -->
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

        <main>
            <h2>ACCOUNT CREATION</h2>
            <form action="../handler/inputs.php" method="POST">
                <!-- Account Type -->
                <div id="accountType">
                    <h3>Select Account type</h3>
                    <label><input type="radio" name="account_type" value="student"> Student</label>
                    <label><input type="radio" name="account_type" value="teacher"> Teacher</label>
                    <label><input type="radio" name="account_type" value="admin"> Admin</label>
                </div>

                <!-- Name -->
                <div>
                    <label for="fname">*Firstname</label>
                    <input type="text" id="fname" name="fname" required>
                    
                    <label for="mname">*Middle Initial</label>
                    <input type="text" id="mname" name="mname" maxlength="1" size="1">

                    <label for="lname">*Lastname</label>
                    <input type="text" id="lname" name="lname" required>
                </div>

                <!-- Contact -->
                <div>
                    <label for="contact">*Contact Number</label>
                    <input type="text" id="contact" name="contact" required>
                </div>

                <!-- Address -->
                <div>
                    <h3>Address:</h3>
                    <label>Street:</label>
                    <input type="text" name="street" required>
                    <label>City:</label>
                    <input type="text" name="city" required>
                    <label>State/Province/Region:</label>
                    <input type="text" name="state" required>
                </div>

                <!-- Student Fields -->
                <div id="subject" style="display:none; margin-top:15px;">
                    <h3>Select Subject</h3>
                    <label><input type="radio" name="subject" value="math"> Math</label>
                    <label><input type="radio" name="subject" value="english"> English</label>
                </div>

                <div id="student-plan" style="display:none; margin-top:15px;">
                    <h3>Select Tuition Plan</h3>
                    <label><input type="radio" name="plan" value="A"> Plan A - ₱2,200</label>
                    <label><input type="radio" name="plan" value="B"> Plan B - ₱2,350</label>
                </div>

                <!-- Account Info -->
                <div>
                    <h3>Account Information</h3>
                    <label>Username:</label>
                    <input type="text" id="username" name="username" readonly>
                    <label>Password:</label>
                    <input type="text" id="password" name="password" readonly>
                </div>

                <button type="submit">Submit</button>
            </form>
        </main>
    </div>

    <!-- ✅ External JS -->
    <script src="../scr/createAccount.js"></script>
    <script>

    </script>
</body>
</html>
