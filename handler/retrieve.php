<?php
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

try {
  $search   = isset($_GET['search']) ? trim($_GET['search']) : "";
  $archived = $_GET['archived'] ?? 0;

  // ✅ Normalize status input
  if ($archived === "1" || strtolower($archived) === "archived") {
    $status = 'archived';
  } else {
    $status = 'active';
  }

  if ($search !== "") {
    $stmt = $pdo->prepare("
      SELECT * FROM users
      WHERE status = :status AND (
        Name LIKE :q OR Surname LIKE :q OR account_type LIKE :q OR Address LIKE :q OR mobileNumber LIKE :q
      )
    ");
    $stmt->execute([
      ':status' => $status,
      ':q'      => "%$search%"
    ]);
  } else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE status = :status");
    $stmt->execute([':status' => $status]);
  }

  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
