<?php
// api/approveBooking.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

// 1. Check if teacher is logged in
if (($_SESSION['account_type'] ?? '') !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// 2. Get schedule_id from the JSON request body
$data = json_decode(file_get_contents('php://input'), true);
$schedule_id = $data['schedule_id'] ?? null;

if (!$schedule_id) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID not provided.']);
    exit;
}

try {
    // 3. Update the booking status to 'approved'
    // We update based on schedule_id because one schedule has one active booking
    $stmt = $pdo->prepare("
        UPDATE ptc_bookings 
        SET status = 'approved' 
        WHERE schedule_id = ? AND status = 'booked'
    ");
    $stmt->execute([$schedule_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking approved successfully.']);
    } else {
        // Either it doesn't exist, or it's already approved/done
        echo json_encode(['success' => false, 'message' => 'Could not approve. Booking might already be processed.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>