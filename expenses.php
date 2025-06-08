<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();
$pageTitle = 'Expense Tracking';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle adding new expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $amount = floatval($_POST['amount']);
    $expense_date = $_POST['expense_date'];
    $category = trim($_POST['category']);
    $payment_method = trim($_POST['payment_method']);
    $description = trim($_POST['description']);
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurrence_period = $is_recurring ? $_POST['recurrence_period'] : null;
    $receipt_path = null;

    // Handle receipt upload
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $uploads_dir = 'uploads/receipts';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES['receipt']['name']);
        $target_path = $uploads_dir . '/' . $filename;
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_path)) {
            $receipt_path = $target_path;
        }
    }

    // Insert category and payment method if not exists
    if ($category !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO expense_categories (user_id, name) VALUES (:user_id, :name)");
        $stmt->execute([':user_id' => $user_id, ':name' => $category]);
        $category_id = $pdo->lastInsertId();
        if ($category_id == 0) {
            $stmt = $pdo->prepare("SELECT id FROM expense_categories WHERE user_id = :user_id AND name = :name");
            $stmt->execute([':user_id' => $user_id, ':name' => $category]);
            $category_id = $stmt->fetchColumn();
        }
    } else {
        $category_id = null;
    }
    if ($payment_method !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO payment_methods (user_id, name) VALUES (:user_id, :name)");
        $stmt->execute([':user_id' => $user_id, ':name' => $payment_method]);
        $payment_method_id = $pdo->lastInsertId();
        if ($payment_method_id == 0) {
            $stmt = $pdo->prepare("SELECT id FROM payment_methods WHERE user_id = :user_id AND name = :name");
            $stmt->execute([':user_id' => $user_id, ':name' => $payment_method]);
            $payment_method_id = $stmt->fetchColumn();
        }
    } else {
        $payment_method_id = null;
    }

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

// Handle delete expense
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $del_id, ':user_id' => $user_id]);
    $message = 'Expense deleted.';
    $message_type = 'success';
}

// Default expense categories and payment methods
$default_expense_categories = ['Food', 'Rent', 'Transport', 'Utilities', 'Entertainment', 'Health', 'Education', 'Shopping', 'Other'];
$default_payment_methods = ['Cash', 'Credit Card', 'Debit Card', 'Mobile Wallet', 'Bank Transfer'];
// Insert defaults if none exist for this user
$cat_check = $pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE user_id = :user_id");
$cat_check->execute([':user_id' => $user_id]);
if ($cat_check->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO expense_categories (user_id, name) VALUES (:user_id, :name)");
    foreach ($default_expense_categories as $cat) {
        $stmt->execute([':user_id' => $user_id, ':name' => $cat]);
    }
}
$method_check = $pdo->prepare("SELECT COUNT(*) FROM payment_methods WHERE user_id = :user_id");
$method_check->execute([':user_id' => $user_id]);
if ($method_check->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO payment_methods (user_id, name) VALUES (:user_id, :name)");
    foreach ($default_payment_methods as $m) {
        $stmt->execute([':user_id' => $user_id, ':name' => $m]);
    }
}
// Fetch categories and payment methods
$categories = $pdo->prepare("SELECT name FROM expense_categories WHERE user_id = :user_id ORDER BY name");
$categories->execute([':user_id' => $user_id]);
$categories = $categories->fetchAll(PDO::FETCH_COLUMN);
$methods = $pdo->prepare("SELECT name FROM payment_methods WHERE user_id = :user_id ORDER BY name");
$methods->execute([':user_id' => $user_id]);
$methods = $methods->fetchAll(PDO::FETCH_COLUMN);

// Fetch all expenses
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name, p.name as payment_method_name FROM expenses e LEFT JOIN expense_categories c ON e.category_id = c.id LEFT JOIN payment_methods p ON e.payment_method_id = p.id WHERE e.user_id = :user_id ORDER BY e.expense_date DESC, e.created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$expenses = $stmt->fetchAll();
?>
<section class="hero section-hero expenses-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="expenseHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#ef4444"/>
                    <stop offset="100%" stop-color="#2563eb"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#expenseHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="fa-solid fa-wallet"></i> Expense Tracking
        </h2>
        <p class="hero-desc">
            Record your spending, receipts, and recurring bills with ease.
        </p>
    </div>
</section>
<div class="form-container card mb-4">
    <h2><i class="fa-solid fa-plus"></i> Add Expense</h2>
    <?php if ($message): ?><div class="flash-message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" action="" enctype="multipart/form-data">
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
            <div style="display:flex;gap:8px;align-items:center;">
                <select id="category" name="category" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addCategoryBtn" class="btn btn-secondary" style="padding:2px 8px;">Add New</button>
            </div>
            <div id="newCategoryForm" style="display:none;margin-top:8px;">
                <input type="text" id="new_category_name" placeholder="New category name">
                <button type="button" id="saveNewCategory" class="btn btn-secondary" style="padding:2px 8px;">Save</button>
                <button type="button" id="cancelNewCategory" class="btn btn-secondary" style="padding:2px 8px;">Cancel</button>
            </div>
        </div>
        <div class="form-group">
            <label for="payment_method">Payment Method:</label>
            <div style="display:flex;gap:8px;align-items:center;">
                <select id="payment_method" name="payment_method" required>
                    <option value="">-- Select Method --</option>
                    <?php foreach ($methods as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addMethodBtn" class="btn btn-secondary" style="padding:2px 8px;">Add New</button>
            </div>
            <div id="newMethodForm" style="display:none;margin-top:8px;">
                <input type="text" id="new_method_name" placeholder="New method name">
                <button type="button" id="saveNewMethod" class="btn btn-secondary" style="padding:2px 8px;">Save</button>
                <button type="button" id="cancelNewMethod" class="btn btn-secondary" style="padding:2px 8px;">Cancel</button>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Category add
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            const newCategoryForm = document.getElementById('newCategoryForm');
            const saveNewCategory = document.getElementById('saveNewCategory');
            const cancelNewCategory = document.getElementById('cancelNewCategory');
            const categorySelect = document.getElementById('category');
            addCategoryBtn.onclick = () => { newCategoryForm.style.display = 'block'; addCategoryBtn.style.display = 'none'; };
            cancelNewCategory.onclick = () => { newCategoryForm.style.display = 'none'; addCategoryBtn.style.display = ''; };
            saveNewCategory.onclick = () => {
                const val = document.getElementById('new_category_name').value.trim();
                if (val) {
                    let exists = false;
                    for (let i = 0; i < categorySelect.options.length; i++) {
                        if (categorySelect.options[i].value === val) { exists = true; break; }
                    }
                    if (!exists) {
                        const opt = document.createElement('option');
                        opt.value = val; opt.textContent = val;
                        categorySelect.appendChild(opt);
                    }
                    categorySelect.value = val;
                    newCategoryForm.style.display = 'none';
                    addCategoryBtn.style.display = '';
                }
            };
            // Payment method add
            const addMethodBtn = document.getElementById('addMethodBtn');
            const newMethodForm = document.getElementById('newMethodForm');
            const saveNewMethod = document.getElementById('saveNewMethod');
            const cancelNewMethod = document.getElementById('cancelNewMethod');
            const methodSelect = document.getElementById('payment_method');
            addMethodBtn.onclick = () => { newMethodForm.style.display = 'block'; addMethodBtn.style.display = 'none'; };
            cancelNewMethod.onclick = () => { newMethodForm.style.display = 'none'; addMethodBtn.style.display = ''; };
            saveNewMethod.onclick = () => {
                const val = document.getElementById('new_method_name').value.trim();
                if (val) {
                    let exists = false;
                    for (let i = 0; i < methodSelect.options.length; i++) {
                        if (methodSelect.options[i].value === val) { exists = true; break; }
                    }
                    if (!exists) {
                        const opt = document.createElement('option');
                        opt.value = val; opt.textContent = val;
                        methodSelect.appendChild(opt);
                    }
                    methodSelect.value = val;
                    newMethodForm.style.display = 'none';
                    addMethodBtn.style.display = '';
                }
            };
        });
        </script>
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
                <td><a href="?delete=<?php echo $exp['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this expense?');">Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?><p>No expenses found.</p><?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
