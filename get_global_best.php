<?php
session_start();
require 'db.php';

$stmt = $pdo->prepare("
  SELECT username, time_taken_seconds AS best_time, moves_count AS best_moves
  FROM game_stats
  JOIN users ON game_stats.user_id = users.user_id
  WHERE win_status = 1
  ORDER BY time_taken_seconds ASC, moves_count ASC
  LIMIT 1
");
$stmt->execute();
$result = $stmt->fetch();

echo json_encode([
  'best_user' => $result['username'] ?? null,
  'best_time' => $result['best_time'] ?? null,
  'best_moves' => $result['best_moves'] ?? null
]);