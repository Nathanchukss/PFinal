<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Handle actions (user update or image actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Player actions
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
            // Use ON DELETE SET NULL strategy, so we can safely delete
            $pdo->prepare("DELETE FROM background_images WHERE image_id = ?")->execute([$imgId]);
        }
    }

    header("Location: admin.php");
    exit();
}

// Fetch data
$players = $pdo->query("SELECT * FROM users ORDER BY registered_at DESC")->fetchAll();
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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="admin-container">
    <!-- Admin Header -->
    <div class="admin-header">
      <h1>Admin Dashboard</h1>
      <p>Manage players, images, and view game statistics</p>
      <a href="fifteen.php">← Back to Puzzle</a>
    </div>

    <!-- Player Management Section -->
    <div class="admin-section">
      <h2>Player Management</h2>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($players as $p): ?>
            <tr>
              <td><?= $p['user_id'] ?></td>
              <td><?= htmlspecialchars($p['username']) ?></td>
              <td><?= htmlspecialchars($p['email']) ?></td>
              <td>
                <span class="<?= $p['role'] === 'admin' ? 'status-admin' : 'status-player' ?>">
                  <?= ucfirst($p['role']) ?>
                </span>
              </td>
              <td>
                <span class="<?= $p['active'] ? 'status-active' : 'status-inactive' ?>">
                  <?= $p['active'] ? 'Active' : 'Deactivated' ?>
                </span>
              </td>
              <td>
                <?php if ($p['user_id'] != $_SESSION['user_id']): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                    <button name="action" value="toggle_active" class="admin-btn <?= $p['active'] ? 'warning' : '' ?>">
                      <?= $p['active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                    <?php if ($p['role'] === 'player'): ?>
                      <button name="action" value="promote" class="admin-btn">
                        Promote to Admin
                      </button>
                    <?php elseif ($p['role'] === 'admin'): ?>
                      <button name="action" value="demote" class="admin-btn warning">
                        Demote to Player
                      </button>
                    <?php endif; ?>
                  </form>
                <?php else: ?>
                  <em style="color: #666;">(You)</em>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Background Images Section -->
    <div class="admin-section">
      <h2>Uploaded Background Images</h2>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Display Name</th>
            <th>Uploader</th>
            <th>Status</th>
            <th>Uploaded</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($images as $img): ?>
            <tr>
              <td><img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Background preview" width="100"></td>
              <?php
                $label = $img['display_name'] ?? $img['filename'];
              ?>
              <td><strong><?= htmlspecialchars($label) ?></strong></td>
              <td><?= htmlspecialchars($img['username']) ?></td>
              <td>
                <span class="<?= $img['is_active'] ? 'status-active' : 'status-inactive' ?>">
                  <?= $img['is_active'] ? 'Active' : 'Disabled' ?>
                </span>
              </td>
              <td><?= date('M j, Y', strtotime($img['upload_time'])) ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="image_id" value="<?= $img['image_id'] ?>">
                  <button name="action" value="toggle" class="admin-btn <?= $img['is_active'] ? 'warning' : '' ?>">
                    <?= $img['is_active'] ? 'Disable' : 'Enable' ?>
                  </button>
                  <button name="action" value="delete" class="admin-btn danger" onclick="return confirm('Delete this image?')">
                    Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Game Statistics Section -->
    <div class="admin-section">
      <h2>Game Statistics (Recent 50 Games)</h2>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Player</th>
            <th>Time (s)</th>
            <th>Moves</th>
            <th>Win</th>
            <th>Background</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($stats as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['username']) ?></td>
              <td><?= $s['time_taken_seconds'] ?></td>
              <td><?= $s['moves_count'] ?></td>
              <td>
                <span class="<?= $s['win_status'] ? 'status-active' : 'status-inactive' ?>">
                  <?= $s['win_status'] ? '✅' : '❌' ?>
                </span>
              </td>
              <td><?= $s['bg_filename'] ? htmlspecialchars($s['bg_filename']) : 'Default' ?></td>
              <td><?= date('M j, Y', strtotime($s['game_date'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>