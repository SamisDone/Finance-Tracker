<?php 
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

requireLogin(); // Redirects to login if not logged in

$pageTitle = 'Dashboard';
include 'includes/header.php'; 

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch some basic data for the dashboard (examples)
// Total Income this month
$stmt_income = $pdo->prepare("SELECT SUM(amount) AS total_income FROM income WHERE user_id = :user_id AND MONTH(income_date) = MONTH(CURDATE()) AND YEAR(income_date) = YEAR(CURDATE())");
$stmt_income->execute([':user_id' => $user_id]);
$total_income_month = $stmt_income->fetchColumn() ?: 0;

// Total Expenses this month
$stmt_expenses = $pdo->prepare("SELECT SUM(amount) AS total_expenses FROM expenses WHERE user_id = :user_id AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
$stmt_expenses->execute([':user_id' => $user_id]);
$total_expenses_month = $stmt_expenses->fetchColumn() ?: 0;

$balance_month = $total_income_month - $total_expenses_month;

// Recent Transactions (last 5)
$stmt_recent = $pdo->prepare("(SELECT 'income' as type, description, amount, income_date as date FROM income WHERE user_id = :user_id ORDER BY income_date DESC, created_at DESC LIMIT 5)
                           UNION ALL
                           (SELECT 'expense' as type, description, amount, expense_date as date FROM expenses WHERE user_id = :user_id ORDER BY expense_date DESC, created_at DESC LIMIT 5)
                           ORDER BY date DESC LIMIT 5");
$stmt_recent->execute([':user_id' => $user_id]);
$recent_transactions = $stmt_recent->fetchAll();

?>

<section class="hero dashboard-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="dashHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2563eb"/>
                    <stop offset="100%" stop-color="#14b8a6"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#dashHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            Welcome back, <span style="color:var(--accent);"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($username); ?></span>!
        </h2>
        <p class="hero-desc">
            Here’s your monthly snapshot and recent activity.
        </p>
    </div>
</section>

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
        <div class="progress-bar-container">
            <div class="progress-bar">
                <div class="progress-bar-inner" style="width: 38%;">
                    38%
                </div>
            </div>
        </div>
        <p class="text-center">Goal progress summary will be displayed here.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
