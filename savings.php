<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();
$pageTitle = 'Savings & Goals';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

$goal_to_edit = null; // Variable to hold goal data for editing

// Handle Delete Financial Goal
if (isset($_GET['delete_goal_id']) && !empty($_GET['delete_goal_id'])) {
    $goal_id_to_delete = $_GET['delete_goal_id'];
    // Ensure the goal belongs to the current user before deleting
    $stmt_delete = $pdo->prepare("DELETE FROM financial_goals WHERE id = :id AND user_id = :user_id");
    $stmt_delete->execute([':id' => $goal_id_to_delete, ':user_id' => $user_id]);
    if ($stmt_delete->rowCount() > 0) {
        $message = 'Goal deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Error deleting goal or goal not found.';
        $message_type = 'error';
    }
    // To prevent re-deletion on refresh if user manually reloads with query params:
    // header('Location: savings.php'); 
    // exit;
}

// Handle Fetch Financial Goal for Editing
if (isset($_GET['edit_goal_id']) && !empty($_GET['edit_goal_id'])) {
    $goal_id_to_edit = $_GET['edit_goal_id'];
    $stmt_fetch_edit = $pdo->prepare("SELECT * FROM financial_goals WHERE id = :id AND user_id = :user_id");
    $stmt_fetch_edit->execute([':id' => $goal_id_to_edit, ':user_id' => $user_id]);
    $goal_to_edit = $stmt_fetch_edit->fetch();
    if (!$goal_to_edit) {
        $message = 'Goal not found or you do not have permission to edit it.';
        $message_type = 'error';
        // Clear the GET parameter to avoid confusion if the form is submitted for adding a new goal
        // unset($_GET['edit_goal_id']); 
    }
}

// Handle add savings account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_savings'])) {
    $account_name = trim($_POST['account_name']);
    $current_balance = floatval($_POST['current_balance']);
    $stmt = $pdo->prepare("INSERT INTO savings_accounts (user_id, account_name, current_balance) VALUES (:user_id, :account_name, :current_balance)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':account_name' => $account_name,
        ':current_balance' => $current_balance
    ]);
    $message = 'Savings account added!';
    $message_type = 'success';
}

// Handle Add or Update Financial Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_goal']) || isset($_POST['update_goal']))) {
    $goal_name = trim($_POST['goal_name']);
    $target_amount = floatval($_POST['target_amount']);
    $current_amount = floatval($_POST['current_amount']);
    $target_date = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
    $description = trim($_POST['description']);
    
    if (isset($_POST['update_goal']) && !empty($_POST['goal_id'])) {
        // Update existing goal
        $goal_id = $_POST['goal_id'];
        $stmt_update = $pdo->prepare("UPDATE financial_goals SET goal_name = :goal_name, target_amount = :target_amount, current_amount = :current_amount, target_date = :target_date, description = :description WHERE id = :id AND user_id = :user_id");
        $stmt_update->execute([
            ':goal_name' => $goal_name,
            ':target_amount' => $target_amount,
            ':current_amount' => $current_amount,
            ':target_date' => $target_date,
            ':description' => $description,
            ':id' => $goal_id,
            ':user_id' => $user_id
        ]);
        if ($stmt_update->rowCount() > 0) {
            $message = 'Goal updated successfully!';
            $message_type = 'success';
        } else {
            // Could be an error, or no actual change was made to the data
            $message = 'No changes detected or error updating goal.'; 
            $message_type = 'warning'; 
        }
        // After updating, it's good practice to redirect to clear POST data and avoid re-submission on refresh
        // header('Location: savings.php'); 
        // exit;
        // For now, we'll let it reload. $goal_to_edit will be null unless edit_goal_id is still in URL.
        $goal_to_edit = null; // Reset after update attempt

    } else if (isset($_POST['add_goal'])) {
        // Add new goal
        $stmt_insert = $pdo->prepare("INSERT INTO financial_goals (user_id, goal_name, target_amount, current_amount, target_date, description) VALUES (:user_id, :goal_name, :target_amount, :current_amount, :target_date, :description)");
        $stmt_insert->execute([
            ':user_id' => $user_id,
            ':goal_name' => $goal_name,
            ':target_amount' => $target_amount,
            ':current_amount' => $current_amount,
            ':target_date' => $target_date,
            ':description' => $description
        ]);
        $message = 'Goal added!';
        $message_type = 'success';
    }
}

// Fetch savings accounts
$stmt = $pdo->prepare("SELECT * FROM savings_accounts WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$savings = $stmt->fetchAll();
// Fetch goals
$stmt = $pdo->prepare("SELECT * FROM financial_goals WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$goals = $stmt->fetchAll();
?>
<section class="hero section-hero savings-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="savingsHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#22c55e"/>
                    <stop offset="100%" stop-color="#2563eb"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#savingsHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="fa-solid fa-piggy-bank"></i> Savings & Goals
        </h2>
        <p class="hero-desc">
            Track your savings accounts and financial goals visually.
        </p>
    </div>
</section>
<div class="form-container card mb-4">
    <h2><i class="fa-solid fa-plus"></i> Add Savings Account</h2>
    <?php if ($message): ?><div class="flash-message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="account_name"><i class="fa-solid fa-building-columns"></i> Account Name:</label>
            <input type="text" id="account_name" name="account_name" required>
        </div>
        <div class="form-group">
            <label for="current_balance">Current Balance:</label>
            <input type="number" step="0.01" min="0" id="current_balance" name="current_balance" required>
        </div>
        <button type="submit" name="add_savings" class="btn btn-full-width">Add Savings Account</button>
    </form>
</div>
<div class="card data-display-card mb-4">
    <h2 class="card-header">Your Savings Accounts</h2>
    <div class="card-content">
    <?php if (count($savings) > 0): ?>
    <table>
        <thead>
            <tr><th>Account Name</th><th>Current Balance</th><th>Created</th></tr>
        </thead>
        <tbody>
            <?php foreach ($savings as $s): ?>
            <tr>
                <td><?php echo htmlspecialchars($s['account_name']); ?></td>
                <td>$<?php echo number_format($s['current_balance'], 2); ?></td>
                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($s['created_at']))); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?><p>No savings accounts found.</p><?php endif; ?>
    </div>
</div>
<div class="form-container card mb-4">
    <h2>
        <i class="fa-solid fa-bullseye"></i> 
        <?php echo $goal_to_edit ? 'Edit Financial Goal' : 'Add Financial Goal'; ?>
    </h2>
    <form method="POST" action="savings.php<?php echo $goal_to_edit ? '?edit_goal_id=' . htmlspecialchars($goal_to_edit['id']) : ''; ?>">
        <?php if ($goal_to_edit): ?>
            <input type="hidden" name="goal_id" value="<?php echo htmlspecialchars($goal_to_edit['id']); ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="goal_name">Goal Name:</label>
            <input type="text" id="goal_name" name="goal_name" value="<?php echo htmlspecialchars($goal_to_edit['goal_name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="target_amount">Target Amount:</label>
            <input type="number" step="0.01" min="0" id="target_amount" name="target_amount" value="<?php echo htmlspecialchars($goal_to_edit['target_amount'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="current_amount">Current Saved:</label>
            <input type="number" step="0.01" min="0" id="current_amount" name="current_amount" value="<?php echo htmlspecialchars($goal_to_edit['current_amount'] ?? '0'); ?>" required>
        </div>
        <div class="form-group">
            <label for="target_date">Target Date (optional):</label>
            <input type="date" id="target_date" name="target_date" value="<?php echo htmlspecialchars($goal_to_edit['target_date'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="description">Description (optional):</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($goal_to_edit['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" name="<?php echo $goal_to_edit ? 'update_goal' : 'add_goal'; ?>" class="btn btn-full-width">
            <?php echo $goal_to_edit ? 'Update Goal' : 'Add Goal'; ?>
        </button>
        <?php if ($goal_to_edit): ?>
            <a href="savings.php" class="btn btn-secondary btn-full-width mt-2">Cancel Edit</a>
        <?php endif; ?>
    </form>
</div>
<div class="card data-display-card mb-4">
    <h2 class="card-header">Your Financial Goals</h2>
    <div class="card-content">
    <?php if (count($goals) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Goal</th>
                <th>Target</th>
                <th>Saved</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Target Date</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($goals as $g): ?>
            <?php 
                  $progress = 0;
                  if ($g['target_amount'] > 0) { // Avoid division by zero if target is 0
                      $progress = min(100, round(($g['current_amount'] / $g['target_amount']) * 100));
                  } elseif ($g['current_amount'] > 0 && $g['target_amount'] == 0) {
                      $progress = 100; // If target is 0 but saved something, consider it 100% (or handle as per logic)
                  }
                  
                  $status_class = 'status-not-started';
                  $status_text = 'Not Started';
                  if ($progress >= 100) { // Use >= 100 in case current_amount exceeds target_amount
                      $status_class = 'status-achieved';
                      $status_text = 'Achieved!';
                  } elseif ($progress > 0) {
                      $status_class = 'status-in-progress';
                      $status_text = 'In Progress';
                  }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($g['goal_name']); ?></td>
                <td>$<?php echo number_format($g['target_amount'], 2); ?></td>
                <td>$<?php echo number_format($g['current_amount'], 2); ?></td>
                <td>
                    <div class="budget-progress-bar-container" title="<?php echo $progress; ?>%">
                        <div class="budget-progress-bar <?php echo ($progress >= 100) ? 'bg-success' : 'bg-primary'; ?>" style="width:<?php echo $progress; ?>%;">
                            <?php echo $progress; ?>%
                        </div>
                    </div>
                </td>
                <td class="goal-status <?php echo $status_class; ?>">
                    <?php echo $status_text; ?>
                </td>
                <td><?php echo $g['target_date'] ? htmlspecialchars(date('Y-m-d', strtotime($g['target_date']))) : 'N/A'; ?></td>
                <td><?php echo htmlspecialchars($g['description'] ?: 'N/A'); ?></td>
                <td class="actions-cell">
                    <a href="savings.php?edit_goal_id=<?php echo $g['id']; ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-edit"></i> Edit</a>
                    <a href="savings.php?delete_goal_id=<?php echo $g['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this goal: \'<?php echo htmlspecialchars(addslashes($g['goal_name'])); ?>\'?');"><i class="fa-solid fa-trash"></i> Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No financial goals found.</p>
    <?php endif; ?>
    </div>
<?php include 'includes/footer.php'; ?>
