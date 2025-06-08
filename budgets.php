<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();
$pageTitle = 'Budgets';
include 'includes/header.php';
echo '<div class="container page-container mt-3">'; 
echo '  <nav aria-label="breadcrumb" class="mb-4">';
echo '    <ol class="breadcrumb">';
echo '      <li class="breadcrumb-item"><a href="index.php?view=dashboard">Dashboard</a></li>';
echo '      <li class="breadcrumb-item"><a href="budgets.php">Budgets</a></li>';
if (isset($_GET['edit_id'])) {
    echo '      <li class="breadcrumb-item active" aria-current="page">Edit Budget</li>';
}
echo '    </ol>';
echo '  </nav>';
echo '</div>';

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle add budget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_budget'])) {
    $name = trim($_POST['name']);
    $period_type = $_POST['period_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_limit = floatval($_POST['total_limit']);
    $category_limits = $_POST['category_limits'] ?? [];

    // Insert budget
    $stmt = $pdo->prepare("INSERT INTO budgets (user_id, name, period_type, start_date, end_date, total_limit) VALUES (:user_id, :name, :period_type, :start_date, :end_date, :total_limit)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':name' => $name,
        ':period_type' => $period_type,
        ':start_date' => $start_date,
        ':end_date' => $end_date,
        ':total_limit' => $total_limit
    ]);
    $budget_id = $pdo->lastInsertId();

    // Insert category limits
    foreach ($category_limits as $cat_id => $limit) {
        if (is_numeric($cat_id) && $limit !== '') {
            $stmt = $pdo->prepare("INSERT INTO budget_categories (budget_id, expense_category_id, limit_amount) VALUES (:budget_id, :cat_id, :limit)");
            $stmt->execute([
                ':budget_id' => $budget_id,
                ':cat_id' => $cat_id,
                ':limit' => floatval($limit)
            ]);
        }
    }
    $message = 'Budget created!';
    $message_type = 'success';
}

// Default expense categories
$default_expense_categories = ['Food', 'Rent', 'Transport', 'Utilities', 'Entertainment', 'Health', 'Education', 'Shopping', 'Other'];
$cat_check = $pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE user_id = :user_id");
$cat_check->execute([':user_id' => $user_id]);
if ($cat_check->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO expense_categories (user_id, name) VALUES (:user_id, :name)");
    foreach ($default_expense_categories as $cat) {
        $stmt->execute([':user_id' => $user_id, ':name' => $cat]);
    }
}
// Fetch categories
$cat_stmt = $pdo->prepare("SELECT id, name FROM expense_categories WHERE user_id = :user_id ORDER BY name");
$cat_stmt->execute([':user_id' => $user_id]);
$categories = $cat_stmt->fetchAll();

// Fetch budgets
$bud_stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = :user_id ORDER BY start_date DESC");
$bud_stmt->execute([':user_id' => $user_id]);
$budgets = $bud_stmt->fetchAll();

// Fetch budget categories
$budget_cats = [];
if ($budgets) {
    $budget_ids = array_column($budgets, 'id');
    $in = str_repeat('?,', count($budget_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM budget_categories WHERE budget_id IN ($in)");
    $stmt->execute($budget_ids);
    foreach ($stmt->fetchAll() as $row) {
        $budget_cats[$row['budget_id']][] = $row;
    }
}
// Calculate actuals for each budget/category
$actuals = [];
foreach ($budgets as $b) {
    // Get all expenses in this budget period
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
<section class="hero section-hero budgets-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="budgetHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2563eb"/>
                    <stop offset="100%" stop-color="#f59e42"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#budgetHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="fa-solid fa-chart-pie"></i> Budgets
        </h2>
        <p class="hero-desc">
            Set monthly or category-wise limits and track your spending visually.
        </p>
    </div>
</section>
<div class="form-container card mb-4">
    <h2>
    <?php if (isset($_GET['edit_id'])): ?>
        <i class="fa-solid fa-pencil-alt"></i> Edit Budget
    <?php else: ?>
        <i class="fa-solid fa-plus"></i> Add Budget
    <?php endif; ?>
</h2>
    <?php if ($message): ?><div class="flash-message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Budget Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="period_type">Period:</label>
            <select name="period_type" id="period_type" required>
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" required>
        </div>
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" required>
        </div>
        <div class="form-group">
            <label for="total_limit">Total Limit:</label>
            <input type="number" step="0.01" min="0" id="total_limit" name="total_limit" required>
        </div>
        <h4>Category-wise Limits (optional):</h4>
        <div id="budget-category-limits">
        <?php foreach ($categories as $cat): ?>
            <div class="form-group budget-cat-limit-row">
                <label><?php echo htmlspecialchars($cat['name']); ?></label>
                <input type="number" step="0.01" min="0" name="category_limits[<?php echo $cat['id']; ?>]" placeholder="Limit for <?php echo htmlspecialchars($cat['name']); ?>">
            </div>
        <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <button type="button" id="addBudgetCategoryBtn" class="btn btn-secondary" style="padding:2px 8px;">Add New Category</button>
        </div>
        <div id="newBudgetCategoryForm" style="display:none;margin-top:8px;">
            <input type="text" id="new_budget_category_name" placeholder="New category name">
            <button type="button" id="saveNewBudgetCategory" class="btn btn-secondary" style="padding:2px 8px;">Save</button>
            <button type="button" id="cancelNewBudgetCategory" class="btn btn-secondary" style="padding:2px 8px;">Cancel</button>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('addBudgetCategoryBtn');
            const newForm = document.getElementById('newBudgetCategoryForm');
            const saveBtn = document.getElementById('saveNewBudgetCategory');
            const cancelBtn = document.getElementById('cancelNewBudgetCategory');
            const container = document.getElementById('budget-category-limits');
            addBtn.onclick = () => { newForm.style.display = 'block'; addBtn.style.display = 'none'; };
            cancelBtn.onclick = () => { newForm.style.display = 'none'; addBtn.style.display = ''; };
            saveBtn.onclick = () => {
                const val = document.getElementById('new_budget_category_name').value.trim();
                if (val) {
                    // Check if already exists
                    let exists = false;
                    container.querySelectorAll('label').forEach(function(lbl) {
                        if (lbl.textContent === val) exists = true;
                    });
                    if (!exists) {
                        // Add new row
                        const div = document.createElement('div');
                        div.className = 'form-group budget-cat-limit-row';
                        div.innerHTML = `<label>${val}</label><input type=\"number\" step=\"0.01\" min=\"0\" name=\"category_limits[new_${val.replace(/[^a-zA-Z0-9]/g,'_')}\"] placeholder=\"Limit for ${val}\">`;
                        container.appendChild(div);
                    }
                    newForm.style.display = 'none';
                    addBtn.style.display = '';
                }
            };
        });
        </script>
        <button type="submit" name="add_budget" class="btn btn-full-width">Create Budget</button>
    </form>
</div>
<div class="card data-display-card mt-4">
    <h2 class="card-header">Your Budgets</h2>
    <div class="card-content">
                    <div class="table-controls-container mb-3">
                        <div class="search-filter-container">
                            <div class="search-bar me-2">
                                <input type="text" class="form-control form-control-sm" placeholder="Search budgets..." aria-label="Search budgets">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary filter-btn">
                                <i class="fas fa-filter me-1"></i> Filters
                            </button>
                        </div>
                    </div>
        <?php if (count($budgets) > 0): ?>
        <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Period</th>
                <th>Dates</th>
                <th>Total Limit</th>
                <th>Spent</th>
                <th>Status</th>
                <th>Category Limits</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($budgets as $b): ?>
            <?php $over = $actuals[$b['id']]['total'] > $b['total_limit']; ?>
            <tr>
                <td><?php echo htmlspecialchars($b['name']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($b['period_type'])); ?></td>
                <td><?php echo htmlspecialchars($b['start_date']) . ' to ' . htmlspecialchars($b['end_date']); ?></td>
                <td>$<?php echo number_format($b['total_limit'], 2); ?></td>
                <td>$<?php echo number_format($actuals[$b['id']]['total'], 2); ?></td>
                <td class="budget-status <?php echo $over ? 'status-overspent' : 'status-ok'; ?>"><?php echo $over ? 'Overspent!' : 'OK'; ?></td>
                <td>
                    <?php if (!empty($budget_cats[$b['id']])): ?>
                        <ul>
                            <?php foreach ($budget_cats[$b['id']] as $cat): ?>
                                <?php $cat_over = isset($actuals[$b['id']]['categories'][$cat['expense_category_id']]) && $actuals[$b['id']]['categories'][$cat['expense_category_id']] > $cat['limit_amount']; ?>
                                <li>
                                    <?php
                                    $cat_name = '';
                                    foreach ($categories as $c) if ($c['id'] == $cat['expense_category_id']) $cat_name = $c['name'];
                                    ?>
                                    <?php echo htmlspecialchars($cat_name); ?>: $<?php echo number_format($cat['limit_amount'], 2); ?> (Spent: $<?php echo number_format($actuals[$b['id']]['categories'][$cat['expense_category_id']] ?? 0, 2); ?>)
                                    <span class="budget-status <?php echo $cat_over ? 'status-overspent' : 'status-ok'; ?>">
                                        <?php echo $cat_over ? 'Overspent!' : 'OK'; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <em>None</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No budgets found.</p>
    <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
