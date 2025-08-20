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

        $query = "INSERT INTO users 
            (account_type, Name, MiddleName, Surname, Address, username, password, subject, mobileNumber) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $accountType, $firstName, $middleName, $lastName, 
            $address, $username, $password, $subject, $contactNumber
        ]);

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
