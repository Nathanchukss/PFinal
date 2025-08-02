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
  $messageType = "success";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Preferences - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .preferences-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .preferences-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      padding: 40px;
      margin: 20px 0;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .preferences-header {
      text-align: center;
      margin-bottom: 40px;
      color: #333;
    }

    .preferences-header h2 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 15px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .preferences-header p {
      font-size: 1.1rem;
      color: #666;
      margin: 0;
    }

    .preferences-form {
      max-width: 600px;
      margin: 0 auto;
    }

    .form-section {
      margin-bottom: 35px;
      padding: 25px;
      background: rgba(102, 126, 234, 0.05);
      border-radius: 15px;
      border-left: 4px solid #667eea;
    }

    .form-group {
      margin-bottom: 25px;
    }

    .form-group label {
      display: block;
      font-size: 1.2rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
    }

    .form-select {
      width: 100%;
      padding: 15px;
      border: 2px solid #e1e5e9;
      border-radius: 12px;
      font-size: 1rem;
      background: white;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .form-select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }

    .checkbox-group input[type="checkbox"] {
      width: 20px;
      height: 20px;
      accent-color: #667eea;
      cursor: pointer;
    }

    .checkbox-group label {
      font-size: 1.1rem;
      font-weight: 500;
      color: #333;
      cursor: pointer;
      margin: 0;
    }

    .save-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 25px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: block;
      margin: 30px auto;
      min-width: 200px;
    }

    .save-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .back-btn {
      display: inline-block;
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      padding: 12px 24px;
      text-decoration: none;
      border-radius: 20px;
      font-weight: 500;
      transition: all 0.3s ease;
      border: 2px solid #667eea;
    }

    .back-btn:hover {
      background: #667eea;
      color: white;
      transform: translateY(-2px);
    }

    .message {
      padding: 15px 25px;
      border-radius: 12px;
      margin-bottom: 25px;
      text-align: center;
      font-weight: 500;
      font-size: 1.1rem;
    }

    .message.success {
      background: rgba(40, 167, 69, 0.1);
      color: #155724;
      border: 2px solid #28a745;
    }

    .puzzle-size-info {
      background: rgba(102, 126, 234, 0.1);
      padding: 15px;
      border-radius: 10px;
      margin-top: 10px;
      font-size: 0.9rem;
      color: #667eea;
      border-left: 3px solid #667eea;
    }

    @media (max-width: 768px) {
      .preferences-container {
        padding: 10px;
      }
      
      .preferences-card {
        padding: 25px;
      }
      
      .preferences-header h2 {
        font-size: 2rem;
      }
      
      .form-section {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="preferences-container">
    <div class="preferences-card">
      <div class="preferences-header">
        <h2>⚙️ User Preferences</h2>
        <p>Customize your puzzle gaming experience</p>
      </div>

      <?php if (isset($message)): ?>
        <div class="message <?= $messageType ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="preferences-form">
        <div class="form-section">
          <div class="form-group">
            <label for="puzzle_size">🎯 Default Puzzle Size</label>
            <select name="puzzle_size" id="puzzle_size" class="form-select">
              <?php foreach (['4x4', '5x5', '6x6'] as $option): ?>
                <option value="<?= $option ?>" <?= $prefs['default_puzzle_size'] === $option ? 'selected' : '' ?>>
                  <?= $option ?> (<?= $option === '4x4' ? '16 pieces' : ($option === '5x5' ? '25 pieces' : '36 pieces') ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="puzzle-size-info">
              💡 Larger puzzles offer more challenge but take longer to solve
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-group">
            <label for="background">🖼️ Preferred Background Image</label>
            <select name="background" id="background" class="form-select">
              <option value="">Default Background</option>
              <?php foreach ($images as $img): ?>
                <option value="<?= $img['image_id'] ?>" <?= $prefs['preferred_background_image_id'] == $img['image_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($img['filename']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-section">
          <div class="form-group">
            <label>🔊 Audio & Visual Settings</label>
            <div class="checkbox-group">
              <input type="checkbox" name="sound_enabled" id="sound_enabled" <?= $prefs['sound_enabled'] ? 'checked' : '' ?>>
              <label for="sound_enabled">Enable Sound Effects</label>
            </div>
            <div class="checkbox-group">
              <input type="checkbox" name="animations_enabled" id="animations_enabled" <?= $prefs['animations_enabled'] ? 'checked' : '' ?>>
              <label for="animations_enabled">Enable Animations</label>
            </div>
          </div>
        </div>

        <button type="submit" class="save-btn">💾 Save Preferences</button>
      </form>

      <div style="text-align: center; margin-top: 30px;">
        <a href="fifteen.php" class="back-btn">← Back to Game</a>
      </div>
    </div>
  </div>
</body>
</html>