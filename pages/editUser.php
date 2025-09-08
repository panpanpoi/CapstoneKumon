<?php
require_once __DIR__ . '/../database.php';

// STEP 1: Check if form is submitted (update mode)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['user_id'] ?? null;
    $name = $_POST['Name'] ?? '';
    $surname = $_POST['Surname'] ?? '';
    $address = $_POST['Address'] ?? '';
    $mobileNumber = $_POST['mobileNumber'] ?? '';
    $account_type = $_POST['account_type'] ?? '';

    if (!$id) {
        echo json_encode(['error' => 'User ID is required.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users 
                           SET Name = :name, Surname = :surname, Address = :address, 
                               mobileNumber = :mobile, account_type = :account_type
                           WHERE user_id = :id");
    $stmt->execute([
        ':name' => $name,
        ':surname' => $surname,
        ':address' => $address,
        ':mobile' => $mobileNumber,
        ':account_type' => $account_type,
        ':id' => $id
    ]);

    echo "<script>alert('User updated successfully!'); window.location.href='/pages/accountlist.html';</script>";
    exit;
}

// STEP 2: Load user info (edit mode)
$id = $_GET['id'] ?? null;
if (!$id) {
    die("User ID is required in URL.");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit User</title>
</head>
<body>
  <h2>Edit User</h2>
  <form method="POST">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>">

    <label>Name:</label>
    <input type="text" name="Name" value="<?= htmlspecialchars($user['Name']) ?>" required><br>

    <label>Surname:</label>
    <input type="text" name="Surname" value="<?= htmlspecialchars($user['Surname']) ?>" required><br>

    <label>Address:</label>
    <input type="text" name="Address" value="<?= htmlspecialchars($user['Address']) ?>"><br>

    <label>Mobile Number:</label>
    <input type="text" name="mobileNumber" value="<?= htmlspecialchars($user['mobileNumber']) ?>"><br>

    <label>Account Type:</label>
    <select name="account_type" required>
      <option value="student" <?= $user['account_type'] === 'student' ? 'selected' : '' ?>>Student</option>
      <option value="admin" <?= $user['account_type'] === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select><br>

    <button type="submit">Save</button>
    <a href="accountlist.html">Cancel</a>
  </form>
</body>
</html>
