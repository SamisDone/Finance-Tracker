<?php 
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/hero_section.php';

requireLogin(); // Redirects to login if not logged in

$pageTitle = 'Dashboard';
include 'includes/header.php'; 

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Total Income this month (use helper function to keep views clean)
$db_type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($db_type === 'sqlite') {
    $stmt_income = $pdo->prepare("SELECT SUM(amount) AS total_income FROM income WHERE user_id = :user_id AND strftime('%m', income_date) = strftime('%m', 'now') AND strftime('%Y', income_date) = strftime('%Y', 'now')");
    $stmt_expenses = $pdo->prepare("SELECT SUM(amount) AS total_expenses FROM expenses WHERE user_id = :user_id AND strftime('%m', expense_date) = strftime('%m', 'now') AND strftime('%Y', expense_date) = strftime('%Y', 'now')");
    $stmt_recent = $pdo->prepare("(SELECT 'income' as type, description, amount, income_date as date FROM income WHERE user_id = :user_id ORDER BY income_date DESC, created_at DESC LIMIT 5)
                       UNION ALL
                       (SELECT 'expense' as type, description, amount, expense_date as date FROM expenses WHERE user_id = :user_id ORDER BY expense_date DESC, created_at DESC LIMIT 5)
                       ORDER BY date DESC LIMIT 5");
} else {
    $stmt_income = $pdo->prepare("SELECT SUM(amount) AS total_income FROM income WHERE user_id = :user_id AND MONTH(income_date) = MONTH(CURDATE()) AND YEAR(income_date) = YEAR(CURDATE())");
    $stmt_expenses = $pdo->prepare("SELECT SUM(amount) AS total_expenses FROM expenses WHERE user_id = :user_id AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
    $stmt_recent = $pdo->prepare("(SELECT 'income' as type, description, amount, income_date as date FROM income WHERE user_id = :user_id ORDER BY income_date DESC, created_at DESC LIMIT 5)
                       UNION ALL
                       (SELECT 'expense' as type, description, amount, expense_date as date FROM expenses WHERE user_id = :user_id ORDER BY expense_date DESC, created_at DESC LIMIT 5)
                       ORDER BY date DESC LIMIT 5");
}
$stmt_income->execute([':user_id' => $user_id]);
$total_income_month = $stmt_income->fetchColumn() ?: 0;

$stmt_expenses->execute([':user_id' => $user_id]);
$total_expenses_month = $stmt_expenses->fetchColumn() ?: 0;

$balance_month = $total_income_month - $total_expenses_month;

// Recent Transactions (last 5)
$stmt_recent->execute([':user_id' => $user_id]);
$recent_transactions = $stmt_recent->fetchAll();

// Fetch goals and calculate overall progress
$stmt_goals = $pdo->prepare("SELECT * FROM financial_goals WHERE user_id = :user_id AND status = 'active' ORDER BY target_date ASC");
$stmt_goals->execute([':user_id' => $user_id]);
$goals = $stmt_goals->fetchAll();

$total_target = 0;
$total_saved = 0;
foreach ($goals as $g) {
    $total_target += $g['target_amount'];
    $total_saved += $g['current_amount'];
}
$overall_progress = $total_target > 0 ? min(100, round(($total_saved / $total_target) * 100)) : 0;
?>

<?php renderHeroSection('dashHeroGradient', '#2563eb', '#14b8a6', 'fa-solid fa-gauge', 'Welcome back, ' . htmlspecialchars($username) . '!', 'Here\'s your monthly snapshot and recent activity.'); ?>

<div class="dashboard-container">
    <div class="summary-cards">
        <div class="card summary-card income-card">
            <div class="card-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            <h3>Income (This Month)</h3>
            <p class="amount">$<?php echo number_format($total_income_month, 2); ?></p>
        </div>
        <div class="card summary-card expense-card">
            <div class="card-icon"><i class="fa-solid fa-wallet"></i></div>
            <h3>Expenses (This Month)</h3>
            <p class="amount">$<?php echo number_format($total_expenses_month, 2); ?></p>
        </div>
        <div class="card summary-card balance-card">
            <div class="card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
            <h3>Balance (This Month)</h3>
            <p class="amount <?php echo $balance_month >= 0 ? 'positive' : 'negative'; ?>">$<?php echo number_format($balance_month, 2); ?></p>
        </div>
    </div>

    <div class="section recent-transactions-section">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Transactions</h3>
        <?php if (!empty($recent_transactions)): ?>
        <table class="transaction-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_transactions as $transaction): ?>
                <tr class="transaction-row-<?php echo htmlspecialchars($transaction['type']); ?>">
                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($transaction['date']))); ?></td>
                    <td class="transaction-type <?php echo $transaction['type'] === 'income' ? 'type-income' : 'type-expense'; ?>">
                        <i class="fa-solid fa-<?php echo $transaction['type'] === 'income' ? 'arrow-down' : 'arrow-up'; ?>"></i> <?php echo htmlspecialchars($transaction['type']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($transaction['description'] ?: 'N/A'); ?></td>
                    <td class="text-right">$<?php echo number_format($transaction['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No recent transactions found.</p>
        <?php endif; ?>
    </div>

    <div class="section quick-actions-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions-grid">
            <a href="income.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Income</a>
            <a href="expenses.php?action=add" class="btn btn-accent"><i class="fa-solid fa-minus"></i> Add Expense</a> 
            <a href="budgets.php" class="btn btn-secondary"><i class="fa-solid fa-chart-pie"></i> View Budgets</a>
        </div>
    </div>

    <div class="section financial-goals-section">
        <h3><i class="fa-solid fa-bullseye"></i> Financial Goals Progress</h3>
        <?php if (count($goals) > 0): ?>
        <div class="progress-bar-container" title="<?php echo $overall_progress; ?>% overall progress">
            <div class="progress-bar">
                <div class="progress-bar-inner" style="width: <?php echo $overall_progress; ?>%;">
                    <?php echo $overall_progress; ?>%
                </div>
            </div>
        </div>
        <p class="text-center">$<?php echo number_format($total_saved, 2); ?> saved of $<?php echo number_format($total_target, 2); ?> total target</p>
        <?php else: ?>
        <p class="text-center">No active financial goals. <a href="savings.php">Create one now!</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
