<?php
require_once "../database.php"; // your PDO connection file

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST['student_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $payment_date = $_POST['payment_date'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $reference_number = $_POST['reference_number'] ?? null;
    $remarks = $_POST['remarks'] ?? null;

    // Basic validation
    if (!$student_id || !$amount || !$payment_date || !$payment_method) {
        die("Missing required fields.");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO payments 
            (student_id, amount, payment_date, payment_method, reference_number, remarks) 
            VALUES (:student_id, :amount, :payment_date, :payment_method, :reference_number, :remarks)");

        $stmt->execute([
            ':student_id' => $student_id,
            ':amount' => $amount,
            ':payment_date' => $payment_date,
            ':payment_method' => $payment_method,
            ':reference_number' => $reference_number,
            ':remarks' => $remarks
        ]);

     // Store flash message in session
        $_SESSION['success'] = "Payment recorded successfully!";
        header("Location: ../pages/viewPayment.html.php");
        exit;
    } catch (PDOException $e) {
        die("Error saving payment: " . $e->getMessage());
    }
}