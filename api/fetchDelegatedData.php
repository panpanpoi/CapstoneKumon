<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ⭐ --- END OF DEBUGGING --- ⭐
require_once "../api/auth.php";
require_once "../database.php";
if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json; charset=utf-8");

// 🔒 Only admin can access
if (!isset($_SESSION['account_type']) || strtolower($_SESSION['account_type']) !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // --- GET: Fetch delegated students for a specific teacher ---
        if (!isset($_GET['teacher_id'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing teacher_id parameter."]);
            exit;
        }

        $teacher_id = (int)$_GET['teacher_id'];

        // Validate teacher existence
        $check = $pdo->prepare("SELECT teacher_id FROM teachers WHERE teacher_id = ?");
        $check->execute([$teacher_id]);
        if ($check->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Teacher not found."]);
            exit;
        }

        // Fetch students currently assigned to this teacher
        $sql = "
            SELECT 
                s.student_id,
                s.studentCode,
                CONCAT(s.Firstname, ' ', s.Lastname) AS student_name,
                COALESCE(cs.level, 'N/A') AS level
            FROM students s
            JOIN class_students cs ON s.student_id = cs.student_id
            WHERE cs.teacher_id = ?
            ORDER BY student_name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$teacher_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "type" => "students",
            "teacher_id" => $teacher_id,
            "data" => $students,
            "count" => count($students)
        ]);
        exit;
    }

    // All create and delete operations are now handled with POST
    elseif ($method === 'POST') {
        
        $input = json_decode(file_get_contents("php://input"), true);
        
        if ($input === null) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "DEBUG: JSON payload was null. Check if JS is sending body."]);
            exit;
        }

        // Get the 'action' from the JavaScript file
        $action = $input['action'] ?? null;

        if ($action === 'create') {
            // 🟢 POST: Delegate a single student (Insertion logic)
            
            // 1. Input Validation
            if (
                empty($input['teacher_id']) ||
                !isset($input['student_ids']) ||
                !is_array($input['student_ids']) ||
                count($input['student_ids']) !== 1
            ) {
                http_response_code(400);
                echo json_encode([
                    "success" => false, 
                    "message" => "DEBUG: Invalid delegation input."
                ]);
                exit;
            }

            $teacher_id = (int)$input['teacher_id'];
            $student_id = (int)$input['student_ids'][0];

            // 2. Check if student is already assigned to ANY teacher
            $check_assigned = $pdo->prepare("SELECT 1 FROM class_students WHERE student_id = ?");
            $check_assigned->execute([$student_id]);
            
            if ($check_assigned->rowCount() > 0) {
                $message = "Student is already assigned to a teacher.";
                http_response_code(409); // Conflict
                echo json_encode(["success" => false, "message" => $message]);
                exit;
            }

            // 3. Insert the new assignment
            $insert = $pdo->prepare("
                INSERT INTO class_students (teacher_id, student_id, level)
                VALUES (?, ?, 'N/A')
            ");
            $insert->execute([$teacher_id, $student_id]);

            $message = "Student successfully delegated to the class.";

            echo json_encode([
                "success" => true,
                "message" => $message,
                "teacher_id" => $teacher_id,
                "student_id" => $student_id
            ]);
            exit;

        } elseif ($action === 'delete') {
            // 🔴 POST (acting as DELETE): Unassign a single student (Deletion logic)
            
            // 1. Input Validation (from your old DELETE block)
            if (empty($input['teacher_id']) || empty($input['student_id'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Invalid removal input: Missing teacher or student ID."]);
                exit;
            }

            $teacher_id = (int)$input['teacher_id'];
            $student_id = (int)$input['student_id'];

            // 2. Delete the student assignment (from your old DELETE block)
            $delete = $pdo->prepare("
                DELETE FROM class_students 
                WHERE teacher_id = ? AND student_id = ?
            ");
            $delete->execute([$teacher_id, $student_id]);

            if ($delete->rowCount() > 0) {
                echo json_encode([
                    "success" => true,
                    "message" => "Student successfully unassigned.",
                    "teacher_id" => $teacher_id,
                    "student_id" => $student_id
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Assignment not found for this teacher and student.",
                    "teacher_id" => $teacher_id,
                    "student_id" => $student_id
                ]);
            }
            exit;

        } else {
            // No action or an invalid action was provided
            http_response_code(400);
            echo json_encode([
                "success" => false, 
                "message" => "DEBUG: Invalid or missing 'action' property.",
                "received_action" => $action
            ]);
            exit;
        }
    }

    else {
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    // ⭐ --- DEBUGGING --- ⭐
    // This will now print the detailed SQL error message
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}