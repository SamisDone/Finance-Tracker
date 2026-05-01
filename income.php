<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/hero_section.php';
require_once 'includes/helpers.php';
requireLogin();
$pageTitle = 'Income Tracking';
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'error';
    } elseif (isset($_POST['add_income'])) {
        $amount = floatval($_POST['amount']);
        $income_date = $_POST['income_date'];
        $description = trim($_POST['description']);
        $source = trim($_POST['source']);
        $category = trim($_POST['category']);
        $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
        $recurrence_period = $is_recurring ? $_POST['recurrence_period'] : null;

        $source_id = getOrCreateId($pdo, $user_id, 'income_sources', $source);
        $category_id = getOrCreateId($pdo, $user_id, 'income_categories', $category);

        $stmt = $pdo->prepare("INSERT INTO income (user_id, source_id, category_id, amount, income_date, description, is_recurring, recurrence_period) VALUES (:user_id, :source_id, :category_id, :amount, :income_date, :description, :is_recurring, :recurrence_period)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':source_id' => $source_id,
            ':category_id' => $category_id,
            ':amount' => $amount,
            ':income_date' => $income_date,
            ':description' => $description,
            ':is_recurring' => $is_recurring,
            ':recurrence_period' => $recurrence_period
        ]);
        $message = 'Income entry added successfully!';
        $message_type = 'success';
    }
}

// Insert default categories
insertDefaultCategories($pdo, $user_id);

// Fetch dropdown data
$sources = $pdo->prepare("SELECT name FROM income_sources WHERE user_id = :user_id ORDER BY name");
$sources->execute([':user_id' => $user_id]);
$sources = $sources->fetchAll(PDO::FETCH_COLUMN);

$categories = $pdo->prepare("SELECT name FROM income_categories WHERE user_id = :user_id ORDER BY name");
$categories->execute([':user_id' => $user_id]);
$categories = $categories->fetchAll(PDO::FETCH_COLUMN);

// Build search query
$search_sql = '';
$params = [':user_id' => $user_id];
if ($search) {
    $search_sql = " AND (i.description LIKE :search OR s.name LIKE :search OR c.name LIKE :search)";
    $params[':search'] = "%$search%";
}

// Get total count for pagination
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM income i LEFT JOIN income_sources s ON i.source_id = s.id LEFT JOIN income_categories c ON i.category_id = c.id WHERE i.user_id = :user_id" . $search_sql);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();

// Fetch income entries with pagination
$stmt = $pdo->prepare("SELECT i.*, s.name as source_name, c.name as category_name FROM income i LEFT JOIN income_sources s ON i.source_id = s.id LEFT JOIN income_categories c ON i.category_id = c.id WHERE i.user_id = :user_id" . $search_sql . " ORDER BY i.income_date DESC, i.created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$income_entries = $stmt->fetchAll();
?>

<?php renderHeroSection('incomeHeroGradient', '#2563eb', '#14b8a6', 'fa-solid fa-money-bill-trend-up', 'Income Tracking', 'Log your earnings, recurring income, and sources—all in one place.'); ?>

<div class="form-container card mb-4">
    <h2><i class="fa-solid fa-plus"></i> Add Income</h2>
    <?php if ($message): ?><div class="flash-message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="form-group">
            <label for="amount"><i class="fa-solid fa-dollar-sign"></i> Amount:</label>
            <input type="number" step="0.01" min="0" id="amount" name="amount" required>
        </div>
        <div class="form-group">
            <label for="income_date"><i class="fa-solid fa-calendar"></i> Date:</label>
            <input type="date" id="income_date" name="income_date" required value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
            <label for="source">Source:</label>
            <div class="d-flex align-items-center gap-2">
                <select id="source" name="source" required class="form-control-sm flex-grow-1">
                    <option value="">-- Select Source --</option>
                    <?php foreach ($sources as $src): ?>
                        <option value="<?php echo htmlspecialchars($src); ?>"><?php echo htmlspecialchars($src); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addSourceBtn" class="btn btn-secondary btn-sm">Add New</button>
            </div>
            <div id="newSourceForm" class="mt-2" style="display:none;">
                <input type="text" id="new_source_name" placeholder="New source name" class="form-control-sm mb-2">
                <button type="button" id="saveNewSource" class="btn btn-secondary btn-sm me-1">Save</button>
                <button type="button" id="cancelNewSource" class="btn btn-secondary btn-sm">Cancel</button>
            </div>
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
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function setupAddNew(fieldId, selectId, btnId, saveBtnId, cancelBtnId, formId) {
                const btn = document.getElementById(btnId);
                const form = document.getElementById(formId);
                const saveBtn = document.getElementById(saveBtnId);
                const cancelBtn = document.getElementById(cancelBtnId);
                const select = document.getElementById(selectId);
                const input = document.getElementById(fieldId);

                btn.onclick = () => { form.style.display = 'block'; btn.style.display = 'none'; };
                cancelBtn.onclick = () => { form.style.display = 'none'; btn.style.display = ''; };
                saveBtn.onclick = () => {
                    const val = input.value.trim();
                    if (val) {
                        let exists = false;
                        for (let i = 0; i < select.options.length; i++) {
                            if (select.options[i].value === val) { exists = true; break; }
                        }
                        if (!exists) {
                            const opt = document.createElement('option');
                            opt.value = val; opt.textContent = val;
                            select.appendChild(opt);
                        }
                        select.value = val;
                        form.style.display = 'none';
                        btn.style.display = '';
                    }
                };
            }
            setupAddNew('new_source_name', 'source', 'addSourceBtn', 'saveNewSource', 'cancelNewSource', 'newSourceForm');
            setupAddNew('new_category_name', 'category', 'addCategoryBtn', 'saveNewCategory', 'cancelNewCategory', 'newCategoryForm');
        });
        </script>
        <div class="form-group">
            <label><input type="checkbox" name="is_recurring" id="is_recurring" onchange="document.getElementById('recurrence_period').style.display = this.checked ? 'block' : 'none';"> Recurring Income?</label>
            <select name="recurrence_period" id="recurrence_period" style="display:none;">
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
                <option value="yearly">Yearly</option>
                <option value="daily">Daily</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description (optional):</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <button type="submit" name="add_income" class="btn btn-full-width">Add Income</button>
    </form>
</div>

<div class="card data-display-card mb-4">
    <h2 class="card-header">Your Income Entries</h2>
    <div class="card-content">
        <?php if (count($income_entries) > 0): ?>
            <div class="table-controls-container mb-3">
                <form method="GET" action="" class="search-filter-container">
                    <?php echo renderSearchForm($search); ?>
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Source</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Recurring</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($income_entries as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry['income_date']); ?></td>
                        <td>$<?php echo number_format($entry['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($entry['source_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($entry['category_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($entry['description']); ?></td>
                        <td><?php echo $entry['is_recurring'] ? 'Yes (' . htmlspecialchars($entry['recurrence_period']) . ')' : 'No'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php echo renderPagination($page, $total_records, $per_page); ?>
        <?php else: ?>
            <p>No income entries found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
