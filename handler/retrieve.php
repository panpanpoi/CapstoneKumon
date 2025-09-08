<?php
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

try {
  $search = isset($_GET['search']) ? trim($_GET['search']) : "";

  if ($search !== "") {
    $stmt = $pdo->prepare("
      SELECT * FROM users
      WHERE status = 'active' AND (
        Name LIKE :q OR Surname LIKE :q OR account_type LIKE :q OR Address LIKE :q OR mobileNumber LIKE :q
      )
    ");
    $stmt->execute([':q' => "%$search%"]);
  } else {
    $stmt = $pdo->query("SELECT * FROM users WHERE status = 'active'");
  }

  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
