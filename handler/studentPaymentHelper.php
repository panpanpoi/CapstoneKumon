<?php
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';

    // ====================================================
    // 🔍 SEARCH STUDENTS FOR DRAWER
    // ====================================================
    if ($action === 'list') {
        $query = trim($_GET['q'] ?? '');
        $sql = "SELECT 
                    student_id, 
                    studentCode, 
                    CONCAT(Firstname, ' ', Lastname) AS full_name,
                    level,
                    plan,
                    monthlyFee
                FROM students
                WHERE Firstname LIKE ? 
                   OR Lastname LIKE ? 
                   OR studentCode LIKE ?
                ORDER BY Firstname ASC
                LIMIT 25";
        $stmt = $pdo->prepare($sql);
        $search = "%$query%";
        $stmt->execute([$search, $search, $search]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // ====================================================
    // 💰 FETCH SELECTED STUDENT PLAN + LEDGER
    // ====================================================
    if ($action === 'plan') {
        $id = $_GET['id'] ?? '';
        if ($id === '') {
            throw new Exception('Missing student ID');
        }

        // --- Get student details ---
        $sql = "SELECT 
                    student_id, 
                    studentCode, 
                    CONCAT(Firstname, ' ', Lastname) AS full_name,
                    plan,
                    monthlyFee,
                    level
                FROM students
                WHERE student_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            throw new Exception('Student not found');
        }

        // --- Get student ledger ---
        $ledgerQuery = "SELECT 
                            payment_date AS date,
                            amount,
                            remarks
                        FROM payments
                        WHERE student_id = ?
                        ORDER BY payment_date DESC";
        $ledgerStmt = $pdo->prepare($ledgerQuery);
        $ledgerStmt->execute([$id]);
        $ledger = $ledgerStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'student' => $student,
            'ledger' => $ledger
        ]);
        exit;
    }

    throw new Exception('Invalid or missing action.');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
