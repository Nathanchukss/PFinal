<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($username && $email && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$username, $email, $hash]);
            echo "Registration successful. <a href='login.php'>Login here</a>";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "All fields are required.";
    }
}
?>

<form method="POST" class="auth-form">
  <h2>Register</h2> <!-- or "Login" depending on the page -->
  Username: <input type="text" name="username" required><br>
  Email: <input type="email" name="email" required><br> <!-- Only for register -->
  Password: <input type="password" name="password" required><br>
  <button type="submit">Register</button> <!-- or "Login" -->
  <p>Already have an account? <a href="login.php">Login here</a></p> <!-- or link to register -->
</form>