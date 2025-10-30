<?php
session_start();
require_once "../database.php"; // connect to database

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id       = $_POST['student_id']       ?? null;
    $amount           = $_POST['amount']           ?? null;
    $payment_date     = $_POST['payment_date']     ?? null;
    $payment_method   = $_POST['payment_method']   ?? null;
    $reference_number = $_POST['reference_number'] ?? null;
    $remarks          = $_POST['remarks']          ?? null;

    // ✅ Required field check
    if (!$student_id || !$amount || !$payment_date || !$payment_method) {
        $_SESSION['error'] = "Missing required fields. Please complete the form.";
        header("Location: ../pages/recordPayment.php");
        exit;
    }

    try {
        // ✅ Compute due date (30 days after payment date)
        $dueDateObj = new DateTime($payment_date);
        $dueDateObj->modify('+30 days');
        $due_date = $dueDateObj->format('Y-m-d');

        // ✅ Insert payment with computed due date
        $stmt = $pdo->prepare("
            INSERT INTO payments 
                (student_id, amount, payment_date, due_date, payment_method, reference_number, remarks) 
            VALUES 
                (:student_id, :amount, :payment_date, :due_date, :payment_method, :reference_number, :remarks)
        ");

        $stmt->execute([
            ':student_id'       => $student_id,
            ':amount'           => $amount,
            ':payment_date'     => $payment_date,
            ':due_date'         => $due_date,
            ':payment_method'   => $payment_method,
            ':reference_number' => $reference_number ?: null,
            ':remarks'          => $remarks ?: null
        ]);

        $_SESSION['success'] = "✅ Payment recorded successfully! (Next due: {$due_date})";
        header("Location: ../pages/viewPayment.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error saving payment: " . $e->getMessage();
        header("Location: ../pages/recordPayment.php");
        exit;
    }
}
