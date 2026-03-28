<?php
$pageTitle = 'Comparative Analytics • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();

$mode = $_GET['mode'] ?? 'month';
if (!in_array($mode, ['month', 'year'], true)) {
    $mode = 'month';
}
$graphType = $_GET['graph'] ?? 'bar';
if (!in_array($graphType, ['bar', 'line', 'pie'], true)) {
    $graphType = 'bar';
}
$currentMonth = $_GET['current_month'] ?? date('Y-m');
$compareMonth = $_GET['compare_month'] ?? date('Y-m', strtotime('-1 month'));
$currentYear = (int)($_GET['current_year'] ?? date('Y'));
$compareYear = (int)($_GET['compare_year'] ?? (date('Y') - 1));
if (!valid_month_string($currentMonth)) $currentMonth = date('Y-m');
if (!valid_month_string($compareMonth)) $compareMonth = date('Y-m', strtotime('-1 month'));
if (!valid_year_value($currentYear)) $currentYear = (int)date('Y');
if (!valid_year_value($compareYear)) $compareYear = (int)date('Y') - 1;

function comparative_period_data(PDO $pdo, int $userId, string $mode, string|int $period): array {
    $where = '';
    $params = [$userId];
    $label = '';
    if ($mode === 'year') {
        $where = 'YEAR(t.date) = ?';
        $params[] = (int)$period;
        $label = (string)$period;
    } else {
        [$y, $m] = explode('-', (string)$period);
        $where = 'YEAR(t.date) = ? AND MONTH(t.date) = ?';
        $params[] = (int)$y;
        $params[] = (int)$m;
        $label = date('F Y', strtotime((string)$period . '-01'));
    }

    $stmt = $pdo->prepare(
        "SELECT t.amount, t.type, t.date, COALESCE(c.name, 'Uncategorized') AS category_name
         FROM transactions t
         LEFT JOIN categories c ON c.id = t.category_id
         WHERE t.user_id = ? AND $where
         ORDER BY t.date ASC, t.id ASC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $income = 0.0;
    $expenses = 0.0;
    $transactions = count($rows);
    $categoryExpenses = [];
    foreach ($rows as $row) {
        if ($row['type'] === 'Saving') {
            $income += (float)$row['amount'];
        } else {
            $expenses += (float)$row['amount'];
            $categoryExpenses[$row['category_name']] = ($categoryExpenses[$row['category_name']] ?? 0.0) + (float)$row['amount'];
        }
    }
    arsort($categoryExpenses);
    return [
        'label' => $label,
        'income' => $income,
        'expenses' => $expenses,
        'transactions' => $transactions,
        'categories' => $categoryExpenses,
    ];
}

$currentData = comparative_period_data($pdo, $userId, $mode, $mode === 'year' ? $currentYear : $currentMonth);
$compareData = comparative_period_data($pdo, $userId, $mode, $mode === 'year' ? $compareYear : $compareMonth);

$changeText = static function (float $current, float $previous): string {
    if ($previous == 0.0 && $current == 0.0) return 'No change';
    if ($previous == 0.0) return 'New activity';
    $deltaPct = (($current - $previous) / $previous) * 100;
    if (abs($deltaPct) < 0.01) return 'No change';
    return ($deltaPct > 0 ? 'Up ' : 'Down ') . number_format(abs($deltaPct), 1) . '%';
};

$allCategories = array_values(array_unique(array_merge(array_keys($currentData['categories']), array_keys($compareData['categories']))));
sort($allCategories);
$currentCategorySeries = [];
$compareCategorySeries = [];
foreach ($allCategories as $cat) {
    $currentCategorySeries[] = $currentData['categories'][$cat] ?? 0;
    $compareCategorySeries[] = $compareData['categories'][$cat] ?? 0;
}
$topCurrentCat = $currentData['categories'] ? array_key_first($currentData['categories']) : 'None';
$topCompareCat = $compareData['categories'] ? array_key_first($compareData['categories']) : 'None';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Comparative Analytics</h1>
    <p class="text-muted-green mb-0">Compare any two months or any two years and review category-level differences.</p>
  </div>
</div>

<div class="card p-4 mb-4">
  <form method="get" class="row g-3 align-items-end">
    <div class="col-lg-2">
      <label class="form-label">Compare By</label>
      <select class="form-select" name="mode" id="compareMode">
        <option value="month" <?= $mode === 'month' ? 'selected' : '' ?>>Month</option>
        <option value="year" <?= $mode === 'year' ? 'selected' : '' ?>>Year</option>
      </select>
    </div>
    <div class="col-lg-2">
      <label class="form-label">Graph Type</label>
      <select class="form-select" name="graph">
        <option value="bar" <?= $graphType === 'bar' ? 'selected' : '' ?>>Bar Chart</option>
        <option value="line" <?= $graphType === 'line' ? 'selected' : '' ?>>Line Graph</option>
        <option value="pie" <?= $graphType === 'pie' ? 'selected' : '' ?>>Pie Chart</option>
      </select>
    </div>
    <div class="col-lg-3 month-group">
      <label class="form-label">Current Month</label>
      <input class="form-control" type="month" name="current_month" value="<?= e($currentMonth) ?>">
    </div>
    <div class="col-lg-3 month-group">
      <label class="form-label">Compare Month</label>
      <input class="form-control" type="month" name="compare_month" value="<?= e($compareMonth) ?>">
    </div>
    <div class="col-lg-2 year-group">
      <label class="form-label">Current Year</label>
      <input class="form-control" type="number" min="2000" max="2100" name="current_year" value="<?= e((string)$currentYear) ?>">
    </div>
    <div class="col-lg-2 year-group">
      <label class="form-label">Compare Year</label>
      <input class="form-control" type="number" min="2000" max="2100" name="compare_year" value="<?= e((string)$compareYear) ?>">
    </div>
    <div class="col-12 d-flex gap-2 flex-wrap">
      <button class="btn btn-success" type="submit">Apply Comparison</button>
      <a class="btn btn-outline-light" href="comparative.php">Reset</a>
    </div>
  </form>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="metric-card card h-100"><div class="text-muted-green small"><?= e($currentData['label']) ?> Income</div><div class="fs-4 fw-bold">$<?= number_format($currentData['income'],2) ?></div></div></div>
  <div class="col-md-3"><div class="metric-card card h-100"><div class="text-muted-green small"><?= e($compareData['label']) ?> Income</div><div class="fs-4 fw-bold">$<?= number_format($compareData['income'],2) ?></div></div></div>
  <div class="col-md-3"><div class="metric-card card h-100"><div class="text-muted-green small"><?= e($currentData['label']) ?> Expenses</div><div class="fs-4 fw-bold">$<?= number_format($currentData['expenses'],2) ?></div></div></div>
  <div class="col-md-3"><div class="metric-card card h-100"><div class="text-muted-green small"><?= e($compareData['label']) ?> Expenses</div><div class="fs-4 fw-bold">$<?= number_format($compareData['expenses'],2) ?></div></div></div>
</div>

<div class="card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h2 class="h5 fw-bold mb-1">Category Comparison Graph</h2>
      <div class="text-muted-green small">The left-side dataset always represents the current selected month or year, while the right-side dataset represents the comparison period.</div>
    </div>
  </div>
  <div class="chart-panel chart-panel-tall"><canvas id="categoryCompareChart"></canvas></div>
</div>

<div class="card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h2 class="h5 fw-bold mb-1">Category Comparison Table</h2>
      <div class="text-muted-green small">Each expense category is compared side by side for the two selected periods.</div>
    </div>
  </div>
  <div class="table-scroll" style="max-height:430px">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Category</th><th class="text-end"><?= e($currentData['label']) ?></th><th class="text-end"><?= e($compareData['label']) ?></th><th class="text-end">Difference</th></tr></thead>
      <tbody>
        <?php foreach ($allCategories as $cat): $curr = (float)($currentData['categories'][$cat] ?? 0); $prev = (float)($compareData['categories'][$cat] ?? 0); ?>
          <tr>
            <td><?= e($cat) ?></td>
            <td class="text-end">$<?= number_format($curr, 2) ?></td>
            <td class="text-end">$<?= number_format($prev, 2) ?></td>
            <td class="text-end <?= $curr - $prev > 0 ? 'text-danger' : 'text-success' ?>">$<?= number_format($curr - $prev, 2) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$allCategories): ?><tr><td colspan="4" class="text-center text-muted-green py-5">No expense activity found for the selected periods.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card p-4 summary-card-vertical">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h2 class="h5 fw-bold mb-1">Smart Insights</h2>
      <div class="text-muted-green small">Action-oriented insights based on the comparison.</div>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-lg-6"><div class="summary-line-item"><span>Income trend</span><strong><?= e($changeText($currentData['income'], $compareData['income'])) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Expense trend</span><strong><?= e($changeText($currentData['expenses'], $compareData['expenses'])) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Leading expense category now</span><strong><?= e($topCurrentCat) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Leading expense category then</span><strong><?= e($topCompareCat) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Transaction activity</span><strong><?= e($changeText((float)$currentData['transactions'], (float)$compareData['transactions'])) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Overall takeaway</span><strong><?= $currentData['expenses'] > $compareData['expenses'] ? 'Spending is higher in ' . e($currentData['label']) : ($currentData['expenses'] < $compareData['expenses'] ? 'Spending is lower in ' . e($currentData['label']) : 'Spending is steady across both periods') ?></strong></div></div>
  </div>
</div>
<script>
const compareModeSelect = document.getElementById('compareMode');
const monthGroups = document.querySelectorAll('.month-group');
const yearGroups = document.querySelectorAll('.year-group');
function syncCompareMode(){
  const mode = compareModeSelect.value;
  monthGroups.forEach(el => el.style.display = mode === 'month' ? '' : 'none');
  yearGroups.forEach(el => el.style.display = mode === 'year' ? '' : 'none');
}
syncCompareMode();
compareModeSelect.addEventListener('change', syncCompareMode);
const bodyColor = getComputedStyle(document.body).color;
const selectedGraph = <?= json_encode($graphType) ?>;
const chartType = selectedGraph === 'pie' ? 'doughnut' : selectedGraph;
const ctx = document.getElementById('categoryCompareChart').getContext('2d');
const labels = <?= json_encode($allCategories) ?>;
const currentSeries = <?= json_encode($currentCategorySeries) ?>;
const compareSeries = <?= json_encode($compareCategorySeries) ?>;
const datasets = selectedGraph === 'pie'
  ? [
      { label: <?= json_encode($currentData['label']) ?>, data: currentSeries, backgroundColor: ['rgba(47,163,107,.88)','rgba(116,216,156,.85)','rgba(25,110,74,.82)','rgba(83,171,116,.82)','rgba(163,214,181,.9)','rgba(31,122,79,.88)'], borderWidth: 1 },
      { label: <?= json_encode($compareData['label']) ?>, data: compareSeries, backgroundColor: ['rgba(217,98,98,.88)','rgba(242,147,147,.85)','rgba(176,70,70,.82)','rgba(228,122,122,.84)','rgba(245,177,177,.9)','rgba(140,53,53,.88)'], borderWidth: 1 }
    ]
  : [
      { label: <?= json_encode($currentData['label']) ?>, data: currentSeries, backgroundColor: 'rgba(47,163,107,0.75)', borderColor: 'rgba(47,163,107,1)', borderWidth: 2, borderRadius: 8, tension: .35, fill: false },
      { label: <?= json_encode($compareData['label']) ?>, data: compareSeries, backgroundColor: 'rgba(217,98,98,0.75)', borderColor: 'rgba(217,98,98,1)', borderWidth: 2, borderRadius: 8, tension: .35, fill: false }
    ];
new Chart(ctx, {
  type: chartType,
  data: { labels, datasets },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { color: bodyColor } } },
    scales: chartType === 'doughnut' ? {} : { x: { ticks: { color: bodyColor }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: bodyColor }, grid: { color: 'rgba(255,255,255,0.10)' } } }
  }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
