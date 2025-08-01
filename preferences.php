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

$message = '';
$messageType = '';

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

  $message = "✅ Preferences saved successfully!";
  $messageType = 'success';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Preferences - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .preferences-container {
      max-width: 800px;
      margin: 60px auto;
      padding: 20px;
    }
    
    .preferences-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 50px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .preferences-header {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .preferences-header h1 {
      font-size: 3rem;
      font-weight: 700;
      background: linear-gradient(135deg, #a78bfa, #8b5cf6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 15px;
    }
    
    .preferences-header p {
      color: #666;
      font-size: 1.3rem;
      line-height: 1.6;
    }
    
    .preferences-form {
      display: grid;
      gap: 30px;
    }
    
    .form-section {
      background: rgba(167, 139, 250, 0.05);
      border-radius: 15px;
      padding: 30px;
      border-left: 4px solid #a78bfa;
    }
    
    .form-section h3 {
      font-size: 1.5rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .form-group {
      margin-bottom: 25px;
    }
    
    .form-group label {
      display: block;
      font-size: 1.2rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 12px;
    }
    
    .form-select {
      width: 100%;
      padding: 15px 20px;
      border: 2px solid #ddd;
      border-radius: 12px;
      font-size: 1.1rem;
      background: white;
      color: #333;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .form-select:focus {
      outline: none;
      border-color: #a78bfa;
      box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
    }
    
    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .checkbox-group input[type="checkbox"] {
      width: 24px;
      height: 24px;
      accent-color: #a78bfa;
      cursor: pointer;
    }
    
    .checkbox-group label {
      font-size: 1.2rem;
      font-weight: 600;
      color: #333;
      cursor: pointer;
      margin: 0;
    }
    
    .save-btn {
      background: linear-gradient(135deg, #a78bfa, #8b5cf6);
      color: white;
      border: none;
      padding: 18px 40px;
      font-size: 1.3rem;
      font-weight: 600;
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(167, 139, 250, 0.3);
      width: 100%;
      margin-top: 20px;
    }
    
    .save-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(167, 139, 250, 0.4);
    }
    
    .back-btn {
      display: inline-block;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 15px 30px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      margin-top: 30px;
      text-align: center;
    }
    
    .back-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .message {
      padding: 20px;
      border-radius: 15px;
      margin: 20px 0;
      font-weight: 600;
      font-size: 1.2rem;
      text-align: center;
    }
    
    .message.success {
      background: linear-gradient(135deg, #4ade80, #22c55e);
      color: white;
    }
    
    .puzzle-size-info {
      background: rgba(167, 139, 250, 0.1);
      border-radius: 12px;
      padding: 20px;
      margin-top: 15px;
    }
    
    .puzzle-size-info h4 {
      color: #8b5cf6;
      margin-bottom: 10px;
      font-size: 1.1rem;
    }
    
    .puzzle-size-info ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .puzzle-size-info li {
      padding: 5px 0;
      color: #666;
      position: relative;
      padding-left: 25px;
    }
    
    .puzzle-size-info li::before {
      content: '•';
      position: absolute;
      left: 0;
      color: #a78bfa;
      font-weight: bold;
      font-size: 1.2rem;
    }
    
    @media (max-width: 768px) {
      .preferences-container {
        margin: 20px auto;
        padding: 10px;
      }
      
      .preferences-card {
        padding: 30px 20px;
      }
      
      .preferences-header h1 {
        font-size: 2.2rem;
      }
      
      .preferences-header p {
        font-size: 1.1rem;
      }
      
      .form-section {
        padding: 20px;
      }
      
      .form-group label {
        font-size: 1.1rem;
      }
      
      .checkbox-group label {
        font-size: 1.1rem;
      }
    }
  </style>
</head>
<body>
  <div class="preferences-container">
    <div class="preferences-card">
      <div class="preferences-header">
        <h1>⚙️ Puzzle Preferences</h1>
        <p>Customize your puzzle gaming experience</p>
      </div>

      <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
          <?= $message ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="preferences-form">
        <div class="form-section">
          <h3>🎯 Puzzle Settings</h3>
          
          <div class="form-group">
            <label for="puzzle_size">Default Puzzle Size:</label>
            <select name="puzzle_size" id="puzzle_size" class="form-select">
              <?php foreach (['4x4', '5x5', '6x6'] as $option): ?>
                <option value="<?= $option ?>" <?= $prefs['default_puzzle_size'] === $option ? 'selected' : '' ?>><?= $option ?></option>
              <?php endforeach; ?>
            </select>
            
            <div class="puzzle-size-info">
              <h4>📏 Size Guide:</h4>
              <ul>
                <li><strong>4x4:</strong> Classic 15-puzzle (16 tiles)</li>
                <li><strong>5x5:</strong> Advanced puzzle (25 tiles)</li>
                <li><strong>6x6:</strong> Expert challenge (36 tiles)</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>🖼️ Visual Preferences</h3>
          
          <div class="form-group">
            <label for="background">Preferred Background Image:</label>
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
          <h3>🔊 Experience Settings</h3>
          
          <div class="checkbox-group">
            <input type="checkbox" name="sound_enabled" id="sound_enabled" <?= $prefs['sound_enabled'] ? 'checked' : '' ?>>
            <label for="sound_enabled">🔊 Enable Sound Effects</label>
          </div>
          
          <div class="checkbox-group">
            <input type="checkbox" name="animations_enabled" id="animations_enabled" <?= $prefs['animations_enabled'] ? 'checked' : '' ?>>
            <label for="animations_enabled">✨ Enable Animations</label>
          </div>
        </div>

        <button type="submit" class="save-btn">💾 Save Preferences</button>
      </form>

      <div style="text-align: center;">
        <a href="fifteen.php" class="back-btn">← Back to Puzzle Game</a>
      </div>
    </div>
  </div>
</body>
</html>