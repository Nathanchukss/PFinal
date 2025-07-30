<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch available active backgrounds
$images = $pdo->query("SELECT * FROM background_images WHERE is_active = 1 ORDER BY upload_time DESC")->fetchAll();

// Fetch or initialize user preferences
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$prefs = $stmt->fetch();

if (!$prefs) {
  $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)")->execute([$user_id]);
  $prefs = [
    'default_puzzle_size' => '4x4',
    'preferred_background_image_id' => null,
    'sound_enabled' => true,
    'animations_enabled' => true
  ];
}

// Save preferences on POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $size = $_POST["puzzle_size"];
  $bg_id = $_POST["background"];
  $sound = isset($_POST["sound_enabled"]) ? 1 : 0;
  $anim = isset($_POST["animations_enabled"]) ? 1 : 0;

  $stmt = $pdo->prepare("
    UPDATE user_preferences
    SET default_puzzle_size = ?, preferred_background_image_id = ?, sound_enabled = ?, animations_enabled = ?
    WHERE user_id = ?
  ");
  $stmt->execute([$size, $bg_id ?: null, $sound, $anim, $user_id]);

  echo "<p style='text-align:center; color:green;'>Preferences saved!</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>User Preferences</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <h2 style="text-align:center;">Update Your Puzzle Preferences</h2>

  <form method="POST" style="max-width: 400px; margin:auto;">
    <label><strong>Default Puzzle Size:</strong></label>
    <select name="puzzle_size">
      <?php foreach (['4x4', '5x5', '6x6'] as $option): ?>
        <option value="<?= $option ?>" <?= $prefs['default_puzzle_size'] === $option ? 'selected' : '' ?>><?= $option ?></option>
      <?php endforeach; ?>
    </select><br><br>

    <label><strong>Preferred Background Image:</strong></label>
    <select name="background">
      <option value="">Default</option>
      <?php foreach ($images as $img): ?>
        <option value="<?= $img['image_id'] ?>" <?= $prefs['preferred_background_image_id'] == $img['image_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($img['filename']) ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

    <label><input type="checkbox" name="sound_enabled" <?= $prefs['sound_enabled'] ? 'checked' : '' ?>> Enable Sound</label><br>
    <label><input type="checkbox" name="animations_enabled" <?= $prefs['animations_enabled'] ? 'checked' : '' ?>> Enable Animations</label><br><br>

    <button type="submit">Save Preferences</button>
  </form>

  <p style="text-align:center;"><a href="fifteen.php">← Back to Game</a></p>

</body>
</html>