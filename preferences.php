<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch active backgrounds
$images = $pdo->query("SELECT * FROM background_images WHERE is_active = 1 ORDER BY upload_time DESC")->fetchAll();

// Fetch existing preferences
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$prefs = $stmt->fetch();

if (!$prefs) {
  // Insert initial defaults
  $pdo->prepare("INSERT INTO user_preferences (user_id, default_puzzle_size, preferred_background_image_id, sound_enabled, animations_enabled)
                 VALUES (?, '4x4', NULL, 1, 1)")->execute([$user_id]);

  $prefs = [
    'default_puzzle_size' => '4x4',
    'preferred_background_image_id' => null,
    'sound_enabled' => 1,
    'animations_enabled' => 1
  ];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Use fallback values if nothing is posted
  $size = $_POST["puzzle_size"] ?? "4x4";
  $bg_id = isset($_POST["background"]) && $_POST["background"] !== "" ? $_POST["background"] : null;
  $sound = isset($_POST["sound_enabled"]) ? 1 : 0;
  $anim = isset($_POST["animations_enabled"]) ? 1 : 0;

  $stmt = $pdo->prepare("
    UPDATE user_preferences
    SET default_puzzle_size = ?, preferred_background_image_id = ?, sound_enabled = ?, animations_enabled = ?
    WHERE user_id = ?
  ");
  $stmt->execute([$size, $bg_id, $sound, $anim, $user_id]);

  // Refresh local preferences for display
  $prefs = [
    'default_puzzle_size' => $size,
    'preferred_background_image_id' => $bg_id,
    'sound_enabled' => $sound,
    'animations_enabled' => $anim
  ];

  $message = "Preferences saved!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Preferences - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="preferences-container">
    <div class="preferences-card">
      <!-- Preferences Header -->
      <div class="preferences-header">
        <h2>Update Your Puzzle Preferences</h2>
      </div>

      <?php if (isset($message)): ?>
        <div class="preferences-message">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <!-- Preferences Form -->
      <form method="POST" class="preferences-form">
        <label for="puzzle_size">Default Puzzle Size:</label>
        <select name="puzzle_size" id="puzzle_size">
          <?php foreach (['4x4', '5x5', '6x6'] as $option): ?>
            <option value="<?= $option ?>" <?= $prefs['default_puzzle_size'] === $option ? 'selected' : '' ?>><?= $option ?></option>
          <?php endforeach; ?>
        </select>

        <label for="background">Preferred Background Image:</label>
        <select name="background" id="background">
          <option value="">Default</option>
          <?php foreach ($images as $img): ?>
            <option value="<?= $img['image_id'] ?>" <?= $prefs['preferred_background_image_id'] == $img['image_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($img['display_name'] ?: $img['filename']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="checkbox-group">
          <label>
            <input type="checkbox" name="sound_enabled" <?= $prefs['sound_enabled'] ? 'checked' : '' ?>>
            Enable Sound Effects
          </label>
          <label>
            <input type="checkbox" name="animations_enabled" <?= $prefs['animations_enabled'] ? 'checked' : '' ?>>
            Enable Animations
          </label>
        </div>

        <button type="submit">Save Preferences</button>
      </form>

      <div style="text-align: center; margin-top: 30px;">
        <a href="fifteen.php" class="back-btn">← Back to Game</a>
      </div>
    </div>
  </div>
</body>
</html>