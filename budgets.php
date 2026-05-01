<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/helpers.php';
requireLogin();

$pageTitle = 'Budgets';
$user_id = $_SESSION['user_id'];
$initial = strtoupper(substr($_SESSION['username'], 0, 1));
$message = '';
$message_type = '';

$budget_to_edit = null;
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt_edit = $pdo->prepare("SELECT * FROM budgets WHERE id = :id AND user_id = :user_id");
    $stmt_edit->execute([':id' => $edit_id, ':user_id' => $user_id]);
    $budget_to_edit = $stmt_edit->fetch();
    if ($budget_to_edit) {
        $stmt_cats = $pdo->prepare("SELECT * FROM budget_categories WHERE budget_id = :budget_id");
        $stmt_cats->execute([':budget_id' => $edit_id]);
        $budget_to_edit['category_limits'] = $stmt_cats->fetchAll();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_budget'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $message_type = 'error';
    } else {
        $pdo->prepare("DELETE FROM budgets WHERE id = :id AND user_id = :user_id")->execute([':id' => intval($_POST['delete_budget']), ':user_id' => $user_id]);
        $message = 'Budget deleted';
        $message_type = 'success';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_budget']) || isset($_POST['update_budget']))) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $message_type = 'error';
    } else {
        $name = trim($_POST['name']);
        $period_type = $_POST['period_type'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $total_limit = floatval($_POST['total_limit']);
        $category_limits = $_POST['category_limits'] ?? [];

        if (isset($_POST['update_budget']) && !empty($_POST['budget_id'])) {
            $budget_id = $_POST['budget_id'];
            $pdo->prepare("UPDATE budgets SET name = :name, period_type = :period_type, start_date = :start_date, end_date = :end_date, total_limit = :total_limit WHERE id = :id AND user_id = :user_id")->execute([':name' => $name, ':period_type' => $period_type, ':start_date' => $start_date, ':end_date' => $end_date, ':total_limit' => $total_limit, ':id' => $budget_id, ':user_id' => $user_id]);
            $pdo->prepare("DELETE FROM budget_categories WHERE budget_id = :budget_id")->execute([':budget_id' => $budget_id]);
            foreach ($category_limits as $cat_id => $limit) {
                if (is_numeric($cat_id) && $limit !== '') {
                    $pdo->prepare("INSERT INTO budget_categories (budget_id, expense_category_id, limit_amount) VALUES (:budget_id, :cat_id, :limit)")->execute([':budget_id' => $budget_id, ':cat_id' => $cat_id, ':limit' => floatval($limit)]);
                }
            }
            $message = 'Budget updated';
            $message_type = 'success';
        } else {
            $pdo->prepare("INSERT INTO budgets (user_id, name, period_type, start_date, end_date, total_limit) VALUES (:user_id, :name, :period_type, :start_date, :end_date, :total_limit)")->execute([':user_id' => $user_id, ':name' => $name, ':period_type' => $period_type, ':start_date' => $start_date, ':end_date' => $end_date, ':total_limit' => $total_limit]);
            $budget_id = $pdo->lastInsertId();
            foreach ($category_limits as $cat_id => $limit) {
                if (is_numeric($cat_id) && $limit !== '') {
                    $pdo->prepare("INSERT INTO budget_categories (budget_id, expense_category_id, limit_amount) VALUES (:budget_id, :cat_id, :limit)")->execute([':budget_id' => $budget_id, ':cat_id' => $cat_id, ':limit' => floatval($limit)]);
                }
            }
            $message = 'Budget created';
            $message_type = 'success';
        }
    }
}

insertDefaultCategories($pdo, $user_id);

$cat_stmt = $pdo->prepare("SELECT id, name FROM expense_categories WHERE user_id = :user_id ORDER BY name");
$cat_stmt->execute([':user_id' => $user_id]);
$categories = $cat_stmt->fetchAll();

$bud_stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = :user_id ORDER BY start_date DESC");
$bud_stmt->execute([':user_id' => $user_id]);
$budgets = $bud_stmt->fetchAll();

$budget_cats = [];
if ($budgets) {
    $budget_ids = array_column($budgets, 'id');
    $placeholders = implode(',', array_fill(0, count($budget_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM budget_categories WHERE budget_id IN ($placeholders)");
    $stmt->execute($budget_ids);
    foreach ($stmt->fetchAll() as $row) {
        $budget_cats[$row['budget_id']][] = $row;
    }
}

$actuals = [];
foreach ($budgets as $b) {
    $stmt = $pdo->prepare("SELECT e.amount, e.category_id FROM expenses e WHERE e.user_id = :user_id AND e.expense_date BETWEEN :start AND :end");
    $stmt->execute([':user_id' => $user_id, ':start' => $b['start_date'], ':end' => $b['end_date']]);
    $exp = $stmt->fetchAll();
    $total = 0;
    $cat_totals = [];
    foreach ($exp as $e) {
        $total += $e['amount'];
        if ($e['category_id']) {
            $cat_totals[$e['category_id']] = ($cat_totals[$e['category_id']] ?? 0) + $e['amount'];
        }
    }
    $actuals[$b['id']] = ['total' => $total, 'categories' => $cat_totals];
}
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Budgets — FinPulse</title>
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

      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight">Budgets</h2>
          <p class="text-sm text-zinc-400">Monthly category limits and live progress.</p>
        </div>
        <a href="#" onclick="document.getElementById('createForm').classList.toggle('hidden');return false" class="rounded-md bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">+ New Budget</a>
      </div>

      <form id="createForm" method="POST" class="<?php if (!$budget_to_edit) echo 'hidden'; ?> rounded-xl border border-zinc-800 bg-zinc-900/40 p-6">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <?php if ($budget_to_edit): ?><input type="hidden" name="budget_id" value="<?php echo $budget_to_edit['id']; ?>"><?php endif; ?>
        <h3 class="text-base font-semibold"><?php echo $budget_to_edit ? 'Edit Budget' : 'Create Budget'; ?></h3>
        <p class="mt-1 text-xs text-zinc-500">Set limits to keep spending on track.</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Name</label>
            <input required type="text" name="name" value="<?php echo htmlspecialchars($budget_to_edit['name'] ?? ''); ?>" placeholder="e.g., Monthly Budget"
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Period</label>
            <select name="period_type" required
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30">
              <option value="monthly" <?php echo ($budget_to_edit['period_type'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
              <option value="weekly" <?php echo ($budget_to_edit['period_type'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
              <option value="yearly" <?php echo ($budget_to_edit['period_type'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Start Date</label>
            <input required type="date" name="start_date" value="<?php echo htmlspecialchars($budget_to_edit['start_date'] ?? ''); ?>"
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm text-zinc-300 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">End Date</label>
            <input required type="date" name="end_date" value="<?php echo htmlspecialchars($budget_to_edit['end_date'] ?? ''); ?>"
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm text-zinc-300 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Total Limit</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">$</span>
              <input required type="number" step="0.01" name="total_limit" value="<?php echo htmlspecialchars($budget_to_edit['total_limit'] ?? ''); ?>" placeholder="0.00"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 py-2.5 pl-7 pr-3 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
            </div>
          </div>
        </div>
        <h4 class="mt-6 mb-3 text-sm font-medium text-zinc-400">Category limits (optional)</h4>
        <div class="max-h-60 space-y-3 overflow-y-auto pr-2">
          <?php foreach ($categories as $cat): 
            $current_limit = '';
            if (isset($budget_to_edit['category_limits'])) {
              foreach ($budget_to_edit['category_limits'] as $cl) {
                if ($cl['expense_category_id'] == $cat['id']) { $current_limit = $cl['limit_amount']; break; }
              }
            }
          ?>
          <div class="flex items-center gap-3">
            <label class="w-32 shrink-0 text-sm text-zinc-300"><?php echo htmlspecialchars($cat['name']); ?></label>
            <input type="number" step="0.01" name="category_limits[<?php echo $cat['id']; ?>]" value="<?php echo $current_limit; ?>" placeholder="No limit"
              class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-6 flex gap-2">
          <button type="submit" name="<?php echo $budget_to_edit ? 'update_budget' : 'add_budget'; ?>" class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:scale-[1.03] hover:bg-violet-400"><?php echo $budget_to_edit ? 'Update Budget' : 'Create Budget'; ?></button>
          <?php if ($budget_to_edit): ?><a href="budgets.php" class="rounded-md border border-zinc-800 px-4 py-2 text-sm text-zinc-400 hover:bg-zinc-800">Cancel</a><?php endif; ?>
        </div>
      </form>

      <div id="grid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($budgets as $b): 
          $spent = $actuals[$b['id']]['total'];
          $pct = $b['total_limit'] > 0 ? min(100, round(($spent / $b['total_limit']) * 100)) : 0;
          $over = $spent > $b['total_limit'];
          $near = $pct >= 85 && !$over;
          $barClass = $over ? 'from-rose-500 to-orange-500' : ($near ? 'from-amber-400 to-orange-400' : 'from-emerald-400 to-cyan-400');
          $status = $over ? 'Over budget' : ($near ? 'Almost there' : 'On track');
          $statusClass = $over ? 'text-rose-300' : ($near ? 'text-amber-300' : 'text-emerald-300');
          $icons = ['monthly' => '📅', 'weekly' => '📆', 'yearly' => '🗓️'];
          $icon = $icons[$b['period_type']] ?? '💰';
        ?>
        <div class="group rounded-xl border border-zinc-800 bg-zinc-900/40 p-5 transition hover:-translate-y-0.5 hover:border-zinc-700">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <span class="grid h-10 w-10 place-items-center rounded-lg bg-zinc-800/80 text-lg"><?php echo $icon; ?></span>
              <div>
                <p class="font-medium"><?php echo htmlspecialchars($b['name']); ?></p>
                <p class="text-xs <?php echo $statusClass; ?>"><?php echo $status; ?> · <?php echo htmlspecialchars(date('M d', strtotime($b['start_date']))); ?> – <?php echo htmlspecialchars(date('M d', strtotime($b['end_date']))); ?></p>
              </div>
            </div>
            <div class="flex gap-1">
              <a href="?edit_id=<?php echo $b['id']; ?>" class="rounded-md p-1.5 text-zinc-500 opacity-0 transition group-hover:opacity-100 hover:bg-zinc-800 hover:text-zinc-200">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 113 3L7 19l-4 1 1-4z"/></svg>
              </a>
              <form method="POST" onsubmit="return confirm('Delete this budget?');" class="inline">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="delete_budget" value="<?php echo $b['id']; ?>">
                <button type="submit" class="rounded-md p-1.5 text-zinc-500 opacity-0 transition group-hover:opacity-100 hover:bg-zinc-800 hover:text-rose-300">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                </button>
              </form>
            </div>
          </div>
          <div class="mt-5 flex items-baseline justify-between text-sm">
            <p class="text-zinc-300"><span class="text-xl font-semibold text-zinc-50">$<?php echo number_format($spent, 2); ?></span> <span class="text-zinc-500">/ $<?php echo number_format($b['total_limit'], 2); ?></span></p>
            <p class="<?php echo $over ? 'text-rose-300' : 'text-zinc-400'; ?> text-xs font-medium"><?php echo $pct; ?>%</p>
          </div>
          <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-zinc-800">
            <div class="h-full rounded-full bg-gradient-to-r <?php echo $barClass; ?>" data-progress="<?php echo min(100, $pct); ?>"></div>
          </div>
          <?php if (!empty($budget_cats[$b['id']])): ?>
          <div class="mt-4 space-y-2 border-t border-zinc-800 pt-4">
            <?php foreach ($budget_cats[$b['id']] as $bc): ?>
              <?php $cat_spent = $actuals[$b['id']]['categories'][$bc['expense_category_id']] ?? 0;
                    $cat_name = ''; foreach ($categories as $c) if ($c['id'] == $bc['expense_category_id']) $cat_name = $c['name'];
                    $cat_pct = $bc['limit_amount'] > 0 ? min(100, round(($cat_spent / $bc['limit_amount']) * 100)) : 0;
                    $cat_over = $cat_spent > $bc['limit_amount'];
              ?>
            <div>
              <div class="flex justify-between text-xs text-zinc-400">
                <span><?php echo htmlspecialchars($cat_name); ?></span>
                <span class="<?php echo $cat_over ? 'text-rose-300' : 'text-zinc-500'; ?>">$<?php echo number_format($cat_spent, 2); ?> / $<?php echo number_format($bc['limit_amount'], 2); ?></span>
              </div>
              <div class="mt-1 h-1 w-full overflow-hidden rounded-full bg-zinc-800/60">
                <div class="h-full rounded-full <?php echo $cat_over ? 'bg-rose-500' : 'bg-zinc-500'; ?>" style="width:<?php echo min(100, $cat_pct); ?>%"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($budgets)): ?>
        <div class="sm:col-span-2 xl:col-span-3 rounded-xl border border-zinc-800 bg-zinc-900/40 p-10 text-center">
          <span class="text-4xl">📊</span>
          <p class="mt-3 text-sm text-zinc-500">No budgets yet. Create one above to start tracking.</p>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
