<?php
// db.php
// Database connection settings
$host = 'localhost'; 
$dbname = 'cnwaokocha1';
$username = 'cnwaokocha1';
$password = 'cnwaokocha1'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>