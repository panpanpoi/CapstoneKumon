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

    // 2️⃣ Fetch payments for the current month only
    $startOfMonth = date('Y-m-01');
    $endOfMonth   = date('Y-m-t');

    $stmt = $pdo->prepare("
        SELECT payment_id, payment_date, amount, payment_method, payment_status, remarks, receipt_path, status
        FROM payments
        WHERE student_id = ?
          AND status = 'active'
          AND payment_date BETWEEN ? AND ?
        ORDER BY payment_date ASC
    ");
    $stmt->execute([$student_id, $startOfMonth, $endOfMonth]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3️⃣ Calculate total verified payments for this month
    $total_paid_this_month = 0;
    foreach ($payments as $p) {
        if (strtolower($p['payment_status']) === 'verified') {
            $total_paid_this_month += floatval($p['amount']);
        }
    }

    // 4️⃣ Calculate remaining balance and fully_paid flag
    $remaining_balance = max($monthlyFee - $total_paid_this_month, 0);
    $fully_paid = ($remaining_balance == 0);

    // 5️⃣ Compute next due date (next month from last verified payment)
    $last_verified_payment = null;
    foreach (array_reverse($payments) as $p) {
        if (strtolower($p['payment_status']) === 'verified') {
            $last_verified_payment = $p;
            break;
        }
    }

    if ($last_verified_payment) {
        $base_date = strtotime($last_verified_payment['payment_date']);
        $next_due_raw = strtotime('+1 month', $base_date);
    } else {
        $next_due_raw = strtotime('first day of next month');
    }
    $next_due = date('F j, Y', $next_due_raw);

    // 6️⃣ Payment reminder logic
    $dayOfMonth = date('j');
    $shouldNotify = ($dayOfMonth >= 24 && !$fully_paid);

    // 7️⃣ Return structured data
    return [
        'payments' => $payments,
        'total_paid' => $total_paid_this_month,
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
        'next_due' => date('F j, Y', strtotime('first day of next month')),
        'shouldNotify' => false
    ];
}
?>
