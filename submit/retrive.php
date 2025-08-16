<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kumon_ortigas_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query
$sql = "SELECT * FROM users";
$result = $conn->query($sql);
// Removed echo $result; as it causes errors

$data = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($data);

exit();
