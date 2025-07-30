<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  die("You must be logged in to upload an image.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bg_image'])) {
  $file = $_FILES['bg_image'];

  // Accept only image types
  $allowed = ['image/jpeg', 'image/png', 'image/gif'];
  if (!in_array($file['type'], $allowed)) {
    echo "Only JPG, PNG, or GIF files are allowed.";
  } elseif ($file['size'] > 2 * 1024 * 1024) {
    echo "File too large. Max 2MB.";
  } elseif ($file['error'] !== UPLOAD_ERR_OK) {
    echo "File upload error.";
  } else {
    $filename = uniqid() . '_' . basename($file['name']);
    $targetPath = "uploads/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
      $stmt = $pdo->prepare("INSERT INTO background_images (filename, uploaded_by) VALUES (?, ?)");
      $stmt->execute([$filename, $_SESSION['user_id']]);
      echo "Image uploaded successfully.";
    } else {
      echo "Failed to move uploaded file.";
    }
  }
}
?>

<h2>Upload a Puzzle Background</h2>
<form method="POST" enctype="multipart/form-data">
  <input type="file" name="bg_image" required>
  <button type="submit">Upload</button>
</form>

<a href="fifteen.php">Back to Puzzle</a>