<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  die("Access denied. Admins only.");
}

// Handle image actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["image_id"])) {
  $imgId = $_POST["image_id"];
  $action = $_POST["action"];

  if ($action === "toggle") {
    $pdo->prepare("UPDATE background_images SET is_active = NOT is_active WHERE image_id = ?")->execute([$imgId]);
  } elseif ($action === "delete") {
    $pdo->prepare("DELETE FROM background_images WHERE image_id = ?")->execute([$imgId]);
  }

  header("Location: admin.php");
  exit();
}

// Load users
$users = $pdo->query("SELECT * FROM users ORDER BY registered_at DESC")->fetchAll();

// Load backgrounds + uploader names
$images = $pdo->query("
  SELECT background_images.*, users.username 
  FROM background_images 
  JOIN users ON background_images.uploaded_by_user_id = users.user_id 
  ORDER BY upload_time DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Fifteen Puzzle</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .admin-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .admin-header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      text-align: center;
    }
    
    .admin-header h1 {
      font-size: 2.5rem;
      font-weight: 700;
      background: linear-gradient(135deg, #2ecc71, #27ae60);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 10px;
    }
    
    .admin-header p {
      color: #666;
      font-size: 1.1rem;
      margin: 10px 0;
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
      margin-top: 15px;
    }
    
    .back-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .section-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .section-card h2 {
      font-size: 1.8rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 25px;
      text-align: center;
      border-bottom: 2px solid #f0f0f0;
      padding-bottom: 15px;
    }
    
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .admin-table th {
      background: linear-gradient(135deg, #1a1a2e, #16213e);
      color: white;
      padding: 15px 12px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .admin-table td {
      padding: 15px 12px;
      border-bottom: 1px solid #f0f0f0;
      color: #333;
      font-size: 14px;
    }
    
    .admin-table tr:hover {
      background: rgba(102, 126, 234, 0.05);
    }
    
    .admin-table img {
      height: 60px;
      width: 60px;
      object-fit: cover;
      border-radius: 8px;
      border: 2px solid #ddd;
    }
    
    .status-active {
      background: linear-gradient(135deg, #4ade80, #22c55e);
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .status-disabled {
      background: linear-gradient(135deg, #f87171, #ef4444);
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .admin-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 20px;
      cursor: pointer;
      font-weight: 600;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      margin: 0 5px;
    }
    
    .btn-toggle {
      background: linear-gradient(135deg, #60a5fa, #3b82f6);
      color: white;
    }
    
    .btn-toggle:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(96, 165, 250, 0.4);
    }
    
    .btn-delete {
      background: linear-gradient(135deg, #f87171, #ef4444);
      color: white;
    }
    
    .btn-delete:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(248, 113, 113, 0.4);
    }
    
    .role-admin {
      background: linear-gradient(135deg, #4ade80, #22c55e);
      color: white;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .role-user {
      background: linear-gradient(135deg, #a78bfa, #8b5cf6);
      color: white;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    @media (max-width: 768px) {
      .admin-container {
        padding: 10px;
      }
      
      .admin-header h1 {
        font-size: 2rem;
      }
      
      .admin-table {
        font-size: 12px;
      }
      
      .admin-table th,
      .admin-table td {
        padding: 10px 8px;
      }
      
      .admin-table img {
        height: 40px;
        width: 40px;
      }
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <div class="admin-header">
      <h1>🔧 Admin Dashboard</h1>
      <p>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (Administrator)</p>
      <p>Manage users and puzzle background images</p>
      <a href="fifteen.php" class="back-btn">← Back to Puzzle</a>
    </div>

    <div class="section-card">
      <h2>👥 Registered Users</h2>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Registered</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
          <tr>
            <td>#<?= $user['user_id'] ?></td>
            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
              <span class="<?= $user['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                <?= $user['role'] ?>
              </span>
            </td>
            <td><?= date('M j, Y', strtotime($user['registered_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="section-card">
      <h2>🖼️ Uploaded Background Images</h2>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Preview</th>
            <th>Filename</th>
            <th>Uploader</th>
            <th>Status</th>
            <th>Uploaded</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($images as $img): ?>
          <tr>
            <td>#<?= $img['image_id'] ?></td>
            <td><img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Puzzle background"></td>
            <td><strong><?= htmlspecialchars($img['filename']) ?></strong></td>
            <td><?= htmlspecialchars($img['username']) ?></td>
            <td>
              <span class="<?= $img['is_active'] ? 'status-active' : 'status-disabled' ?>">
                <?= $img['is_active'] ? 'Active' : 'Disabled' ?>
              </span>
            </td>
            <td><?= date('M j, Y', strtotime($img['upload_time'])) ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="image_id" value="<?= $img['image_id'] ?>">
                <button name="action" value="toggle" class="admin-btn btn-toggle">Toggle</button>
                <button name="action" value="delete" class="admin-btn btn-delete" onclick="return confirm('Are you sure you want to delete this image?')">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>