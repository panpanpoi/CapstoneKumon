<?php
require_once "../database.php";

if (!isset($_SESSION)) {
    session_start();
}

// Initialize variables
$upcoming_ptc = null;
$past_ptcs = [];
$error = null;

try {
    if (!isset($_SESSION['student_id'])) {
        throw new Exception("Student session not found.");
    }

    $student_id = $_SESSION['student_id'];

    // Fetch the next upcoming PTC (scheduled or pending)
    $stmt = $pdo->prepare("
        SELECT ptc_id, meetingDate, start_time, end_time, status, remarks
        FROM ptc_meetings
        WHERE student_id = ?
        ORDER BY meetingDate ASC
        LIMIT 1
    ");
    $stmt->execute([$student_id]);
    $upcoming_ptc = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch last 5 past PTCs
    $stmt = $pdo->prepare("
        SELECT meetingDate, start_time, end_time, remarks
        FROM ptc_meetings
        WHERE student_id = ? AND status = 'done'
        ORDER BY meetingDate DESC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $past_ptcs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

// After including this file, the following variables are available:
// $upcoming_ptc, $past_ptcs, $error
