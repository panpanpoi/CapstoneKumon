<?php
require_once __DIR__ . '/../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    http_response_code(400);
    exit('Invalid request.');
}

$file = $_FILES['csv_file']['tmp_name'];

if (!is_uploaded_file($file)) {
    http_response_code(400);
    exit('No file uploaded.');
}

try {
    $handle = fopen($file, 'r');
    if ($handle === false) {
        throw new Exception('Failed to open uploaded file.');
    }

    // Skip CSV header row
    fgetcsv($handle);

    $insertStmt = $pdo->prepare("
        INSERT INTO payments (
            student_id, amount, payment_date, due_date,
            payment_method, reference_number, tf_month_covered,
            remarks, payment_status
        ) VALUES (
            :student_id, :amount, :payment_date, :due_date,
            :payment_method, :reference_number, :tf_month_covered,
            :remarks, :payment_status
        )
    ");

    $rowCount = 0;

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        if (count($row) < 11) continue; // Skip incomplete rows

        [$payment_id, $student_id, $student_name, $amount, $payment_date, $due_date,
         $payment_method, $reference_number, $tf_month_covered, $remarks, $status] = $row;

        // Skip empty student IDs or invalid data
        if (empty($student_id) || !is_numeric($amount)) continue;

        $insertStmt->execute([
            ':student_id' => $student_id,
            ':amount' => $amount,
            ':payment_date' => $payment_date ?: date('Y-m-d'),
            ':due_date' => $due_date ?: date('Y-m-d', strtotime('+1 month', strtotime($payment_date))),
            ':payment_method' => $payment_method ?: 'Cash',
            ':reference_number' => $reference_number ?: '',
            ':tf_month_covered' => $tf_month_covered ?: '',
            ':remarks' => $remarks ?: '',
            ':payment_status' => $status ?: 'unverified'
        ]);

        $rowCount++;
    }

    fclose($handle);

    echo "<p style='color:lime;'>Successfully imported {$rowCount} payments.</p>";

} catch (Exception $e) {
    http_response_code(500);
    echo "Error importing payments: " . htmlspecialchars($e->getMessage());
}
?>


