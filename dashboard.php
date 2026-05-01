<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();

// Ensure session data is complete
if (!isset($_SESSION['username'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$pageTitle = 'Dashboard';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$initial = strtoupper(substr($username, 0, 1));

$db_type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$is_sqlite = $db_type === 'sqlite';

// Monthly income
if ($is_sqlite) {
    $stmt_income = $pdo->prepare("SELECT SUM(amount) AS total FROM income WHERE user_id = :user_id AND strftime('%m', income_date) = strftime('%m', 'now') AND strftime('%Y', income_date) = strftime('%Y', 'now')");
    $stmt_expenses = $pdo->prepare("SELECT SUM(amount) AS total FROM expenses WHERE user_id = :user_id AND strftime('%m', expense_date) = strftime('%m', 'now') AND strftime('%Y', expense_date) = strftime('%Y', 'now')");
} else {
    $stmt_income = $pdo->prepare("SELECT SUM(amount) AS total FROM income WHERE user_id = :user_id AND MONTH(income_date) = MONTH(CURDATE()) AND YEAR(income_date) = YEAR(CURDATE())");
    $stmt_expenses = $pdo->prepare("SELECT SUM(amount) AS total FROM expenses WHERE user_id = :user_id AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
}
$stmt_income->execute([':user_id' => $user_id]);
$monthly_income = $stmt_income->fetchColumn() ?: 0;
$stmt_expenses->execute([':user_id' => $user_id]);
$monthly_expenses = $stmt_expenses->fetchColumn() ?: 0;
$balance = $monthly_income - $monthly_expenses;

// Savings goals progress
$stmt_goals = $pdo->prepare("SELECT * FROM financial_goals WHERE user_id = :user_id AND status = 'active'");
$stmt_goals->execute([':user_id' => $user_id]);
$goals = $stmt_goals->fetchAll();
$total_target = 0; $total_saved = 0;
foreach ($goals as $g) { $total_target += $g['target_amount']; $total_saved += $g['current_amount']; }
$savings_pct = $total_target > 0 ? min(100, round(($total_saved / $total_target) * 100)) : 0;

// Recent transactions
if ($is_sqlite) {
    $stmt_txn = $pdo->prepare("SELECT * FROM (SELECT 'income' as type, description, amount, income_date as date, source_id as cat_id FROM income WHERE user_id = :user_id ORDER BY income_date DESC, created_at DESC LIMIT 8)
                        UNION ALL SELECT * FROM (SELECT 'expense' as type, description, amount, expense_date as date, category_id as cat_id FROM expenses WHERE user_id = :user_id ORDER BY expense_date DESC, created_at DESC LIMIT 8)
                        ORDER BY date DESC LIMIT 8");
} else {
    $stmt_txn = $pdo->prepare("(SELECT 'income' as type, description, amount, income_date as date, source_id as cat_id FROM income WHERE user_id = :user_id ORDER BY income_date DESC, created_at DESC LIMIT 8)
                        UNION ALL (SELECT 'expense' as type, description, amount, expense_date as date, category_id as cat_id FROM expenses WHERE user_id = :user_id ORDER BY expense_date DESC, created_at DESC LIMIT 8)
                        ORDER BY date DESC LIMIT 8");
}
$stmt_txn->execute([':user_id' => $user_id]);
$transactions = $stmt_txn->fetchAll();

// Cash flow chart data (last 30 days)
if ($is_sqlite) {
    $stmt_cf = $pdo->prepare("SELECT income_date as date, SUM(amount) as amount FROM income WHERE user_id = :user_id AND income_date >= date('now', '-30 days') GROUP BY income_date ORDER BY income_date");
} else {
    $stmt_cf = $pdo->prepare("SELECT income_date as date, SUM(amount) as amount FROM income WHERE user_id = :user_id AND income_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY income_date ORDER BY income_date");
}
$stmt_cf->execute([':user_id' => $user_id]);
$income_flow = $stmt_cf->fetchAll(PDO::FETCH_KEY_PAIR);

if ($is_sqlite) {
    $stmt_ef = $pdo->prepare("SELECT expense_date as date, SUM(amount) as amount FROM expenses WHERE user_id = :user_id AND expense_date >= date('now', '-30 days') GROUP BY expense_date ORDER BY expense_date");
} else {
    $stmt_ef = $pdo->prepare("SELECT expense_date as date, SUM(amount) as amount FROM expenses WHERE user_id = :user_id AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY expense_date ORDER BY expense_date");
}
$stmt_ef->execute([':user_id' => $user_id]);
$expense_flow = $stmt_ef->fetchAll(PDO::FETCH_KEY_PAIR);

$now = new DateTime();
$labels = []; $income_data = []; $expense_data = [];
for ($i = 29; $i >= 0; $i--) {
    $d = clone $now; $d->modify("-$i days");
    $key = $d->format('Y-m-d');
    $labels[] = $d->format('M j');
    $income_data[] = $income_flow[$key] ?? 0;
    $expense_data[] = $expense_flow[$key] ?? 0;
}
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Dashboard — FinPulse</title>
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
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight">Good <?php echo date('G') < 12 ? 'morning' : (date('G') < 18 ? 'afternoon' : 'evening'); ?>, <?php echo htmlspecialchars($username); ?> 👋</h2>
          <p class="text-sm text-zinc-400">Here's what's happening with your money today.</p>
        </div>
        <a href="expenses.php" class="rounded-md bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">+ Add Transaction</a>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5 transition hover:border-zinc-700">
          <div class="flex items-center justify-between">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Total Balance</p>
            <span class="grid h-8 w-8 place-items-center rounded-md bg-zinc-800/80 text-zinc-300">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h18v10H3z"/><path d="M16 12h.01"/></svg>
            </span>
          </div>
          <p class="mt-3 text-3xl font-semibold">$<?php echo number_format($balance, 2); ?></p>
          <p class="mt-1 text-xs text-<?php echo $balance >= 0 ? 'emerald' : 'rose'; ?>-400"><?php echo $balance >= 0 ? '▲' : '▼'; ?> This month</p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5 transition hover:border-zinc-700">
          <div class="flex items-center justify-between">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Monthly Income</p>
            <span class="grid h-8 w-8 place-items-center rounded-md bg-emerald-500/10 text-emerald-300">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
            </span>
          </div>
          <p class="mt-3 text-3xl font-semibold text-emerald-300">$<?php echo number_format($monthly_income, 2); ?></p>
          <p class="mt-1 text-xs text-zinc-500">This month</p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5 transition hover:border-zinc-700">
          <div class="flex items-center justify-between">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Monthly Expenses</p>
            <span class="grid h-8 w-8 place-items-center rounded-md bg-rose-500/10 text-rose-300">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
            </span>
          </div>
          <p class="mt-3 text-3xl font-semibold text-rose-300">$<?php echo number_format($monthly_expenses, 2); ?></p>
          <p class="mt-1 text-xs text-zinc-500">This month</p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5 transition hover:border-zinc-700">
          <div class="flex items-center justify-between">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Savings Progress</p>
            <span class="grid h-8 w-8 place-items-center rounded-md bg-cyan-500/10 text-cyan-300">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l3 6 6 .9-4.5 4.2L18 20l-6-3.5L6 20l1.5-6.9L3 8.9 9 8z"/></svg>
            </span>
          </div>
          <p class="mt-3 text-3xl font-semibold"><?php echo $savings_pct; ?>%</p>
          <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-zinc-800">
            <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-violet-400" data-progress="<?php echo $savings_pct; ?>"></div>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold">Cash Flow</h3>
            <p class="text-xs text-zinc-500">Last 30 days</p>
          </div>
        </div>
        <div class="relative h-72 w-full"><canvas id="cashFlow"></canvas></div>
      </div>

      <div class="rounded-xl border border-zinc-800 bg-zinc-900/40">
        <div class="flex items-center justify-between border-b border-zinc-800 p-5">
          <div>
            <h3 class="text-base font-semibold">Recent Transactions</h3>
            <p class="text-xs text-zinc-500">Latest entries</p>
          </div>
          <a href="expenses.php" class="text-xs text-zinc-400 hover:text-zinc-50">View all →</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-xs text-zinc-500">
              <tr class="border-b border-zinc-800">
                <th class="px-5 py-3 text-left font-medium">Date</th>
                <th class="px-5 py-3 text-left font-medium">Type</th>
                <th class="px-5 py-3 text-left font-medium">Description</th>
                <th class="px-5 py-3 text-right font-medium">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/70 text-zinc-200">
              <?php foreach ($transactions as $txn): ?>
              <tr class="transition hover:bg-zinc-800/40">
                <td class="px-5 py-3 text-zinc-400"><?php echo htmlspecialchars(date('M d', strtotime($txn['date']))); ?></td>
                <td class="px-5 py-3">
                  <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?php echo $txn['type'] === 'income' ? 'bg-emerald-500/10 text-emerald-300 ring-1 ring-emerald-500/20' : 'bg-rose-500/10 text-rose-300 ring-1 ring-rose-500/20'; ?>">
                    <?php echo htmlspecialchars(ucfirst($txn['type'])); ?>
                  </span>
                </td>
                <td class="px-5 py-3"><?php echo htmlspecialchars($txn['description'] ?: 'N/A'); ?></td>
                <td class="px-5 py-3 text-right font-medium <?php echo $txn['type'] === 'income' ? 'text-emerald-300' : 'text-rose-300'; ?>">
                  <?php echo $txn['type'] === 'income' ? '+' : '-'; ?>$<?php echo number_format($txn['amount'], 2); ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($transactions)): ?>
              <tr><td colspan="4" class="px-5 py-8 text-center text-zinc-500">No transactions yet</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  const labels = <?php echo json_encode($labels); ?>;
  const incomeData = <?php echo json_encode($income_data); ?>;
  const expenseData = <?php echo json_encode($expense_data); ?>;
  function renderCashFlow(canvasId) {
    const el = document.getElementById(canvasId);
    if (!el) return;
    const waitForChart = setInterval(function() {
      if (window.Chart) {
        clearInterval(waitForChart);
        new Chart(el, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              { label: 'Income', data: incomeData, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', fill: true, tension: .3, pointRadius: 0, borderWidth: 2 },
              { label: 'Expenses', data: expenseData, borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,.10)', fill: true, tension: .3, pointRadius: 0, borderWidth: 2 },
            ],
          },
          options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
              legend: { labels: { color: '#a1a1aa', usePointStyle: true, boxWidth: 8 } },
              tooltip: { backgroundColor: '#09090b', borderColor: '#27272a', borderWidth: 1, titleColor: '#fafafa', bodyColor: '#d4d4d8' },
            },
            scales: {
              x: { ticks: { color: '#52525b', maxTicksLimit: 10 }, grid: { color: 'rgba(63,63,70,.3)' } },
              y: { ticks: { color: '#52525b' }, grid: { color: 'rgba(63,63,70,.3)' }, beginAtZero: true },
            },
          },
        });
      }
    }, 100);
  }
  renderCashFlow('cashFlow');
  </script>
</body>
</html>
