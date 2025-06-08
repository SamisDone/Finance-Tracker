<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Finance Tracker'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- You might want to add a favicon link here -->
    <!-- <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon"> -->
</head>
<body>
    <header class="main-header" style="animation:fadeIn 0.7s;">
        <nav class="navbar sticky-navbar">
            <a href="dashboard.php" class="navbar-brand"><i class="fa-solid fa-coins"></i> Finance Tracker</a>
            <input type="checkbox" id="navbar-toggle" hidden>
            <label for="navbar-toggle" class="navbar-toggle-label"><span></span><span></span><span></span></label>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php"<?php if ($pageTitle == 'Dashboard') echo ' class="active"'; ?>><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <li><a href="income.php"<?php if ($pageTitle == 'Income Tracking') echo ' class="active"'; ?>><i class="fa-solid fa-money-bill-wave"></i> Income</a></li>
                    <li><a href="expenses.php"<?php if ($pageTitle == 'Expense Tracking') echo ' class="active"'; ?>><i class="fa-solid fa-wallet"></i> Expenses</a></li>
                    <li><a href="budgets.php"<?php if ($pageTitle == 'Budgets') echo ' class="active"'; ?>><i class="fa-solid fa-chart-pie"></i> Budgets</a></li>
                    <li><a href="reports.php"<?php if ($pageTitle == 'Reports & Visualizations') echo ' class="active"'; ?>><i class="fa-solid fa-chart-line"></i> Reports</a></li>
                    <li><a href="savings.php"<?php if ($pageTitle == 'Savings & Goals') echo ' class="active"'; ?>><i class="fa-solid fa-piggy-bank"></i> Savings & Goals</a></li>
                    <li class="profile-dropdown">
                        <a href="#" class="profile-link"><i class="fa-solid fa-user"></i> <span>Profile</span> <i class="fa-solid fa-caret-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="profile.php"><i class="fa-solid fa-user-gear"></i> My Profile</a></li>
                            <li><a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="index.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?php echo htmlspecialchars($_SESSION['flash_type'] ?? ''); ?>">
                <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            </div>
        <?php endif; ?>
    </header>
    <main class="container">
        <!-- Flash messages will be displayed here -->
        <script>
        // Responsive navbar toggle
        window.addEventListener('DOMContentLoaded', function() {
            const toggle = document.querySelector('.navbar-toggle-label');
            const nav = document.querySelector('.navbar-nav');
            toggle && toggle.addEventListener('click', function() {
                nav.classList.toggle('open');
            });
            // Dropdown
            document.querySelectorAll('.profile-dropdown').forEach(function(drop) {
                drop.addEventListener('mouseenter', function() {
                    drop.querySelector('.dropdown-menu').style.display = 'block';
                });
                drop.addEventListener('mouseleave', function() {
                    drop.querySelector('.dropdown-menu').style.display = 'none';
                });
            });
        });
        </script>
