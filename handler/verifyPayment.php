<?php
require_once "../database.php";
session_start();

// ✅ Allow only admins
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentId = $_POST['payment_id'] ?? null;
    $remarks   = $_POST['remarks'] ?? '';

    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing payment_id"]);
        exit;
    }

    // ✅ Handle receipt upload
    $receiptPath = null;
    if (!empty($_FILES['receipt']['name'])) {
        $uploadDir = __DIR__ . "/../uploads/receipts/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['receipt']['name']);
        $targetFile = $uploadDir . $fileName;

        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetFile)) {
                $receiptPath = "uploads/receipts/" . $fileName; // relative path for DB
            }
        }
    }

    // ✅ Update payment record
    $query = "UPDATE payments 
              SET remarks = :remarks";

    if ($receiptPath) {
        $query .= ", receipt_path = :receipt_path";
    }

    $query .= " WHERE payment_id = :id";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":remarks", $remarks);
    $stmt->bindParam(":id", $paymentId, PDO::PARAM_INT);

    if ($receiptPath) {
        $stmt->bindParam(":receipt_path", $receiptPath);
    }

    $success = $stmt->execute();

    if ($success) {
        echo json_encode(["success" => true, "message" => "Payment verified successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to verify payment"]);
    }
}
