<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Handle actions (user update or image actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User actions
    if (isset($_POST['user_id']) && $_POST['user_id'] != $_SESSION['user_id']) {
        $uid = $_POST['user_id'];
        if ($_POST['action'] === 'toggle_active') {
            $pdo->prepare("UPDATE users SET active = NOT active WHERE user_id = ?")->execute([$uid]);
        } elseif ($_POST['action'] === 'promote') {
            $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?")->execute([$uid]);
        } elseif ($_POST['action'] === 'demote') {
            $pdo->prepare("UPDATE users SET role = 'player' WHERE user_id = ?")->execute([$uid]);
        }
    }

    // Image actions
    if (isset($_POST['image_id'])) {
        $imgId = $_POST['image_id'];
        if ($_POST['action'] === 'toggle') {
            $pdo->prepare("UPDATE background_images SET is_active = NOT is_active WHERE image_id = ?")->execute([$imgId]);
        } elseif ($_POST['action'] === 'delete') {
            $pdo->prepare("DELETE FROM background_images WHERE image_id = ?")->execute([$imgId]);
        }
    }

    header("Location: admin.php");
    exit();
}

// Fetch data
$users = $pdo->query("SELECT * FROM users ORDER BY registered_at DESC")->fetchAll();
$images = $pdo->query("
    SELECT bg.*, u.username FROM background_images bg
    JOIN users u ON bg.uploaded_by_user_id = u.user_id
    ORDER BY bg.upload_time DESC
")->fetchAll();
$stats = $pdo->query("
    SELECT s.*, u.username, bg.filename AS bg_filename
    FROM game_stats s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN background_images bg ON s.background_image_id = bg.image_id
    ORDER BY s.game_date DESC
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Panel</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table { width: 100%; border-collapse: collapse; margin: 30px 0; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background: #eee; }
    img { max-height: 50px; }
    button { margin: 0 2px; }
  </style>
</head>
<body>
  <h1>Admin Dashboard</h1>
  <p><a href="fifteen.php">← Back to Puzzle</a></p>

  <h2>👥 User Management</h2>
  <table>
    <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= $u['user_id'] ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= $u['role'] ?></td>
        <td><?= $u['active'] ? 'Active' : 'Deactivated' ?></td>
        <td>
          <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
              <button name="action" value="toggle_active"><?= $u['active'] ? 'Deactivate' : 'Activate' ?></button>
              <?php if ($u['role'] === 'player'): ?>
                <button name="action" value="promote">Promote to Admin</button>
              <?php elseif ($u['role'] === 'admin'): ?>
                <button name="action" value="demote">Demote to Player</button>
              <?php endif; ?>
            </form>
          <?php else: ?>
            <em>(You)</em>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h2>🖼️ Uploaded Background Images</h2>
  <table>
    <tr><th>Preview</th><th>Filename</th><th>Uploader</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr>
    <?php foreach ($images as $img): ?>
      <tr>
        <td><img src="uploads/<?= htmlspecialchars($img['filename']) ?>"></td>
        <td><?= htmlspecialchars($img['filename']) ?></td>
        <td><?= htmlspecialchars($img['username']) ?></td>
        <td><?= $img['is_active'] ? 'Active' : 'Disabled' ?></td>
        <td><?= $img['upload_time'] ?></td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="image_id" value="<?= $img['image_id'] ?>">
            <button name="action" value="toggle"><?= $img['is_active'] ? 'Disable' : 'Enable' ?></button>
            <button name="action" value="delete" onclick="return confirm('Delete this image?')">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h2>📊 Game Statistics (Recent)</h2>
  <table>
    <tr><th>Player</th><th>Time (s)</th><th>Moves</th><th>Win</th><th>Background</th><th>Date</th></tr>
    <?php foreach ($stats as $s): ?>
      <tr>
        <td><?= htmlspecialchars($s['username']) ?></td>
        <td><?= $s['time_taken_seconds'] ?></td>
        <td><?= $s['moves_count'] ?></td>
        <td><?= $s['win_status'] ? '✅' : '❌' ?></td>
        <td><?= $s['bg_filename'] ? htmlspecialchars($s['bg_filename']) : 'Default' ?></td>
        <td><?= $s['game_date'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>
</html>