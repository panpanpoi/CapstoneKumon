<?php
require_once "../database.php";

try {
    $stmt = $pdo->query("
        SELECT 
            p.payment_id,
            s.studentCode,
            CONCAT(s.Firstname, ' ', s.Lastname) AS student_name,
            p.amount,
            p.payment_date,
            p.payment_method,
            p.reference_number,
            p.remarks
        FROM payments p
        JOIN students s ON p.student_id = s.student_id
        ORDER BY p.payment_date DESC
    ");

    echo "<table id='paymentsTable'>";
    echo "<thead>
            <tr>
                <th style='display: none;'>Payment id</th>
                <th>Student Code</th>
                <th>Student Name</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Payment Method</th>
                <th>Reference #</th>
                <th>Remarks</th>
            </tr>
          </thead>
          <tbody>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td style ='display:none;'>" . htmlspecialchars($row['payment_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['studentCode']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
        echo "<td>" . number_format($row['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['payment_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['payment_method']) . "</td>";
        echo "<td>" . htmlspecialchars($row['reference_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

} catch (PDOException $e) {
    echo "Error fetching payments: " . $e->getMessage();
}

