<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();

$pageTitle = 'Profile';
$user_id = $_SESSION['user_id'];
$initial = strtoupper(substr($_SESSION['username'], 0, 1));
$message = '';
$message_type = '';

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

$stmt_prefs = $pdo->prepare("SELECT notification_preferences FROM users WHERE id = :id");
$stmt_prefs->execute([':id' => $user_id]);
$prefs_json = $stmt_prefs->fetchColumn();
$prefs = json_decode($prefs_json ?: '{}', true);
$notify_reminders = $prefs['reminders'] ?? 1;
$notify_budget_alerts = $prefs['budget_alerts'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.'; $message_type = 'error';
    } elseif (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        if ($username && $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $dup_check = $pdo->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id LIMIT 1");
            $dup_check->execute([':username' => $username, ':email' => $email, ':id' => $user_id]);
            if ($dup_check->fetch()) {
                $message = 'Username or email already taken.'; $message_type = 'error';
            } else {
                $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id")->execute([':username' => $username, ':email' => $email, ':id' => $user_id]);
                $_SESSION['username'] = $username;
                $message = 'Profile updated'; $message_type = 'success';
                $user['username'] = $username; $user['email'] = $email;
            }
        } else { $message = 'Invalid username or email.'; $message_type = 'error'; }
    } elseif (isset($_POST['update_password'])) {
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        if ($password && strlen($password) >= 8 && $password === $confirm && preg_match('/[A-Z]/', $password) && preg_match('/[0-9]/', $password) && preg_match('/[^A-Za-z0-9]/', $password)) {
            $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user_id]);
            $message = 'Password updated'; $message_type = 'success';
        } else { $message = 'Password must be 8+ chars with uppercase, number, and special character.'; $message_type = 'error'; }
    } elseif (isset($_POST['update_notifications'])) {
        $prefs = ['reminders' => isset($_POST['notify_reminders']) ? 1 : 0, 'budget_alerts' => isset($_POST['notify_budget_alerts']) ? 1 : 0];
        $pdo->prepare("UPDATE users SET notification_preferences = :prefs WHERE id = :id")->execute([':prefs' => json_encode($prefs), ':id' => $user_id]);
        $notify_reminders = $prefs['reminders']; $notify_budget_alerts = $prefs['budget_alerts'];
        $message = 'Notification preferences updated'; $message_type = 'success';
    }
}
$csrf = generateCsrfToken();
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Profile — FinPulse</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="assets/app.js"></script>
<style>body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="h-screen overflow-hidden bg-zinc-950 text-zinc-50 antialiased">
  <?php include 'includes/sidebar.php'; ?>
  <div class="lg:pl-64 flex flex-col h-screen">
    <?php include 'includes/topbar.php'; ?>
    <main class="mx-auto w-full max-w-5xl flex-1 overflow-y-auto p-4 sm:p-5">
      <?php if ($message): ?>
      <div class="mb-3 rounded-md border <?php echo $message_type === 'error' ? 'border-red-500/30 bg-red-500/10 text-red-200' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'; ?> px-4 py-2.5 text-sm"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Header + identity -->
      <div class="mb-4 flex items-center gap-4">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-gradient-to-br from-violet-500 to-cyan-400 text-lg font-bold text-zinc-950"><?php echo $initial; ?></span>
        <div>
          <h2 class="text-xl font-semibold tracking-tight"><?php echo htmlspecialchars($user['username']); ?></h2>
          <p class="text-sm text-zinc-400"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
      </div>

      <!-- 2-column grid: Account + Password on top row -->
      <div class="grid gap-4 lg:grid-cols-2">
        <!-- Account info -->
        <form method="POST" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
          <h3 class="text-sm font-semibold">Account Information</h3>
          <div class="mt-3 space-y-3">
            <div>
              <label class="mb-1 block text-xs font-medium text-zinc-400">Username</label>
              <input required type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-zinc-400">Email</label>
              <input required type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
            </div>
          </div>
          <div class="mt-4 flex justify-end">
            <button type="submit" name="update_profile" class="rounded-md bg-zinc-50 px-3.5 py-1.5 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">Save</button>
          </div>
        </form>

        <!-- Password -->
        <form method="POST" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
          <h3 class="text-sm font-semibold">Change Password</h3>
          <div class="mt-3 space-y-3">
            <div>
              <label class="mb-1 block text-xs font-medium text-zinc-400">New password</label>
              <input type="password" name="password" placeholder="At least 8 chars" minlength="8"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-zinc-400">Confirm password</label>
              <input type="password" name="confirm_password" placeholder="Repeat password" minlength="8"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
            </div>
          </div>
          <div class="mt-4 flex justify-end">
            <button type="submit" name="update_password" class="rounded-md bg-zinc-50 px-3.5 py-1.5 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">Update</button>
          </div>
        </form>
      </div>

      <!-- Bottom row: Notifications + Danger zone -->
      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <!-- Notifications -->
        <form method="POST" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
          <h3 class="text-sm font-semibold">Notifications</h3>
          <div class="mt-3 space-y-2.5">
            <label class="flex cursor-pointer items-center gap-3">
              <input type="checkbox" name="notify_reminders" value="1" <?php if ($notify_reminders) echo 'checked'; ?>
                class="h-4 w-4 rounded border-zinc-700 bg-zinc-800 text-violet-500 focus:ring-violet-500/30"/>
              <span class="text-sm text-zinc-300">Bill reminders</span>
            </label>
            <label class="flex cursor-pointer items-center gap-3">
              <input type="checkbox" name="notify_budget_alerts" value="1" <?php if ($notify_budget_alerts) echo 'checked'; ?>
                class="h-4 w-4 rounded border-zinc-700 bg-zinc-800 text-violet-500 focus:ring-violet-500/30"/>
              <span class="text-sm text-zinc-300">Budget alerts</span>
            </label>
          </div>
          <div class="mt-4 flex justify-end">
            <button type="submit" name="update_notifications" class="rounded-md bg-zinc-50 px-3.5 py-1.5 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">Save</button>
          </div>
        </form>

        <!-- Danger zone -->
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/5 p-5">
          <h3 class="text-sm font-semibold text-rose-200">Danger Zone</h3>
          <p class="mt-1 text-xs text-rose-300/70">Permanently delete your account and all data.</p>
          <button onclick="alert('Contact support to delete your account.')" class="mt-3 rounded-md border border-rose-500/40 bg-rose-500/10 px-3 py-1.5 text-sm text-rose-200 hover:bg-rose-500/20">Delete account</button>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
