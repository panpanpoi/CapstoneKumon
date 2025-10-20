<?php
require_once "../handler/auth.php"; 

// Only allow admins
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: loginform.php");
    exit;
}

// User session data
$username   = $_SESSION['username'];              
$userRole   = ucfirst($_SESSION['account_type']); 
$initials   = $_SESSION['initials'];              

// Flash messages
$successMsg = $_SESSION['success'] ?? null;
$errorMsg   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
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

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <a href="kumonAdmin.php">
                <img src="../styles/kumonLogoBlue.png" alt="KUMON Logo" style="height:55px; vertical-align:middle; margin-right:6px;">
            </a>
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
                <button class="subnavbtn active">
                    <i class="fa fa-users"></i> User Management
                    <i class="fa fa-caret-down caret-icon"></i>
                </button>
                <ul class="subnav-content" style="display:block;">
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

        <main>
            <h2>Create New Account</h2>

            <!-- Flash Messages -->
            <?php if ($successMsg): ?>
                <div class="alert success"><?= $successMsg ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert error"><?= $errorMsg ?></div>
            <?php endif; ?>

            <!-- Account Form -->
            <form action="../handler/inputs.php" method="POST" class="account-form">

                <!-- Account Type -->
                <section id="accountType">
                    <h3>Account Type</h3>
                    <label><input type="radio" name="account_type" value="student" required> Student</label>
                    <label><input type="radio" name="account_type" value="teacher"> Teacher</label>
                    <!-- <label><input type="radio" name="account_type" value="admin"> Admin</label> -->
                </section>

                <!-- Personal Info -->
                <section>
                    <h3>Personal Information</h3>
                    <label for="fname">* First Name</label>
                    <input type="text" id="fname" name="fname" required>

                    <label for="mname">Middle Initial</label>
                    <input type="text" id="mname" name="mname" maxlength="2" style="text-transform:uppercase;">
                    
                    <label for="lname">* Last Name</label>
                    <input type="text" id="lname" name="lname" required>
                 </section>

                <!-- Contact -->
                <section>
                    <label for="contact">* Contact Number</label>
                    <input type="text" id="contact" name="contact" required>
                </section>

                <!-- Address -->
                <section>
                    <h3>Address</h3>
                    <label>Street:</label>
                    <input type="text" name="street" required>
                    <label>City:</label>
                    <input type="text" name="city" required>
                    <label>State/Province/Region:</label>
                    <input type="text" name="state" required>
                </section>

                <!-- Student-Only Fields -->
                <section id="subject" style="display:none;">
                    <h3>Subject</h3>
                    <label><input type="radio" name="subject" value="math"> Math</label>
                    <label><input type="radio" name="subject" value="english"> English</label>
                </section>

                <section id="student-plan" style="display:none;">
                    <h3>Tuition Plan</h3>
                    <label><input type="radio" name="plan" value="A"> Plan A - ₱2,200</label>
                    <label><input type="radio" name="plan" value="B"> Plan B - ₱2,350</label>
                </section>

                <!-- Student/Teacher Code (auto-filled by JS + handler) -->
                <section id="student-code-field" style="display:none;">
                    <h3>Generated ID</h3>
                    <input type="text" id="studentCode" name="studentCode" readonly>
                </section>

                <!-- Account Info Preview -->
                <section>
                    <h3>Account Login</h3>
                    <label>Username:</label>
                    <input type="text" id="username" name="username" readonly>
                    <label>Password:</label>
                    <input type="text" id="password" name="password" readonly>
                </section>

                <button type="submit" class="btn-primary">Create Account</button>
            </form>
        </main>
    </div>

    
    <script src="../scr/createAccount.js"></script>
    <script>
(function () {
  // Defensive wrapper so other script errors don't break this
  try {
    document.querySelectorAll(".subnavbtn").forEach(btn => {
      // ensure it's not a submit button inside a form
      if (btn.tagName.toLowerCase() === "button" && (!btn.type || btn.type === "submit")) {
        btn.type = "button";
      }

      btn.addEventListener("click", () => {
        try {
          const content = btn.nextElementSibling;
          const caret = btn.querySelector(".caret-icon");

          // Toggle content visibility with a "show" class
          if (content) {
            const isShown = content.classList.toggle("show");
          }

          // rotate caret if present
          if (caret) caret.classList.toggle("rotate");
        } catch (innerErr) {
          console.error("Subnav inner error:", innerErr);
        }
      });
    });
  } catch (err) {
    console.error("Subnav initialization failed:", err);
  }
})();
</script>

</body>
</html>
