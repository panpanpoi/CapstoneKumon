<?php
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (empty($_POST['payment_id']) || empty($_FILES['receipt'])) {
        throw new Exception('Missing payment ID or receipt file.');
    }

    $payment_id = (int)$_POST['payment_id'];
    $file = $_FILES['receipt'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed.');
    }

    // ✅ Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, or PDF allowed.');
    }

    // ✅ Create upload directory if needed
    $uploadDir = __DIR__ . '/../uploads/receipts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // ✅ Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'receipt_' . $payment_id . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    $relativePath = 'uploads/receipts/' . $filename;

    // ✅ Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file.');
    }

    // ✅ Update only if payment_status = 'unverified'
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET receipt_path = :path, payment_status = 'pending'
        WHERE payment_id = :id
          AND payment_status = 'unverified'
    ");
    $stmt->execute([
        ':path' => $relativePath,
        ':id' => $payment_id
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Upload not allowed. Payment is already pending, verified, or rejected.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Receipt uploaded successfully. Status is now Pending verification.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
