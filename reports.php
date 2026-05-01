<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
requireLogin();

$pageTitle = 'Reports';
$user_id = $_SESSION['user_id'];
$initial = strtoupper(substr($_SESSION['username'], 0, 1));

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// --- Fetch expenses with category names ---
$stmt = $pdo->prepare("SELECT e.amount, e.expense_date, e.description, c.name as category_name
    FROM expenses e LEFT JOIN expense_categories c ON e.category_id = c.id
    WHERE e.user_id = :user_id AND e.expense_date BETWEEN :start AND :end");
$stmt->execute([':user_id' => $user_id, ':start' => $start_date, ':end' => $end_date]);
$expenses = $stmt->fetchAll();

// --- Fetch income with source names ---
$stmt = $pdo->prepare("SELECT i.amount, i.income_date, i.description, s.name as source_name
    FROM income i LEFT JOIN income_sources s ON i.source_id = s.id
    WHERE i.user_id = :user_id AND i.income_date BETWEEN :start AND :end");
$stmt->execute([':user_id' => $user_id, ':start' => $start_date, ':end' => $end_date]);
$incomes = $stmt->fetchAll();

// --- Aggregate data ---
$exp_by_category = [];
$exp_by_month = [];
$exp_daily = [];
foreach ($expenses as $e) {
    $cat = $e['category_name'] ?: 'Uncategorized';
    $exp_by_category[$cat] = ($exp_by_category[$cat] ?? 0) + $e['amount'];
    $month = date('M Y', strtotime($e['expense_date']));
    $exp_by_month[$month] = ($exp_by_month[$month] ?? 0) + $e['amount'];
    $exp_daily[$e['expense_date']] = ($exp_daily[$e['expense_date']] ?? 0) + $e['amount'];
}

$inc_by_source = [];
$inc_by_month = [];
$inc_daily = [];
foreach ($incomes as $i) {
    $src = $i['source_name'] ?: 'Uncategorized';
    $inc_by_source[$src] = ($inc_by_source[$src] ?? 0) + $i['amount'];
    $month = date('M Y', strtotime($i['income_date']));
    $inc_by_month[$month] = ($inc_by_month[$month] ?? 0) + $i['amount'];
    $inc_daily[$i['income_date']] = ($inc_daily[$i['income_date']] ?? 0) + $i['amount'];
}

$total_income = array_sum($inc_by_source);
$total_expenses = array_sum($exp_by_category);
$net_savings = $total_income - $total_expenses;

// Build aligned daily series for income vs expense overlay
$all_dates = array_unique(array_merge(array_keys($inc_daily), array_keys($exp_daily)));
sort($all_dates);
$daily_labels = array_map(fn($d) => date('M j', strtotime($d)), $all_dates);
$daily_inc_vals = array_map(fn($d) => $inc_daily[$d] ?? 0, $all_dates);
$daily_exp_vals = array_map(fn($d) => $exp_daily[$d] ?? 0, $all_dates);
$daily_net_vals = array_map(fn($d) => ($inc_daily[$d] ?? 0) - ($exp_daily[$d] ?? 0), $all_dates);

// Monthly comparison: merge month keys
$all_months = array_unique(array_merge(array_keys($inc_by_month), array_keys($exp_by_month)));
// Sort chronologically
usort($all_months, fn($a, $b) => strtotime($a) - strtotime($b));
$month_inc_vals = array_map(fn($m) => $inc_by_month[$m] ?? 0, $all_months);
$month_exp_vals = array_map(fn($m) => $exp_by_month[$m] ?? 0, $all_months);

// Top 5 expenses & income for horizontal bar
arsort($exp_by_category);
$top_exp = array_slice($exp_by_category, 0, 5, true);
arsort($inc_by_source);
$top_inc = array_slice($inc_by_source, 0, 5, true);

$has_data = $total_income > 0 || $total_expenses > 0;

// --- CSV export ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="finpulse_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Type', 'Date', 'Amount', 'Category/Source', 'Description']);
    foreach ($incomes as $i) {
        fputcsv($out, ['Income', $i['income_date'], $i['amount'], $i['source_name'] ?? '', $i['description'] ?? '']);
    }
    foreach ($expenses as $e) {
        fputcsv($out, ['Expense', $e['expense_date'], $e['amount'], $e['category_name'] ?? '', $e['description'] ?? '']);
    }
    fclose($out);
    exit;
}
?>
<!doctype html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Reports — FinPulse</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="assets/app.js"></script>
<style>body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-50 antialiased">
  <?php include 'includes/sidebar.php'; ?>
  <div class="lg:pl-64">
    <?php include 'includes/topbar.php'; ?>
    <main class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight">Reports</h2>
          <p class="text-sm text-zinc-400">Visualize your income and spending patterns.</p>
        </div>
        <div class="flex gap-2">
          <button id="exportPdfBtn" onclick="exportPDF()" class="rounded-md bg-violet-500 px-3 py-2 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-violet-400 transition">⬇ Export PDF</button>
          <a href="?start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>&export=csv" class="rounded-md bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-950 hover:scale-[1.03] hover:bg-white">↓ Export CSV</a>
        </div>
      </div>

      <!-- Date filter -->
      <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
        <form method="GET" class="flex flex-wrap items-end gap-4">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Start date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>"
              class="rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm text-zinc-300 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-zinc-400">End date</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>"
              class="rounded-md border border-zinc-800 bg-zinc-950/60 px-3 py-2.5 text-sm text-zinc-300 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"/>
          </div>
          <button type="submit" class="rounded-md bg-violet-500 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:scale-[1.03] hover:bg-violet-400">Filter</button>
        </form>
      </div>

      <?php if ($has_data): ?>
      <!-- Summary cards -->
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <p class="text-xs uppercase tracking-wide text-zinc-500">Total Income</p>
          <p class="mt-3 text-3xl font-semibold text-emerald-300">$<?php echo number_format($total_income, 2); ?></p>
          <p class="mt-1 text-xs text-zinc-500"><?php echo count($incomes); ?> transactions</p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <p class="text-xs uppercase tracking-wide text-zinc-500">Total Expenses</p>
          <p class="mt-3 text-3xl font-semibold text-rose-300">$<?php echo number_format($total_expenses, 2); ?></p>
          <p class="mt-1 text-xs text-zinc-500"><?php echo count($expenses); ?> transactions</p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <p class="text-xs uppercase tracking-wide text-zinc-500">Net Savings</p>
          <p class="mt-3 text-3xl font-semibold <?php echo $net_savings >= 0 ? 'text-cyan-300' : 'text-rose-300'; ?>">
            <?php echo $net_savings >= 0 ? '+' : '-'; ?>$<?php echo number_format(abs($net_savings), 2); ?>
          </p>
          <p class="mt-1 text-xs text-zinc-500"><?php echo $net_savings >= 0 ? 'surplus' : 'deficit'; ?></p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <p class="text-xs uppercase tracking-wide text-zinc-500">Savings Rate</p>
          <p class="mt-3 text-3xl font-semibold text-violet-300"><?php echo $total_income > 0 ? round(($net_savings / $total_income) * 100) : 0; ?>%</p>
          <p class="mt-1 text-xs text-zinc-500">of income saved</p>
        </div>
      </div>

      <!-- Row 1: Doughnut charts -->
      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <h3 class="text-base font-semibold">Expenses by Category</h3>
          <p class="text-xs text-zinc-500"><?php echo count($exp_by_category); ?> categories</p>
          <div class="relative mt-4 h-72"><canvas id="expCatChart"></canvas></div>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <h3 class="text-base font-semibold">Income by Source</h3>
          <p class="text-xs text-zinc-500"><?php echo count($inc_by_source); ?> sources</p>
          <div class="relative mt-4 h-72"><canvas id="incSrcChart"></canvas></div>
        </div>
      </div>

      <!-- Row 2: Monthly comparison bar + stacked area -->
      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <h3 class="text-base font-semibold">Monthly Comparison</h3>
          <p class="text-xs text-zinc-500">Income vs Expenses by month</p>
          <div class="relative mt-4 h-72"><canvas id="monthCompChart"></canvas></div>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <h3 class="text-base font-semibold">Daily Cash Flow</h3>
          <p class="text-xs text-zinc-500">Income & expense overlay</p>
          <div class="relative mt-4 h-72"><canvas id="dailyFlowChart"></canvas></div>
        </div>
      </div>

      <!-- Row 3: Net savings trend (full width) -->
      <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
        <h3 class="text-base font-semibold">Net Savings Trend</h3>
        <p class="text-xs text-zinc-500">Daily net (income – expenses)</p>
        <div class="relative mt-4 h-64"><canvas id="netChart"></canvas></div>
      </div>

      <!-- Row 4: Top categories horizontal bars -->
      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <h3 class="text-base font-semibold">Top Expense Categories</h3>
          <p class="text-xs text-zinc-500">Highest spending areas</p>
          <div class="relative mt-4 h-64"><canvas id="topExpChart"></canvas></div>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
          <h3 class="text-base font-semibold">Top Income Sources</h3>
          <p class="text-xs text-zinc-500">Biggest earners</p>
          <div class="relative mt-4 h-64"><canvas id="topIncChart"></canvas></div>
        </div>
      </div>

      <?php else: ?>
      <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-10 text-center">
        <span class="text-4xl">📊</span>
        <p class="mt-3 text-sm text-zinc-500">No data available for the selected date range.</p>
      </div>
      <?php endif; ?>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script>
  // Shared palette and config helpers (DRY)
  const P = ['#a78bfa','#22d3ee','#f472b6','#34d399','#fbbf24','#f87171','#60a5fa','#a3e635','#fb923c','#e879f9'];
  const dark = { grid: 'rgba(63,63,70,.3)', tick: '#52525b', border: '#18181b' };
  const legendCfg = { position:'bottom', labels:{ color:'#a1a1aa', usePointStyle:true, boxWidth:8, padding:14 } };
  const scaleCfg = (showGrid = true) => ({
    x:{ ticks:{ color: dark.tick, maxTicksLimit:10 }, grid:{ display: showGrid, color: dark.grid } },
    y:{ ticks:{ color: dark.tick }, grid:{ color: dark.grid }, beginAtZero:true }
  });

  function mkChart(id, type, data, opts = {}) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, { type, data, options: {
      responsive:true, maintainAspectRatio:false, animation:false,
      ...opts
    }});
  }

  function init() {
    if (typeof Chart === 'undefined') return;

    // 1. Expense by Category – Doughnut
    mkChart('expCatChart', 'doughnut', {
      labels: <?php echo json_encode(array_keys($exp_by_category)); ?>,
      datasets: [{ data: <?php echo json_encode(array_values($exp_by_category)); ?>, backgroundColor: P, borderColor: dark.border, borderWidth: 2, hoverOffset: 8 }]
    }, { cutout:'62%', plugins:{ legend: legendCfg } });

    // 2. Income by Source – Doughnut
    mkChart('incSrcChart', 'doughnut', {
      labels: <?php echo json_encode(array_keys($inc_by_source)); ?>,
      datasets: [{ data: <?php echo json_encode(array_values($inc_by_source)); ?>, backgroundColor: P, borderColor: dark.border, borderWidth: 2, hoverOffset: 8 }]
    }, { cutout:'62%', plugins:{ legend: legendCfg } });

    // 3. Monthly Comparison – Grouped Bar
    mkChart('monthCompChart', 'bar', {
      labels: <?php echo json_encode(array_values($all_months)); ?>,
      datasets: [
        { label:'Income', data: <?php echo json_encode(array_values($month_inc_vals)); ?>, backgroundColor:'rgba(52,211,153,.55)', borderColor:'#34d399', borderWidth:1, borderRadius:4 },
        { label:'Expenses', data: <?php echo json_encode(array_values($month_exp_vals)); ?>, backgroundColor:'rgba(244,63,94,.45)', borderColor:'#f43f5e', borderWidth:1, borderRadius:4 }
      ]
    }, { plugins:{ legend: legendCfg }, scales: scaleCfg(false) });

    // 4. Daily Cash Flow – Area overlay
    mkChart('dailyFlowChart', 'line', {
      labels: <?php echo json_encode(array_values($daily_labels)); ?>,
      datasets: [
        { label:'Income', data: <?php echo json_encode(array_values($daily_inc_vals)); ?>, borderColor:'#34d399', backgroundColor:'rgba(52,211,153,.12)', fill:true, tension:.4, pointRadius:0, borderWidth:2 },
        { label:'Expenses', data: <?php echo json_encode(array_values($daily_exp_vals)); ?>, borderColor:'#f43f5e', backgroundColor:'rgba(244,63,94,.10)', fill:true, tension:.4, pointRadius:0, borderWidth:2 }
      ]
    }, { plugins:{ legend: legendCfg }, interaction:{ intersect:false, mode:'index' }, scales: scaleCfg() });

    // 5. Net Savings Trend – Line
    mkChart('netChart', 'line', {
      labels: <?php echo json_encode(array_values($daily_labels)); ?>,
      datasets: [{
        label:'Net', data: <?php echo json_encode(array_values($daily_net_vals)); ?>,
        borderColor:'#a78bfa', backgroundColor:'rgba(167,139,250,.12)', fill:true, tension:.4,
        pointRadius:2, pointBackgroundColor:'#a78bfa', borderWidth:2,
        segment:{ borderColor: ctx => ctx.p1.parsed.y < 0 ? '#f43f5e' : '#a78bfa' }
      }]
    }, { plugins:{ legend:{ display:false } }, scales: scaleCfg() });

    // 6. Top Expense Categories – Horizontal Bar
    mkChart('topExpChart', 'bar', {
      labels: <?php echo json_encode(array_keys($top_exp)); ?>,
      datasets: [{ data: <?php echo json_encode(array_values($top_exp)); ?>, backgroundColor:'rgba(244,63,94,.5)', borderColor:'#f43f5e', borderWidth:1, borderRadius:4 }]
    }, { indexAxis:'y', plugins:{ legend:{ display:false } }, scales:{
      x:{ ticks:{ color: dark.tick }, grid:{ color: dark.grid }, beginAtZero:true },
      y:{ ticks:{ color:'#a1a1aa' }, grid:{ display:false } }
    }});

    // 7. Top Income Sources – Horizontal Bar
    mkChart('topIncChart', 'bar', {
      labels: <?php echo json_encode(array_keys($top_inc)); ?>,
      datasets: [{ data: <?php echo json_encode(array_values($top_inc)); ?>, backgroundColor:'rgba(52,211,153,.5)', borderColor:'#34d399', borderWidth:1, borderRadius:4 }]
    }, { indexAxis:'y', plugins:{ legend:{ display:false } }, scales:{
      x:{ ticks:{ color: dark.tick }, grid:{ color: dark.grid }, beginAtZero:true },
      y:{ ticks:{ color:'#a1a1aa' }, grid:{ display:false } }
    }});
  }

  // Wait for Chart.js then initialize
  if (typeof Chart !== 'undefined') { init(); }
  else { const p = setInterval(() => { if (window.Chart) { clearInterval(p); init(); } }, 100); }

  // --- PDF Export ---
  async function exportPDF() {
    const btn = document.getElementById('exportPdfBtn');
    const origText = btn.textContent;
    btn.textContent = 'Generating…';
    btn.disabled = true;

    // Wait for libs
    while (typeof html2canvas === 'undefined' || typeof jspdf === 'undefined') {
      await new Promise(r => setTimeout(r, 200));
    }

    const { jsPDF } = jspdf;
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();
    const margin = 12;
    const usableW = pageW - margin * 2;
    let curY = margin;

    // Collect all chart panels (rounded-xl cards) inside main
    const main = document.querySelector('main');
    const panels = main.querySelectorAll('.rounded-xl');

    for (let i = 0; i < panels.length; i++) {
      const panel = panels[i];
      try {
        const canvas = await html2canvas(panel, {
          backgroundColor: '#09090b',
          scale: 2,
          useCORS: true,
          logging: false
        });

        const imgData = canvas.toDataURL('image/png');
        const imgW = usableW;
        const imgH = (canvas.height / canvas.width) * imgW;

        // If this panel won't fit on current page, add a new page
        if (curY + imgH > pageH - margin) {
          pdf.addPage();
          curY = margin;
        }

        pdf.addImage(imgData, 'PNG', margin, curY, imgW, imgH);
        curY += imgH + 6;
      } catch (e) {
        console.warn('Skipped panel', i, e);
      }
    }

    // Footer on last page
    pdf.setFontSize(8);
    pdf.setTextColor(150);
    pdf.text('Generated by FinPulse • ' + new Date().toLocaleDateString(), margin, pageH - 5);

    pdf.save('FinPulse_Report_' + new Date().toISOString().slice(0,10) + '.pdf');

    btn.textContent = origText;
    btn.disabled = false;
  }
  </script>
</body>
</html>
