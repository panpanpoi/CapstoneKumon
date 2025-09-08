<?php
require_once __DIR__ . '/../database.php'; // defines $pdo

header('Content-Type: application/json');

try {
  $id     = $_POST['id']     ?? null;
  $status = $_POST['status'] ?? null; // 'active' | 'archived'

  if (!$id || !$status) {
    throw new Exception('User ID and status are required.');
  }

  $stmt = $pdo->prepare("UPDATE users SET status = :status WHERE user_id = :id");
  $stmt->execute([':status' => $status, ':id' => $id]);

  echo json_encode(['success' => true, 'message' => "User status set to {$status}"]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}
