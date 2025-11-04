<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/auth.php'; // ensures valid student session
header('Content-Type: application/json');

try {
    $student_id = $_SESSION['student_id'] ?? ($_GET['student_id'] ?? null);

    if (!$student_id) {
        throw new Exception('Missing student ID.');
    }

    // --- Handle selected month/year ---
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
    $year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

    // Fetch monthly fee for student
    $stmt = $pdo->prepare("SELECT monthlyFee FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $monthlyFee = floatval($stmt->fetchColumn() ?? 0);

    // Fetch all active payments for this student
    $stmt = $pdo->prepare("
        SELECT 
            payment_id, 
            amount, 
            payment_date, 
            due_date, 
            payment_method, 
            reference_number, 
            tf_month_covered, 
            remarks, 
            receipt_path, 
            payment_status
        FROM payments
        WHERE student_id = ? AND status = 'active'
        ORDER BY STR_TO_DATE(payment_date, '%Y-%m-%d') ASC
    ");
    $stmt->execute([$student_id]);
    $all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter payments by selected month/year
    $payments_this_period = array_filter($all_payments, function ($p) use ($month, $year) {
        $pMonth = (int)date('n', strtotime($p['payment_date']));
        $pYear  = (int)date('Y', strtotime($p['payment_date']));
        return ($pMonth === $month && $pYear === $year);
    });

    // Compute totals
    $total_this_period = array_sum(array_column($payments_this_period, 'amount'));
    $remaining_balance = max($monthlyFee - $total_this_period, 0);
    $fully_paid = ($remaining_balance <= 0);

    // 🧾 Get latest due date for this student's payments
    $dueQuery = $pdo->prepare("
        SELECT due_date 
        FROM payments 
        WHERE student_id = ? 
        ORDER BY due_date DESC 
        LIMIT 1
    ");
    $dueQuery->execute([$student_id]);
    $latest = $dueQuery->fetch(PDO::FETCH_ASSOC);

    $next_due = $latest && !empty($latest['due_date'])
        ? $latest['due_date']
        : date('Y-m-d', strtotime("first day of next month"));

    // Notify rule (24th onward, if not fully paid)
    $shouldNotify = (date('j') >= 24 && !$fully_paid);

    // Add badge labels for UI
        foreach ($payments_this_period as &$payment) {
            if ($payment['payment_status'] === 'pending') {
                $payment['badge'] = 'Pending';
                $payment['can_upload'] = false;
            } elseif ($payment['payment_status'] === 'verified') {
                $payment['badge'] = 'Verified';
                $payment['can_upload'] = false;
            } elseif ($payment['payment_status'] === 'rejected') {
                $payment['badge'] = 'Rejected';
                $payment['can_upload'] = true; // allow re-upload if rejected
            } elseif ($payment['payment_status'] === 'unverified') {
                $payment['badge'] = 'Unpaid';
                $payment['can_upload'] = true; // allow upload
            } else {
                $payment['badge'] = 'Unknown';
                $payment['can_upload'] = false;
            }
        }


    // ✅ Final JSON output
    echo json_encode([
        'success' => true,
        'payments' => array_values($payments_this_period),
        'total_paid' => $total_this_period,
        'remaining_balance' => $remaining_balance,
        'fully_paid' => $fully_paid,
        'next_due' => $next_due,
        'shouldNotify' => $shouldNotify,
        'selectedMonth' => $month,
        'selectedYear' => $year
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'payments' => [],
        'total_paid' => 0,
        'remaining_balance' => 0,
        'fully_paid' => false,
        'next_due' => date('Y-m-d', strtotime("first day of next month")),
        'shouldNotify' => false,
        'selectedMonth' => (int)date('n'),
        'selectedYear' => (int)date('Y')
    ]);
}
?>
