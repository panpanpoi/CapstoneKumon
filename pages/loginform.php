<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>KUMON Login</title>
</head>
<body>
  <h2>Login</h2>

  <!-- Error message -->
  <?php if (isset($_GET['error'])): ?>
    <p style="color:red;"><?= htmlspecialchars($_GET['error']); ?></p>
  <?php endif; ?>

  <form action="../handler/login.php" method="POST">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required><br><br>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required><br><br>

    <button type="submit">Login</button>
  </form>
</body>
</html>
