<?php
// Database configuration - supports both MySQL and SQLite for easy deployment
$db_type = getenv('DB_TYPE') ?: 'sqlite'; // 'mysql' or 'sqlite'

try {
    if ($db_type === 'sqlite') {
        // SQLite - portable, no server required, perfect for deployment
        $db_path = getenv('DB_PATH') ?: __DIR__ . '/../finance_tracker.db';
        
        // Check if database file exists, if not, create it and initialize schema
        $db_exists = file_exists($db_path);
        
        $pdo = new PDO("sqlite:" . $db_path);
        // Enable foreign keys for SQLite
        $pdo->exec('PRAGMA foreign_keys = ON;');
        
        // Create tables if database is new
        if (!$db_exists) {
            $schema_path = __DIR__ . '/../database/schema_sqlite.sql';
            if (file_exists($schema_path)) {
                $sql = file_get_contents($schema_path);
                $pdo->exec($sql);
            }
        }
    } else {
        // MySQL configuration from environment variables
        $db_host = getenv('DB_HOST') ?: 'localhost';
        $db_user = getenv('DB_USER') ?: 'root';
        $db_pass = getenv('DB_PASS') ?: '';
        $db_name = getenv('DB_NAME') ?: 'finance_tracker_db';
        $pdo = new PDO("mysql:host=" . $db_host . ";dbname=" . $db_name . ";charset=utf8mb4", $db_user, $db_pass);
    }

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Could not connect to the database. Please check your configuration.");
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
