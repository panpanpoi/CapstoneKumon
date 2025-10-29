<?php
require_once "../database.php";

if (!isset($_SESSION)) {
    session_start();
}

// Check for active session
if (!isset($_SESSION['user_id'], $_SESSION['session_token'])) {
    header("Location: ../pages/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT user_id, Name, Surname, account_type, session_token, password, mustChangePassword
    FROM users 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

if ($user['session_token'] !== $_SESSION['session_token']) {
    session_destroy();
    header("Location: ../login.php?error=You have been logged out because another session started.");
    exit;
}

// ✅ Force password change if default or flagged
$defaultPasswords = ['password', 'password123', 'default', '123456', 'changeme'];
$isDefault = in_array($user['password'], $defaultPasswords);

if ($isDefault || $user['mustChangePassword'] == 1) {
    if (basename($_SERVER['PHP_SELF']) !== 'forceChangePassword.php') {
        header("Location: ../pages/forceChangePassword.php");
        exit;
    }
}

$_SESSION['account_type'] = strtolower($user['account_type']);
$_SESSION['username'] = $user['Name'] . " " . $user['Surname'];

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
?>
