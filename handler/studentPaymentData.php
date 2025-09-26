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
    // Fetch student monthly fee
    $stmt = $pdo->prepare("SELECT monthlyFee FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    $monthlyFee = floatval($student['monthlyFee'] ?? 0);

    // Current month/year
    $currentMonth = date('Y-m');
    $currentDate = date('Y-m-d');
    $dayOfMonth = date('j');

    // Fetch all payments for this student
    $stmt = $pdo->prepare("
        SELECT payment_id, payment_date, due_date, amount, payment_method, reference_number, status
        FROM payments
        WHERE student_id = ?
        ORDER BY due_date ASC
    ");
    $stmt->execute([$student_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Compute balance for this month ---
    $paidThisMonth = 0;
    foreach ($payments as $pay) {
        $payMonth = date('Y-m', strtotime($pay['due_date']));
        if ($payMonth === $currentMonth && strtolower($pay['status']) === 'paid') {
            $paidThisMonth += floatval($pay['amount']);
        }
    }

    $remaining_balance = $monthlyFee - $paidThisMonth;
    if ($remaining_balance < 0) $remaining_balance = 0; // prevent negative if overpaid

    // --- Totals across history ---
    $total_paid = 0;
    foreach ($payments as $pay) {
        if (strtolower($pay['status']) === 'paid') {
            $total_paid += floatval($pay['amount']);
        }
    }

    $total_pending = max(0, ($monthlyFee - $paidThisMonth));

    // Last payment (latest by payment_date)
    $last_payment = !empty($payments) ? end($payments) : null;

    // --- Notification trigger ---
    $shouldNotify = false;
    if ($dayOfMonth >= 24 && $remaining_balance > 0) {
        $shouldNotify = true;
        // Here you would integrate with your push notification system
        // Example: sendNotification($student_id, "You still have ₱$remaining_balance due this month.");
    }

} catch (PDOException $e) {
    error_log("Error fetching student payments: " . $e->getMessage());
    $payments = [];
    $total_paid = 0;
    $total_pending = 0;
    $remaining_balance = 0;
    $last_payment = null;
    $shouldNotify = false;
}
