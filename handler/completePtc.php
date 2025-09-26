<?php
require_once "../database.php";
session_start();

if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ptc_id = $_POST['ptc_id'] ?? null;
    $remarks = $_POST['remarks'] ?? null;

    if (!$ptc_id) {
        header("Location: ../pages/kumonTeacher.php?error=Missing PTC ID");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE ptc_meetings 
        SET status = 'done', remarks = ? 
        WHERE ptc_id = ?
    ");
    $stmt->execute([$remarks, $ptc_id]);

    header("Location: ../pages/kumonTeacher.php?success=Meeting completed");
    exit;
}
?>
