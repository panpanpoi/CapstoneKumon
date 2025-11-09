<?php
$host = 'sql306.infinityfree.com';     // ✅ Your MySQL Hostname
$dbname = 'if0_40208721_kumon_ortigas_db'; // ✅ Replace XXX with your actual database name (shown below that section)
$username = 'if0_40208721';            // ✅ Your MySQL Username
$password = '7af9Ei6qmr';        // ✅ The password you created in InfinityFree

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>


