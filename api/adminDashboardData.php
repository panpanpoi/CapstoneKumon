<?php
// 1. Turn off default error display so it doesn't break JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. Start buffering to catch any accidental whitespace
ob_start();

// ✅ UPDATED: Using your path "../database.php"
require_once "../database.php"; 

ob_clean();
header('Content-Type: application/json');

try {
    // Detect DB Driver ($pdo vs $conn)
    $driver = null;
    if (isset($pdo) && ($pdo instanceof PDO)) {
        $driver = 'pdo';
    } elseif (isset($conn) && ($conn instanceof mysqli)) {
        $driver = 'mysqli';
    } else {
        // Fallback if database.php uses different variable names
        // Try to assume $conn is available if not detected
        if (isset($conn)) $driver = 'mysqli';
        else throw new Exception("Database connection variable (\$pdo or \$conn) not found.");
    }

    // ---------------------------------------------------------
    // 1. GET STUDENT COUNTS (Total, Math, English)
    // ---------------------------------------------------------
    $mathCount = 0;
    $englishCount = 0;
    
    // We fetch all users to count them
    $sql1 = "SELECT subject FROM users"; 
    $users = [];

    if ($driver === 'pdo') {
        $stmt = $pdo->query($sql1);
        if (!$stmt) throw new Exception("Error fetching users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = $conn->query($sql1);
        if (!$result) throw new Exception("Error fetching users: " . $conn->error);
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }

    // ✅ NEW: Calculate Total Students (Unique Users)
    // Assuming every row in 'users' table is a student (or we filter by role if needed)
    $totalStudents = count($users);

    foreach ($users as $user) {
        $subject = strtolower($user['subject'] ?? '');
        if (strpos($subject, 'math') !== false) {
            $mathCount++;
        } 
        // Note: Using separate if statements allows counting both if a student takes both
        if (strpos($subject, 'english') !== false || strpos($subject, 'reading') !== false) {
            $englishCount++;
        }
    }

    // ---------------------------------------------------------
    // 2. GET LATEST 5 PAYMENTS
    // ---------------------------------------------------------
    $sql2 = "
        SELECT 
            p.amount, 
            p.payment_date, 
            p.payment_method, 
            s.Firstname, 
            s.Lastname 
        FROM payments p 
        JOIN students s ON p.student_id = s.student_id 
        ORDER BY p.payment_date DESC, p.payment_id DESC 
        LIMIT 5
    ";

    $recentPayments = [];

    if ($driver === 'pdo') {
        $stmt = $pdo->query($sql2);
        if ($stmt) $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = $conn->query($sql2);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recentPayments[] = $row;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'counts' => [
            'total'   => $totalStudents, // <--- SENT TO JS
            'math'    => $mathCount,
            'english' => $englishCount
        ],
        'recent' => $recentPayments
    ]);

} catch (Throwable $e) { 
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

ob_end_flush();
?>