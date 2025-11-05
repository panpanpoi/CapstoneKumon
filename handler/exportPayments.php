<?php
require_once __DIR__ . '/../database.php';

try {
    // 🗓 Get selected month (default: current month)
    $monthInput = $_GET['month'] ?? date('Y-m');
    [$year, $month] = explode('-', $monthInput);

    // 🔍 Fetch payments for the given month
    $sql = "
    SELECT 
        p.payment_id,
        s.student_id,
        CONCAT(s.Firstname, ' ', s.Lastname) AS student_name,
        p.amount,
        p.payment_date,
        p.due_date,
        p.payment_method,
        p.reference_number,
        p.tf_month_covered,
        p.remarks,
        p.payment_status,
        p.verified_by,
        p.verified_at
    FROM payments p
    LEFT JOIN students s ON p.student_id = s.student_id
    WHERE MONTH(p.payment_date) = :month
      AND YEAR(p.payment_date) = :year
    ORDER BY p.payment_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':month' => $month, ':year' => $year]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($payments)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "No payment records found for {$monthInput}.";
        exit;
    }

    // 📁 Prepare CSV for download
    $filename = "payments_export_{$monthInput}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    // ✅ Initialize output stream
    $output = fopen('php://output', 'w');
    if ($output === false) throw new Exception("Failed to open output stream.");

    // 🧩 CSV Header
    fputcsv($output, [
        'Payment ID',
        'Student ID',
        'Student Name',
        'Amount (₱)',
        'Payment Date',
        'Due Date',
        'Payment Method',
        'Reference #',
        'TF-Month Covered',
        'Remarks',
        'Status',
        'Verified By',
        'Verified At'
    ]);

    // 🧾 CSV Data
    foreach ($payments as $row) {
        $verifiedAt = '';
        if (!empty($row['verified_at'])) {
            try {
                $dt = new DateTime($row['verified_at']);
                $verifiedAt = $dt->format('Y-m-d H:i');
            } catch (Exception $e) {
                $verifiedAt = $row['verified_at'];
            }
        }

        fputcsv($output, [
            $row['payment_id'],
            $row['student_id'],
            $row['student_name'],
            number_format((float) $row['amount'], 2),
            $row['payment_date'],
            $row['due_date'] ?? '',
            $row['payment_method'] ?? '',
            $row['reference_number'] ?? '',
            $row['tf_month_covered'] ?? '',
            $row['remarks'] ?? '',
            ucfirst($row['payment_status'] ?? 'Unverified'),
            $row['verified_by'] ?? '',
            $verifiedAt
        ]);
    }

    fclose($output);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error generating report: " . htmlspecialchars($e->getMessage());
    exit;
}
