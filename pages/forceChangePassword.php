<?php
require_once "../database.php";
session_start();

// Redirect if not logged in or unauthorized
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: loginform.php?error=Unauthorized access");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = trim($_POST['newPassword'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    if ($newPassword === "" || $confirmPassword === "") {
        $error = "Please fill in all fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Update password + clear mustChangePassword flag
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, mustChangePassword = 0 
            WHERE user_id = ?
        ");
        $stmt->execute([
            password_hash($newPassword, PASSWORD_DEFAULT),
            $_SESSION['temp_user_id']
        ]);

        // Destroy the temporary session
        unset($_SESSION['temp_user_id']);

        // Redirect to login page with success message
        header("Location: loginform.php?success=Password changed successfully. Please log in.");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password | Kumon Portal</title>
  <link rel="icon" type="image/png" href="../styles/kumonIcon.png">
  <link rel="stylesheet" href="../styles/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="login-container">
    <div class="background-logo"></div> 
    
    <div class="login-box">
      <img src="../styles/kumonLogo.png" alt="Kumon Logo" class="login-logo">

      <h2 style="margin-bottom: 10px;">Change Your Password</h2>
      <p style="font-size: 0.9em; color: #555; margin-bottom: 15px;">
        For security, you must set a new password before continuing.
      </p>

      <?php if ($error): ?>
        <p class="error-message"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      <?php if ($success): ?>
        <p class="success-message"><?= htmlspecialchars($success) ?></p>
      <?php endif; ?>

      <form method="POST" novalidate>
        <label for="newPassword">New Password</label>
        <div class="password-group">
          <input type="password" id="newPassword" name="newPassword" required>
          <button type="button" id="toggleNew" aria-label="Show password">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>

        <label for="confirmPassword">Confirm Password</label>
        <div class="password-group">
          <input type="password" id="confirmPassword" name="confirmPassword" required>
          <button type="button" id="toggleConfirm" aria-label="Show password">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>

        <button type="submit" class="login-btn">Update Password</button>
      </form>
    </div>
  </div>

  <script>
    // Toggle for both password fields
    function setupToggle(inputId, buttonId) {
      const input = document.getElementById(inputId);
      const button = document.getElementById(buttonId);
      const icon = button.querySelector("i");

      button.addEventListener("click", () => {
        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        icon.className = isPassword ? "fa-solid fa-eye-slash" : "fa-solid fa-eye";
        button.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
      });
    }

    setupToggle("newPassword", "toggleNew");
    setupToggle("confirmPassword", "toggleConfirm");
  </script>
</body>
</html>
