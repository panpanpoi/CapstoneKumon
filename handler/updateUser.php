<?php
try {
    require_once __DIR__ . '/../database.php'; // must define $pdo
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database configuration error.']);
    exit();
}

try {
    // ✅ Get POST data
    $id       = $_POST['id'] ?? null;
    $name     = $_POST['name'] ?? null;
    $surname  = $_POST['surname'] ?? null;
    $address  = $_POST['address'] ?? null;
    $mobile   = $_POST['mobile'] ?? null;
    $account  = $_POST['account_type'] ?? null;

    if (!$id) {
        throw new Exception("User ID is required.");
    }

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

    echo json_encode(["success" => true, "message" => "User updated successfully."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
