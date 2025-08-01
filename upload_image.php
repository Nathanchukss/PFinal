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
        $stmt = $pdo->prepare("INSERT INTO background_images (filename, image_url, is_active, uploaded_by_user_id)
        VALUES (?, ?, 1, ?)");
        $stmt->execute([$filename, $targetPath, $_SESSION['user_id']]);
        $message = "✅ Image uploaded successfully! You can now select it in the puzzle game.";
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Image - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .upload-container {
      max-width: 600px;
      margin: 60px auto;
      padding: 20px;
    }
    
    .upload-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      text-align: center;
    }
    
    .upload-header {
      margin-bottom: 30px;
    }
    
    .upload-header h2 {
      font-size: 2.2rem;
      font-weight: 700;
      background: linear-gradient(135deg, #60a5fa, #3b82f6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 10px;
    }
    
    .upload-header p {
      color: #666;
      font-size: 1.1rem;
      line-height: 1.6;
    }
    
    .upload-form {
      margin: 30px 0;
    }
    
    .file-upload-area {
      border: 3px dashed #ddd;
      border-radius: 15px;
      padding: 40px 20px;
      margin: 20px 0;
      background: rgba(96, 165, 250, 0.05);
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .file-upload-area:hover {
      border-color: #60a5fa;
      background: rgba(96, 165, 250, 0.1);
    }
    
    .file-upload-area.dragover {
      border-color: #3b82f6;
      background: rgba(59, 130, 246, 0.15);
      transform: scale(1.02);
    }
    
    .upload-icon {
      font-size: 3rem;
      color: #60a5fa;
      margin-bottom: 15px;
    }
    
    .file-input {
      display: none;
    }
    
    .file-label {
      display: block;
      cursor: pointer;
      color: #333;
      font-weight: 600;
      font-size: 1.1rem;
    }
    
    .file-info {
      color: #666;
      font-size: 0.9rem;
      margin-top: 10px;
    }
    
    .upload-btn {
      background: linear-gradient(135deg, #60a5fa, #3b82f6);
      color: white;
      border: none;
      padding: 15px 40px;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(96, 165, 250, 0.3);
      margin: 20px 0;
      width: 100%;
      max-width: 300px;
    }
    
    .upload-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(96, 165, 250, 0.4);
    }
    
    .upload-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .message {
      padding: 15px 20px;
      border-radius: 10px;
      margin: 20px 0;
      font-weight: 600;
      font-size: 1rem;
    }
    
    .message.success {
      background: linear-gradient(135deg, #4ade80, #22c55e);
      color: white;
    }
    
    .message.error {
      background: linear-gradient(135deg, #f87171, #ef4444);
      color: white;
    }
    
    .back-btn {
      display: inline-block;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 12px 24px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      margin-top: 20px;
    }
    
    .back-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .requirements {
      background: rgba(96, 165, 250, 0.1);
      border-radius: 15px;
      padding: 20px;
      margin: 20px 0;
      text-align: left;
    }
    
    .requirements h3 {
      color: #3b82f6;
      margin-bottom: 10px;
      font-size: 1.1rem;
    }
    
    .requirements ul {
      list-style: none;
      padding: 0;
    }
    
    .requirements li {
      padding: 5px 0;
      color: #666;
      position: relative;
      padding-left: 25px;
    }
    
    .requirements li::before {
      content: '✓';
      position: absolute;
      left: 0;
      color: #4ade80;
      font-weight: bold;
    }
    
    @media (max-width: 768px) {
      .upload-container {
        margin: 20px auto;
        padding: 10px;
      }
      
      .upload-card {
        padding: 30px 20px;
      }
      
      .upload-header h2 {
        font-size: 1.8rem;
      }
    }
  </style>
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
    // File input handling
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

    // Drag and drop functionality
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