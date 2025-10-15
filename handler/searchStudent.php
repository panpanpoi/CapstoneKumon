<?php
require_once "../database.php";

$q = $_GET['q'] ?? '';

// Only search if query is at least 2 characters
if (strlen($q) >= 2) {
    $stmt = $pdo->prepare("
        SELECT student_id, studentCode, Firstname, Lastname
        FROM students 
        WHERE studentCode LIKE ? OR Firstname LIKE ? OR Lastname LIKE ? 
        LIMIT 10
    ");
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id   = (int)$row['student_id']; 
        $code = $row['studentCode'];
        $name = $row['Firstname'] . " " . $row['Lastname'];

        // Encode values for JavaScript
        $jsCode = json_encode($code);
        $jsName = json_encode($name);

        echo "<div onclick='selectStudent($id, $jsCode, $jsName)'>[$code] $name</div>";
    }
}
