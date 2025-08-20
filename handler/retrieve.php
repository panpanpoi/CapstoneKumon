<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kumon_ortigas_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search term from query string
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";

// Query
if ($search !== "") {
    $sql = "SELECT * FROM users 
            WHERE Name LIKE '%$search%' 
               OR Surname LIKE '%$search%' 
               OR account_type LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM users";
}

$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
exit();
