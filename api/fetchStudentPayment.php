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

    // --- [START] Updated Next Due Date Logic ---

    // 🧾 Calculate Next Due Date based on latest VERIFIED payment
    $next_due_fallback = date('Y-m-d', strtotime("first day of next month"));
    $next_due = $next_due_fallback;

    // Filter for verified payments only
    $verified_payments = array_filter($all_payments, function($p) {
        return $p['payment_status'] === 'verified';
    });

    if (count($verified_payments) > 0) {
        $covered_dates = [];
        foreach ($verified_payments as $p) {
            // Convert "Month YYYY" string (e.g., "March 2026") to a valid DateTime object
            $date = date_create_from_format('F Y', $p['tf_month_covered']);
            
            if ($date) {
                // Set to the first day of that month for comparison
                $covered_dates[] = $date->modify('first day of this month');
            }
        }

        if (count($covered_dates) > 0) {
            // Find the latest (max) date from the verified payments
            $latest_covered_date = max($covered_dates);
            
            // Add 1 month to get the next due date
            $latest_covered_date->modify('+1 month');
            
            // Set the final next_due string, formatted as YYYY-MM-DD
            $next_due = $latest_covered_date->format('Y-m-d');
        }
    } else {
        // If no verified payments, just use the fallback
        $next_due = $next_due_fallback;
    }

    // --- [END] Updated Next Due Date Logic ---


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