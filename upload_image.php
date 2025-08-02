<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  die("You must be logged in to upload an image.");
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bg_image'])) {
  $file = $_FILES['bg_image'];
  $customName = trim($_POST['display_name']) ?: '';

  // Accept only image types
  $allowed = ['image/jpeg', 'image/png', 'image/gif'];
  if (!in_array($file['type'], $allowed)) {
    $message = "❌ Only JPG, PNG, or GIF files are allowed.";
    $messageType = 'error';
  } elseif ($file['size'] > 2 * 1024 * 1024) {
    $message = "❌ File too large. Maximum size is 2MB.";
    $messageType = 'error';
  } elseif ($file['error'] !== UPLOAD_ERR_OK) {
    $message = "❌ File upload error occurred.";
    $messageType = 'error';
  } else {
    $filename = uniqid() . '_' . basename($file['name']);
    $targetPath = "uploads/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
      $stmt = $pdo->prepare("INSERT INTO background_images (filename, image_url, is_active, uploaded_by_user_id, display_name)
                             VALUES (?, ?, 1, ?, ?)");
      $stmt->execute([$filename, $targetPath, $_SESSION['user_id'], $customName ?: null]);

      $message = "✅ Image uploaded successfully!";
      $messageType = 'success';
    } else {
      $message = "❌ Failed to move uploaded file.";
      $messageType = 'error';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Upload Image - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="upload-container">
    <div class="upload-card">
      <div class="upload-header">
        <h2>📤 Upload Puzzle Background</h2>
        <p>Add your own custom image to use as a puzzle background</p>
      </div>

      <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
          <?= $message ?>
        </div>
      <?php endif; ?>

      <div class="requirements">
        <h3>📋 Upload Requirements</h3>
        <ul>
          <li>File format: JPG, PNG, or GIF</li>
          <li>Maximum file size: 2MB</li>
          <li>Recommended dimensions: 400x400 pixels or larger</li>
          <li>Images will be automatically resized for the puzzle</li>
        </ul>
      </div>

      <form method="POST" enctype="multipart/form-data" class="upload-form">
        <!-- Custom Display Name Input -->
        <input type="text" name="display_name" placeholder="Enter display name for puzzle image" 
               style="padding: 12px; width: 100%; border-radius: 10px; border: 2px solid #ccc; margin-bottom: 20px; box-sizing: border-box;" />

        <div class="file-upload-area" onclick="document.getElementById('bg_image').click()">
          <div class="upload-icon">📁</div>
          <label for="bg_image" class="file-label">Click to select an image</label>
          <div class="file-info">or drag and drop your image here</div>
          <input type="file" name="bg_image" id="bg_image" class="file-input" accept="image/*" required>
        </div>

        <button type="submit" class="upload-btn" id="submit-btn" disabled>
          📤 Upload Image
        </button>
      </form>

      <a href="fifteen.php" class="back-btn">← Back to Puzzle</a>
    </div>
  </div>

  <script>
    
    const fileInput = document.getElementById('bg_image');
    const uploadArea = document.querySelector('.file-upload-area');
    const submitBtn = document.getElementById('submit-btn');
    const fileLabel = document.querySelector('.file-label');
    const fileInfo = document.querySelector('.file-info');

    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        fileLabel.textContent = `Selected: ${file.name}`;
        fileInfo.textContent = `Size: ${(file.size / 1024 / 1024).toFixed(2)} MB`;
        submitBtn.disabled = false;
        uploadArea.style.borderColor = '#4ade80';
        uploadArea.style.background = 'rgba(74, 222, 128, 0.1)';
      } else {
        fileLabel.textContent = 'Click to select an image';
        fileInfo.textContent = 'or drag and drop your image here';
        submitBtn.disabled = true;
        uploadArea.style.borderColor = '#ddd';
        uploadArea.style.background = 'rgba(96, 165, 250, 0.05)';
      }
    });


    uploadArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
      e.preventDefault();
      uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
      e.preventDefault();
      uploadArea.classList.remove('dragover');

      const files = e.dataTransfer.files;
      if (files.length > 0) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
      }
    });
  </script>
</body>
</html>