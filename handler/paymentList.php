<?php
require_once "../database.php";

// ✅ Filter payments (default: active)
$status = ($_GET['status'] ?? 'active') === 'archived' ? 'archived' : 'active';

// ✅ Fetch payments + join students
$stmt = $pdo->prepare("
    SELECT p.payment_id, p.amount, p.payment_date, p.payment_method, 
           p.reference_number, p.remarks, p.status, p.receipt_path,
           s.studentCode, s.Firstname, s.Lastname
    FROM payments p
    JOIN students s ON p.student_id = s.student_id
    WHERE p.status = ?
    ORDER BY p.payment_date DESC
");
$stmt->execute([$status]);

echo "<table id='paymentsTable'>";
echo "<thead>
        <tr>
          <th>ID</th>
          <th>Student Code</th>
          <th>Student Name</th>
          <th>Amount</th>
          <th>Date</th>
          <th>Method</th>
          <th>Reference</th>
          <th>Notes</th>
          <th>Receipt</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $paymentId   = (int)$row['payment_id'];
    $studentCode = htmlspecialchars($row['studentCode']);
    $studentName = htmlspecialchars($row['Firstname'] . " " . $row['Lastname']);
    $amount      = htmlspecialchars($row['amount']);
    $date        = htmlspecialchars($row['payment_date']);
    $method      = htmlspecialchars($row['payment_method']);
    $reference   = htmlspecialchars($row['reference_number']);
    $remarks     = htmlspecialchars($row['remarks']);
    $receiptPath = $row['receipt_path'] ?? '';

    echo "<tr id='row-{$paymentId}'>
            <td>{$paymentId}</td>
            <td>{$studentCode}</td>
            <td>{$studentName}</td>
            <td>{$amount}</td>
            <td>{$date}</td>
            <td>{$method}</td>
            <td>{$reference}</td>
            <td>{$remarks}</td>
            <td>";

    // ✅ Show receipt if exists
    if (!empty($receiptPath)) {
        $safePath = htmlspecialchars("../" . $receiptPath);
        $ext = strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            echo "<a href='{$safePath}' target='_blank'>
                    <img src='{$safePath}' alt='Receipt' class='receipt-thumb'>
                  </a>";
        } else {
            echo "<span style='color:red;'>Invalid file type (Only JPG/PNG allowed)</span>";
        }
    } else {
        echo "No receipt";
    }

    echo "</td>
          <td>";

    // ✅ Actions
    if ($status === 'active') {
        echo "<button class='btn-verify' onclick=\"openVerifyModal({$paymentId})\">Verify</button>
              <button class='btn-archive' onclick=\"archivePayment({$paymentId})\">Archive</button>";
    } else {
        echo "<button class='btn-restore' onclick=\"restorePayment({$paymentId})\">Restore</button>";
    }

    echo "  </td>
          </tr>";
}

echo "</tbody></table>";
