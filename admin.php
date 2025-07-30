<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  die("Access denied. Admins only.");
}

// Handle image actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["image_id"])) {
  $imgId = $_POST["image_id"];
  $action = $_POST["action"];

  if ($action === "toggle") {
    $pdo->prepare("UPDATE background_images SET is_active = NOT is_active WHERE image_id = ?")->execute([$imgId]);
  } elseif ($action === "delete") {
    $pdo->prepare("DELETE FROM background_images WHERE image_id = ?")->execute([$imgId]);
  }

  header("Location: admin.php");
  exit();
}

// Load users
$users = $pdo->query("SELECT * FROM users ORDER BY registered_at DESC")->fetchAll();

// Load backgrounds + uploader names
$images = $pdo->query("
  SELECT background_images.*, users.username 
  FROM background_images 
  JOIN users ON background_images.uploaded_by_user_id = users.user_id 
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
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background-color: #f5f5f5; }
    h1, h2 { color: #333; }
    img { height: 80px; }
    .btn { padding: 4px 12px; border: none; cursor: pointer; border-radius: 4px; }
    .btn-toggle { background: #2980b9; color: white; }
    .btn-delete { background: #e74c3c; color: white; }
  </style>
</head>
<body>

<h1>Admin Dashboard</h1>
<p>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (Admin)</p>
<p><a href="fifteen.php">← Back to Puzzle</a></p>

<h2>Registered Users</h2>
<table>
  <tr>
    <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th>
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

<h2>Uploaded Background Images</h2>
<table>
  <tr>
    <th>ID</th><th>Image</th><th>Filename</th><th>Uploader</th><th>Status</th><th>Uploaded</th><th>Actions</th>
  </tr>
  <?php foreach ($images as $img): ?>
  <tr>
    <td><?= $img['image_id'] ?></td>
    <td><img src="<?= htmlspecialchars($img['image_url']) ?>"></td>
    <td><?= htmlspecialchars($img['filename']) ?></td>
    <td><?= htmlspecialchars($img['username']) ?></td>
    <td><?= $img['is_active'] ? 'Active' : 'Disabled' ?></td>
    <td><?= $img['upload_time'] ?></td>
    <td>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="image_id" value="<?= $img['image_id'] ?>">
        <button name="action" value="toggle" class="btn btn-toggle">Toggle</button>
        <button name="action" value="delete" class="btn btn-delete" onclick="return confirm('Delete this image?')">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

</body>
</html>