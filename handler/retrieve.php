<?php
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json');

try {
    // --- Get query params ---
    $search   = isset($_GET['search']) ? trim($_GET['search']) : '';
    $archived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;
    $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit    = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset   = ($page - 1) * $limit;

    // --- Base query ---
    $sql = "SELECT user_id, Name, Surname, Address, mobileNumber, account_type, status
            FROM users
            WHERE 1=1";

    $params = [];

    // --- Filter by status ---
    if ($archived === 1) {
        $sql .= " AND status = 'archived'";
    } else {
        $sql .= " AND (status IS NULL OR status != 'archived')";
    }

    // --- Search filter ---
    if ($search !== '') {
        $sql .= " AND (Name LIKE :search OR Surname LIKE :search OR mobileNumber LIKE :search OR account_type LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // --- Count total rows for pagination ---
    $countSql = "SELECT COUNT(*) FROM ($sql) AS tmp";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    // --- Add pagination to query ---
    $sql .= " ORDER BY user_id ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $users,
        'meta' => [
            'pagination' => [
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRows' => $totalRows,
                'limit' => $limit
            ]
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
