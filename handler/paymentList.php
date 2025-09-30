<?php
require_once "../database.php";

// ✅ Filter payments (default: active)
$status = isset($_GET['status']) && $_GET['status'] === 'archived' ? 'archived' : 'active';

// fetch payments + join students
$stmt = $pdo->prepare("
    SELECT p.payment_id, p.amount, p.payment_date, p.payment_method, 
           p.reference_number, p.remarks, p.status,
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
          <th>Actions</th>
        </tr>
      </thead><tbody>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $studentName = htmlspecialchars($row['Firstname'] . " " . $row['Lastname']);
    $studentCode = htmlspecialchars($row['studentCode']);
    $paymentId   = (int)$row['payment_id'];

    echo "<tr id='row-{$paymentId}'>
            <td>{$paymentId}</td>
            <td>{$studentCode}</td>
            <td>{$studentName}</td>
            <td>{$row['amount']}</td>
            <td>{$row['payment_date']}</td>
            <td>{$row['payment_method']}</td>
            <td>{$row['reference_number']}</td>
            <td>{$row['remarks']}</td>
            <td>";

    // ✅ Action buttons
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
