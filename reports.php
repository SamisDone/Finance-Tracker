<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();
$pageTitle = 'Reports & Visualizations';
$include_chartjs = true;
include 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch expenses for the period
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name FROM expenses e LEFT JOIN expense_categories c ON e.category_id = c.id WHERE e.user_id = :user_id AND e.expense_date BETWEEN :start AND :end");
$stmt->execute([':user_id' => $user_id, ':start' => $start_date, ':end' => $end_date]);
$expenses = $stmt->fetchAll();

// Prepare data for charts
$category_totals = [];
$month_totals = [];
$trend = [];
foreach ($expenses as $exp) {
    // Pie chart: category-wise
    $cat = $exp['category_name'] ?: 'Uncategorized';
    $category_totals[$cat] = ($category_totals[$cat] ?? 0) + $exp['amount'];
    // Bar chart: month-wise
    $month = date('Y-m', strtotime($exp['expense_date']));
    $month_totals[$month] = ($month_totals[$month] ?? 0) + $exp['amount'];
    // Line chart: trend by date
    $date = $exp['expense_date'];
    $trend[$date] = ($trend[$date] ?? 0) + $exp['amount'];
}

// Export to CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="expenses_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Amount', 'Category', 'Payment Method', 'Notes']);
    foreach ($expenses as $exp) {
        fputcsv($out, [
            $exp['expense_date'],
            $exp['amount'],
            $exp['category_name'],
            $exp['payment_method_id'],
            $exp['description']
        ]);
    }
    fclose($out);
    exit;
}
?>
<section class="hero section-hero reports-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="reportsHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2563eb"/>
                    <stop offset="100%" stop-color="#14b8a6"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#reportsHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="fa-solid fa-chart-line"></i> Reports & Visualizations
        </h2>
        <p class="hero-desc">
            Visualize your spending and trends with interactive charts.
        </p>
    </div>
</section>
<div class="form-container card mb-4">
    <h2><i class="fa-solid fa-chart-pie"></i> Reports & Visualizations</h2>
    <form method="GET" action="">
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <button type="submit" class="btn">Filter</button>
        <a href="?start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>&export=csv" class="btn btn-secondary">Export CSV</a>
        <button type="button" class="btn btn-secondary" disabled>Export PDF (coming soon)</button>
    </form>
    <hr>
    <div class="charts-grid">
        <div class="chart-container">
            <h3>Pie Chart: Expenses by Category</h3>
            <canvas id="pieChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Bar Chart: Expenses by Month</h3>
            <canvas id="barChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Line Chart: Expense Trend</h3>
            <canvas id="lineChart"></canvas>
        </div>
    </div>
</div>
<script>
// Pie chart data
const pieLabels = <?php echo json_encode(array_keys($category_totals)); ?>;
const pieData = <?php echo json_encode(array_values($category_totals)); ?>;
// Bar chart data
const barLabels = <?php echo json_encode(array_keys($month_totals)); ?>;
const barData = <?php echo json_encode(array_values($month_totals)); ?>;
// Line chart data
const lineLabels = <?php echo json_encode(array_keys($trend)); ?>;
const lineData = <?php echo json_encode(array_values($trend)); ?>;

window.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        // Pie Chart
        new Chart(document.getElementById('pieChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: [
                        '#3498db', '#e67e22', '#2ecc71', '#9b59b6', '#e74c3c', '#f1c40f', '#1abc9c', '#34495e', '#7f8c8d'
                    ]
                }]
            },
            options: {responsive: true}
        });
        // Bar Chart
        new Chart(document.getElementById('barChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{
                    label: 'Expenses',
                    data: barData,
                    backgroundColor: '#3498db'
                }]
            },
            options: {responsive: true}
        });
        // Line Chart
        new Chart(document.getElementById('lineChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: lineLabels,
                datasets: [{
                    label: 'Expenses',
                    data: lineData,
                    borderColor: '#e67e22',
                    backgroundColor: 'rgba(230,126,34,0.1)',
                    fill: true
                }]
            },
            options: {responsive: true}
        });
    }
});
</script>
<?php include 'includes/footer.php'; ?>
