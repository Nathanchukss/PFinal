<?php
session_start();
require 'db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $targetId = intval($_POST['user_id']);
    if ($_POST['action'] === 'toggle_active') {
        $pdo->prepare("UPDATE users SET active = NOT active WHERE user_id = ?")->execute([$targetId]);
    } elseif ($_POST['action'] === 'make_admin') {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?")->execute([$targetId]);
    } elseif ($_POST['action'] === 'make_player') {
        $pdo->prepare("UPDATE users SET role = 'player' WHERE user_id = ?")->execute([$targetId]);
    }
}

// Get users
$users = $pdo->query("SELECT * FROM users ORDER BY registered_at DESC")->fetchAll();

// Get game stats
$stats = $pdo->query("
    SELECT gs.*, u.username, b.filename AS background
    FROM game_stats gs
    JOIN users u ON gs.user_id = u.user_id
    LEFT JOIN background_images b ON gs.background_image_id = b.image_id
    ORDER BY gs.game_date DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1 style="text-align:center;">Admin Dashboard</h1>
<p style="text-align:center;"><a href="fifteen.php">← Back to Game</a></p>

<!-- USERS MANAGEMENT -->
<h2 style="text-align:center;">User Management</h2>
<table border="1" style="width:100%; margin-bottom: 40px;">
  <tr>
    <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th>
  </tr>
  <?php foreach ($users as $u): ?>
  <tr>
    <td><?= $u['user_id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= $u['role'] ?></td>
    <td><?= $u['active'] ? "Active" : "Deactivated" ?></td>
    <td>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
        <button type="submit" name="action" value="<?= $u['active'] ? 'toggle_active' : 'toggle_active' ?>">
          <?= $u['active'] ? "Deactivate" : "Activate" ?>
        </button>
      </form>
      <?php if ($u['role'] === 'player'): ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
          <button type="submit" name="action" value="make_admin">Promote to Admin</button>
        </form>
      <?php elseif ($u['role'] === 'admin' && $u['user_id'] != $_SESSION['user_id']): ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
          <button type="submit" name="action" value="make_player">Demote to Player</button>
        </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<!-- GAME STATS -->
<h2 style="text-align:center;">Game Statistics</h2>
<table border="1" style="width:100%;">
  <tr>
    <th>Username</th>
    <th>Size</th>
    <th>Time (s)</th>
    <th>Moves</th>
    <th>Won?</th>
    <th>Background</th>
    <th>Date</th>
  </tr>
  <?php foreach ($stats as $s): ?>
  <tr>
    <td><?= htmlspecialchars($s['username']) ?></td>
    <td><?= $s['puzzle_size'] ?></td>
    <td><?= $s['time_taken_seconds'] ?></td>
    <td><?= $s['moves_count'] ?></td>
    <td><?= $s['win_status'] ? "Yes" : "No" ?></td>
    <td><?= htmlspecialchars($s['background'] ?? "Default") ?></td>
    <td><?= $s['game_date'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
</body>
</html>