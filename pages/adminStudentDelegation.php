<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../database.php";
require_once "../api/auth.php"; // This should be correct now

// ✅ Ensure user is logged in and is an admin
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['account_type']) ||
    strtolower($_SESSION['account_type']) !== 'admin'
) {
    session_destroy();
    header("Location: ../pages/loginform.php?error=Access denied. Admins only.");
    exit;
}

// --- Assign session variables ---
$username   = $_SESSION['username'] ?? 'Administrator';
$userRole   = ucfirst($_SESSION['account_type']);
$initials   = $_SESSION['initials'] ?? 'AD';

// --- Pagination setup ---
const TEACHERS_PER_PAGE = 10;
// Validate and sanitize current page number
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
$current_page = max(1, $current_page); 
$offset = ($current_page - 1) * TEACHERS_PER_PAGE;

// --- Fetch teachers and their student counts ---
$teachers = [];
$total_pages = 1;
$db_error = null;

try {
    // 1. Get total count
    $stmt_total = $pdo->query("SELECT COUNT(teacher_id) AS total FROM teachers");
    $total_teachers = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_teachers / TEACHERS_PER_PAGE);

    // 2. Fetch paginated teachers and their student counts
    $stmt_teachers = $pdo->prepare("
        SELECT 
            t.teacher_id,
            CONCAT(u.Name, ' ', u.Surname) AS teacher_name,
            COUNT(cs.student_id) AS student_count
        FROM teachers t
        JOIN users u ON t.user_id = u.user_id
        LEFT JOIN class_students cs ON t.teacher_id = cs.teacher_id
        GROUP BY t.teacher_id
        ORDER BY teacher_name ASC
        LIMIT :offset, :limit
    ");
    $stmt_teachers->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_teachers->bindValue(':limit', TEACHERS_PER_PAGE, PDO::PARAM_INT);
    $stmt_teachers->execute();
    $teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = "Error fetching teachers: " . htmlspecialchars($e->getMessage());
}

// --- Get and clear session messages ---
$delegation_success = $_SESSION['delegation_success'] ?? null;
$delegation_error   = $_SESSION['delegation_error'] ?? null;
unset($_SESSION['delegation_success'], $_SESSION['delegation_error']);

$js_version = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Delegation - Kumon Admin</title>
    
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonAdmin.css">
    <link rel="stylesheet" href="../styles/adminStudentDelegation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
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
            <li><a href="kumonAdmin.php"><i class="fa fa-home"></i> <span>Home</span></a></li>

            <li><a href="accountList.php"><i class="fa fa-list"></i> Account List</a></li>
            <li><a href="createAccount.php"><i class="fa fa-user-plus"></i> Create Account</a></li>
            <li><a href="recordPayment.php"><i class="fa fa-pen-to-square"></i> Record Payment</a></li>
            <li><a href="viewPayment.php"><i class="fa fa-list"></i> Payments List</a></li>
            <li><a href="adminStudentDelegation.php" class="active"><i class="fa fa-user-tag"></i> Student Delegation</a></li>

            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="user-name"><?= htmlspecialchars($username) ?></div>
                </div>
            </div>
        </header>

        <div class="page-content">
            <h2>Student Delegation & Monitoring</h2>

            <?php if ($delegation_success): ?>
                <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($delegation_success) ?></div>
            <?php endif; ?>

            <?php if ($delegation_error): ?>
                <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($delegation_error) ?></div>
            <?php endif; ?>

            <?php if (isset($db_error)): ?>
                <div class="alert alert-danger"><i class="fa fa-database"></i> <?= htmlspecialchars($db_error) ?></div>
            <?php endif; ?>

            <div id="delegation-dashboard">
                
                <div class="teacher-list">
                    <h3><i class="fa fa-chalkboard-user"></i> Select Teacher</h3>
                    <ul id="teachers-container">
                        <?php if (!empty($teachers)): ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <li class="teacher-item"
                                    data-teacher-id="<?= $teacher['teacher_id'] ?>"
                                    data-teacher-name="<?= htmlspecialchars($teacher['teacher_name']) ?>"
                                    data-student-count="<?= $teacher['student_count'] ?>">
                                    <strong><?= htmlspecialchars($teacher['teacher_name']) ?></strong>
                                    <span class="student-count-badge"><?= htmlspecialchars($teacher['student_count']) ?> Students</span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No teachers found.</li>
                        <?php endif; ?>
                    </ul>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?= $current_page - 1 ?>" class="page-link prev-next"><i class="fa fa-chevron-left"></i> Prev</a>
                            <?php endif; ?>

                            <?php 
                                // Simple pagination display logic to avoid too many pages
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                if ($start_page > 1) { echo '<span class="pagination-dots">...</span>'; }
                            ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?= $i ?>" class="page-link <?= ($i === $current_page) ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages) { echo '<span class="pagination-dots">...</span>'; } ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?>" class="page-link prev-next">Next <i class="fa fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="class-details-panel" id="class-details-panel">
                    <div class="initial-message">
                        <i class="fa fa-arrow-left fa-2x"></i>
                        <h3>Select a Teacher to View Class Details</h3>
                        <p>Delegated students and the “Add Student” form will appear here.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../scr/adminSidebar.js"></script>
    <script src="../scr/adminStudentDelegation.js?v=<?= $js_version ?>"></script>
</body>
</html>