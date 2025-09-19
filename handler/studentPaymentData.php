<?php
if (!isset($_SESSION)) session_start();
require_once "../database.php";
require_once "auth.php"; // ensures student is logged in

$student_id = $_SESSION['student_id'] ?? null;

if (!$student_id) {
    $_SESSION['error'] = "Student session not found.";
    header("Location: ../login.php");
    exit;
}

try {
    // Fetch student monthly fee and plan
    $stmt = $pdo->prepare("SELECT plan, monthlyFee FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    $monthlyFee = floatval($student['monthlyFee'] ?? 0);

    // Fetch all payments, ordered by due_date ascending
    $stmt = $pdo->prepare("
        SELECT payment_id, payment_date, due_date, amount, payment_method, reference_number, status
        FROM payments
        WHERE student_id = ?
        ORDER BY due_date ASC
    ");
    $stmt->execute([$student_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute totals
    $total_paid = 0;
    $total_pending = 0;
    $remaining_balance = $monthlyFee; // assume current month balance
    $last_payment = null;

    $currentMonth = date('Y-m');

    foreach ($payments as $pay) {
        $pay['amount'] = floatval($pay['amount']);
        $payMonth = date('Y-m', strtotime($pay['due_date']));
        if ($payMonth === $currentMonth && strtolower($pay['status']) === 'paid') {
            $total_paid += $pay['amount'];
            $remaining_balance -= $pay['amount'];
        } elseif (strtolower($pay['status']) === 'paid') {
            $total_paid += $pay['amount'];
        } else {
            $total_pending += $pay['amount'];
        }
    }

    // Last payment (latest by due_date)
    $last_payment = end($payments);

} catch (PDOException $e) {
    error_log("Error fetching student payments: " . $e->getMessage());
    $payments = [];
    $total_paid = 0;
    $total_pending = 0;
    $remaining_balance = 0;
    $last_payment = null;
}
