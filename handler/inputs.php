<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accountType   = $_POST['account_type'] ?? '';
    $subject       = $_POST['subject'] ?? '';
    $firstName     = $_POST['fname'] ?? '';
    $middleName    = $_POST['mname'] ?? '';
    $lastName      = $_POST['lname'] ?? '';
    $contactNumber = $_POST['contact'] ?? '';
    $street        = $_POST['street'] ?? '';
    $city          = $_POST['city'] ?? '';
    $state         = $_POST['state'] ?? '';

    $address = "$street, $city, $state";

    $username = $_POST['username'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    try {
        require_once '../database.php';

        // Insert into users table
        $query = "INSERT INTO users 
            (account_type, Name, MiddleName, Surname, Address, username, password, subject, mobileNumber) 
            VALUES (:account_type, :Name, :MiddleName, :Surname, :Address, :username, :password, :subject, :mobileNumber);";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
           ':account_type' => $accountType, 
           ':Name' => $firstName, 
           ':MiddleName' => $middleName, 
           ':Surname' => $lastName, 
            ':Address' => $address, 
            ':username' => $username, 
            ':password' => $password, 
            ':subject' => $subject, 
            ':mobileNumber' => $contactNumber
        ]);

       if ($accountType === "student" && isset($_POST['fname'], $_POST['lname'], $_POST['plan'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $plan  = $_POST['plan'];

    // Generate studentCode like STU-2025-001
    $year = date("Y");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE studentCode LIKE ?");
    $stmt->execute(["STU$year%"]);
    $count = $stmt->fetchColumn() + 1;

    $student_code = "STU$year" . str_pad($count, 3, "0", STR_PAD_LEFT);

    // Decide fee based on plan
    $monthly_fee = ($plan === 'A') ? 2200 : 2350;

    // Insert into students table
    $query = "INSERT INTO students (studentCode, Firstname, Lastname, plan, monthlyFee) 
              VALUES (:studentCode, :fname, :lname, :plan, :monthlyFee)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':studentCode'  => $student_code,
        ':fname'        => $fname,
        ':lname'        => $lname,    
        ':plan'         => $plan,
        ':monthlyFee'  => $monthly_fee
    ]);
}

        // Cleanup
        $stmt = null;
        $pdo  = null;

        // Redirect
        header("Location: ../pages/kumonAdmin.html");
        exit();

    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }

} else {
    header("Location: ../pages/kumonAdmin.html");
    exit();
}
