<?php
// api/cancelBooking.php

// 1. Turn off error display to prevent HTML from breaking JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../database.php';

    // Check DB connection
    if (!isset($pdo)) {
        throw new Exception("Database connection failed.");
    }

    // Check login
    if (!isset($_SESSION['student_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
        exit;
    }
    $student_id = $_SESSION['student_id'];

    // Get Data
    $data = json_decode(file_get_contents('php://input'), true);
    $booking_id = $data['booking_id'] ?? null;

    if (!$booking_id) {
        throw new Exception('Booking ID not provided.');
    }

    $pdo->beginTransaction();

    // 3. Find the booking
    // IMPORTANT: Using table 'ptc_bookings' (plural) to match your other files. 
    // If your database table is singular ('ptc_booking'), remove the 's'.
    $stmt = $pdo->prepare("
        SELECT schedule_id 
        FROM ptc_bookings 
        WHERE booking_id = ? AND student_id = ? AND status = 'booked'
    ");
    $stmt->execute([$booking_id, $student_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        // Check if it exists but is already cancelled or done?
        // For now, just assume it's invalid.
        throw new Exception('Booking not found or you do not have permission to cancel it.');
    }
    
    $schedule_id = $booking['schedule_id'];

    // 4. Delete the booking
    // Note: Using 'ptc_bookings'
    $stmt = $pdo->prepare("DELETE FROM ptc_bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);

    // 5. Re-open the schedule
    $stmt = $pdo->prepare("UPDATE ptc_schedules SET status = 'open' WHERE schedule_id = ?");
    $stmt->execute([$schedule_id]);

    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Booking successfully deleted.']);

} catch (Throwable $e) {
    // 'Throwable' catches both Exceptions AND Fatal Errors (PHP 7+)
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Return a clean JSON error
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>