<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

header("Content-Type: application/json; charset=utf-8");

// ✅ Force numeric IDs
$teacher_id = isset($_SESSION['teacher_id']) ? (int)$_SESSION['teacher_id'] : null;
$action     = $_POST['action'] ?? null;
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
$level      = $_POST['level'] ?? null;

if (!$teacher_id) {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

if (!$student_id || !$action) {
    echo json_encode(["success" => false, "message" => "Missing required data."]);
    exit;
}

try {
    // 🔹 ADD STUDENT
    if ($action === "add") {
        // Check if already assigned
        $check = $pdo->prepare("SELECT 1 FROM class_students WHERE teacher_id = ? AND student_id = ?");
        $check->execute([$teacher_id, $student_id]);
        if ($check->rowCount() > 0) {
            echo json_encode(["success" => false, "message" => "Student is already assigned to your class."]);
            exit;
        }

        // Insert into class_students
        $insert = $pdo->prepare("INSERT INTO class_students (teacher_id, student_id, level) VALUES (?, ?, ?)");
        $insert->execute([$teacher_id, $student_id, $level]);
        $class_id = $pdo->lastInsertId();

        // Schedule 1
        if (!empty($_POST['schedule1_day']) && !empty($_POST['schedule1_start']) && !empty($_POST['schedule1_end'])) {
            $sched1 = $pdo->prepare("INSERT INTO class_schedules (class_id, schedule_day, start_time, end_time) VALUES (?, ?, ?, ?)");
            $sched1->execute([$class_id, $_POST['schedule1_day'], $_POST['schedule1_start'], $_POST['schedule1_end']]);
        }

        // Schedule 2 (optional)
        if (!empty($_POST['schedule2_day']) && !empty($_POST['schedule2_start']) && !empty($_POST['schedule2_end'])) {
            $sched2 = $pdo->prepare("INSERT INTO class_schedules (class_id, schedule_day, start_time, end_time) VALUES (?, ?, ?, ?)");
            $sched2->execute([$class_id, $_POST['schedule2_day'], $_POST['schedule2_start'], $_POST['schedule2_end']]);
        }

        echo json_encode(["success" => true, "message" => "✅ Student added successfully."]);
        exit;

    } elseif ($action === "remove") {
        // Get class_id
        $getClass = $pdo->prepare("SELECT class_id FROM class_students WHERE teacher_id = ? AND student_id = ?");
        $getClass->execute([$teacher_id, $student_id]);
        $class = $getClass->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            echo json_encode([
                "success" => false,
                "message" => "Student not found in your class.",
                "teacher_id" => $teacher_id,
                "student_id" => $student_id
            ]);
            exit;
        }

        $class_id = $class['class_id'];

        // ✅ Transaction ensures both deletions succeed together
        $pdo->beginTransaction();
        try {
            // Delete schedules first
            $delSched = $pdo->prepare("DELETE FROM class_schedules WHERE class_id = ?");
            $delSched->execute([$class_id]);

            // Delete student assignment
            $delStudent = $pdo->prepare("DELETE FROM class_students WHERE class_id = ?");
            $delStudent->execute([$class_id]);

            $pdo->commit();
            echo json_encode(["success" => true, "message" => "🗑️ Student removed successfully."]);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
            exit;
        }

    } else {
        echo json_encode(["success" => false, "message" => "Invalid action."]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>
