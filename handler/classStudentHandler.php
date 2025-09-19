<?php
require_once "../database.php";
if (!isset($_SESSION)) session_start();

// Make sure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../pages/kumonClass.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$action = $_POST['action'] ?? null;

try {
    if ($action === "add") {
        $student_id = $_POST['student_id'] ?? null;
        $level = $_POST['level'] ?? null;
        $class_id = $_POST['class_id'] ?? null;

        if (!$student_id || !$level || !$class_id) {
            throw new Exception("Missing required fields.");
        }

        // Insert student into class_students
        $stmt = $pdo->prepare("
            INSERT INTO class_students (student_id, teacher_id, class_id, level)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $teacher_id, $class_id, $level]);

        $_SESSION['success'] = "Student added successfully";
        header("Location: ../pages/kumonClass.php");
        exit;
    }

    if ($action === "remove") {
        $student_id = $_POST['student_id'] ?? null;
        $class_id = $_POST['class_id'] ?? null;

        if (!$student_id || !$class_id) {
            throw new Exception("Missing student or class ID.");
        }

        // Delete student from class_students
        $stmt = $pdo->prepare("
            DELETE FROM class_students
            WHERE student_id = ? AND class_id = ? AND teacher_id = ?
        ");
        $stmt->execute([$student_id, $class_id, $teacher_id]);

        $_SESSION['success'] = "Student removed successfully";
        header("Location: ../pages/kumonClass.php");
        exit;
    }

    if ($action === "update_level") {
        $student_id = $_POST['student_id'] ?? null;
        $level = $_POST['level'] ?? null;

        if (!$student_id || !$level) {
            throw new Exception("Missing student ID or level.");
        }

        // Update student's level
        $stmt = $pdo->prepare("
            UPDATE class_students
            SET level = ?
            WHERE student_id = ? AND teacher_id = ?
        ");
        $stmt->execute([$level, $student_id, $teacher_id]);

        $_SESSION['success'] = "Level updated successfully";
        header("Location: ../pages/kumonClass.php");
        exit;
    }

    throw new Exception("Invalid action.");

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../pages/kumonClass.php");
    exit;
}
