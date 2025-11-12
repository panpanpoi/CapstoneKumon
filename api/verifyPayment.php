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

    // --- ⬇️ ADMIN FILE UPLOAD LOGIC ⬇️ ---

    $newReceiptPath = null; // Will store the new path if uploaded

    // Check if admin is uploading a new file (from the 'admin_receipt' input)
    if (isset($_FILES['admin_receipt']) && $_FILES['admin_receipt']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['admin_receipt'];

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        // Use mime_content_type for better reliability
        $fileType = mime_content_type($file['tmp_name']); 
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, or PDF allowed.');
        }

        // Create upload directory
        $uploadDir = __DIR__ . '/../uploads/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'receipt_' . $payment_id . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file.');
        }
        
        $newReceiptPath = 'uploads/receipts/' . $filename; // Set the new path to be saved
    }
    
    // --- ⬆️ END FILE UPLOAD LOGIC ⬆️ ---


    // ✅ MODIFIED: Ensure receipt exists before approving
    // A receipt "has" to exist if it *already* existed ($payment['receipt_path'])
    // OR if the admin *just* uploaded one ($newReceiptPath)
    $hasReceipt = !empty($payment['receipt_path']) || !empty($newReceiptPath);
    
    if ($action === 'approve' && !$hasReceipt) {
        echo json_encode(['success' => false, 'error' => 'Cannot approve payment without a receipt. Please upload one.']);
        exit;
    }

    // ✅ Determine new status and message
    $newStatus = ($action === 'reject') ? 'rejected' : 'verified';
    $successMsg = ($action === 'reject') 
        ? 'Payment has been rejected.' 
        : 'Payment verified successfully.';

    // --- ⬇️ MODIFIED UPDATE QUERY ⬇️ ---

    // Base query
    $sql = "
        UPDATE payments
        SET
            payment_status = :status,
            reference_number = :reference_number,
            remarks = :remarks,
            verified_by = :verified_by,
            verified_at = NOW()
    ";
    
    // Parameters
    $params = [
        ':status' => $newStatus,
        ':reference_number' => $reference_number,
        ':remarks' => $remarks,
        ':verified_by' => $_SESSION['username'],
        ':id' => $payment_id
    ];

    // Conditionally add receipt_path to the query ONLY if a new one was uploaded
    if ($newReceiptPath) {
        $sql .= ", receipt_path = :path";
        $params[':path'] = $newReceiptPath;
    }

    // Add the WHERE clause
    $sql .= " WHERE payment_id = :id";
    
    // Prepare and execute
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // --- ⬆️ END MODIFIED UPDATE QUERY ⬆️ ---

    echo json_encode([
        'success' => true,
        'message' => $successMsg,
        'payment_status' => $newStatus
    ]);

} catch (Exception $e) { // Catch generic Exception for file upload errors
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>