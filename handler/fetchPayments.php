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

// 🧩 Get filters
$status = $_GET['status'] ?? 'active';
if (!in_array($status, ['active', 'archived'])) {
    $status = 'active';
}

// 🧩 Pagination setup
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, (int)$_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

try {
    // 🧠 Count total rows (for pagination)
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE status = :status");
    $countStmt->execute([':status' => $status]);
    $totalPayments = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalPayments / $limit);

    // 🧾 Fetch payments with student details
    $stmt = $pdo->prepare("
        SELECT 
            p.payment_id,
            p.student_id,
            p.amount,
            p.payment_date,
            p.due_date,
            p.payment_method,
            p.reference_number,
            p.tf_month_covered,
            p.remarks,
            p.status,
            p.payment_status,
            p.receipt_path,
            s.studentCode,
            s.Firstname,
            s.Lastname
        FROM payments p
        LEFT JOIN students s ON p.student_id = s.student_id
        WHERE p.status = :status
        ORDER BY p.payment_date DESC, s.Lastname ASC, s.Firstname ASC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🧩 Transform data for frontend
    $result = array_map(function ($row) use ($pdo) {
        $receiptPath = $row['receipt_path'] ?? null;
        $validReceipt = false;

        // 🖼 Validate receipt image
        if (!empty($receiptPath)) {
            $ext = strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION));
            $validReceipt = in_array($ext, ['jpg', 'jpeg', 'png']);
            if (!$validReceipt) {
                $receiptPath = 'invalid';
            }
        }

        // 🔹 Auto-verify cash payments
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
            "student_name"     => trim(($row['Firstname'] ?? '') . ' ' . ($row['Lastname'] ?? '')),
            "amount"           => (float)$row['amount'], // ✅ raw numeric value, no peso sign
            "payment_date"     => !empty($row['payment_date']) ? date('Y-m-d', strtotime($row['payment_date'])) : null,
            "due_date"         => !empty($row['due_date']) ? date('Y-m-d', strtotime($row['due_date'])) : null,
            "payment_method"   => $row['payment_method'] ?: "-",
            "reference_number" => $row['reference_number'] ?: "-",
            "tf_month_covered" => $row['tf_month_covered'] ?: "-", // ✅ Correctly included
            "remarks"          => $row['remarks'] ?: "",
            "status"           => $row['status'] ?? "active",
            "payment_status"   => $paymentStatus,
            "receipt_path"     => $receiptPath,
            "valid_receipt"    => $validReceipt
        ];
    }, $payments);

    // ✅ Output response
    echo json_encode([
        "success" => true,
        "page" => $page,
        "total_pages" => $totalPages,
        "count" => count($result),
        "total_count" => $totalPayments,
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
