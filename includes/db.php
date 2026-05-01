<?php
// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load .env file if it exists (manual parser — no external dependencies)
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
        }
    }
}

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

// Add notification_preferences column to users table if not exists (SQLite compatible)
try {
    $db_type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($db_type === 'sqlite') {
        $check = $pdo->query("PRAGMA table_info(users)");
        $columns = $check->fetchAll(PDO::FETCH_ASSOC);
        $has_prefs = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'notification_preferences') {
                $has_prefs = true;
                break;
            }
        }
        if (!$has_prefs) {
            $pdo->exec("ALTER TABLE users ADD COLUMN notification_preferences TEXT DEFAULT '{}'");
        }
    } else {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'notification_preferences'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN notification_preferences TEXT DEFAULT '{}'");
        }
    }
} catch (Exception $e) {
    // Ignore if table doesn't exist yet
}
?>