<?php
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        throw new Exception('Invalid user ID.');
    }

    // Fetch base user info
    $sql = "SELECT user_id, Name, Surname, Address, mobileNumber, account_type, status
            FROM users
            WHERE user_id = :id
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found.');
    }

    // Fetch corresponding code (studentCode or teacherCode)
    switch ($user['account_type']) {
        case 'student':
            $codeQuery = "SELECT studentCode AS code FROM students WHERE user_id = :id LIMIT 1";
            break;
        case 'teacher':
            $codeQuery = "SELECT teacherCode AS code FROM teachers WHERE user_id = :id LIMIT 1";
            break;
        default:
            $codeQuery = null;
            break;
    }

    if ($codeQuery) {
        $stmt2 = $pdo->prepare($codeQuery);
        $stmt2->execute([':id' => $id]);
        $result = $stmt2->fetch(PDO::FETCH_ASSOC);
        $user['code'] = $result['code'] ?? null;
    } else {
        $user['code'] = null;
    }

    // Include change_password field for frontend toggle
    $user['change_password'] = false; // default (UI will toggle this)

    echo json_encode([
        'success' => true,
        'data' => $user
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
