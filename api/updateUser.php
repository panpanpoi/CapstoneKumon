<?php
header('Content-Type: application/json');

try {
    // ✅ Correct path to your database connection
    require_once __DIR__ . '/../database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection error.']);
    exit();
}

try {
    // === Get POST data ===
    $id        = $_POST['user_id'] ?? null;
    $name      = $_POST['Name'] ?? null;
    $surname   = $_POST['Surname'] ?? null;
    $address   = $_POST['Address'] ?? null;
    $mobile    = $_POST['mobileNumber'] ?? null;
    $account   = $_POST['account_type'] ?? null;
    $changePwd = isset($_POST['change_password']);

    // === Validation ===
    if (!$id) throw new Exception("User ID is required.");
    if (!$name || !$surname) throw new Exception("Name and Surname are required.");
    if (!$account) throw new Exception("Account type is required.");

    $message = "User information updated successfully.";

    // ===================================================
    // 🔹 Handle Change Password (Auto Default)
    // ===================================================
    if ($changePwd) {
        if ($account === 'student') {
            $stmt = $pdo->prepare("SELECT studentCode FROM students WHERE user_id = ?");
            $stmt->execute([$id]);
            $code = $stmt->fetchColumn();

            if (!$code) throw new Exception("Student code not found.");

            // Default password: lastname + kumon + (code without KSTU)
            $suffix = str_replace("KSTU", "", $code);
            $defaultPassword = strtolower($surname . "kumon" . $suffix);

        } elseif ($account === 'teacher') {
            $stmt = $pdo->prepare("SELECT teacherCode FROM teachers WHERE user_id = ?");
            $stmt->execute([$id]);
            $code = $stmt->fetchColumn();

            if (!$code) throw new Exception("Teacher code not found.");

            // Default password: lastname + kumon + (code without KTEA)
            $suffix = str_replace("KTEA", "", $code);
            $defaultPassword = strtolower($surname . "kumon" . $suffix);

        } else {
            throw new Exception("Only students and teachers can have default passwords.");
        }

        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET password = ?, mustChangePassword = 1
            WHERE user_id = ?
        ");
        $stmt->execute([$hashedPassword, $id]);

        $message = "Password reset to default successfully.";
    }

    // ===================================================
    // 🔹 Always update user info
    // ===================================================
    $sql = "
        UPDATE users 
        SET Name = :name, 
            Surname = :surname,
            Address = :address,
            mobileNumber = :mobile,
            account_type = :account
        WHERE user_id = :id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'      => $id,
        ':name'    => $name,
        ':surname' => $surname,
        ':address' => $address,
        ':mobile'  => $mobile,
        ':account' => $account
    ]);

    // ===================================================
    // 🔹 Sync with student/teacher table
    // ===================================================
    if ($account === 'student') {
        $stmt = $pdo->prepare("
            UPDATE students 
            SET Firstname = ?, Lastname = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$name, $surname, $id]);
    } elseif ($account === 'teacher') {
        $stmt = $pdo->prepare("
            UPDATE teachers 
            SET Firstname = ?, Lastname = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$name, $surname, $id]);
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


