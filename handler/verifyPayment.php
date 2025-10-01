<?php
require_once "../database.php";
session_start();

// Only allow admins
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Ensure POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// Get data
$payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;

if (!$payment_id) {
    echo json_encode(["error" => "Payment ID is required"]);
    exit;
}

$receipt_path = null;

// Handle optional receipt upload
if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/receipts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmp = $_FILES['receipt']['tmp_name'];
    $fileName = basename($_FILES['receipt']['name']);
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = 'receipt_' . $payment_id . '_' . time() . '.' . $fileExt;
    $destination = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmp, $destination)) {
        $receipt_path = 'uploads/receipts/' . $newFileName;
    } else {
        echo json_encode(["error" => "Failed to upload receipt."]);
        exit;
    }
}

try {
    // Build query
    $fields = ["status = 'active'"];
    $params = [":payment_id" => $payment_id];

    // Update remarks if provided
    if ($remarks !== null) {
        $fields[] = "remarks = :remarks";
        $params[":remarks"] = $remarks;
    }

    // Update receipt_path if uploaded
    if ($receipt_path !== null) {
        $fields[] = "receipt_path = :receipt_path";
        $params[":receipt_path"] = $receipt_path;
    }

    // Update status to active (verified)
    $fields[] = "status = 'active'";

    $sql = "UPDATE payments SET " . implode(", ", $fields) . " WHERE payment_id = :payment_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount()) {
        echo json_encode(["success" => true, "message" => "Payment verified successfully."]);
    } else {
        echo json_encode(["error" => "Payment not found or already verified."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to verify payment: " . $e->getMessage()]);
}
