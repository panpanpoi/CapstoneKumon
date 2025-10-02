<?php
require_once "../database.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if ($token && $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET fcm_token = ? WHERE user_id = ?");
    $stmt->execute([$token, $user_id]);
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
