<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>KUMON Login</title>
  <link rel="stylesheet" href="../styles/login.css">
  <!-- Font Awesome for Eye Icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <!-- 🔐 Login Box -->
  <div class="login-box">
    <img src="../styles/KumonLogo.png" alt="Kumon Logo" class="login-logo">

    <!-- Error message -->
    <?php if (isset($_GET['error'])): ?>
      <p class="error-message"><?= htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <form action="../handler/login.php" method="POST" novalidate>
      <!-- Username -->
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required>

      <!-- Password -->
      <label for="password">Password</label>
      <div class="password-group">
        <input type="password" id="password" name="password" required>
        <button type="button" id="togglePassword" aria-label="Show password">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>

      <!-- Submit -->
      <button type="submit" class="login-btn">Login</button>
    </form>
  </div>

  <!-- 👁️ Password Toggle Script -->
  <script>
    const passwordInput = document.getElementById("password");
    const toggleBtn = document.getElementById("togglePassword");
    const icon = toggleBtn.querySelector("i");

    toggleBtn.addEventListener("click", () => {
      const isPassword = passwordInput.type === "password";
      passwordInput.type = isPassword ? "text" : "password";
      icon.className = isPassword ? "fa-solid fa-eye-slash" : "fa-solid fa-eye";
      toggleBtn.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
    });
  </script>
</body>
</html>
