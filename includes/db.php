<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Replace with your database username
define('DB_PASS', '');   // Replace with your database password
define('DB_NAME', 'finance_tracker_db'); // Replace with your database name

// Create a PDO database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error to a file or use a more robust error handling mechanism
    error_log("Database Connection Error: " . $e->getMessage());
    // Display a user-friendly error message
    die("Could not connect to the database. Please check your configuration or contact support. Details: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
