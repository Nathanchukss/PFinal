<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Handle actions (user update or image actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Player actions
    if (isset($_POST['user_id']) && $_POST['user_id'] != $_SESSION['user_id']) {
        $uid = $_POST['user_id'];
        if ($_POST['action'] === 'toggle_active') {
            $pdo->prepare("UPDATE users SET active = NOT active WHERE user_id = ?")->execute([$uid]);
        } elseif ($_POST['action'] === 'promote') {
            $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?")->execute([$uid]);
        } elseif ($_POST['action'] === 'demote') {
            $pdo->prepare("UPDATE users SET role = 'player' WHERE user_id = ?")->execute([$uid]);
        }
    }

    // Image actions
    if (isset($_POST['image_id'])) {
        $imgId = $_POST['image_id'];
        if ($_POST['action'] === 'toggle') {
            $pdo->prepare("UPDATE background_images SET is_active = NOT is_active WHERE image_id = ?")->execute([$imgId]);
        } elseif ($_POST['action'] === 'delete') {
            $pdo->prepare("DELETE FROM background_images WHERE image_id = ?")->execute([$imgId]);
        }
    }

    header("Location: admin.php");
    exit();
}

// Fetch data
$players = $pdo->query("SELECT * FROM users ORDER BY registered_at DESC")->fetchAll();
$images = $pdo->query("
    SELECT bg.*, u.username FROM background_images bg
    JOIN users u ON bg.uploaded_by_user_id = u.user_id
    ORDER BY bg.upload_time DESC
")->fetchAll();
$stats = $pdo->query("
    SELECT s.*, u.username, bg.filename AS bg_filename
    FROM game_stats s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN background_images bg ON s.background_image_id = bg.image_id
    ORDER BY s.game_date DESC
    LIMIT 50
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .admin-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }

        .admin-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .back-btn {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-card h2 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .admin-table td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }

        .admin-table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .status-active {
            color: #28a745;
            font-weight: 600;
        }

        .status-disabled {
            color: #dc3545;
            font-weight: 600;
        }

        .admin-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 2px;
            transition: all 0.3s ease;
        }

        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-toggle {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        }

        .role-admin {
            color: #667eea;
            font-weight: 600;
        }

        .role-user {
            color: #6c757d;
            font-weight: 600;
        }

        .admin-table img {
            max-height: 50px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }
            
            .section-card {
                padding: 20px;
            }
            
            .admin-table {
                font-size: 0.8rem;
            }
            
            .admin-table th,
            .admin-table td {
                padding: 8px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🔧 Admin Dashboard</h1>
            <p>Manage users, images, and view game statistics</p>
            <a href="fifteen.php" class="back-btn">← Back to Puzzle</a>
        </div>

        <div class="section-card">
            <h2>👥 Player Management</h2>
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($players as $p): ?>
                    <tr>
                        <td><?= $p['user_id'] ?></td>
                        <td><?= htmlspecialchars($p['username']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td class="<?= $p['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                            <?= $p['role'] === 'admin' ? '👑 Admin' : '👤 Player' ?>
                        </td>
                        <td class="<?= $p['active'] ? 'status-active' : 'status-disabled' ?>">
                            <?= $p['active'] ? '✅ Active' : '❌ Deactivated' ?>
                        </td>
                        <td>
                            <?php if ($p['user_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                                    <button type="submit" name="action" value="toggle_active" class="admin-btn btn-toggle">
                                        <?= $p['active'] ? '🚫 Deactivate' : '✅ Activate' ?>
                                    </button>
                                    <?php if ($p['role'] === 'player'): ?>
                                        <button type="submit" name="action" value="promote" class="admin-btn">
                                            👑 Promote
                                        </button>
                                    <?php elseif ($p['role'] === 'admin'): ?>
                                        <button type="submit" name="action" value="demote" class="admin-btn">
                                            👤 Demote
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <em style="color: #667eea;">(You)</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section-card">
            <h2>🖼️ Uploaded Background Images</h2>
            <table class="admin-table">
                <tr>
                    <th>Preview</th>
                    <th>Filename</th>
                    <th>Uploader</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($images as $img): ?>
                    <tr>
                        <td><img src="uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Background preview"></td>
                        <td><?= htmlspecialchars($img['filename']) ?></td>
                        <td><?= htmlspecialchars($img['username']) ?></td>
                        <td class="<?= $img['is_active'] ? 'status-active' : 'status-disabled' ?>">
                            <?= $img['is_active'] ? '✅ Active' : '❌ Disabled' ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($img['upload_time'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="image_id" value="<?= $img['image_id'] ?>">
                                <button type="submit" name="action" value="toggle" class="admin-btn btn-toggle">
                                    <?= $img['is_active'] ? '🚫 Disable' : '✅ Enable' ?>
                                </button>
                                <button type="submit" name="action" value="delete" class="admin-btn btn-delete" 
                                        onclick="return confirm('Are you sure you want to delete this image?')">
                                    🗑️ Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section-card">
            <h2>📊 Game Statistics (Recent 50)</h2>
            <table class="admin-table">
                <tr>
                    <th>Player</th>
                    <th>Time (s)</th>
                    <th>Moves</th>
                    <th>Win</th>
                    <th>Background</th>
                    <th>Date</th>
                </tr>
                <?php foreach ($stats as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['username']) ?></td>
                        <td><?= $s['time_taken_seconds'] ?></td>
                        <td><?= $s['moves_count'] ?></td>
                        <td><?= $s['win_status'] ? '🎉 Win' : '❌ Loss' ?></td>
                        <td><?= $s['bg_filename'] ? htmlspecialchars($s['bg_filename']) : 'Default' ?></td>
                        <td><?= date('M j, Y', strtotime($s['game_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>