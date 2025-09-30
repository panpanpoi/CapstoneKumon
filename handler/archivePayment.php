<?php
require_once "../database.php";
session_start();

if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $paymentId = $_POST['payment_id'] ?? null;

    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing payment_id"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE payments SET archived = 1 WHERE payment_id = :id");
    $stmt->bindParam(":id", $paymentId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Payment archived successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to archive payment"]);
    }
}
