<?php
// handler/auth.php
require_once "../database.php";

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch base user info
$stmt = $pdo->prepare("SELECT user_id, Name, Surname, account_type FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// ✅ Normalize account_type
$_SESSION['account_type'] = strtolower($user['account_type']);

// ✅ Store common display info
$_SESSION['username'] = $user['Name'] . " " . $user['Surname'];

// ✅ Generate initials for avatar
function getInitials($name) {
    $parts = explode(" ", trim($name));
    $initials = "";
    foreach ($parts as $p) {
        if ($p !== "") {
            $initials .= strtoupper(substr($p, 0, 1));
        }
    }
    return $initials;
}
$_SESSION['initials'] = getInitials($_SESSION['username']);

// ✅ Ensure role-specific IDs are set
switch ($_SESSION['account_type']) {
    case 'teacher':
        if (empty($_SESSION['teacher_id'])) {
            $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['teacher_id'] = $teacher['teacher_id'] ?? null;
        }
        break;

    case 'student':
        if (empty($_SESSION['student_id'])) {
            $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['student_id'] = $student['student_id'] ?? null;
        }
        break;

    case 'admin':
        $_SESSION['is_admin'] = true;
        break;
}
