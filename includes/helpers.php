<?php
/**
 * Helper functions for Finance Tracker
 * Follows DRY principle - common logic extracted here
 */

/**
 * Get default expense categories
 */
function getDefaultExpenseCategories(): array {
    return ['Food', 'Rent', 'Transport', 'Utilities', 'Entertainment', 'Health', 'Education', 'Shopping', 'Other'];
}

/**
 * Get default payment methods
 */
function getDefaultPaymentMethods(): array {
    return ['Cash', 'Credit Card', 'Debit Card', 'Mobile Wallet', 'Bank Transfer'];
}

/**
 * Get default income sources
 */
function getDefaultIncomeSources(): array {
    return ['Salary', 'Freelance', 'Interest', 'Gifts', 'Investments'];
}

/**
 * Get default income categories
 */
function getDefaultIncomeCategories(): array {
    return ['Primary Job', 'Side Hustle', 'Bonus', 'Passive Income', 'Other'];
}

/**
 * Insert default categories for a user if none exist
 */
function insertDefaultCategories(PDO $pdo, int $user_id): void {
    // Expense categories
    $cat_check = $pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE user_id = :user_id");
    $cat_check->execute([':user_id' => $user_id]);
    if ($cat_check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO expense_categories (user_id, name) VALUES (:user_id, :name)");
        foreach (getDefaultExpenseCategories() as $cat) {
            $stmt->execute([':user_id' => $user_id, ':name' => $cat]);
        }
    }

    // Payment methods
    $method_check = $pdo->prepare("SELECT COUNT(*) FROM payment_methods WHERE user_id = :user_id");
    $method_check->execute([':user_id' => $user_id]);
    if ($method_check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO payment_methods (user_id, name) VALUES (:user_id, :name)");
        foreach (getDefaultPaymentMethods() as $method) {
            $stmt->execute([':user_id' => $user_id, ':name' => $method]);
        }
    }

    // Income sources
    $src_check = $pdo->prepare("SELECT COUNT(*) FROM income_sources WHERE user_id = :user_id");
    $src_check->execute([':user_id' => $user_id]);
    if ($src_check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO income_sources (user_id, name) VALUES (:user_id, :name)");
        foreach (getDefaultIncomeSources() as $src) {
            $stmt->execute([':user_id' => $user_id, ':name' => $src]);
        }
    }

    // Income categories
    $cat_check = $pdo->prepare("SELECT COUNT(*) FROM income_categories WHERE user_id = :user_id");
    $cat_check->execute([':user_id' => $user_id]);
    if ($cat_check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO income_categories (user_id, name) VALUES (:user_id, :name)");
        foreach (getDefaultIncomeCategories() as $cat) {
            $stmt->execute([':user_id' => $user_id, ':name' => $cat]);
        }
    }
}

/**
 * Get or create category/method ID
 */
function getOrCreateId(PDO $pdo, int $user_id, string $table, string $name): ?int {
    if (empty($name)) return null;

    // Try to insert (will ignore if exists due to UNIQUE constraint)
    $stmt = $pdo->prepare("INSERT IGNORE INTO $table (user_id, name) VALUES (:user_id, :name)");
    $stmt->execute([':user_id' => $user_id, ':name' => $name]);

    // Get the ID
    $stmt = $pdo->prepare("SELECT id FROM $table WHERE user_id = :user_id AND name = :name");
    $stmt->execute([':user_id' => $user_id, ':name' => $name]);
    return $stmt->fetchColumn() ?: null;
}

/**
 * Build pagination HTML
 */
function renderPagination(int $current_page, int $total_records, int $per_page, string $base_url = ''): string {
    $total_pages = ceil($total_records / $per_page);
    if ($total_pages <= 1) return '';

    $html = '<div class="pagination">';
    if ($current_page > 1) {
        $html .= '<a href="?' . http_build_query(array_merge($_GET, ['page' => $current_page - 1])) . '" class="btn btn-sm">&laquo; Prev</a>';
    }

    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="btn btn-sm btn-primary">' . $i . '</span>';
        } else {
            $html .= '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '" class="btn btn-sm">' . $i . '</a>';
        }
    }

    if ($current_page < $total_pages) {
        $html .= '<a href="?' . http_build_query(array_merge($_GET, ['page' => $current_page + 1])) . '" class="btn btn-sm">Next &raquo;</a>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * Build search form HTML
 */
function renderSearchForm(string $search_term = ''): string {
    return '<div class="search-bar mb-3">
                <input type="text" name="search" value="' . htmlspecialchars($search_term) . '" placeholder="Search..." aria-label="Search">
                <i class="fas fa-search search-icon"></i>
            </div>';
}
