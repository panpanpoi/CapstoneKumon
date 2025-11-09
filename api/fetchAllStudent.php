<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

header('Content-Type: application/json; charset=utf-8');

// Only allow teachers
$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT student_id, studentCode, Firstname, Lastname 
        FROM students 
        ORDER BY Firstname ASC
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $all = [];
    foreach ($students as $st) {
        $all[] = [
            "student_id"   => $st['student_id'],
            "studentCode"  => $st['studentCode'],
            "full_name"    => $st['Firstname'] . ' ' . $st['Lastname']
        ];
    }

    echo json_encode(["success" => true, "data" => $all], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>


