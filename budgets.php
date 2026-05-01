<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/hero_section.php';
require_once 'includes/helpers.php';
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

// Handle edit budget - fetch existing data
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

// Handle delete budget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_budget'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $message_type = 'error';
    } else {
        $del_id = intval($_POST['delete_budget']);
        $stmt_del = $pdo->prepare("DELETE FROM budgets WHERE id = :id AND user_id = :user_id");
        $stmt_del->execute([':id' => $del_id, ':user_id' => $user_id]);
        $message = 'Budget deleted.';
        $message_type = 'success';
    }
}

// Handle add/update budget
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
            $stmt_upd = $pdo->prepare("UPDATE budgets SET name = :name, period_type = :period_type, start_date = :start_date, end_date = :end_date, total_limit = :total_limit WHERE id = :id AND user_id = :user_id");
            $stmt_upd->execute([
                ':name' => $name,
                ':period_type' => $period_type,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':total_limit' => $total_limit,
                ':id' => $budget_id,
                ':user_id' => $user_id
            ]);

            $stmt_del = $pdo->prepare("DELETE FROM budget_categories WHERE budget_id = :budget_id");
            $stmt_del->execute([':budget_id' => $budget_id]);

            foreach ($category_limits as $cat_id => $limit) {
                if (is_numeric($cat_id) && $limit !== '') {
                    $stmt_ins = $pdo->prepare("INSERT INTO budget_categories (budget_id, expense_category_id, limit_amount) VALUES (:budget_id, :cat_id, :limit)");
                    $stmt_ins->execute([
                        ':budget_id' => $budget_id,
                        ':cat_id' => $cat_id,
                        ':limit' => floatval($limit)
                    ]);
                }
            }
            $message = 'Budget updated!';
            $message_type = 'success';
        } else {
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
    }
}

insertDefaultCategories($pdo, $user_id);

// Fetch categories
$cat_stmt = $pdo->prepare("SELECT id, name FROM expense_categories WHERE user_id = :user_id ORDER BY name");
$cat_stmt->execute([':user_id' => $user_id]);
$categories = $cat_stmt->fetchAll();

// Fetch budgets
$bud_stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = :user_id ORDER BY start_date DESC");
$bud_stmt->execute([':user_id' => $user_id]);
$budgets = $bud_stmt->fetchAll();

// Fetch budget categories safely
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

// Calculate actuals for each budget/category
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

<?php renderHeroSection('budgetHeroGradient', '#2563eb', '#f59e42', 'fa-solid fa-chart-pie', 'Budgets', 'Set monthly or category-wise limits and track your spending visually.'); ?>

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
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <?php if ($budget_to_edit): ?>
            <input type="hidden" name="budget_id" value="<?php echo $budget_to_edit['id']; ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="name">Budget Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($budget_to_edit['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="period_type">Period:</label>
            <select name="period_type" id="period_type" required>
                <option value="monthly" <?php echo (isset($budget_to_edit) && $budget_to_edit['period_type'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                <option value="weekly" <?php echo (isset($budget_to_edit) && $budget_to_edit['period_type'] == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                <option value="yearly" <?php echo (isset($budget_to_edit) && $budget_to_edit['period_type'] == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
            </select>
        </div>
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $budget_to_edit['start_date'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $budget_to_edit['end_date'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="total_limit">Total Limit:</label>
            <input type="number" step="0.01" min="0" id="total_limit" name="total_limit" value="<?php echo $budget_to_edit['total_limit'] ?? ''; ?>" required>
        </div>
        <h4>Category-wise Limits (optional):</h4>
        <div id="budget-category-limits">
        <?php foreach ($categories as $cat): 
            $current_limit = '';
            if (isset($budget_to_edit['category_limits'])) {
                foreach ($budget_to_edit['category_limits'] as $cl) {
                    if ($cl['expense_category_id'] == $cat['id']) {
                        $current_limit = $cl['limit_amount'];
                        break;
                    }
                }
            }
        ?>
            <div class="form-group budget-cat-limit-row">
                <label><?php echo htmlspecialchars($cat['name']); ?></label>
                <input type="number" step="0.01" min="0" name="category_limits[<?php echo $cat['id']; ?>]" placeholder="Limit for <?php echo htmlspecialchars($cat['name']); ?>" value="<?php echo $current_limit; ?>">
            </div>
        <?php endforeach; ?>
        </div>
        <button type="submit" name="<?php echo $budget_to_edit ? 'update_budget' : 'add_budget'; ?>" class="btn btn-full-width">
            <?php echo $budget_to_edit ? 'Update Budget' : 'Create Budget'; ?>
        </button>
        <?php if ($budget_to_edit): ?>
            <a href="budgets.php" class="btn btn-secondary btn-full-width mt-2">Cancel Edit</a>
        <?php endif; ?>
    </form>
</div>

<div class="card data-display-card mt-4">
    <h2 class="card-header">Your Budgets</h2>
    <div class="card-content">
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
                <th>Actions</th>
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
                <td>
                    <a href="?edit_id=<?php echo $b['id']; ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-edit"></i> Edit</a>
                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this budget?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="delete_budget" value="<?php echo $b['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
                    </form>
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
