<?php
session_start();
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION["user_id"])) exit;

$user_id = $_SESSION["user_id"];
$time_taken = intval($data["time_taken"] ?? 0);
$moves = intval($data["moves"] ?? 0);
$win = $data["win"] ? 1 : 0;
$bgPath = basename($data["background"]); // get file name only

// Get background image ID
$stmt = $pdo->prepare("SELECT image_id FROM background_images WHERE filename = ?");
$stmt->execute([$bgPath]);
$bgRow = $stmt->fetch();
$bg_id = $bgRow ? $bgRow["image_id"] : null;

// Insert game stat
$stmt = $pdo->prepare("INSERT INTO game_stats (user_id, time_taken_seconds, moves_count, win_status, background_image_id)
                       VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$user_id, $time_taken, $moves, $win, $bg_id]);

echo "Saved!";
?>