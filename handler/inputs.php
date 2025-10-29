<?php
require_once "../database.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../pages/createAccount.php");
    exit;
}

try {
    // Collect and sanitize inputs
    $accountType   = trim($_POST['account_type'] ?? '');
    $firstName     = trim($_POST['fname'] ?? '');
    $middleName    = trim($_POST['mname'] ?? '');
    $lastName      = trim($_POST['lname'] ?? '');
    $contactNumber = trim($_POST['contact'] ?? '');
    $street        = trim($_POST['street'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $state         = trim($_POST['state'] ?? '');
    $subject       = $_POST['subject'] ?? null;
    $plan          = $_POST['plan'] ?? null;

    $address = trim("$street, $city, $state");
    $year    = date("Y");

    // Validation
    if (!$accountType || !$firstName || !$lastName) {
        throw new Exception("Missing required fields.");
    }
    if ($accountType === "student" && !$plan) {
        throw new Exception("Please select a plan for the student.");
    }

    // Insert base user record (temporarily blank username/password)
    $stmt = $pdo->prepare("
        INSERT INTO users (
            account_type, 
            Name, 
            middleName, 
            Surname, 
            Address, 
            mobileNumber, 
            username, 
            password, 
            subject,
            mustChangePassword
        ) VALUES (
            :account_type, 
            :Name, 
            :middleName, 
            :Surname, 
            :Address, 
            :mobileNumber, 
            '', 
            '', 
            :subject,
            1
        )
    ");
    $stmt->execute([
        ':account_type' => $accountType,
        ':Name'         => $firstName,
        ':middleName'   => $middleName,
        ':Surname'      => $lastName,
        ':Address'      => $address,
        ':subject'      => $subject,
        ':mobileNumber' => $contactNumber
    ]);

    $user_id = $pdo->lastInsertId();

    // Student
    if ($accountType === "student") {
        $stmt = $pdo->prepare("SELECT MAX(studentCode) FROM students WHERE studentCode LIKE ?");
        $stmt->execute(["KSTU$year%"]);
        $lastCode = $stmt->fetchColumn();

        $nextNumber = $lastCode ? ((int)substr($lastCode, 8) + 1) : 1;
        $studentCode = "KSTU$year" . str_pad($nextNumber, 3, "0", STR_PAD_LEFT);

        $monthly_fee = match($plan) {
            'A' => 2200,
            'B' => 2350,
            default => throw new Exception("Invalid student plan selected.")
        };

        $level = "7A";

        $stmt = $pdo->prepare("
            INSERT INTO students (user_id, studentCode, Firstname, Lastname, plan, monthlyFee, level)
            VALUES (:user_id, :studentCode, :fname, :lname, :plan, :monthlyFee, :level)
        ");
        $stmt->execute([
            ':user_id'     => $user_id,
            ':studentCode' => $studentCode,
            ':fname'       => $firstName,
            ':lname'       => $lastName,
            ':plan'        => $plan,
            ':monthlyFee'  => $monthly_fee,
            ':level'       => $level
        ]);

        $numericCode   = str_replace("KSTU", "", $studentCode);
        $username      = strtolower($lastName) . $numericCode . "kumon";
        $passwordPlain = strtolower($lastName) . "kumon" . $numericCode;
        $password      = password_hash($passwordPlain, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, mustChangePassword = 1 WHERE user_id = ?");
        $stmt->execute([$username, $password, $user_id]);

        $_SESSION['success'] = "Student created!<br>
            Username: <b>$username</b><br>
            Password: <b>$passwordPlain</b><br>
            Student Code: <b>$studentCode</b>";
    }

    // Teacher
    if ($accountType === "teacher") {
        $stmt = $pdo->prepare("SELECT MAX(teacherCode) FROM teachers WHERE teacherCode LIKE ?");
        $stmt->execute(["KTEA$year%"]);
        $lastCode = $stmt->fetchColumn();

        $nextNumber = $lastCode ? ((int)substr($lastCode, 8) + 1) : 1;
        $teacherCode = "KTEA$year" . str_pad($nextNumber, 3, "0", STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO teachers (user_id, teacherCode, Firstname, Lastname)
            VALUES (:user_id, :teacherCode, :fname, :lname)
        ");
        $stmt->execute([
            ':user_id'     => $user_id,
            ':teacherCode' => $teacherCode,
            ':fname'       => $firstName,
            ':lname'       => $lastName
        ]);

        $numericCode   = str_replace("KTEA", "", $teacherCode);
        $username      = strtolower($lastName) . $numericCode . "kumon";
        $passwordPlain = strtolower($lastName) . "kumon" . $numericCode;
        $password      = password_hash($passwordPlain, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, mustChangePassword = 1 WHERE user_id = ?");
        $stmt->execute([$username, $password, $user_id]);

        $_SESSION['success'] = "Teacher created!<br>
            Username: <b>$username</b><br>
            Password: <b>$passwordPlain</b><br>
            Teacher Code: <b>$teacherCode</b>";
    }

    // Admin
    if ($accountType === "admin") {
        $username      = strtolower($lastName) . "admin";
        $passwordPlain = strtolower($lastName) . "kumon";
        $password      = password_hash($passwordPlain, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, mustChangePassword = 1 WHERE user_id = ?");
        $stmt->execute([$username, $password, $user_id]);

        $_SESSION['success'] = "Admin created!<br>
            Username: <b>$username</b><br>
            Password: <b>$passwordPlain</b>";
    }

    header("Location: ../pages/createAccount.php");
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../pages/createAccount.php");
    exit;
}
