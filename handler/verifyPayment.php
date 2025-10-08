<?php
require_once "../database.php";
require_once "auth.php";

header('Content-Type: application/json');

// Only admins
if ($_SESSION['account_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Get POST data
$payment_id = $_POST['payment_id'] ?? null;
$remarks    = trim($_POST['remarks'] ?? '');

if (!$payment_id) {
    echo json_encode(['success' => false, 'error' => 'Payment ID is required.']);
    exit;
}

try {
    // Fetch existing payment
    $stmt = $pdo->prepare("SELECT payment_status, receipt_path FROM payments WHERE payment_id = :id");
    $stmt->execute([':id' => $payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Payment not found.']);
        exit;
    }

    $receipt_path = $payment['receipt_path'];

    // Handle receipt upload if present
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($_FILES['receipt']['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG/PNG allowed.']);
            exit;
        }

        $uploadsDir = "../uploads/";
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

        $filename = uniqid() . "-" . basename($_FILES['receipt']['name']);
        $targetFile = $uploadsDir . $filename;

        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $targetFile)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload file.']);
            exit;
        }

        $receipt_path = "uploads/" . $filename;
    }

    // Update payment: mark as verified
    $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'verified', remarks = :remarks, receipt_path = :receipt WHERE payment_id = :id");
    $stmt->execute([
        ':remarks' => $remarks,
        ':receipt' => $receipt_path,
        ':id' => $payment_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully.',
        'payment_status' => 'verified',
        'receipt_path' => $receipt_path
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: '.$e->getMessage()]);
}
