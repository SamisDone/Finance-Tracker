<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

if (isset($_SESSION['user_id'], $_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'error';
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $result = registerUser($pdo, $username, $email, $password, $confirm_password);
        if ($result === true) {
            $_SESSION['flash_message'] = 'Account created! Please login.';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: login.php');
            exit;
        } else {
            $message = $result;
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
<title>Create account — FinPulse</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="assets/app.js"></script>
<style>body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="relative min-h-screen overflow-hidden bg-zinc-950 text-zinc-50 antialiased">
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-32 right-1/3 h-[500px] w-[500px] rounded-full bg-cyan-500/20 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 h-[400px] w-[400px] rounded-full bg-violet-600/30 blur-3xl"></div>
  </div>

  <main class="grid min-h-screen place-items-center px-4 py-10">
    <div class="w-full max-w-md rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-8 shadow-2xl backdrop-blur-xl">
      <a href="index.php" class="mb-6 flex items-center justify-center gap-2">
        <span class="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-violet-500 to-cyan-400 font-bold text-zinc-950">F</span>
        <span class="font-semibold tracking-tight">FinPulse</span>
      </a>
      <h1 class="text-center text-2xl font-semibold tracking-tight">Create your account</h1>
      <p class="mt-1 text-center text-sm text-zinc-400">Start tracking in under a minute</p>

      <?php if ($message): ?>
      <div class="mt-4 rounded-md border <?php echo $message_type === 'error' ? 'border-red-500/30 bg-red-500/10 text-red-200' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'; ?> px-4 py-3 text-sm">
        <?php echo htmlspecialchars($message); ?>
      </div>
      <?php endif; ?>

      <form class="mt-8 space-y-4" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div>
          <label class="mb-1.5 block text-xs font-medium text-zinc-400">Username</label>
          <input required type="text" name="username" placeholder="Alex Stone" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
            class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-medium text-zinc-400">Email</label>
          <input required type="email" name="email" placeholder="you@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-medium text-zinc-400">Password</label>
          <input required type="password" name="password" placeholder="At least 8 characters" minlength="8" pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
            class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          <ul class="mt-2 space-y-1 text-xs text-zinc-500">
            <li id="rule-length" class="flex items-center gap-1.5">
              <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="8"/></svg>
              <span>At least 8 characters</span>
            </li>
            <li id="rule-upper" class="flex items-center gap-1.5">
              <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="8"/></svg>
              <span>One uppercase letter</span>
            </li>
            <li id="rule-number" class="flex items-center gap-1.5">
              <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="8"/></svg>
              <span>One number</span>
            </li>
            <li id="rule-special" class="flex items-center gap-1.5">
              <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="8"/></svg>
              <span>One special character</span>
            </li>
          </ul>
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-medium text-zinc-400">Confirm password</label>
          <input required type="password" name="confirm_password" placeholder="Repeat password"
            class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
        </div>
        <button type="submit" name="register" class="w-full rounded-md bg-zinc-50 py-2.5 text-sm font-semibold text-zinc-950 transition hover:scale-[1.02] hover:bg-white hover:shadow-glow">Create account</button>
      </form>

      <p class="mt-6 text-center text-sm text-zinc-400">
        Already have an account? <a href="login.php" class="text-zinc-50 underline-offset-4 hover:underline">Log in</a>
      </p>
    </div>
  </main>
  <script>
  (function(){
    const pw = document.querySelector('input[name="password"]');
    if (!pw) return;
    const rules = {
      length: { el: document.getElementById('rule-length'), test: v => v.length >= 8 },
      upper:  { el: document.getElementById('rule-upper'),  test: v => /[A-Z]/.test(v) },
      number: { el: document.getElementById('rule-number'), test: v => /[0-9]/.test(v) },
      special:{ el: document.getElementById('rule-special'),test: v => /[^A-Za-z0-9]/.test(v) },
    };
    pw.addEventListener('input', function(){
      const v = this.value;
      for (const k in rules) {
        const ok = rules[k].test(v);
        const svg = rules[k].el.querySelector('svg');
        rules[k].el.querySelector('span').style.color = ok ? '#34d399' : '';
        svg.style.color = ok ? '#34d399' : '#52525b';
        svg.innerHTML = ok
          ? '<path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/>'
          : '<circle cx="8" cy="8" r="8"/>';
      }
    });
  })();
  </script>
</body>
</html>
