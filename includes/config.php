<?php
// config.php - PasteForge configuration

// Path to the SQLite database
define('DB_PATH', __DIR__ . '/../database/pastebin.db');

try {
    // Create a new PDO connection
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection error
    die("Database connection failed: " . $e->getMessage());
}
?>
