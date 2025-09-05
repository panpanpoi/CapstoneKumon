<?php
require_once "../database.php";

$q = $_GET['q'] ?? '';

if (strlen($q) >= 2) {
    $stmt = $pdo->prepare("
        SELECT student_id, studentCode, Firstname, Lastname
        FROM students 
        WHERE studentCode LIKE ? OR Firstname LIKE ? OR Lastname LIKE ? 
        LIMIT 10
    ");
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id   = (int)$row['student_id']; // numeric PK
        $code = htmlspecialchars($row['studentCode']);
        $name = htmlspecialchars($row['Firstname'] . " " . $row['Lastname']);

        // show studentCode in search, but keep student_id for saving
       echo '<div onclick="selectStudent('
        . $id . ',' 
        . htmlspecialchars(json_encode($code), ENT_QUOTES) . ',' 
        . htmlspecialchars(json_encode($name), ENT_QUOTES) . ')">[' 
        . $code . '] ' . $name . '</div>';
    }
}
