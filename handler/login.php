<?php
session_start();
require_once "../database.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        header("Location: ../pages/loginForm.php?error=Please fill in all fields");
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // ✅ Base user session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['account_type'] = strtolower($user['account_type']); // normalize
        $_SESSION['username'] = $user['username'];

        // Flash welcome message
        $_SESSION['flash_success'] = "Welcome back, " . ucfirst($_SESSION['account_type']) . "!";

        // ✅ Store role-specific IDs
        switch ($_SESSION['account_type']) {
            case 'teacher':
                $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['teacher_id'] = $teacher['teacher_id'] ?? null;
                header("Location: ../pages/kumonTeacher.php");
                break;

            case 'student':
                $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['student_id'] = $student['student_id'] ?? null;
                header("Location: ../pages/kumonStudent.php");
                break;

            case 'admin':
                $_SESSION['is_admin'] = true;
                header("Location: ../pages/kumonAdmin.php");
                break;

            default:
                header("Location: ../pages/loginForm.php?error=Invalid role");
                break;
        }
        exit;

    } else {
        header("Location: ../pages/loginForm.php?error=Invalid username or password");
        exit;
    }
}
?>
