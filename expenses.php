<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/helpers.php';
requireLogin();

$pageTitle = 'Expenses';
$user_id = $_SESSION['user_id'];
$initial = strtoupper(substr($_SESSION['username'], 0, 1));
$message = '';
$message_type = '';

$per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $message_type = 'error';
    } else {
        $amount = floatval($_POST['amount']);
        $expense_date = $_POST['expense_date'];
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $category_id = getOrCreateId($pdo, $user_id, 'expense_categories', $category);
        $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category_id, amount, expense_date, description) VALUES (:user_id, :category_id, :amount, :expense_date, :description)");
        $stmt->execute([':user_id' => $user_id, ':category_id' => $category_id, ':amount' => $amount, ':expense_date' => $expense_date, ':description' => $description]);
        $message = 'Expense added';
        $message_type = 'success';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_expense'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $message_type = 'error';
    } else {
        $stmt = $pdo->prepare("SELECT receipt_path FROM expenses WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => intval($_POST['delete_expense']), ':user_id' => $user_id]);
        $exp = $stmt->fetch();
        if ($exp && $exp['receipt_path'] && file_exists($exp['receipt_path'])) unlink($exp['receipt_path']);
        $pdo->prepare("DELETE FROM expenses WHERE id = :id AND user_id = :user_id")->execute([':id' => intval($_POST['delete_expense']), ':user_id' => $user_id]);
        $message = 'Expense deleted';
        $message_type = 'success';
    }
}

insertDefaultCategories($pdo, $user_id);

$categories = $pdo->prepare("SELECT name FROM expense_categories WHERE user_id = :user_id ORDER BY name");
$categories->execute([':user_id' => $user_id]);
$categories = $categories->fetchAll(PDO::FETCH_COLUMN);

$search_sql = '';
$params = [':user_id' => $user_id];
if ($search) {
    $search_sql = " AND (e.description LIKE :search OR c.name LIKE :search)";
    $params[':search'] = "%$search%";
}

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM expenses e LEFT JOIN expense_categories c ON e.category_id = c.id WHERE e.user_id = :user_id" . $search_sql);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();

$stmt = $pdo->prepare("SELECT e.*, c.name as category_name FROM expenses e LEFT JOIN expense_categories c ON e.category_id = c.id WHERE e.user_id = :user_id" . $search_sql . " ORDER BY e.expense_date DESC, e.created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$entries = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Expenses — FinPulse</title>
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
      <div>
        <h2 class="text-2xl font-semibold tracking-tight">Expenses</h2>
        <p class="text-sm text-zinc-400">Stay aware of where your money goes.</p>
      </div>

      <div class="grid gap-6 lg:grid-cols-5">
        <form method="POST" class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-6 lg:col-span-2">
          <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
          <h3 class="text-base font-semibold">Add new expense</h3>
          <p class="mt-1 text-xs text-zinc-500">Record a purchase or bill.</p>
          <div class="mt-6 space-y-4">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-zinc-400">Amount</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">$</span>
                <input required type="number" step="0.01" name="amount" placeholder="0.00"
                  class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 py-2.5 pl-7 pr-3 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/30"/>
              </div>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-zinc-400">Date</label>
              <input required type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>"
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm text-zinc-300 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/30"/>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-zinc-400">Category</label>
              <select name="category" required
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/30">
                <?php foreach ($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-zinc-400">Description</label>
              <textarea rows="3" name="description" placeholder="Optional note..."
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm placeholder-zinc-600 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/30"></textarea>
            </div>
            <button type="submit" name="add_expense" class="w-full rounded-md bg-rose-500 py-2.5 text-sm font-semibold text-rose-950 transition hover:scale-[1.02] hover:bg-rose-400">Add Expense</button>
          </div>
        </form>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 lg:col-span-3">
          <div class="flex flex-wrap items-center gap-3 border-b border-zinc-800 p-4">
            <form method="GET" class="relative flex-1 min-w-[180px]">
              <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search expenses..."
                class="w-full rounded-md border border-zinc-800 bg-zinc-950/60 py-2 pl-9 pr-3 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
            </form>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="text-xs text-zinc-500">
                <tr class="border-b border-zinc-800">
                  <th class="px-5 py-3 text-left font-medium">Date</th>
                  <th class="px-5 py-3 text-left font-medium">Category</th>
                  <th class="px-5 py-3 text-left font-medium">Description</th>
                  <th class="px-5 py-3 text-right font-medium">Amount</th>
                  <th class="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-800/70">
                <?php foreach ($entries as $e): ?>
                <tr class="transition hover:bg-zinc-800/40">
                  <td class="px-5 py-3 text-zinc-400"><?php echo htmlspecialchars(date('M d, Y', strtotime($e['expense_date']))); ?></td>
                  <td class="px-5 py-3"><span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-xs text-rose-300 ring-1 ring-rose-500/20"><?php echo htmlspecialchars($e['category_name'] ?? ''); ?></span></td>
                  <td class="px-5 py-3 text-zinc-300"><?php echo htmlspecialchars($e['description'] ?: '—'); ?></td>
                  <td class="px-5 py-3 text-right font-medium text-rose-300">-$<?php echo number_format($e['amount'], 2); ?></td>
                  <td class="px-5 py-3 text-right">
                    <form method="POST" onsubmit="return confirm('Delete this expense?');" class="inline">
                      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                      <input type="hidden" name="delete_expense" value="<?php echo $e['id']; ?>">
                      <button type="submit" class="rounded-md p-1.5 text-zinc-400 hover:bg-rose-500/10 hover:text-rose-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($entries)): ?>
                <tr><td colspan="5" class="px-5 py-8 text-center text-zinc-500">No expenses found</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php if ($total_records > $per_page): ?>
          <div class="flex items-center justify-center gap-2 border-t border-zinc-800 p-4">
            <?php if ($page > 1): ?><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="rounded-md border border-zinc-800 px-3 py-1.5 text-xs text-zinc-400 hover:bg-zinc-800 hover:text-zinc-50">← Prev</a><?php endif; ?>
            <span class="text-xs text-zinc-500">Page <?php echo $page; ?></span>
            <?php if ($page < ceil($total_records / $per_page)): ?><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="rounded-md border border-zinc-800 px-3 py-1.5 text-xs text-zinc-400 hover:bg-zinc-800 hover:text-zinc-50">Next →</a><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
