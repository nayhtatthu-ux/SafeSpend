<?php
$pageTitle = 'Transactions • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();

$search = trim($_GET['search'] ?? '');
$filterBy = $_GET['filter_by'] ?? 'month';
if (!in_array($filterBy, ['date', 'month', 'year'], true)) {
    $filterBy = 'month';
}
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedYear = $_GET['year'] ?? date('Y');
$chartType = $_GET['chart_type'] ?? 'bar';
if (!in_array($chartType, ['bar', 'line', 'pie'], true)) {
    $chartType = 'bar';
}
$typeFilter = $_GET['type'] ?? '';
if (!in_array($typeFilter, ['', 'Saving', 'Expense'], true)) {
    $typeFilter = '';
}
$sortBy = $_GET['sort'] ?? 'date_desc';
if (!in_array($sortBy, ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'], true)) {
    $sortBy = 'date_desc';
}
$minAmount = trim($_GET['min_amount'] ?? '');
$maxAmount = trim($_GET['max_amount'] ?? '');
$categoryFilter = (int)($_GET['category_id'] ?? 0);

$categoriesStmt = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ? ORDER BY name');
$categoriesStmt->execute([$userId]);
$allCategories = $categoriesStmt->fetchAll();

$sql = "SELECT t.id, t.description, t.amount, t.type, t.date, c.name AS category_name, c.id AS category_id
        FROM transactions t
        LEFT JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = :user_id";
$params = ['user_id' => $userId];

if ($search !== '') {
    $sql .= " AND (t.description LIKE :search_desc OR COALESCE(c.name, '') LIKE :search_cat OR t.type LIKE :search_type OR t.date LIKE :search_date)";
    $searchWildcard = '%' . $search . '%';
    $params['search_desc'] = $searchWildcard;
    $params['search_cat'] = $searchWildcard;
    $params['search_type'] = $searchWildcard;
    $params['search_date'] = $searchWildcard;
}
if ($typeFilter !== '') {
    $sql .= ' AND t.type = :type_filter';
    $params['type_filter'] = $typeFilter;
}
if ($categoryFilter > 0) {
    $sql .= ' AND t.category_id = :category_filter';
    $params['category_filter'] = $categoryFilter;
}
if ($minAmount !== '' && is_numeric($minAmount)) {
    $sql .= ' AND t.amount >= :min_amount';
    $params['min_amount'] = (float)$minAmount;
}
if ($maxAmount !== '' && is_numeric($maxAmount)) {
    $sql .= ' AND t.amount <= :max_amount';
    $params['max_amount'] = (float)$maxAmount;
}

switch ($filterBy) {
    case 'date':
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = date('Y-m-d');
        }
        $sql .= ' AND t.date = :selected_date';
        $params['selected_date'] = $selectedDate;
        break;
    case 'year':
        if (!preg_match('/^\d{4}$/', $selectedYear)) {
            $selectedYear = date('Y');
        }
        $sql .= ' AND YEAR(t.date) = :selected_year';
        $params['selected_year'] = (int)$selectedYear;
        break;
    case 'month':
    default:
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }
        [$yearPart, $monthPart] = explode('-', $selectedMonth);
        $sql .= ' AND YEAR(t.date) = :selected_year AND MONTH(t.date) = :selected_month';
        $params['selected_year'] = (int)$yearPart;
        $params['selected_month'] = (int)$monthPart;
        break;
}

$orderSql = match ($sortBy) {
    'date_asc' => ' ORDER BY t.date ASC, t.id ASC',
    'amount_desc' => ' ORDER BY t.amount DESC, t.date DESC',
    'amount_asc' => ' ORDER BY t.amount ASC, t.date DESC',
    default => ' ORDER BY t.date DESC, t.id DESC',
};
$sql .= $orderSql;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$chartMap = [];
$totalIncome = 0.0;
$totalExpenses = 0.0;
foreach (array_reverse($rows) as $row) {
    $label = match ($filterBy) {
        'year' => date('M', strtotime($row['date'])),
        'month' => date('d', strtotime($row['date'])),
        default => date('d M Y', strtotime($row['date'])),
    };
    if (!isset($chartMap[$label])) {
        $chartMap[$label] = ['income' => 0.0, 'expenses' => 0.0];
    }
    if ($row['type'] === 'Saving') {
        $chartMap[$label]['income'] += (float)$row['amount'];
        $totalIncome += (float)$row['amount'];
    } else {
        $chartMap[$label]['expenses'] += (float)$row['amount'];
        $totalExpenses += (float)$row['amount'];
    }
}
$chartLabels = array_keys($chartMap);
$chartIncome = array_map(fn($v) => $v['income'], array_values($chartMap));
$chartExpenses = array_map(fn($v) => $v['expenses'], array_values($chartMap));

$dailyAnalytics = [];
$weeklyAnalytics = [];
if ($filterBy === 'month') {
    foreach ($rows as $row) {
        $day = date('d M', strtotime($row['date']));
        if (!isset($dailyAnalytics[$day])) $dailyAnalytics[$day] = 0.0;
        if ($row['type'] === 'Expense') $dailyAnalytics[$day] += (float)$row['amount'];
    }
} elseif ($filterBy === 'year') {
    foreach ($rows as $row) {
        $week = 'W' . date('W', strtotime($row['date']));
        if (!isset($weeklyAnalytics[$week])) $weeklyAnalytics[$week] = 0.0;
        if ($row['type'] === 'Expense') $weeklyAnalytics[$week] += (float)$row['amount'];
    }
}

$totalTransactions = count($rows);
$netBalance = $totalIncome - $totalExpenses;
$averageExpense = 0.0;
$expenseCount = 0;
$categoryTotals = [];
$dayTotals = [];
foreach ($rows as $row) {
    if ($row['type'] === 'Expense') {
        $expenseCount++;
        $averageExpense += (float)$row['amount'];
        $cat = $row['category_name'] ?: 'Uncategorized';
        $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + (float)$row['amount'];
        $dayTotals[$row['date']] = ($dayTotals[$row['date']] ?? 0) + (float)$row['amount'];
    }
}
$averageExpense = $expenseCount > 0 ? $averageExpense / $expenseCount : 0.0;
arsort($categoryTotals);
$highestCategory = $categoryTotals ? array_key_first($categoryTotals) : 'None';
$highestCategoryAmount = $categoryTotals ? reset($categoryTotals) : 0.0;
arsort($dayTotals);
$highestDay = $dayTotals ? array_key_first($dayTotals) : null;
$highestDayAmount = $dayTotals ? reset($dayTotals) : 0.0;
$periodLabel = $filterBy === 'year' ? $selectedYear : ($filterBy === 'date' ? date('F j, Y', strtotime($selectedDate)) : date('F Y', strtotime($selectedMonth . '-01')));

$healthBudgetRows = [];
if ($filterBy === 'year') {
    for ($m = 1; $m <= 12; $m++) {
        foreach (get_budget_overview($pdo, $userId, (int)$selectedYear, $m) as $budgetRow) {
            $healthBudgetRows[] = $budgetRow;
        }
    }
} elseif ($filterBy === 'date') {
    $healthBudgetRows = get_budget_overview($pdo, $userId, (int)date('Y', strtotime($selectedDate)), (int)date('n', strtotime($selectedDate)));
} else {
    [$healthYear, $healthMonth] = explode('-', $selectedMonth);
    $healthBudgetRows = get_budget_overview($pdo, $userId, (int)$healthYear, (int)$healthMonth);
}

$previousPeriodIncome = 0.0;
$previousPeriodExpenses = 0.0;
if ($filterBy === 'date') {
    $prevDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
    $prevStmt = $pdo->prepare("SELECT
        COALESCE(SUM(CASE WHEN type='Saving' THEN amount ELSE 0 END),0) AS income,
        COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS expenses
        FROM transactions WHERE user_id = ? AND date = ?");
    $prevStmt->execute([$userId, $prevDate]);
} elseif ($filterBy === 'year') {
    $prevStmt = $pdo->prepare("SELECT
        COALESCE(SUM(CASE WHEN type='Saving' THEN amount ELSE 0 END),0) AS income,
        COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS expenses
        FROM transactions WHERE user_id = ? AND YEAR(date) = ?");
    $prevStmt->execute([$userId, (int)$selectedYear - 1]);
} else {
    $prevMonthValue = date('Y-m', strtotime($selectedMonth . '-01 -1 month'));
    [$prevYear, $prevMonth] = explode('-', $prevMonthValue);
    $prevStmt = $pdo->prepare("SELECT
        COALESCE(SUM(CASE WHEN type='Saving' THEN amount ELSE 0 END),0) AS income,
        COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS expenses
        FROM transactions WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
    $prevStmt->execute([$userId, (int)$prevYear, (int)$prevMonth]);
}
$prevPeriod = $prevStmt->fetch();
$previousPeriodIncome = (float)($prevPeriod['income'] ?? 0);
$previousPeriodExpenses = (float)($prevPeriod['expenses'] ?? 0);

$periodIncomeValue = (float)$totalIncome;
$periodExpenseValue = (float)$totalExpenses;
$periodNetValue = $periodIncomeValue - $periodExpenseValue;
$savingsRateScore = $periodIncomeValue > 0 ? max(min($periodNetValue / $periodIncomeValue, 1), 0) : ($periodExpenseValue <= 0 ? 1 : 0);
if ($healthBudgetRows) {
    $budgetRatios = array_map(function ($budget) {
        $used = (float)($budget['percentage_used'] ?? 0);
        return $used <= 100 ? 1 : max(0, 100 / max($used, 1));
    }, $healthBudgetRows);
    $budgetControlScore = count($budgetRatios) > 0 ? array_sum($budgetRatios) / count($budgetRatios) : 0.7;
} else {
    $budgetControlScore = $periodIncomeValue > 0 && $periodExpenseValue <= $periodIncomeValue ? 0.75 : 0.55;
}
if ($previousPeriodExpenses > 0) {
    $spendingStabilityScore = max(0, 1 - min(abs($periodExpenseValue - $previousPeriodExpenses) / $previousPeriodExpenses, 1));
} else {
    $spendingStabilityScore = $periodExpenseValue > 0 ? 0.65 : 1;
}
$healthScore = (int)round(($savingsRateScore * 45) + ($budgetControlScore * 35) + ($spendingStabilityScore * 20));
$healthScore = max(0, min(100, $healthScore));
if ($healthScore >= 80) {
    $healthLabel = 'Excellent';
    $healthClass = 'success';
} elseif ($healthScore >= 60) {
    $healthLabel = 'Stable';
    $healthClass = 'warning';
} else {
    $healthLabel = 'Needs Attention';
    $healthClass = 'danger';
}

$queryString = http_build_query([
    'search' => $search,
    'filter_by' => $filterBy,
    'date' => $selectedDate,
    'month' => $selectedMonth,
    'year' => $selectedYear,
    'chart_type' => $chartType,
    'type' => $typeFilter,
    'category_id' => $categoryFilter,
    'min_amount' => $minAmount,
    'max_amount' => $maxAmount,
    'sort' => $sortBy,
]);
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Transactions</h1>
    <p class="text-muted-green mb-0">Filter records, review charts, and view your transaction summary in one place.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-light" href="export_transactions.php?format=csv&amp;<?= e($queryString) ?>"><i class="bi bi-filetype-csv me-2"></i>Export CSV</a>
    <a class="btn btn-outline-light" href="comparative.php"><i class="bi bi-bar-chart-line me-2"></i>Comparative Analytics</a>
    <a class="btn btn-success" href="transaction_new.php"><i class="bi bi-plus-circle me-2"></i>Add Transaction</a>
  </div>
</div>

<div class="row g-3 mb-4 dashboard-metrics">
  <div class="col-xl-4 col-md-6"><div class="metric-card card h-100"><div class="d-flex justify-content-between"><div><div class="text-muted-green small">Filtered Income</div><div class="fs-3 fw-bold">$<?= number_format($totalIncome, 2) ?></div></div><div class="icon"><i class="bi bi-graph-up-arrow"></i></div></div></div></div>
  <div class="col-xl-4 col-md-6"><div class="metric-card card h-100"><div class="d-flex justify-content-between"><div><div class="text-muted-green small">Filtered Expenses</div><div class="fs-3 fw-bold">$<?= number_format($totalExpenses, 2) ?></div></div><div class="icon"><i class="bi bi-cash-stack"></i></div></div></div></div>
  <div class="col-xl-4 col-md-12">
    <div class="metric-card card h-100 health-card">
      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
          <div class="text-muted-green small">Financial Health Score</div>
          <div class="d-flex align-items-end gap-2 flex-wrap">
            <div class="fs-3 fw-bold"><?= $healthScore ?>/100</div>
            <span class="badge text-bg-<?= e($healthClass) ?>"><?= e($healthLabel) ?></span>
          </div>
        </div>
        <div class="icon"><i class="bi bi-heart-pulse"></i></div>
      </div>
      <div class="progress health-progress mb-2" role="progressbar" aria-valuenow="<?= $healthScore ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar bg-<?= e($healthClass) ?>" style="width: <?= $healthScore ?>%"></div>
      </div>
      <div class="small text-muted-green">Based on the selected period's income, expense, budget control, and stability against the previous matching period.</div>
    </div>
  </div>
</div>
<div class="card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h2 class="h5 fw-bold mb-1">Search and Filters</h2>
      <div class="text-muted-green small">Use one filter panel for the table and graph. The buttons stay separate for quicker actions.</div>
    </div>
  </div>
  <form action="transactions.php" method="get" class="row g-3 align-items-end" novalidate>
    <div class="col-lg-4">
      <label class="form-label">Search</label>
      <input class="form-control" name="search" placeholder="Description, category, type, or date" value="<?= e($search) ?>">
    </div>
    <div class="col-lg-2">
      <label class="form-label">Filter By</label>
      <select class="form-select" name="filter_by" id="filterBy">
        <option value="date" <?= $filterBy === 'date' ? 'selected' : '' ?>>Date</option>
        <option value="month" <?= $filterBy === 'month' ? 'selected' : '' ?>>Month</option>
        <option value="year" <?= $filterBy === 'year' ? 'selected' : '' ?>>Year</option>
      </select>
    </div>
    <div class="col-lg-2" id="dateField"><label class="form-label">Select Date</label><input class="form-control" type="date" name="date" value="<?= e($selectedDate) ?>"></div>
    <div class="col-lg-2" id="monthField"><label class="form-label">Select Month</label><input class="form-control" type="month" name="month" value="<?= e($selectedMonth) ?>"></div>
    <div class="col-lg-2" id="yearField"><label class="form-label">Select Year</label><input class="form-control" type="number" min="2000" max="2100" name="year" value="<?= e($selectedYear) ?>"></div>
    <div class="col-lg-2">
      <label class="form-label">Type</label>
      <select class="form-select" name="type">
        <option value="">All</option>
        <option value="Saving" <?= $typeFilter === 'Saving' ? 'selected' : '' ?>>Income</option>
        <option value="Expense" <?= $typeFilter === 'Expense' ? 'selected' : '' ?>>Expense</option>
      </select>
    </div>
    <div class="col-lg-2">
      <label class="form-label">Category</label>
      <select class="form-select" name="category_id">
        <option value="0">All</option>
        <?php foreach ($allCategories as $category): ?>
          <option value="<?= (int)$category['id'] ?>" <?= $categoryFilter === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-2"><label class="form-label">Min Amount</label><input class="form-control" type="number" step="0.01" name="min_amount" value="<?= e($minAmount) ?>"></div>
    <div class="col-lg-2"><label class="form-label">Max Amount</label><input class="form-control" type="number" step="0.01" name="max_amount" value="<?= e($maxAmount) ?>"></div>
    <div class="col-lg-2">
      <label class="form-label">Sort</label>
      <select class="form-select" name="sort">
        <option value="date_desc" <?= $sortBy === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
        <option value="date_asc" <?= $sortBy === 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
        <option value="amount_desc" <?= $sortBy === 'amount_desc' ? 'selected' : '' ?>>Amount High to Low</option>
        <option value="amount_asc" <?= $sortBy === 'amount_asc' ? 'selected' : '' ?>>Amount Low to High</option>
      </select>
    </div>
    <div class="col-lg-2">
      <label class="form-label">Graph Type</label>
      <select class="form-select" name="chart_type">
        <option value="bar" <?= $chartType === 'bar' ? 'selected' : '' ?>>Bar Chart</option>
        <option value="line" <?= $chartType === 'line' ? 'selected' : '' ?>>Line Graph</option>
        <option value="pie" <?= $chartType === 'pie' ? 'selected' : '' ?>>Pie Chart</option>
      </select>
    </div>
    <div class="col-12 d-flex gap-2 flex-wrap">
      <button class="btn btn-success" type="submit" name="submit_mode" value="search">Search Table</button>
      <button class="btn btn-outline-light" type="submit" name="submit_mode" value="filters">Apply Filters</button>
      <a class="btn btn-outline-light" href="transactions.php">Reset</a>
    </div>
  </form>
</div>
<div class="row g-4 mb-4 align-items-stretch">
  <div class="col-xl-8">
    <div class="card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
        <div>
          <h2 class="h5 fw-bold mb-1">Transaction Table</h2>
          <div class="text-muted-green small">Results shown: <?= count($rows) ?></div>
        </div>
      </div>
      <div class="table-scroll transaction-table-scroll flex-grow-1">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th class="text-end">Amount</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= e($row['date']) ?></td>
              <td><?= e($row['description']) ?></td>
              <td><?= e($row['category_name'] ?? 'Uncategorized') ?></td>
              <td><span class="badge <?= $row['type'] === 'Saving' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= e(format_type_label($row['type'])) ?></span></td>
              <td class="text-end">$<?= number_format((float)$row['amount'], 2) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-light" href="transaction_edit.php?id=<?= (int)$row['id'] ?>">Edit</a>
                <form method="post" action="transaction_delete.php" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this transaction?')">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted-green py-5">No matching transactions found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card p-4 h-100">
      <div class="mb-3 text-center">
        <h2 class="h5 fw-bold mb-1">Filtered Graph</h2>
        <div class="text-muted-green small">The graph updates with the selected filters.</div>
      </div>
      <div class="chart-panel chart-right-panel <?= $chartType === 'pie' ? 'chart-panel-pie' : '' ?>"><canvas id="transactionsChart"></canvas></div>
    </div>
  </div>
</div>

<div class="card p-4 summary-card-vertical">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h2 class="h5 fw-bold mb-1">Summary for <?= e($periodLabel) ?></h2>
      <div class="text-muted-green small">A detailed view of the filtered transaction period.</div>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-lg-6"><div class="summary-line-item"><span>Highest expense category</span><strong><?= e($highestCategory) ?><?= $highestCategoryAmount > 0 ? ' ($' . number_format($highestCategoryAmount, 2) . ')' : '' ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Average expense transaction</span><strong>$<?= number_format($averageExpense, 2) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Most expensive day</span><strong><?= $highestDay ? e($highestDay) . ' ($' . number_format($highestDayAmount, 2) . ')' : 'No expense activity' ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Graph mode</span><strong><?= e(ucfirst($chartType)) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Filtered income</span><strong>$<?= number_format($totalIncome, 2) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Filtered expenses</span><strong>$<?= number_format($totalExpenses, 2) ?></strong></div></div>
    <div class="col-lg-6"><div class="summary-line-item"><span>Net balance</span><strong>$<?= number_format($netBalance, 2) ?></strong></div></div>
  </div>
</div>
<script>
const filterBySelect = document.getElementById('filterBy');
const dateField = document.getElementById('dateField');
const monthField = document.getElementById('monthField');
const yearField = document.getElementById('yearField');
function syncFilterFields() {
  const value = filterBySelect.value;
  dateField.style.display = value === 'date' ? '' : 'none';
  monthField.style.display = value === 'month' ? '' : 'none';
  yearField.style.display = value === 'year' ? '' : 'none';
}
syncFilterFields();
filterBySelect.addEventListener('change', syncFilterFields);
const chartType = <?= json_encode($chartType) ?>;
const labels = <?= json_encode($chartLabels) ?>;
const incomeData = <?= json_encode($chartIncome) ?>;
const expensesData = <?= json_encode($chartExpenses) ?>;
const totalIncome = <?= json_encode($totalIncome) ?>;
const totalExpenses = <?= json_encode($totalExpenses) ?>;
const bodyColor = getComputedStyle(document.body).color;
new Chart(document.getElementById('transactionsChart').getContext('2d'), {
  type: chartType,
  data: chartType === 'pie' ? {
      labels: ['Income', 'Expenses'],
      datasets: [{ data: [totalIncome, totalExpenses], backgroundColor: ['rgba(47,163,107,0.9)','rgba(217,98,98,0.9)'], borderColor: ['rgba(47,163,107,1)','rgba(217,98,98,1)'], borderWidth: 1 }]
    } : {
      labels: labels,
      datasets: [
        { label: 'Income', data: incomeData, borderColor: 'rgba(116,216,156,1)', backgroundColor: 'rgba(47,163,107,0.6)', tension: 0.35, fill: chartType === 'line' },
        { label: 'Expenses', data: expensesData, borderColor: 'rgba(255,130,130,1)', backgroundColor: 'rgba(217,98,98,0.6)', tension: 0.35, fill: chartType === 'line' }
      ]
    },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { color: bodyColor } } },
    scales: chartType === 'pie' ? {} : {
      x: { ticks: { color: bodyColor }, grid: { color: 'rgba(255,255,255,0.10)' } },
      y: { ticks: { color: bodyColor }, grid: { color: 'rgba(255,255,255,0.10)' }, beginAtZero: true }
    }
  }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
