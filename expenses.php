<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/hero_section.php';
require_once 'includes/helpers.php';
requireLogin();
$pageTitle = 'Expense Tracking';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Pagination settings
$per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Search
$search = trim($_GET['search'] ?? '');

// Handle form submission
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'error';
    } else {
        $amount = floatval($_POST['amount']);
        $expense_date = $_POST['expense_date'];
        $category = trim($_POST['category']);
        $payment_method = trim($_POST['payment_method']);
        $description = trim($_POST['description']);
        $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
        $recurrence_period = $is_recurring ? $_POST['recurrence_period'] : null;
        $receipt_path = null;

        // Handle receipt upload with security
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $uploads_dir = 'uploads/receipts';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0755, true);
            }

            $file = $_FILES['receipt'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $file['tmp_name']);

            if (!in_array($mime_type, $allowed_types)) {
                $message = 'Invalid file type. Only JPG, PNG, GIF, and PDF are allowed.';
                $message_type = 'error';
            } elseif ($file['size'] > 5242880) { // 5MB limit
                $message = 'File too large. Maximum 5MB allowed.';
                $message_type = 'error';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safe_filename = md5(uniqid() . $file['name']) . '.' . $ext;
                $target_path = $uploads_dir . '/' . $safe_filename;
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $receipt_path = $target_path;
                } else {
                    $message = 'Failed to save receipt file. Check directory permissions.';
                    $message_type = 'error';
                }
            }
        }

        if (!$message) {
            $category_id = getOrCreateId($pdo, $user_id, 'expense_categories', $category);
            $payment_method_id = getOrCreateId($pdo, $user_id, 'payment_methods', $payment_method);

            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category_id, payment_method_id, amount, expense_date, description, is_recurring, recurrence_period, receipt_path) VALUES (:user_id, :category_id, :payment_method_id, :amount, :expense_date, :description, :is_recurring, :recurrence_period, :receipt_path)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':category_id' => $category_id,
                ':payment_method_id' => $payment_method_id,
                ':amount' => $amount,
                ':expense_date' => $expense_date,
                ':description' => $description,
                ':is_recurring' => $is_recurring,
                ':recurrence_period' => $recurrence_period,
                ':receipt_path' => $receipt_path
            ]);
            $message = 'Expense entry added successfully!';
            $message_type = 'success';
        }
    }
}

// Handle delete expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_expense'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $message_type = 'error';
    } else {
        $del_id = intval($_POST['delete_expense']);
        // First get the receipt path to delete the file
        $stmt = $pdo->prepare("SELECT receipt_path FROM expenses WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $del_id, ':user_id' => $user_id]);
        $expense = $stmt->fetch();
        if ($expense && $expense['receipt_path'] && file_exists($expense['receipt_path'])) {
            unlink($expense['receipt_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $del_id, ':user_id' => $user_id]);
        $message = 'Expense deleted.';
        $message_type = 'success';
    }
}

// Insert default categories
insertDefaultCategories($pdo, $user_id);

// Fetch dropdown data
$categories = $pdo->prepare("SELECT name FROM expense_categories WHERE user_id = :user_id ORDER BY name");
$categories->execute([':user_id' => $user_id]);
$categories = $categories->fetchAll(PDO::FETCH_COLUMN);

$methods = $pdo->prepare("SELECT name FROM payment_methods WHERE user_id = :user_id ORDER BY name");
$methods->execute([':user_id' => $user_id]);
$methods = $methods->fetchAll(PDO::FETCH_COLUMN);

// Build search query
$search_sql = '';
$params = [':user_id' => $user_id];
if ($search) {
    $search_sql = " AND (e.description LIKE :search OR c.name LIKE :search OR p.name LIKE :search)";
    $params[':search'] = "%$search%";
}

// Get total count for pagination
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM expenses e LEFT JOIN expense_categories c ON e.category_id = c.id LEFT JOIN payment_methods p ON e.payment_method_id = p.id WHERE e.user_id = :user_id" . $search_sql);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();

// Fetch expenses with pagination
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name, p.name as payment_method_name FROM expenses e LEFT JOIN expense_categories c ON e.category_id = c.id LEFT JOIN payment_methods p ON e.payment_method_id = p.id WHERE e.user_id = :user_id" . $search_sql . " ORDER BY e.expense_date DESC, e.created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$expenses = $stmt->fetchAll();
?>

<?php renderHeroSection('expenseHeroGradient', '#ef4444', '#2563eb', 'fa-solid fa-wallet', 'Expense Tracking', 'Record your spending, receipts, and recurring bills with ease.'); ?>

<div class="form-container card mb-4">
    <h2><i class="fa-solid fa-plus"></i> Add Expense</h2>
    <?php if ($message): ?><div class="flash-message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="form-group">
            <label for="amount"><i class="fa-solid fa-dollar-sign"></i> Amount:</label>
            <input type="number" step="0.01" min="0" id="amount" name="amount" required>
        </div>
        <div class="form-group">
            <label for="expense_date">Date:</label>
            <input type="date" id="expense_date" name="expense_date" required value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
            <label for="category">Category:</label>
            <div class="d-flex align-items-center gap-2">
                <select id="category" name="category" required class="form-control-sm flex-grow-1">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addCategoryBtn" class="btn btn-secondary btn-sm">Add New</button>
            </div>
            <div id="newCategoryForm" class="mt-2" style="display:none;">
                <input type="text" id="new_category_name" placeholder="New category name" class="form-control-sm mb-2">
                <button type="button" id="saveNewCategory" class="btn btn-secondary btn-sm me-1">Save</button>
                <button type="button" id="cancelNewCategory" class="btn btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
        <div class="form-group">
            <label for="payment_method">Payment Method:</label>
            <div class="d-flex align-items-center gap-2">
                <select id="payment_method" name="payment_method" required class="form-control-sm flex-grow-1">
                    <option value="">-- Select Method --</option>
                    <?php foreach ($methods as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addMethodBtn" class="btn btn-secondary btn-sm">Add New</button>
            </div>
            <div id="newMethodForm" class="mt-2" style="display:none;">
                <input type="text" id="new_method_name" placeholder="New method name" class="form-control-sm mb-2">
                <button type="button" id="saveNewMethod" class="btn btn-secondary btn-sm me-1">Save</button>
                <button type="button" id="cancelNewMethod" class="btn btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_recurring" id="is_recurring" onchange="document.getElementById('recurrence_period').style.display = this.checked ? 'block' : 'none';"> Recurring Expense?</label>
            <select name="recurrence_period" id="recurrence_period" style="display:none;">
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
                <option value="yearly">Yearly</option>
                <option value="daily">Daily</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Notes (optional):</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <div class="form-group">
            <label for="receipt">Receipt (optional):</label>
            <input type="file" id="receipt" name="receipt" accept="image/*,application/pdf">
        </div>
        <button type="submit" name="add_expense" class="btn btn-full-width">Add Expense</button>
    </form>
</div>

<div class="card data-display-card mb-4">
    <h2 class="card-header">Your Expenses</h2>
    <div class="card-content">
        <div class="table-controls-container mb-3">
            <form method="GET" action="" class="search-filter-container">
                <?php echo renderSearchForm($search); ?>
            </form>
        </div>
        <?php if (count($expenses) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Category</th>
                        <th>Payment Method</th>
                        <th>Notes</th>
                        <th>Recurring</th>
                        <th>Receipt</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $exp): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exp['expense_date']); ?></td>
                        <td>$<?php echo number_format($exp['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($exp['category_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($exp['payment_method_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($exp['description']); ?></td>
                        <td><?php echo $exp['is_recurring'] ? 'Yes (' . htmlspecialchars($exp['recurrence_period']) . ')' : 'No'; ?></td>
                        <td><?php if ($exp['receipt_path']): ?><a href="<?php echo htmlspecialchars($exp['receipt_path']); ?>" target="_blank">View</a><?php endif; ?></td>
                        <td>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this expense?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="delete_expense" value="<?php echo $exp['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php echo renderPagination($page, $total_records, $per_page); ?>
        <?php else: ?>
            <p>No expenses found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
