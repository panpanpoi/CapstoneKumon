<?php
require_once "../database.php";
require_once "auth.php";

header("Content-Type: application/json");

// Only admins
if ($_SESSION['account_type'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Ensure JSON input
$data = json_decode(file_get_contents("php://input"), true);

$payment_id = $data['payment_id'] ?? null;
$status     = strtolower($data['status'] ?? '');
$remarks    = trim($data['remarks'] ?? '');

if (!$payment_id || !$status) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

// Allowed statuses
$allowedStatuses = ['active', 'archived', 'verified'];

if (!in_array($status, $allowedStatuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status"]);
    exit;
}

try {
    if ($status === 'archived' || $status === 'active') {
        $stmt = $pdo->prepare("UPDATE payments SET status = :status WHERE payment_id = :id");
        $stmt->execute([":status" => $status, ":id" => $payment_id]);
    } elseif ($status === 'verified') {
        $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'verified', remarks = :remarks WHERE payment_id = :id");
        $stmt->execute([":remarks" => $remarks, ":id" => $payment_id]);
    }

    echo json_encode([
        "success" => true,
        "message" => "Payment updated successfully."
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
