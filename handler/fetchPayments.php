<?php
header('Content-Type: application/json');
require_once "../database.php";
session_start();

// 🔒 Only allow admins
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// ✅ Get filter status from query string
$status = (isset($_GET['status']) && in_array($_GET['status'], ['active', 'archived']))
          ? $_GET['status']
          : 'active';

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.payment_id,
            p.amount,
            p.payment_date,
            p.payment_method,
            p.reference_number,
            p.remarks,
            p.status,
            p.receipt_path,
            s.studentCode,
            s.firstname AS first_name,
            s.lastname AS last_name
        FROM payments p
        JOIN students s ON p.student_id = s.student_id
        WHERE p.status = :status
        ORDER BY p.payment_date DESC, s.lastname ASC, s.firstname ASC
    ");
    $stmt->bindParam(":status", $status, PDO::PARAM_STR);
    $stmt->execute();

    $payments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $receiptPath = $row['receipt_path'] ?? null;
        $isValidImage = false;

        if ($receiptPath) {
            $ext = strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $isValidImage = true;
            } else {
                // ❌ Invalid file type
                $receiptPath = "invalid"; 
            }
        }

        $payments[] = [
            "payment_id"       => (int)$row['payment_id'],
            "studentCode"      => $row['studentCode'],
            "student_name"     => trim($row['first_name'] . " " . $row['last_name']),
            "amount"           => $row['amount'],
            "payment_date"     => $row['payment_date'],
            "payment_method"   => $row['payment_method'],
            "reference_number" => $row['reference_number'],
            "remarks"          => $row['remarks'],
            "status"           => $row['status'],
            "receipt_path"     => $receiptPath,
            "valid_receipt"    => $isValidImage
        ];
    }

    echo json_encode($payments);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
