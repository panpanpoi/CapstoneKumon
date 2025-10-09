<?php 
require_once "../handler/auth.php"; 
// ✅ Only allow admins
if ($_SESSION['account_type'] !== 'teacher') {
    header("Location: loginform.php");
    exit;
}

$username   = $_SESSION['username'];   
$userRole   = ucfirst($_SESSION['account_type']); // Admin
$initials   = $_SESSION['initials'];   // LR
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>KUMON Teacher Account</title>
  <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/kumonTeacher.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
<div class="sidebar">
    <div class="logo">
        <h1>
          <a href="kumonTeacher.php">
            <img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:100px; vertical-align:middle; margin-right:6px;"></a>
        </h1>
        <p>Practice Makes Possibilities</p>
    </div>
    

    <div class="user-profile"> 
        <div class="user-avatar">
            <?= strtoupper(substr($user['Name'],0,1)) . strtoupper(substr($user['Surname'],0,1)) ?>
        </div>
        <div class="user-details">
            <div class="username"><?= htmlspecialchars($user['Name'] . " " . $user['Surname']); ?></div>
            <div class="user-role"><?= ucfirst($user['account_type']); ?></div>
        </div>
    </div>

    <ul class="nav-menu">
        <li><a href="kumonTeacher.php" class="<?= $currentPage == 'kumonTeacher.php' ? 'active' : '' ?>"><i class="fa fa-home"></i> Home</a></li>
        <li><a href="kumonClass.php" class="<?= $currentPage == 'kumonClass.php' ? 'active' : '' ?>"><i class="fa fa-users"></i> My Class</a></li>
        <li><a href="teacherPtcScheduler.php" class="<?= $currentPage == 'teacherPtcScheduler.php' ? 'active' : '' ?>"><i class="fa fa-calendar"></i> PTC Schedule</a></li>
        <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
     <!-- 🔹 Flash success message -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <p style="color:green; font-weight:bold;">
            <?= htmlspecialchars($_SESSION['flash_success']); ?>
        </p>
        <?php unset($_SESSION['flash_success']); // remove after showing ?>
    <?php endif; ?>

    <h2>Welcome back, <?= htmlspecialchars($user['Name']); ?>!</h2>
    <p>Select an option from the menu.</p>
</div>
</body>
</html>
