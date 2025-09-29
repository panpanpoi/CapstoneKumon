<?php
try {
    require_once __DIR__ . '/../database.php'; // must define $pdo
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database configuration error.']);
    exit();
}

try {
    $id     = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null; // expected values: "active" or "archived"

    if (!$id || !$status) {
        http_response_code(400);
        throw new Exception("User ID and status are required.");
    }

    if (!in_array($status, ['active', 'archived'])) {
        http_response_code(400);
        throw new Exception("Invalid status value.");
    }

    $stmt = $pdo->prepare("
        UPDATE users 
        SET status = :status
        WHERE user_id = :id
    ");

    $stmt->execute([
        ':id'     => $id,
        ':status' => $status
    ]);

    echo json_encode([
        "success" => true,
        "message" => "User status updated to $status"
    ]);

} catch (Exception $e) {
    http_response_code($http_response_code ?? 500);
    echo json_encode(["error" => $e->getMessage()]);
}
