-- Drop existing tables if they exist to start fresh
DROP TABLE IF EXISTS `financial_goals`, `savings_accounts`, `budget_categories`, `budgets`, `expenses`, `payment_methods`, `expense_categories`, `income`, `income_categories`, `income_sources`, `users`;

-- Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Income Sources Table
CREATE TABLE `income_sources` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Income Categories Table
CREATE TABLE `income_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL, -- e.g., Primary Job, Side Hustle, Investment
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Income Table
CREATE TABLE `income` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `source_id` INT,
  `category_id` INT,
  `amount` DECIMAL(10, 2) NOT NULL,
  `income_date` DATE NOT NULL,
  `description` TEXT,
  `is_recurring` BOOLEAN DEFAULT FALSE,
  `recurrence_period` ENUM('daily', 'weekly', 'monthly', 'yearly'),
  `next_recurrence_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`source_id`) REFERENCES `income_sources`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`category_id`) REFERENCES `income_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Expense Categories Table
CREATE TABLE `expense_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL, -- e.g., Food, Rent, Transport, Entertainment
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment Methods Table
CREATE TABLE `payment_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(50) NOT NULL, -- e.g., Cash, Credit Card, Bank Transfer, Mobile Wallet
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Expenses Table
CREATE TABLE `expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category_id` INT,
  `payment_method_id` INT,
  `amount` DECIMAL(10, 2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `description` TEXT,
  `is_recurring` BOOLEAN DEFAULT FALSE,
  `recurrence_period` ENUM('daily', 'weekly', 'monthly', 'yearly'),
  `next_recurrence_date` DATE,
  `receipt_path` VARCHAR(255), -- Path to uploaded receipt image/PDF
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Budgets Table
CREATE TABLE `budgets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `period_type` ENUM('weekly', 'monthly', 'yearly') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_limit` DECIMAL(10, 2),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Budget Categories Table (linking expense categories to a budget with specific limits)
CREATE TABLE `budget_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `budget_id` INT NOT NULL,
  `expense_category_id` INT NOT NULL,
  `limit_amount` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`budget_id`) REFERENCES `budgets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Savings Accounts Table
CREATE TABLE `savings_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `account_name` VARCHAR(100) NOT NULL,
  `current_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Financial Goals Table
CREATE TABLE `financial_goals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `goal_name` VARCHAR(150) NOT NULL,
  `target_amount` DECIMAL(15, 2) NOT NULL,
  `current_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `target_date` DATE,
  `description` TEXT,
  `status` ENUM('active', 'achieved', 'abandoned') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default categories (optional, can be added by user or seeded)
-- INSERT INTO `income_categories` (`user_id`, `name`) VALUES (1, 'Salary'), (1, 'Freelance'), (1, 'Investment'), (1, 'Other');
-- INSERT INTO `expense_categories` (`user_id`, `name`) VALUES (1, 'Food & Groceries'), (1, 'Housing'), (1, 'Transportation'), (1, 'Utilities'), (1, 'Healthcare'), (1, 'Entertainment'), (1, 'Education'), (1, 'Personal Care'), (1, 'Savings/Investments'), (1, 'Debt Repayment'), (1, 'Other');
-- INSERT INTO `payment_methods` (`user_id`, `name`) VALUES (1, 'Cash'), (1, 'Credit Card'), (1, 'Debit Card'), (1, 'Bank Transfer'), (1, 'Mobile Wallet');
