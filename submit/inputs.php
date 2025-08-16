<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accountType = htmlspecialchars($_POST['account_type'] ?? '');
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $firstName = htmlspecialchars($_POST['fname'] ?? '');
    $middleName = htmlspecialchars($_POST['mname'] ?? '');
    $lastName = htmlspecialchars($_POST['lname'] ?? '');
    $contactNumber = htmlspecialchars($_POST['contact'] ?? '');
    $address = htmlspecialchars($_POST['street'] ?? '') . ', ' .
              htmlspecialchars($_POST['city'] ?? '') . ', ' .
              htmlspecialchars($_POST['state'] ?? '');
    $username = htmlspecialchars($_POST['username'] ?? '');
    $password = password_hash(htmlspecialchars($_POST['password'] ?? ''), PASSWORD_DEFAULT);

    try {
       require_once '../database.php';
     
       $query = "INSERT INTO users (account_type, Name, MiddleName, Surname, Address, username, password, subject) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
       $stmt = $pdo->prepare($query);
       $stmt->execute([$accountType, $firstName, $middleName, $lastName, $address, $username, $password, $subject]);

       // Close DB resources
        $stmt = null;
        $pdo  = null;

        // Redirect after success
        header("Location: ../kumonAdmin.html");
       

    } catch (PDOException $e) {
        // Show error for debugging
        echo "Database Error: " . $e->getMessage();
    
    }

}else {
    header("Location: ../kumonAdmin.html");
    exit();
}