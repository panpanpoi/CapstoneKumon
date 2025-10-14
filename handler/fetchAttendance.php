<?php
require_once "../database.php";
require_once "../handler/auth.php";

header('Content-Type: application/json'); // ✅ Always JSON

if ($_SESSION['account_type'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$type = $_GET['type'] ?? 'Normal';
$date = $_GET['date'] ?? null;

if (!$date) {
    echo json_encode(["error" => "Missing date"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            s.studentCode,
            CONCAT(s.Firstname, ' ', s.Lastname) AS name,
            a.status,
            a.type,
            a.date
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        WHERE a.teacher_id = ? 
          AND a.type = ? 
          AND a.date = ?
        ORDER BY a.date DESC, s.studentCode ASC
    ");
    $stmt->execute([$teacher_id, $type, $date]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
