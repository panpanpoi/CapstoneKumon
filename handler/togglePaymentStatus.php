<?php
header('Content-Type: application/json');
require_once "../database.php";
session_start();

// Only allow admins
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Ensure payment_id is provided
$payment_id = $_POST['payment_id'] ?? null;
if (!$payment_id) {
    echo json_encode(["error" => "Payment ID is required"]);
    exit;
}

// Optional: allow explicitly passing new status
$new_status = $_POST['status'] ?? null;

try {
    // If no explicit status, toggle based on current value
    if (!$new_status) {
        $stmt = $pdo->prepare("SELECT status FROM payments WHERE payment_id = :payment_id");
        $stmt->bindParam(":payment_id", $payment_id, PDO::PARAM_INT);
        $stmt->execute();
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) throw new Exception("Payment not found");
        $new_status = $current['status'] === 'active' ? 'archived' : 'active';
    }

    // Update the payment status
    $stmt = $pdo->prepare("UPDATE payments SET status = :status WHERE payment_id = :payment_id");
    $stmt->bindParam(":status", $new_status, PDO::PARAM_STR);
    $stmt->bindParam(":payment_id", $payment_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Payment status updated to '{$new_status}'",
            "new_status" => $new_status
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update payment status"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
