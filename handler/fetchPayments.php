<?php
header('Content-Type: application/json');
require_once "../database.php";
session_start();

// Only allow admins
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Get filter status from query string
$status = isset($_GET['status']) && in_array($_GET['status'], ['active','archived'])
          ? $_GET['status']
          : 'active';

try {
    $stmt = $pdo->prepare("
        SELECT p.payment_id, s.Firstname AS student_name, p.amount, p.payment_date, 
               p.payment_method, p.reference_number, p.remarks, p.status
        FROM payments p
        JOIN students s ON p.student_id = s.student_id
        WHERE p.status = :status
        ORDER BY p.payment_date DESC
    ");
    $stmt->bindParam(":status", $status, PDO::PARAM_STR);
    $stmt->execute();

    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($payments);

} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
