<?php
require_once "../database.php";
session_start();
header('Content-Type: application/json');

// 🔒 Admin-only access
if (empty($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Unauthorized access"]);
    exit;
}

try {
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');

    $stmt = $pdo->prepare("
        SELECT 
            payment_method,
            SUM(amount) AS total
        FROM payments
        WHERE payment_date BETWEEN :start AND :end
          AND status = 'active'
        GROUP BY payment_method
    ");
    $stmt->execute([':start' => $monthStart, ':end' => $monthEnd]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    $cash = 0;
    $gcash = 0;
    $bank = 0;

    foreach ($rows as $r) {
        $method = strtolower(trim($r['payment_method']));
        $sum = (float)$r['total'];
        $total += $sum;

        if (strpos($method, 'cash') !== false && strpos($method, 'gcash') === false) $cash += $sum;
        elseif (strpos($method, 'gcash') !== false) $gcash += $sum;
        elseif (strpos($method, 'bank') !== false) $bank += $sum;
    }

    echo json_encode([
        "success" => true,
        "totals" => [
            "total" => number_format($total, 2),
            "cash" => number_format($cash, 2),
            "gcash" => number_format($gcash, 2),
            "bank" => number_format($bank, 2)
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}


