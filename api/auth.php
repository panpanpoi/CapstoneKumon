<?php
require_once "../database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for active session
if (empty($_SESSION['user_id']) || empty($_SESSION['session_token'])) {
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

// --- Session Validation ---
if (!$user || $user['session_token'] !== $_SESSION['session_token']) {
    session_destroy();
    header("Location: ../pages/loginform.php?error=Session expired. Please log in again.");
    exit;
}

// --- Password Policy Check ---
$defaultPasswords = ['password', 'password123', 'default', '123456', 'changeme'];
$isDefault = in_array($user['password'], $defaultPasswords, true);

if ($isDefault || $user['mustChangePassword'] == 1) {
    if (basename($_SERVER['PHP_SELF']) !== 'forceChangePassword.php') {
        header("Location: ../pages/forceChangePassword.php");
        exit;
    }
}

// --- Session Data Population ---
$_SESSION['account_type'] = strtolower($user['account_type']);
$_SESSION['username'] = $user['Name'] . " " . $user['Surname'];

function getInitials($name) {
    $parts = preg_split('/\s+/', trim($name));
    return strtoupper(substr($parts[0] ?? '', 0, 1) . substr(end($parts) ?? '', 0, 1));
}
$_SESSION['initials'] = getInitials($_SESSION['username']);

// --- Role-based setup ---
switch ($_SESSION['account_type']) {
    case 'teacher':
        if (empty($_SESSION['teacher_id'])) {
            $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['teacher_id'] = $stmt->fetchColumn();
        }
        break;

    case 'student':
        if (empty($_SESSION['student_id'])) {
            $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['student_id'] = $stmt->fetchColumn();
        }
        break;

    case 'admin':
        $_SESSION['is_admin'] = true;
        break;
}
?>


