<?php
session_start();
require 'db.php';

// Check for cookie auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['auth_token'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['auth_token']]);
    $user = $stmt->fetch();

    if ($user) {
        if (!$user['active']) {
            $error = "Your account has been deactivated.";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Update last_login from cookie login too
            $now = date('Y-m-d H:i:s');
            $update = $pdo->prepare("UPDATE users SET last_login = ? WHERE user_id = ?");
            $update->execute([$now, $user['user_id']]);

            header("Location: fifteen.php");
            exit();
        }
    }
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        if (!$user['active']) {
            $error = "Your account has been deactivated.";
        } elseif (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Update last_login timestamp
            $now = date('Y-m-d H:i:s');
            $update = $pdo->prepare("UPDATE users SET last_login = ? WHERE user_id = ?");
            $update->execute([$now, $user['user_id']]);

            // Handle Remember Me
            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(16));
                setcookie("auth_token", $token, time() + (86400 * 30), "/"); // 30 days
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
                $stmt->execute([$token, $user['user_id']]);
            }

            header("Location: fifteen.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Login - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<form method="POST" class="auth-form">
  <h2>Login</h2>

  <?php if (isset($error)): ?>
    <p style="color: red; text-align:center;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <label>Username:</label>
  <input type="text" name="username" required>

  <label>Password:</label>
  <input type="password" name="password" required>

  <button type="submit">Login</button>
  <p>New here? <a href="register.php">Create an account</a></p>
</form>
</body>
</html>