<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();
$pageTitle = 'Profile';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch user data
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

// Fetch notification preferences (for demo, store in session or expand users table for real use)
$notify_reminders = $_SESSION['notify_reminders'] ?? 1;
$notify_budget_alerts = $_SESSION['notify_budget_alerts'] ?? 1;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        // Basic validation
        if ($username && $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
            $stmt->execute([':username' => $username, ':email' => $email, ':id' => $user_id]);
            $_SESSION['username'] = $username;
            $message = 'Profile updated!';
            $message_type = 'success';
            $user['username'] = $username;
            $user['email'] = $email;
        } else {
            $message = 'Invalid username or email.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['update_password'])) {
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        if ($password && strlen($password) >= 6 && $password === $confirm) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $stmt->execute([':hash' => $hash, ':id' => $user_id]);
            $message = 'Password updated!';
            $message_type = 'success';
        } else {
            $message = 'Passwords must match and be at least 6 characters.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['update_notifications'])) {
        $_SESSION['notify_reminders'] = isset($_POST['notify_reminders']) ? 1 : 0;
        $_SESSION['notify_budget_alerts'] = isset($_POST['notify_budget_alerts']) ? 1 : 0;
        $notify_reminders = $_SESSION['notify_reminders'];
        $notify_budget_alerts = $_SESSION['notify_budget_alerts'];
        $message = 'Notification preferences updated!';
        $message_type = 'success';
    }
}
?>
<section class="hero section-hero profile-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="profileHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2563eb"/>
                    <stop offset="100%" stop-color="#14b8a6"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#profileHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="fa-solid fa-user"></i> Your Profile
        </h2>
        <p class="hero-desc">
            Manage your account, password, and notification preferences.
        </p>
    </div>
</section>
<div class="form-container card mb-4">
    <h2><i class="fa-solid fa-user"></i> Your Profile</h2>
    <?php if ($message): ?><div class="flash-message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="username"><i class="fa-solid fa-user"></i> Username:</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>
        <button type="submit" name="update_profile" class="btn">Update Profile</button>
    </form>
    <hr>
    <h3>Change Password</h3>
    <form method="POST" action="">
        <div class="form-group">
            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" minlength="6" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
        </div>
        <button type="submit" name="update_password" class="btn">Update Password</button>
    </form>
    <hr>
    <h3>Notification Preferences</h3>
    <form method="POST" action="">
        <div class="form-group">
            <label><input type="checkbox" name="notify_reminders" <?php if ($notify_reminders) echo 'checked'; ?>> Bill Reminders</label>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="notify_budget_alerts" <?php if ($notify_budget_alerts) echo 'checked'; ?>> Budget Alerts</label>
        </div>
        <button type="submit" name="update_notifications" class="btn">Update Notifications</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
