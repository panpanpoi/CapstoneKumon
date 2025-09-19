<?php
$currentPage = basename($_SERVER['PHP_SELF']);
require_once "../handler/auth.php"; // fetch logged-in user data
?>

<div class="sidebar">
    <!-- Logo -->
    <div class="logo">
        <h1><a href="<?= ($user['account_type'] === 'teacher') ? 'kumonTeacher.php' : 'dashboard.php'; ?>">KUMON</a></h1>
        <p>Practice Makes Possibilities</p>
    </div>

    <!-- User Profile -->
    <div class="user-profile">
        <div class="user-avatar">
            <?= strtoupper(substr($user['Name'],0,1)) . strtoupper(substr($user['Surname'],0,1)) ?>
        </div>
        <div>
            <div class="username">
                <?= htmlspecialchars($user['Name'] . " " . $user['Surname']); ?>
            </div>
            <div class="user-role">
                <?= ucfirst($user['account_type']); ?>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <ul class="nav-menu">
        <?php if ($user['account_type'] === 'teacher'): ?>
            <li>
                <a href="kumonTeacher.php" class="<?= ($currentPage == 'kumonTeacher.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li>
                <a href="kumonClass.php" class="<?= ($currentPage == 'kumonClass.php') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> My Class
                </a>
            </li>
            <li>
                <a href="ptcSchedule.php" class="<?= ($currentPage == 'ptcSchedule.php') ? 'active' : '' ?>">
                    <i class="fas fa-calendar"></i> PTC Schedule
                </a>
            </li>

        <?php elseif ($user['account_type'] === 'student'): ?>
            <li>
                <a href="studentDashboard.php" class="<?= ($currentPage == 'studentDashboard.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="studentSchedule.php" class="<?= ($currentPage == 'studentSchedule.php') ? 'active' : '' ?>">
                    <i class="fas fa-calendar"></i> My Schedule
                </a>
            </li>
            <li>
                <a href="studentPayments.php" class="<?= ($currentPage == 'studentPayments.php') ? 'active' : '' ?>">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
            </li>

        <?php elseif ($user['account_type'] === 'admin'): ?>
            <li>
                <a href="adminDashboard.php" class="<?= ($currentPage == 'adminDashboard.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="manageUsers.php" class="<?= ($currentPage == 'manageUsers.php') ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> Manage Users
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
        <?php endif; ?>

        <!-- Logout (always visible) -->
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>
