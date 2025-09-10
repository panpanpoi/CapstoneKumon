<?php
require_once "../database.php";

if (isset($_GET['id'])) {
    $paymentId = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE payments SET status = 'archived' WHERE payment_id = ?");
    $stmt->execute([$paymentId]);

    header("Location: ../pages/viewPayment.html.php?archived=1");
    exit;
}
