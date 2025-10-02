<?php
header('Content-Type: application/json');
require_once "../database.php";
session_start();

// 🔒 Only allow admins
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// ✅ Validate payment_id
$payment_id = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
if (!$payment_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Payment ID is required"]);
    exit;
}

// ✅ Get new status if provided
$new_status = $_POST['status'] ?? null;

try {
    if ($new_status) {
        // Sanitize/validate provided status
        if (!in_array($new_status, ['active', 'archived'], true)) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Invalid status"]);
            exit;
        }
    } else {
        // Fetch current status and toggle
        $stmt = $pdo->prepare("SELECT status FROM payments WHERE payment_id = :payment_id");
        $stmt->execute([":payment_id" => $payment_id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            http_response_code(404);
            echo json_encode(["success" => false, "error" => "Payment not found"]);
            exit;
        }

        $new_status = $current['status'] === 'active' ? 'archived' : 'active';
    }

    // ✅ Update payment status
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = :status 
        WHERE payment_id = :payment_id
    ");
    $success = $stmt->execute([
        ":status" => $new_status,
        ":payment_id" => $payment_id
    ]);

    if ($success) {
        echo json_encode([
            "success" => true,
            "message" => "Payment status updated to '{$new_status}'",
            "new_status" => $new_status
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to update payment status"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
