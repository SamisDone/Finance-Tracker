<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

if (isset($_SESSION['user_id'], $_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'error';
    } else {
        $username_or_email = trim($_POST['username_or_email']);
        $password = $_POST['password'];
        $user = loginUser($pdo, $username_or_email, $password);
        if ($user === 'locked') {
            $message = 'Too many login attempts. Please try again in 15 minutes.';
            $message_type = 'error';
        } elseif ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['flash_message'] = 'Welcome back, ' . htmlspecialchars($user['username']) . '!';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: dashboard.php');
            exit;
        } else {
            $message = 'Invalid email or password.';
            $message_type = 'error';
        }
    }
}
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Login — FinPulse</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="assets/app.js"></script>
<style>body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="relative min-h-screen overflow-hidden bg-zinc-950 text-zinc-50 antialiased">
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-32 left-1/3 h-[500px] w-[500px] rounded-full bg-violet-600/30 blur-3xl"></div>
    <div class="absolute bottom-0 right-0 h-[400px] w-[400px] rounded-full bg-cyan-500/20 blur-3xl"></div>
  </div>

  <main class="grid min-h-screen place-items-center px-4">
    <div class="w-full max-w-md rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-8 shadow-2xl backdrop-blur-xl">
      <a href="index.php" class="mb-6 flex items-center justify-center gap-2">
        <span class="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-violet-500 to-cyan-400 font-bold text-zinc-950">F</span>
        <span class="font-semibold tracking-tight">FinPulse</span>
      </a>
      <h1 class="text-center text-2xl font-semibold tracking-tight">Welcome back</h1>
      <p class="mt-1 text-center text-sm text-zinc-400">Sign in to access your dashboard</p>

      <?php if ($message): ?>
      <div class="mt-4 rounded-md border <?php echo $message_type === 'error' ? 'border-red-500/30 bg-red-500/10 text-red-200' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'; ?> px-4 py-3 text-sm">
        <?php echo htmlspecialchars($message); ?>
      </div>
      <?php endif; ?>

      <form class="mt-8 space-y-4" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div>
          <label class="mb-1.5 block text-xs font-medium text-zinc-400">Email</label>
          <input type="text" name="username_or_email" required placeholder="you@example.com"
            class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
        </div>
        <div>
          <div class="mb-1.5 flex items-center justify-between">
            <label class="text-xs font-medium text-zinc-400">Password</label>
          </div>
          <input type="password" name="password" required placeholder="••••••••"
            class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
        </div>
        <button type="submit" name="login" class="w-full rounded-md bg-zinc-50 py-2.5 text-sm font-semibold text-zinc-950 transition hover:scale-[1.02] hover:bg-white hover:shadow-glow">Sign in</button>
      </form>

      <div class="my-6 flex items-center gap-3 text-xs text-zinc-600">
        <div class="h-px flex-1 bg-zinc-800"></div>OR<div class="h-px flex-1 bg-zinc-800"></div>
      </div>

      <button onclick="window.location.href='register.php'" class="flex w-full items-center justify-center gap-2 rounded-md border border-zinc-800 bg-zinc-900/60 py-2.5 text-sm transition hover:border-zinc-700 hover:bg-zinc-900">
        <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="#fff" d="M21.35 11.1H12v3.2h5.35c-.23 1.5-1.6 4.4-5.35 4.4-3.22 0-5.85-2.66-5.85-5.95s2.63-5.95 5.85-5.95c1.83 0 3.06.78 3.77 1.45l2.57-2.47C16.78 4.1 14.6 3.2 12 3.2 6.94 3.2 2.85 7.3 2.85 12.35S6.94 21.5 12 21.5c6.93 0 9.5-4.86 9.5-7.4 0-.5-.05-.88-.15-1z"/></svg>
        Create account instead
      </button>

      <p class="mt-6 text-center text-sm text-zinc-400">
        New here? <a href="register.php" class="text-zinc-50 underline-offset-4 hover:underline">Create an account</a>
      </p>
    </div>
  </main>
</body>
</html>
