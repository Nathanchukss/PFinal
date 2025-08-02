<?php
session_start();
require 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "You must be logged in."]);
    exit;
}

// Decode JSON input sent via fetch()
$data = json_decode(file_get_contents("php://input"), true);

// Extract values safely
$user_id     = $_SESSION["user_id"];
$time_taken  = intval($data["time_taken"] ?? 0);
$moves       = intval($data["moves"] ?? 0);
$win         = !empty($data["win"]) ? 1 : 0;
$bgPath      = basename($data["background"] ?? ""); // strip path, just filename

// Lookup background_image_id from filename
$bg_id = null;
if ($bgPath !== "") {
    $stmt = $pdo->prepare("SELECT image_id FROM background_images WHERE filename = ?");
    $stmt->execute([$bgPath]);
    $row = $stmt->fetch();
    if ($row) {
        $bg_id = $row["image_id"];
    }
}

// Insert the game stats
$stmt = $pdo->prepare("
    INSERT INTO game_stats (user_id, time_taken_seconds, moves_count, win_status, background_image_id)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$user_id, $time_taken, $moves, $win, $bg_id]);

// Respond with success
echo json_encode(["success" => true]);
?>