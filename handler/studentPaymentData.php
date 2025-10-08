<?php
if (!isset($_SESSION)) session_start();

require_once "../database.php";
require_once "auth.php"; // ensures valid student session

$student_id = $_SESSION['student_id'] ?? null;

if (!$student_id) {
    $_SESSION['error'] = "Student session not found.";
    header("Location: ../login.php");
    exit;
}

try {
    // 1️⃣ Fetch student's monthly fee
    $stmt = $pdo->prepare("SELECT monthlyFee FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthlyFee = floatval($student['monthlyFee'] ?? 0);

    // 2️⃣ Fetch all active payments up to today
    $stmt = $pdo->prepare("
        SELECT payment_date, amount, payment_status, 
               COALESCE(payment_method, 'N/A') AS payment_method,
               COALESCE(remarks, '') AS remarks,
               COALESCE(receipt_path, '') AS receipt_path
        FROM payments
        WHERE student_id = ? AND status = 'active'
        ORDER BY payment_date ASC
    ");
    $stmt->execute([$student_id]);
    $all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3️⃣ Filter payments for current month & track balances
    $currentMonth = date('m');
    $currentYear = date('Y');

    $payments_this_month = [];
    $total_this_month = 0;
    $overpayment = 0;

    foreach ($all_payments as $p) {
        $pay_month = date('m', strtotime($p['payment_date']));
        $pay_year = date('Y', strtotime($p['payment_date']));
        $amount = floatval($p['amount']);

        // Add overpayment from previous months
        $amount += $overpayment;

        // Handle full month payments
        if ($amount >= $monthlyFee) {
            $overpayment = $amount - $monthlyFee;
            if ($pay_month == $currentMonth && $pay_year == $currentYear) {
                $payments_this_month[] = $p;
                $total_this_month += $monthlyFee; // only count monthly fee
            }
        } else {
            // Partial payment
            $overpayment = 0;
            if ($pay_month == $currentMonth && $pay_year == $currentYear) {
                $payments_this_month[] = $p;
                $total_this_month += $amount;
            }
        }
    }

    // 4️⃣ Compute remaining balance
    $remaining_balance = max($monthlyFee - $total_this_month, 0);

    // 5️⃣ Fully paid flag
    $fully_paid = ($remaining_balance == 0);

    // 6️⃣ Next due date = first day of next month
    $next_due = date('F j, Y', strtotime("first day of next month"));

    // 7️⃣ Payment reminder logic
    $shouldNotify = (date('j') >= 24 && !$fully_paid);

    // 8️⃣ Return structured data
    return [
        'payments' => $payments_this_month,
        'total_paid' => $total_this_month,
        'remaining_balance' => $remaining_balance,
        'fully_paid' => $fully_paid,
        'next_due' => $next_due,
        'shouldNotify' => $shouldNotify
    ];

} catch (PDOException $e) {
    error_log("Error fetching student payments: " . $e->getMessage());
    return [
        'payments' => [],
        'total_paid' => 0,
        'remaining_balance' => 0,
        'fully_paid' => false,
        'next_due' => date('F j, Y', strtotime("first day of next month")),
        'shouldNotify' => false
    ];
}
?>
