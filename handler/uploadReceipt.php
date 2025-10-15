<?php
header("Content-Type: application/json");
require_once "../database.php";

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 Ensure student is logged in
if (empty($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized access."]);
    exit;
}

$student_id = (int)$_SESSION['student_id'];

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Invalid request method."]);
    exit;
}

// Validate inputs
$payment_id = $_POST['payment_id'] ?? null;
$receipt    = $_FILES['receipt'] ?? null;

if (!$payment_id || !$receipt) {
    echo json_encode(["success" => false, "error" => "Missing payment ID or receipt file."]);
    exit;
}

// File validation
$uploadDir = "../uploads/receipts/";
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
    echo json_encode(["success" => false, "error" => "Failed to create upload directory."]);
    exit;
}

$fileTmp  = $receipt['tmp_name'];
$fileName = basename($receipt['name']);
$fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed  = ["jpg", "jpeg", "png", "pdf"];

if (!in_array($fileExt, $allowed)) {
    echo json_encode(["success" => false, "error" => "Invalid file type. Only JPG, PNG, and PDF are allowed."]);
    exit;
}

// Generate safe unique filename
$newFileName = sprintf("receipt_%d_%d.%s", $student_id, time(), $fileExt);
$destPath = $uploadDir . $newFileName;

// Move uploaded file
if (!move_uploaded_file($fileTmp, $destPath)) {
    echo json_encode(["success" => false, "error" => "File upload failed. Please try again."]);
    exit;
}

// Save to database and set payment status to pending review
try {
    $relativePath = "uploads/receipts/" . $newFileName;

    $stmt = $pdo->prepare("
        UPDATE payments
        SET receipt_path = :path, payment_status = 'pending'
        WHERE payment_id = :pid AND student_id = :sid
    ");
    $stmt->execute([
        ':path' => $relativePath,
        ':pid'  => $payment_id,
        ':sid'  => $student_id
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Receipt uploaded successfully. Awaiting admin verification.",
        "path"    => $relativePath
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
