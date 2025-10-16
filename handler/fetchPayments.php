<?php
require_once "../database.php";
session_start();

header('Content-Type: application/json');

// 🔒 Admin-only access
if (empty($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "Unauthorized access."
    ]);
    exit;
}

// Get filter: 'active' or 'archived'
$status = $_GET['status'] ?? 'active';
if (!in_array($status, ['active', 'archived'])) {
    $status = 'active';
}

try {
    // Fetch payments with student details
    $stmt = $pdo->prepare("
        SELECT 
            p.payment_id,
            p.student_id,
            p.amount,
            p.payment_date,
            p.payment_method,
            p.reference_number,
            p.remarks,
            p.status,
            p.payment_status,
            p.receipt_path,
            s.studentCode,
            s.firstname,
            s.lastname
        FROM payments p
        LEFT JOIN students s ON p.student_id = s.student_id
        WHERE p.status = :status
        ORDER BY p.payment_date DESC, s.lastname ASC, s.firstname ASC
    ");
    $stmt->execute([':status' => $status]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🧠 Transform data for frontend & auto-verify cash payments
    $result = array_map(function ($row) use ($pdo) {
        $receiptPath = $row['receipt_path'] ?? null;
        $validReceipt = false;

        // Validate receipt image
        if (!empty($receiptPath)) {
            $ext = strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION));
            $validReceipt = in_array($ext, ['jpg', 'jpeg', 'png']);
            if (!$validReceipt) {
                $receiptPath = 'invalid';
            }
        }

        // 🔹 Auto-verify cash payments if not already verified
        $paymentStatus = strtolower($row['payment_status'] ?? 'unverified');
        if (strtolower($row['payment_method']) === 'cash' && $paymentStatus !== 'verified') {
            $updateStmt = $pdo->prepare("UPDATE payments SET payment_status = 'verified' WHERE payment_id = :payment_id");
            $updateStmt->execute([':payment_id' => $row['payment_id']]);
            $paymentStatus = 'verified';
        }

        return [
            "payment_id"       => (int)$row['payment_id'],
            "student_id"       => $row['student_id'] ? (int)$row['student_id'] : null,
            "studentCode"      => $row['studentCode'] ?? "-",
            "student_name"     => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
            "amount"           => number_format((float)$row['amount'], 2),
            "payment_date"     => !empty($row['payment_date']) ? date('d/m/Y', strtotime($row['payment_date'])) : "",
            "payment_method"   => $row['payment_method'] ?: "-",
            "reference_number" => $row['reference_number'] ?: "-",
            "remarks"          => $row['remarks'] ?: "",
            "status"           => $row['status'] ?? "active",
            "payment_status"   => $paymentStatus, // ✅ lowercase
            "receipt_path"     => $receiptPath,
            "valid_receipt"    => $validReceipt
        ];

    }, $payments);

    echo json_encode([
        "success" => true,
        "count" => count($result),
        "payments" => $result
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database query failed.",
        "details" => $e->getMessage()
    ]);
}
