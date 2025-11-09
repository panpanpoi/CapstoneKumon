<?php
require_once "../database.php";
if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json; charset=utf-8");

// 🔒 Only admin can access
if (!isset($_SESSION['account_type']) || strtolower($_SESSION['account_type']) !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

// This handler only supports GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

try {
    // --- Fetch all students NOT in the class_students table ---
    $stmt_unassigned = $pdo->query("
        SELECT 
            s.student_id, 
            s.studentCode, 
            CONCAT(s.Firstname, ' ', s.Lastname) AS student_name
        FROM students s
        WHERE s.student_id NOT IN (SELECT student_id FROM class_students)
        ORDER BY student_name ASC
    ");
    
    $unassigned_students = $stmt_unassigned->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $unassigned_students,
        "count" => count($unassigned_students)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>

