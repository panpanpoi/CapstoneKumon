<?php
session_start();
require_once "../database.php";

if (isset($_SESSION['user_id'])) {
    // Clear session token from DB
    $stmt = $pdo->prepare("UPDATE users SET session_token = NULL WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

session_unset();   // remove all session variables
session_destroy(); // destroy the session

header("Location: loginform.php?message=Logged out successfully");
exit;
?>
