<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection error.']);
    exit();
}

try {
    // --- Get POST data from JS ---
    $id       = $_POST['user_id'] ?? null;
    $name     = $_POST['Name'] ?? null;
    $surname  = $_POST['Surname'] ?? null;
    $address  = $_POST['Address'] ?? null;
    $mobile   = $_POST['mobileNumber'] ?? null;
    $account  = $_POST['account_type'] ?? null;

    // --- Validate ---
    if (!$id) throw new Exception("User ID is required.");
    if (!$name || !$surname) throw new Exception("Name and surname are required.");
    if (!$account) throw new Exception("Account type is required.");

    // --- Update query ---
    $stmt = $pdo->prepare("
        UPDATE users 
        SET Name = :name,
            Surname = :surname,
            Address = :address,
            mobileNumber = :mobile,
            account_type = :account
        WHERE user_id = :id
    ");

    $stmt->execute([
        ':id'      => $id,
        ':name'    => $name,
        ':surname' => $surname,
        ':address' => $address,
        ':mobile'  => $mobile,
        ':account' => $account
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
