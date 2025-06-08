<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();
$pageTitle = 'Income Tracking';
include 'includes/header.php';
echo '<div class="container page-container mt-3">'; // Assuming a general container for page content
echo '  <nav aria-label="breadcrumb" class="mb-4">';
echo '    <ol class="breadcrumb">';
echo '      <li class="breadcrumb-item"><a href="index.php?view=dashboard">Dashboard</a></li>';
echo '      <li class="breadcrumb-item"><a href="income.php">Income</a></li>';
if (isset($_GET['edit_id'])) {
    echo '      <li class="breadcrumb-item active" aria-current="page">Edit Income Entry</li>';
}
echo '    </ol>';
echo '  </nav>';
echo '</div>'; // Closing the container

$user_id = $_SESSION['user_id'];

// Handle form submission for adding income
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_income'])) {
    $amount = floatval($_POST['amount']);
    $income_date = $_POST['income_date'];
    $description = trim($_POST['description']);
    $source = trim($_POST['source']);
    $category = trim($_POST['category']);
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurrence_period = $is_recurring ? $_POST['recurrence_period'] : null;

    // Insert income source and category if not empty (for now, simple insert)
    if ($source !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO income_sources (user_id, name) VALUES (:user_id, :name)");
        $stmt->execute([':user_id' => $user_id, ':name' => $source]);
        $source_id = $pdo->lastInsertId();
        if ($source_id == 0) {
            $stmt = $pdo->prepare("SELECT id FROM income_sources WHERE user_id = :user_id AND name = :name");
            $stmt->execute([':user_id' => $user_id, ':name' => $source]);
            $source_id = $stmt->fetchColumn();
        }
    } else {
        $source_id = null;
    }
    if ($category !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO income_categories (user_id, name) VALUES (:user_id, :name)");
        $stmt->execute([':user_id' => $user_id, ':name' => $category]);
        $category_id = $pdo->lastInsertId();
        if ($category_id == 0) {
            $stmt = $pdo->prepare("SELECT id FROM income_categories WHERE user_id = :user_id AND name = :name");
            $stmt->execute([':user_id' => $user_id, ':name' => $category]);
            $category_id = $stmt->fetchColumn();
        }
    } else {
        $category_id = null;
    }
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
}
// Default sources and categories
$default_sources = ['Salary', 'Freelance', 'Interest', 'Gifts', 'Investments'];
$default_categories = ['Primary Job', 'Side Hustle', 'Bonus', 'Passive Income', 'Other'];

// Insert defaults if none exist for this user
$src_check = $pdo->prepare("SELECT COUNT(*) FROM income_sources WHERE user_id = :user_id");
$src_check->execute([':user_id' => $user_id]);
if ($src_check->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO income_sources (user_id, name) VALUES (:user_id, :name)");
    foreach ($default_sources as $src) {
        $stmt->execute([':user_id' => $user_id, ':name' => $src]);
    }
}
$cat_check = $pdo->prepare("SELECT COUNT(*) FROM income_categories WHERE user_id = :user_id");
$cat_check->execute([':user_id' => $user_id]);
if ($cat_check->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO income_categories (user_id, name) VALUES (:user_id, :name)");
    foreach ($default_categories as $cat) {
        $stmt->execute([':user_id' => $user_id, ':name' => $cat]);
    }
}
// Fetch all income sources and categories for dropdowns
$sources = $pdo->prepare("SELECT name FROM income_sources WHERE user_id = :user_id ORDER BY name");
$sources->execute([':user_id' => $user_id]);
$sources = $sources->fetchAll(PDO::FETCH_COLUMN);
$categories = $pdo->prepare("SELECT name FROM income_categories WHERE user_id = :user_id ORDER BY name");
$categories->execute([':user_id' => $user_id]);
$categories = $categories->fetchAll(PDO::FETCH_COLUMN);
// Fetch all income entries for the user
$stmt = $pdo->prepare("SELECT i.*, s.name as source_name, c.name as category_name FROM income i LEFT JOIN income_sources s ON i.source_id = s.id LEFT JOIN income_categories c ON i.category_id = c.id WHERE i.user_id = :user_id ORDER BY i.income_date DESC, i.created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$income_entries = $stmt->fetchAll();
?>
<section class="hero section-hero income-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="incomeHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2563eb"/>
                    <stop offset="100%" stop-color="#14b8a6"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#incomeHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="fa-solid fa-money-bill-trend-up"></i> Income Tracking
        </h2>
        <p class="hero-desc">
            Log your earnings, recurring income, and sources—all in one place.
        </p>
    </div>
</section>
<div class="form-container card mb-4">
    <h2>
    <?php if (isset($_GET['edit_id'])): ?>
        <i class="fa-solid fa-pencil-alt"></i> Edit Income Entry
    <?php else: ?>
        <i class="fa-solid fa-plus"></i> Add Income
    <?php endif; ?>
</h2>
    <?php if ($message): ?><div class="flash-message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" action="">
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
            // Source add
            const addSourceBtn = document.getElementById('addSourceBtn');
            const newSourceForm = document.getElementById('newSourceForm');
            const saveNewSource = document.getElementById('saveNewSource');
            const cancelNewSource = document.getElementById('cancelNewSource');
            const sourceSelect = document.getElementById('source');
            addSourceBtn.onclick = () => { newSourceForm.style.display = 'block'; addSourceBtn.style.display = 'none'; };
            cancelNewSource.onclick = () => { newSourceForm.style.display = 'none'; addSourceBtn.style.display = ''; };
            saveNewSource.onclick = () => {
                const val = document.getElementById('new_source_name').value.trim();
                if (val) {
                    let exists = false;
                    for (let i = 0; i < sourceSelect.options.length; i++) {
                        if (sourceSelect.options[i].value === val) { exists = true; break; }
                    }
                    if (!exists) {
                        const opt = document.createElement('option');
                        opt.value = val; opt.textContent = val;
                        sourceSelect.appendChild(opt);
                    }
                    sourceSelect.value = val;
                    newSourceForm.style.display = 'none';
                    addSourceBtn.style.display = '';
                }
            };
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
                        <div class="search-filter-container">
                            <div class="search-bar me-2">
                                <input type="text" class="form-control form-control-sm" placeholder="Search income..." aria-label="Search income entries">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary filter-btn">
                                <i class="fas fa-filter me-1"></i> Filters
                            </button>
                        </div>
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
    <?php else: ?><p>No income entries found.</p><?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
