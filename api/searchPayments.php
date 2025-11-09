<?php
require_once "../database.php";
require_once "../api/auth.php";

header("Content-Type: application/json");

if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Unauthorized access"]);
    exit;
}

$query = trim($_GET['query'] ?? '');
$status = $_GET['status'] ?? 'active';

if ($query === '') {
    echo json_encode(["success" => false, "error" => "Empty search query"]);
    exit;
}

try {
    // ✅ Combine first and last name for display and searching
    $stmt = $pdo->prepare("
        SELECT 
            p.payment_id,
            p.amount,
            p.payment_date,
            p.payment_method,
            p.reference_number,
            p.payment_status,
            p.receipt_path,
            s.studentCode,
            CONCAT(s.Firstname, ' ', s.Lastname) AS student_name
        FROM payments p
        JOIN students s ON p.student_id = s.student_id
        WHERE (
            s.studentCode LIKE :query
            OR s.Firstname LIKE :query
            OR s.Lastname LIKE :query
            OR CONCAT(s.Firstname, ' ', s.Lastname) LIKE :query
        )
        AND p.status = :status
        ORDER BY p.payment_date DESC
    ");

    $stmt->execute([
        ':query' => "%$query%",
        ':status' => $status
    ]);

    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "payments" => $payments]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}


