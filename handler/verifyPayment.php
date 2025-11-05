<?php
require_once "../database.php";
require_once "auth.php";

header('Content-Type: application/json');

// ✅ Only admins can verify or reject
if ($_SESSION['account_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

// ✅ Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// ✅ Required fields
$payment_id = $_POST['payment_id'] ?? null;
$reference_number = trim($_POST['reference_number'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');
$action = $_POST['action'] ?? 'approve'; // 'approve' or 'reject'

if (!$payment_id) {
    echo json_encode(['success' => false, 'error' => 'Missing payment ID.']);
    exit;
}

try {
    // ✅ Fetch payment record
    $stmt = $pdo->prepare("SELECT payment_status, receipt_path FROM payments WHERE payment_id = :id");
    $stmt->execute([':id' => $payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Payment not found.']);
        exit;
    }

    // ✅ Ensure only pending/unverified can be acted on
    if (!in_array($payment['payment_status'], ['pending', 'unverified'])) {
        echo json_encode(['success' => false, 'error' => 'Only pending or unverified payments can be verified.']);
        exit;
    }

    // ✅ Ensure receipt exists before approving
    if ($action === 'approve' && empty($payment['receipt_path'])) {
        echo json_encode(['success' => false, 'error' => 'Cannot approve payment without a receipt.']);
        exit;
    }

    // ✅ Determine new status and message
    // ✅ Determine new status and message
        $newStatus = ($action === 'reject') ? 'rejected' : 'verified';
        $successMsg = ($action === 'reject') 
            ? 'Payment has been rejected.' 
            : 'Payment verified successfully.';

    // ✅ Update record
    $stmt = $pdo->prepare("
    UPDATE payments
    SET
        payment_status = :status,
        reference_number = :reference_number,
        remarks = :remarks,
        verified_by = :verified_by,
        verified_at = NOW()
    WHERE payment_id = :id
");
$stmt->execute([
    ':status' => $newStatus,
    ':reference_number' => $reference_number,
    ':remarks' => $remarks,
    ':verified_by' => $_SESSION['username'], // ✅ stores admin name now
    ':id' => $payment_id
]);

    echo json_encode([
        'success' => true,
        'message' => $successMsg,
        'payment_status' => $newStatus
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
