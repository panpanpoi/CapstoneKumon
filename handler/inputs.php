<?php
require_once "../database.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect inputs safely
    $accountType   = trim($_POST['account_type'] ?? '');
    $firstName     = trim($_POST['fname'] ?? '');
    $middleName    = trim($_POST['mname'] ?? '');
    $lastName      = trim($_POST['lname'] ?? '');
    $contactNumber = trim($_POST['contact'] ?? '');
    $street        = trim($_POST['street'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $state         = trim($_POST['state'] ?? '');
    $username      = strtolower(trim($_POST['username'] ?? ''));
    $rawPassword   = $_POST['password'] ?? '';
    $subject       = $_POST['subject'] ?? null;
    $plan          = $_POST['plan'] ?? null;
    $address       = trim("$street, $city, $state");

    try {
        // 🔒 Validation
        if (!$accountType || !$firstName || !$lastName || !$username || !$rawPassword) {
            throw new Exception("Missing required fields.");
        }

        // 🔍 Check for duplicate username
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username already exists. Please choose another.");
        }

        // ✅ Hash password (make sure column is VARCHAR(255))
        $password = password_hash($rawPassword, PASSWORD_DEFAULT);

        // Step 1: Insert into users
        $query = "INSERT INTO users 
            (account_type, Name, MiddleName, Surname, Address, username, password, subject, mobileNumber) 
            VALUES (:account_type, :Name, :MiddleName, :Surname, :Address, :username, :password, :subject, :mobileNumber)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':account_type' => $accountType,
            ':Name'         => $firstName,
            ':MiddleName'   => $middleName,
            ':Surname'      => $lastName,
            ':Address'      => $address,
            ':username'     => $username,
            ':password'     => $password,
            ':subject'      => $subject,
            ':mobileNumber' => $contactNumber
        ]);

        // Get inserted user_id
        $user_id = $pdo->lastInsertId();

        // Step 2: Teacher account
        if ($accountType === "teacher") {
            $year = date("Y");
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE teacherCode LIKE ?");
            $stmt->execute(["KTEA$year%"]);
            $count = $stmt->fetchColumn() + 1;
            $teacher_code = "KTEA$year" . str_pad($count, 3, "0", STR_PAD_LEFT);

            $query = "INSERT INTO teachers (user_id, teacherCode, Firstname, Lastname) 
                      VALUES (:user_id, :teacherCode, :fname, :lname)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':user_id'     => $user_id,
                ':teacherCode' => $teacher_code,
                ':fname'       => $firstName,
                ':lname'       => $lastName
            ]);
        }

        // Step 3: Student account
        if ($accountType === "student") {
            $year = date("Y");
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE studentCode LIKE ?");
            $stmt->execute(["KSTU$year%"]);
            $count = $stmt->fetchColumn() + 1;
            $student_code = "KSTU$year" . str_pad($count, 3, "0", STR_PAD_LEFT);

            $monthly_fee = ($plan === 'A') ? 2200 : 2350;

            $query = "INSERT INTO students (user_id, studentCode, Firstname, Lastname, plan, monthlyFee) 
                      VALUES (:user_id, :studentCode, :fname, :lname, :plan, :monthlyFee)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':user_id'     => $user_id,
                ':studentCode' => $student_code,
                ':fname'       => $firstName,
                ':lname'       => $lastName,
                ':plan'        => $plan,
                ':monthlyFee'  => $monthly_fee
            ]);
        }

        // Success
        $_SESSION['success'] = "Account created successfully!";
        header("Location: ../pages/kumonAdmin.html");
        exit;

    } catch (Exception $e) {
        // Handles both validation & database errors
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: ../pages/createAccount.html");
        exit;
    }
}
