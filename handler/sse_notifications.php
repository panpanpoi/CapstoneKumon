<?php
require_once "../database.php";
session_start();

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    echo "data: " . json_encode(['error' => 'Not authenticated']) . "\n\n";
    exit;
}

$student_id = $_SESSION['student_id'];
$last_check = time();

// Function to send SSE data
function sendSSEData($data) {
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Send initial connection message
sendSSEData(['type' => 'connected', 'message' => 'Connected to notification stream']);

// Keep connection alive and check for new notifications
while (true) {
    try {
        // Check for new notifications for this student
        $stmt = $pdo->prepare("
            SELECT notification_id, type, title, message, created_at, is_read
            FROM notifications 
            WHERE student_id = ? AND created_at > FROM_UNIXTIME(?)
            ORDER BY created_at DESC
        ");
        $stmt->execute([$student_id, $last_check]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                sendSSEData([
                    'type' => 'notification',
                    'notification' => $notification
                ]);
            }
        }
        
        // Update last check time
        $last_check = time();
        
        // Check for payment status changes
        $stmt = $pdo->prepare("
            SELECT payment_id, status, amount, payment_date
            FROM payments 
            WHERE student_id = ? AND status = 'active' AND updated_at > FROM_UNIXTIME(?)
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$student_id, $last_check - 5]); // Check last 5 seconds
        $payment_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($payment_updates)) {
            foreach ($payment_updates as $payment) {
                sendSSEData([
                    'type' => 'payment_update',
                    'payment' => $payment,
                    'message' => 'Your payment has been verified!'
                ]);
            }
        }
        
        // Send heartbeat every 30 seconds
        if (time() % 30 == 0) {
            sendSSEData(['type' => 'heartbeat', 'timestamp' => time()]);
        }
        
    } catch (Exception $e) {
        sendSSEData(['type' => 'error', 'message' => 'Connection error']);
        break;
    }
    
    // Sleep for 2 seconds before next check
    sleep(2);
}
?>

