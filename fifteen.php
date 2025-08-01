<?php
session_start();
require 'db.php';

// Check for cookie auto-login
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

// Block guests
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Load available backgrounds
$bgImages = $pdo->query("SELECT * FROM background_images WHERE is_active = 1 ORDER BY upload_time DESC")->fetchAll();

// Load user preferences
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$prefs = $stmt->fetch();

$imageMap = [];
foreach ($bgImages as $img) {
    $imageMap[$img['image_id']] = $img['filename'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
  <script>
    const USER_PREFS = {
      size: <?= json_encode($prefs['default_puzzle_size'] ?? '4x4') ?>,
      background: <?= json_encode(($prefs['preferred_background_image_id'] ?? '') ? 'uploads/' . $imageMap[$prefs['preferred_background_image_id']] : '') ?>,
      sound: <?= isset($prefs['sound_enabled']) && $prefs['sound_enabled'] ? 'true' : 'false' ?>,
      animations: <?= isset($prefs['animations_enabled']) && $prefs['animations_enabled'] ? 'true' : 'false' ?>
    };
  </script>
  <script src="fifteen.js" defer></script>
</head>
<body>
  <nav>
    <ul>
      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li><a href="admin.php" class="nav-btn admin-btn">Admin Panel</a></li>
      <?php endif; ?>
      <li><a href="upload_image.php" class="nav-btn upload-btn">Upload Image</a></li>
      <li><a href="preferences.php" class="nav-btn preferences-btn">Preferences</a></li>
      <li><a href="logout.php" class="nav-btn logout-btn">Logout</a></li>
    </ul>
  </nav>

  <header>
    <h1>Fifteen Puzzle</h1>
  </header>

  <section class="intro">
    <div>
      The goal of the fifteen puzzle is to un-jumble its fifteen squares
      by repeatedly making moves that slide squares into the empty space.
      How quickly can you solve it? Click "Shuffle" to begin
    </div>
  </section><br>

  <form style="text-align:center;">
    <label for="bg-select"><strong>Select Image:</strong></label>
    <select id="bg-select" onchange="changeBackground(this.value)">
      <option value="">Default</option>
      <?php foreach ($bgImages as $bg): ?>
        <option value="uploads/<?= htmlspecialchars($bg['filename']) ?>"
          <?= (isset($prefs['preferred_background_image_id']) && $bg['image_id'] == $prefs['preferred_background_image_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($bg['filename']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <div>
    <div id="puzzlearea">
      <div class="controls"></div>
    </div>
  </div>

  <div style="text-align: center;">
    <button id="shufflebutton">Shuffle</button>
  </div><br>

  <section class="history">
    <div>
      American puzzle author and mathematician Sam Loyd is often falsely credited with creating the puzzle;
      indeed, Loyd claimed from 1891 until his death in 1911 that he invented it.
      The puzzle was actually created around 1874 by Noyes Palmer Chapman, a postmaster in Canastota, New York.
    </div>
  </section><br>

  <div class="validators">
    <div class="validators" style="position: fixed; bottom: 10px; right: 10px; z-index: 100;">
      <a href="https://validator.w3.org/"><img src="./img/valid-xhtml11.png" alt="HTML Validator"></a>
      <a href="https://jigsaw.w3.org/css-validator/"><img src="./img/valid-css.png" alt="CSS Validator"></a>
      <a href="https://jslint.com/"><img src="./img/valid-jslint.png" alt="JSLint Validator" style="width:100px; height:42px;"></a>
    </div>
  </div>

  <div id="win-message">
    <div class="win-content">
      <p>🎉 Congratulations, Puzzle solved! 🎉</p>
      <div class="win-actions">
        <button id="play-again-btn">🔁 Play Again</button>
        <button id="close-win-btn">❌ Close</button>
      </div>
    </div>
  </div>
</body>
</html>