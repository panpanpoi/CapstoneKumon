<?php
require_once "../database.php";
session_start();

if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$archived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;

$query = "SELECT p.payment_id, s.Name AS student_name, p.amount, p.payment_date, 
                 p.remarks, p.receipt_path, p.archived
          FROM payments p
          JOIN students s ON p.student_id = s.student_id
          WHERE p.archived = :archived
          ORDER BY p.payment_date DESC";

$stmt = $pdo->prepare($query);
$stmt->bindParam(":archived", $archived, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
