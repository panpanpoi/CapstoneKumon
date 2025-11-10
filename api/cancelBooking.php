<?php
// api/cancelBooking.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

// 1. Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}
$student_id = $_SESSION['student_id'];

// 2. Get booking_id from the JSON request body
$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'error' => 'Booking ID not provided.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 3. Find the booking to verify ownership and get schedule_id
    // This is a security check: students can only cancel THEIR OWN bookings.
    $stmt = $pdo->prepare("
        SELECT schedule_id 
        FROM ptc_bookings 
        WHERE booking_id = ? AND student_id = ? AND status = 'booked'
    ");
    $stmt->execute([$booking_id, $student_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found, already cancelled, or does not belong to you.');
    }
    
    $schedule_id = $booking['schedule_id'];

    // 4. Delete the booking
    $stmt = $pdo->prepare("DELETE FROM ptc_bookings WHERE booking_id = ?");
    $stmt->execute([$booking_id]);

    // 5. Re-open the schedule for other students
    $stmt = $pdo->prepare("UPDATE ptc_schedules SET status = 'open' WHERE schedule_id = ?");
    $stmt->execute([$schedule_id]);

    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Booking successfully deleted.']);

} catch (Exception $e) {
    $pdo->rollBack();
    // Handle any database errors
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>