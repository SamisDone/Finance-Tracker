<?php
// Ensure db.php is included (it should be if session_start is called there)
if (session_status() == PHP_SESSION_NONE) {
    // This might be redundant if db.php is always included before this file
    // and db.php calls session_start().
    session_start(); 
}

/**
 * Registers a new user.
 * 
 * @param PDO $pdo PDO database connection object.
 * @param string $username The desired username.
 * @param string $email The user's email address.
 * @param string $password The user's password.
 * @param string $confirm_password The confirmed password.
 * @return bool|string True on success, error message string on failure.
 */
function registerUser(PDO $pdo, string $username, string $email, string $password, string $confirm_password): bool|string
{
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        return "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    if (strlen($password) < 6) { // Basic password length check
        return "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        return "Passwords do not match.";
    }

    // Check if username or email already exists
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute([':username' => $username, ':email' => $email]);
        if ($stmt->fetch()) {
            return "Username or email already taken.";
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
        $stmt->execute([':username' => $username, ':email' => $email, ':password_hash' => $password_hash]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        return "An error occurred during registration. Please try again.";
    }
}

/**
 * Logs in a user.
 * 
 * @param PDO $pdo PDO database connection object.
 * @param string $username_or_email User's username or email.
 * @param string $password User's password.
 * @return array|false User data array on success, false on failure.
 */
function loginUser(PDO $pdo, string $username_or_email, string $password): array|false
{
    if (empty($username_or_email) || empty($password)) {
        return false; // Or an error message specific to empty fields
    }

    try {
        $sql = "SELECT id, username, email, password_hash FROM users WHERE username = :identifier OR email = :identifier LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':identifier' => $username_or_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password is correct, remove password hash from returned array for security
            unset($user['password_hash']);
            return $user;
        }
        return false; // Invalid credentials
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return false; // Or a specific error indicator
    }
}

/**
 * Checks if a user is logged in.
 *
 * @return bool True if logged in, false otherwise.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Requires a user to be logged in. If not, redirects to the login page.
 * Optionally, can redirect to a specific page.
 */
function requireLogin(string $redirect_to = 'index.php?view=login'): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = 'You need to login to access this page.';
        $_SESSION['flash_message_type'] = 'info';
        header("Location: " . $redirect_to);
        exit;
    }
}

?>
