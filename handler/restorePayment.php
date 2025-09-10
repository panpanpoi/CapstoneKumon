<?php
require_once "../database.php";

if (isset($_GET['id'])) {
    $paymentId = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE payments SET status = 'active' WHERE payment_id = ?");
    $stmt->execute([$paymentId]);

    header("Location: ../pages/viewPayments.html.php?restored=1");
    exit;
}
