<?php
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

try {
    // --- Get user ID from query string ---
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) throw new Exception('Invalid user ID.');

    // --- Prepare and execute query ---
    $sql = "SELECT user_id, Name, Surname, Address, mobileNumber, account_type, status
            FROM users
            WHERE user_id = :id
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    // --- Fetch user ---
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception('User not found.');

    // --- Return JSON ---
    echo json_encode([
        'success' => true,
        'data'    => $user
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
