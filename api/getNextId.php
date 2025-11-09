<?php
require_once "../database.php";
header("Content-Type: application/json; charset=utf-8");

$type = $_GET['type'] ?? '';

try {
    if ($type === "student") {
        $stmt = $pdo->query("SELECT studentCode FROM students WHERE studentCode IS NOT NULL ORDER BY student_id DESC LIMIT 1");
        $latest = $stmt->fetchColumn();
        // example latest: KSTU2025007 -> extract last 3 digits
        $lastNum = 0;
        if ($latest && preg_match('/(\d{3})$/', $latest, $m)) {
            $lastNum = (int)$m[1];
        }
        $next = str_pad($lastNum + 1, 3, "0", STR_PAD_LEFT);
        echo json_encode(["success" => true, "next" => $next]);
        exit;
    }

    if ($type === "teacher") {
        $stmt = $pdo->query("SELECT teacherCode FROM teachers WHERE teacherCode IS NOT NULL ORDER BY teacher_id DESC LIMIT 1");
        $latest = $stmt->fetchColumn();
        $lastNum = 0;
        if ($latest && preg_match('/(\d{3})$/', $latest, $m)) {
            $lastNum = (int)$m[1];
        }
        $next = str_pad($lastNum + 1, 3, "0", STR_PAD_LEFT);
        echo json_encode(["success" => true, "next" => $next]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Invalid type parameter"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}


