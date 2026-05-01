<?php
// db.php already handles session_start(), so this check is redundant
// Keeping the file for authentication functions only

/**
 * Generate CSRF token and store in session.
 *
 * @return string CSRF token
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token.
 *
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken(string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    if ($valid) {
        unset($_SESSION['csrf_token']);
    }
    return $valid;
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
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        return "Password must contain at least one uppercase letter, one number, and one special character.";
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
 * @param int $max_attempts Maximum login attempts (default 5)
 * @return array|false|string User data array on success, false on invalid credentials, 'locked' on too many attempts.
 */
function loginUser(PDO $pdo, string $username_or_email, string $password, int $max_attempts = 5): array|false|string
{
    if (empty($username_or_email) || empty($password)) {
        return false;
    }

    // Check rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $attempt_key = 'login_attempts_' . md5($ip . $username_or_email);
    $lockout_key = 'login_lockout_' . md5($ip . $username_or_email);

    if (!empty($_SESSION[$lockout_key]) && $_SESSION[$lockout_key] > time()) {
        return 'locked';
    }

    try {
        $sql = "SELECT id, username, email, password_hash FROM users WHERE username = :identifier OR email = :identifier LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':identifier' => $username_or_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Reset login attempts on success
            unset($_SESSION[$attempt_key], $_SESSION[$lockout_key]);
            // Remove password hash from returned array for security
            unset($user['password_hash']);
            return $user;
        }

        // Track failed attempts
        $_SESSION[$attempt_key] = ($_SESSION[$attempt_key] ?? 0) + 1;
        if ($_SESSION[$attempt_key] >= $max_attempts) {
            $_SESSION[$lockout_key] = time() + 900; // 15 minute lockout
            unset($_SESSION[$attempt_key]);
        }

        return false; // Invalid credentials
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return false;
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
