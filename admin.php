<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['auth_token'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['auth_token']]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
    }
}

// Block non-admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  die("Access denied. Admins only.");
}

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY registered_at DESC")->fetchAll();

// Get all uploaded backgrounds
$images = $pdo->query("
  SELECT background_images.*, users.username 
  FROM background_images 
  JOIN users ON background_images.uploaded_by = users.user_id 
  ORDER BY upload_time DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background-color: #f5f5f5; }
    h2 { color: #333; }
  </style>
</head>
<body>

<h1>Admin Dashboard</h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (Admin)</p>
<p><a href="fifteen.php">← Back to Puzzle</a></p>

<h2>Registered Users</h2>
<table>
  <tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Role</th>
    <th>Registered</th>
  </tr>
  <?php foreach ($users as $user): ?>
  <tr>
    <td><?= $user['user_id'] ?></td>
    <td><?= htmlspecialchars($user['username']) ?></td>
    <td><?= htmlspecialchars($user['email']) ?></td>
    <td><?= $user['role'] ?></td>
    <td><?= $user['registered_at'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<h2>Uploaded Backgrounds</h2>
<table>
  <tr>
    <th>ID</th>
    <th>Filename</th>
    <th>Uploader</th>
    <th>Upload Time</th>
    <th>Preview</th>
  </tr>
  <?php foreach ($images as $img): ?>
  <tr>
    <td><?= $img['image_id'] ?></td>
    <td><?= htmlspecialchars($img['filename']) ?></td>
    <td><?= htmlspecialchars($img['username']) ?></td>
    <td><?= $img['upload_time'] ?></td>
    <td><img src="uploads/<?= $img['filename'] ?>" width="80"></td>
  </tr>
  <?php endforeach; ?>
</table>

</body>
</html>