<?php
require_once "../database.php";

// fetch only active payments + join students
$stmt = $pdo->query("
    SELECT p.payment_id, p.amount, p.payment_date, p.payment_method, 
           p.reference_number, p.remarks, p.status,
           s.studentCode, s.Firstname, s.Lastname
    FROM payments p
    JOIN students s ON p.student_id = s.student_id
    WHERE p.status = 'active'
    ORDER BY p.payment_date DESC
");

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
          <th>Action</th>
        </tr>
      </thead><tbody>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $studentName = htmlspecialchars($row['Firstname'] . " " . $row['Lastname']);
    $studentCode = htmlspecialchars($row['studentCode']);

    echo "<tr id='row-{$row['payment_id']}'>
            <td>{$row['payment_id']}</td>
            <td>{$studentCode}</td>
            <td>{$studentName}</td>
            <td>{$row['amount']}</td>
            <td>{$row['payment_date']}</td>
            <td>{$row['payment_method']}</td>
            <td>{$row['reference_number']}</td>
            <td>{$row['remarks']}</td>
            <td>
              <button onclick=\"archivePayment({$row['payment_id']})\">Archive</button>
            </td>
          </tr>";
}

echo "</tbody></table>";
?>
