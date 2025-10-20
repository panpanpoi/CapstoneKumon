<?php

$dns = "mysql:host=localhost;dbname=kumon_ortigas_db";
$dbuser = "root";
$dbpass = "";

//error handling
try {
    $pdo = new PDO($dns, $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die('Database connection failed: ' . $e->getMessage());
}

