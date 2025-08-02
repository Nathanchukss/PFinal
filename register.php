<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $email = trim($_POST["email"]);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role, registration_date, last_login)
                               VALUES (?, ?, ?, 'player', ?, ?)");
        $stmt->execute([$username, $hashedPassword, $email, $now, $now]);

        // Redirect on success
        header("Location: login.php");
        exit;

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            // Check which field caused the duplicate entry
            if (str_contains($e->errorInfo[2], 'username')) {
                $error = "Username already exists. Please choose another.";
            } elseif (str_contains($e->errorInfo[2], 'email')) {
                $error = "An account with this email already exists.";
            } else {
                $error = "Duplicate entry. Please try a different username or email.";
            }
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Register - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<form method="POST" class="auth-form">
  <h2>Register</h2>

  <?php if (isset($error)): ?>
    <p style="color: red; text-align:center;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <label>Username:</label>
  <input type="text" name="username" required>

  <label>Email:</label>
  <input type="email" name="email" required>

  <label>Password:</label>
  <input type="password" name="password" required>

  <button type="submit">Register</button>
  <p>Already have an account? <a href="login.php">Login here</a></p>
</form>
</body>
</html>