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

// Load available backgrounds
$bgImages = $pdo->query("SELECT * FROM background_images WHERE is_active = 1 ORDER BY upload_time DESC")->fetchAll();

// Load user preferred background if logged in
$preferred_bg = "";
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT preferred_background FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $prefs = $stmt->fetch();
    if ($prefs && !empty($prefs['preferred_background'])) {
        $preferred_bg = $prefs['preferred_background'];
    }
} else {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
  <script src="fifteen.js" defer></script>
</head>
<body>
    <nav style="background: #333; padding: 12px;">
        <ul style="list-style: none; display: flex; justify-content: center; gap: 20px; margin: 0; padding: 0;">

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="admin.php" style="background: #2ecc71; color: #fff; padding: 8px 20px; border-radius: 5px; text-decoration: none;">Admin Panel</a></li>
            <?php endif; ?>

            <li><a href="upload_image.php" style="background: #3498db; color: #fff; padding: 6px 16px; border-radius: 5px; text-decoration: none;">Upload Image</a></li>

            <li><a href="preferences.php" style="background: #9b59b6; color: #fff; padding: 6px 16px; border-radius: 5px; text-decoration: none;">Preferences</a></li>

            <li><a href="logout.php" style="background: #e74c3c; color: #fff; padding: 8px 20px; border-radius: 5px; text-decoration: none;">Logout</a></li>

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
                <?= ($preferred_bg === $bg['filename']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($bg['filename']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <div>
        <div id="puzzlearea">
            <div class="controls">
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
            <a href="https://jslint.com/">
                <img src="./img/valid-jslint.png" alt="JSLint Validator" style="width:100px; height:42px;">
            </a>
        </div>
    </div>
    <div id="win-message">
        🎉 Congratulations, Puzzle solved! 🎉
    </div>
</body>
</html>

