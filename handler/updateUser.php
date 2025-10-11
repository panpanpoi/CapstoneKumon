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
    $changePassword = isset($_POST['change_password']);
    $newPassword = $_POST['new_password'] ?? null;

    // --- Validate ---
    if (!$id) throw new Exception("User ID is required.");
    if (!$name || !$surname) throw new Exception("Name and surname are required.");
    if (!$account) throw new Exception("Account type is required.");

    // --- Password validation if changing password ---
    if ($changePassword) {
        if (!$newPassword) throw new Exception("New password is required when changing password.");
        if (strlen($newPassword) < 6) throw new Exception("Password must be at least 6 characters long.");
    }

    // --- Build update query ---
    $updateFields = [
        'Name = :name',
        'Surname = :surname', 
        'Address = :address',
        'mobileNumber = :mobile',
        'account_type = :account'
    ];
    
    $params = [
        ':id'      => $id,
        ':name'    => $name,
        ':surname' => $surname,
        ':address' => $address,
        ':mobile'  => $mobile,
        ':account' => $account
    ];

    // --- Add password to update if changing ---
    if ($changePassword) {
        $updateFields[] = 'password = :password';
        $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $message = 'User updated successfully.';
    if ($changePassword) {
        $message .= ' Password has been changed.';
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
