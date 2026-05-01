<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/helpers.php';
requireLogin();

$pageTitle = 'Savings';
$user_id = $_SESSION['user_id'];
$initial = strtoupper(substr($_SESSION['username'], 0, 1));
$message = '';
$message_type = '';

$goal_to_edit = null;
if (isset($_GET['edit_goal_id']) && !empty($_GET['edit_goal_id'])) {
    $stmt_fetch_edit = $pdo->prepare("SELECT * FROM financial_goals WHERE id = :id AND user_id = :user_id");
    $stmt_fetch_edit->execute([':id' => $_GET['edit_goal_id'], ':user_id' => $user_id]);
    $goal_to_edit = $stmt_fetch_edit->fetch();
    if (!$goal_to_edit) { $message = 'Goal not found.'; $message_type = 'error'; $goal_to_edit = null; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_goal_id']) && !empty($_POST['delete_goal_id'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.'; $message_type = 'error';
    } else {
        $stmt_delete = $pdo->prepare("DELETE FROM financial_goals WHERE id = :id AND user_id = :user_id");
        $stmt_delete->execute([':id' => $_POST['delete_goal_id'], ':user_id' => $user_id]);
        $message = 'Goal deleted'; $message_type = 'success';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_savings'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.'; $message_type = 'error';
    } else {
        $pdo->prepare("DELETE FROM savings_accounts WHERE id = :id AND user_id = :user_id")->execute([':id' => intval($_POST['delete_savings']), ':user_id' => $user_id]);
        $message = 'Savings account deleted'; $message_type = 'success';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_savings'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.'; $message_type = 'error';
    } else {
        $pdo->prepare("INSERT INTO savings_accounts (user_id, account_name, current_balance) VALUES (:user_id, :account_name, :current_balance)")->execute([':user_id' => $user_id, ':account_name' => trim($_POST['account_name']), ':current_balance' => floatval($_POST['current_balance'])]);
        $message = 'Savings account added'; $message_type = 'success';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_goal']) || isset($_POST['update_goal']))) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.'; $message_type = 'error';
    } else {
        $goal_name = trim($_POST['goal_name']);
        $target_amount = floatval($_POST['target_amount']);
        $current_amount = floatval($_POST['current_amount']);
        $target_date = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
        $description = trim($_POST['description']);
        if (isset($_POST['update_goal']) && !empty($_POST['goal_id'])) {
            $stmt_update = $pdo->prepare("UPDATE financial_goals SET goal_name = :goal_name, target_amount = :target_amount, current_amount = :current_amount, target_date = :target_date, description = :description WHERE id = :id AND user_id = :user_id");
            $stmt_update->execute([':goal_name' => $goal_name, ':target_amount' => $target_amount, ':current_amount' => $current_amount, ':target_date' => $target_date, ':description' => $description, ':id' => $_POST['goal_id'], ':user_id' => $user_id]);
            $message = 'Goal updated'; $message_type = 'success'; $goal_to_edit = null;
        } else {
            $pdo->prepare("INSERT INTO financial_goals (user_id, goal_name, target_amount, current_amount, target_date, description) VALUES (:user_id, :goal_name, :target_amount, :current_amount, :target_date, :description)")->execute([':user_id' => $user_id, ':goal_name' => $goal_name, ':target_amount' => $target_amount, ':current_amount' => $current_amount, ':target_date' => $target_date, ':description' => $description]);
            $message = 'Goal added'; $message_type = 'success';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM savings_accounts WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$savings = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM financial_goals WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$goals = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Savings — FinPulse</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="assets/app.js"></script>
<style>body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-50 antialiased">
  <?php include 'includes/sidebar.php'; ?>
  <div class="lg:pl-64">
    <?php include 'includes/topbar.php'; ?>
    <main class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
      <?php if ($message): ?>
      <div class="rounded-md border <?php echo $message_type === 'error' ? 'border-red-500/30 bg-red-500/10 text-red-200' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'; ?> px-4 py-3 text-sm"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <!-- Savings Accounts -->
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight">Savings & Goals</h2>
          <p class="text-sm text-zinc-400">Track your balances at a glance.</p>
        </div>
        <a href="#" onclick="document.getElementById('savingsForm').classList.toggle('hidden');return false" class="rounded-md bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">+ Add Account</a>
      </div>

      <form id="savingsForm" method="POST" class="hidden rounded-xl border border-zinc-800 bg-zinc-900/40 p-6">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <h3 class="text-base font-semibold">New savings account</h3>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Account name</label>
            <input required type="text" name="account_name" placeholder="e.g., Emergency Fund"
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30"/>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Current balance</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">$</span>
              <input required type="number" step="0.01" name="current_balance" placeholder="0.00"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 py-2.5 pl-7 pr-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30"/>
            </div>
          </div>
        </div>
        <div class="mt-6"><button type="submit" name="add_savings" class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:scale-[1.03] hover:bg-cyan-400">Add Account</button></div>
      </form>

      <div id="savings-grid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($savings as $s): ?>
        <div class="group rounded-xl border border-zinc-800 bg-zinc-900/40 p-5 transition hover:-translate-y-0.5 hover:border-zinc-700">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <span class="grid h-10 w-10 place-items-center rounded-lg bg-cyan-500/10 text-lg text-cyan-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h18v10H3z"/><path d="M16 12h.01"/></svg>
              </span>
              <div>
                <p class="font-medium"><?php echo htmlspecialchars($s['account_name']); ?></p>
                <p class="text-xs text-zinc-500">Since <?php echo htmlspecialchars(date('M Y', strtotime($s['created_at']))); ?></p>
              </div>
            </div>
            <form method="POST" onsubmit="return confirm('Delete this account?');" class="inline">
              <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
              <input type="hidden" name="delete_savings" value="<?php echo $s['id']; ?>">
              <button type="submit" class="rounded-md p-1.5 text-zinc-500 opacity-0 transition group-hover:opacity-100 hover:bg-zinc-800 hover:text-rose-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
              </button>
            </form>
          </div>
          <p class="mt-5 text-3xl font-semibold text-cyan-300">$<?php echo number_format($s['current_balance'], 2); ?></p>
        </div>
        <?php endforeach; ?>
        <?php if (empty($savings)): ?>
        <div class="sm:col-span-2 xl:col-span-3 rounded-xl border border-zinc-800 bg-zinc-900/40 p-10 text-center">
          <span class="text-4xl">🏦</span>
          <p class="mt-3 text-sm text-zinc-500">No savings accounts yet.</p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Financial Goals -->
      <div class="flex flex-wrap items-end justify-between gap-3 pt-4">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight">Savings Goals</h2>
          <p class="text-sm text-zinc-400">Visualize the path to every dream.</p>
        </div>
        <a href="#" onclick="document.getElementById('goalForm').classList.toggle('hidden');return false" class="rounded-md bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">+ New Goal</a>
      </div>

      <form id="goalForm" method="POST" class="<?php if (!$goal_to_edit) echo 'hidden'; ?> rounded-xl border border-zinc-800 bg-zinc-900/40 p-6">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <?php if ($goal_to_edit): ?><input type="hidden" name="goal_id" value="<?php echo $goal_to_edit['id']; ?>"><?php endif; ?>
        <h3 class="text-base font-semibold"><?php echo $goal_to_edit ? 'Edit Goal' : 'New goal'; ?></h3>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Goal name</label>
            <input required type="text" name="goal_name" value="<?php echo htmlspecialchars($goal_to_edit['goal_name'] ?? ''); ?>" placeholder="e.g., Vacation Fund"
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Target amount</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">$</span>
              <input required type="number" step="0.01" name="target_amount" value="<?php echo htmlspecialchars($goal_to_edit['target_amount'] ?? ''); ?>" placeholder="0.00"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 py-2.5 pl-7 pr-3 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Current saved</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">$</span>
              <input required type="number" step="0.01" name="current_amount" value="<?php echo htmlspecialchars($goal_to_edit['current_amount'] ?? '0'); ?>" placeholder="0.00"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 py-2.5 pl-7 pr-3 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Target date</label>
            <input type="date" name="target_date" value="<?php echo htmlspecialchars($goal_to_edit['target_date'] ?? ''); ?>"
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm text-zinc-300 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Description</label>
            <input type="text" name="description" value="<?php echo htmlspecialchars($goal_to_edit['description'] ?? ''); ?>" placeholder="Optional note..."
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
        </div>
        <div class="mt-6 flex gap-2">
          <button type="submit" name="<?php echo $goal_to_edit ? 'update_goal' : 'add_goal'; ?>" class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:scale-[1.03] hover:bg-violet-400"><?php echo $goal_to_edit ? 'Update Goal' : 'Add Goal'; ?></button>
          <?php if ($goal_to_edit): ?><a href="savings.php" class="rounded-md border border-zinc-800 px-4 py-2 text-sm text-zinc-400 hover:bg-zinc-800">Cancel</a><?php endif; ?>
        </div>
      </form>

      <div id="goals-grid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($goals as $g): 
          $pct = $g['target_amount'] > 0 ? min(100, round(($g['current_amount'] / $g['target_amount']) * 100)) : 0;
          $done = $pct >= 100;
          $bar = $done ? 'from-emerald-400 to-cyan-400' : 'from-violet-400 to-cyan-400';
          $eta = $g['target_date'] ? date('M Y', strtotime($g['target_date'])) : 'No date';
          $icons = ['🎯'];
        ?>
        <div class="group relative overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900/40 p-5 transition hover:-translate-y-0.5 hover:border-zinc-700">
          <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-violet-500/10 blur-2xl"></div>
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <span class="grid h-10 w-10 place-items-center rounded-lg bg-zinc-800/80 text-lg">🎯</span>
              <div>
                <p class="font-medium"><?php echo htmlspecialchars($g['goal_name']); ?></p>
                <p class="text-xs text-zinc-500">ETA · <?php echo htmlspecialchars($eta); ?></p>
              </div>
            </div>
            <span class="rounded-full bg-zinc-800/80 px-2 py-0.5 text-xs text-zinc-300"><?php echo $pct; ?>%</span>
          </div>
          <div class="mt-5">
            <p class="text-xl font-semibold">$<?php echo number_format($g['current_amount'], 2); ?> <span class="text-sm font-normal text-zinc-500">/ $<?php echo number_format($g['target_amount'], 2); ?></span></p>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-zinc-800">
              <div class="h-full rounded-full bg-gradient-to-r <?php echo $bar; ?>" data-progress="<?php echo min(100, $pct); ?>"></div>
            </div>
          </div>
          <div class="mt-5 flex gap-2">
            <a href="?edit_goal_id=<?php echo $g['id']; ?>" class="flex-1 rounded-md border border-zinc-800 bg-zinc-900/60 py-2 text-center text-xs text-zinc-300 hover:border-zinc-700 hover:text-zinc-50">Edit</a>
            <form method="POST" onsubmit="return confirm('Delete this goal?');" class="inline">
              <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
              <input type="hidden" name="delete_goal_id" value="<?php echo $g['id']; ?>">
              <button type="submit" class="rounded-md border border-zinc-800 bg-zinc-900/60 px-3 py-2 text-xs text-zinc-400 hover:border-rose-500/40 hover:text-rose-300">Delete</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($goals)): ?>
        <div class="sm:col-span-2 xl:col-span-3 rounded-xl border border-zinc-800 bg-zinc-900/40 p-10 text-center">
          <span class="text-4xl">🎯</span>
          <p class="mt-3 text-sm text-zinc-500">No goals yet. Create one to start saving.</p>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
