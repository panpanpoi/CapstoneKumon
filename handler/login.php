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
        // Check if user account is active
        if (strtolower($user['status']) !== 'active') {
            header("Location: ../pages/loginForm.php?error=Your account is not active");
            exit;
        }

        // Generate a new session token
        $sessionToken = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET session_token = :token WHERE user_id = :id");
        $stmt->execute([
            'token' => $sessionToken,
            'id'    => $user['user_id']
        ]);

        // Store user information in session
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['account_type'] = strtolower($user['account_type']);
        $_SESSION['username'] = $user['username'];

        // Set welcome message
        $_SESSION['flash_success'] = "Welcome back, " . ucfirst($_SESSION['account_type']) . "!";

        // Redirect based on user role
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
    }

    // Invalid username or password
    header("Location: ../pages/loginForm.php?error=Invalid username or password");
    exit;
}
