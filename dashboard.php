<?php
$pageTitle = 'Dashboard • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();

recalculate_balance($pdo, $userId);
$balance = get_balance($pdo, $userId);
$dashboardSearch = trim($_GET['search'] ?? '');

$totalsStmt = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN type='Saving' THEN amount ELSE 0 END),0) AS total_income,
    COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS total_expenses,
    COUNT(*) AS tx_count
    FROM transactions WHERE user_id = ?");
$totalsStmt->execute([$userId]);
$totals = $totalsStmt->fetch();

$recentSql = "SELECT t.id, t.description, t.amount, t.type, t.date, c.name AS category_name
    FROM transactions t
    LEFT JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = ?";
$recentParams = [$userId];
if ($dashboardSearch !== '') {
    $recentSql .= " AND (t.description LIKE ? OR COALESCE(c.name, '') LIKE ? OR t.type LIKE ? OR t.date LIKE ?)";
    $searchLike = '%' . $dashboardSearch . '%';
    array_push($recentParams, $searchLike, $searchLike, $searchLike, $searchLike);
}
$recentSql .= " ORDER BY t.date DESC, t.id DESC LIMIT 25";
$recentStmt = $pdo->prepare($recentSql);
$recentStmt->execute($recentParams);
$recent = $recentStmt->fetchAll();

$currentMonthStmt = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN type='Saving' THEN amount ELSE 0 END),0) AS month_income,
    COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS month_expenses
    FROM transactions
    WHERE user_id = ? AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())");
$currentMonthStmt->execute([$userId]);
$currentMonth = $currentMonthStmt->fetch();

$previousMonthStmt = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS previous_expenses
    FROM transactions
    WHERE user_id = ?
      AND YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
      AND MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$previousMonthStmt->execute([$userId]);
$previousMonth = $previousMonthStmt->fetch();

$topCategoryStmt = $pdo->prepare("SELECT c.name, COALESCE(SUM(t.amount),0) AS total
    FROM transactions t
    INNER JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = ? AND t.type = 'Expense' AND YEAR(t.date) = YEAR(CURDATE()) AND MONTH(t.date) = MONTH(CURDATE())
    GROUP BY c.id, c.name
    ORDER BY total DESC, c.name ASC
    LIMIT 1");
$topCategoryStmt->execute([$userId]);
$topCategory = $topCategoryStmt->fetch();

$expenseDelta = (float)$currentMonth['month_expenses'] - (float)($previousMonth['previous_expenses'] ?? 0);
$expenseDeltaPercent = (float)($previousMonth['previous_expenses'] ?? 0) > 0 ? ($expenseDelta / (float)$previousMonth['previous_expenses']) * 100 : 0;
$budgetOverview = get_budget_overview($pdo, $userId, (int)date('Y'), (int)date('n'));

$largestExpenseStmt = $pdo->prepare("SELECT description, amount, date FROM transactions WHERE user_id = ? AND type = 'Expense' AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) ORDER BY amount DESC, date DESC LIMIT 1");
$largestExpenseStmt->execute([$userId]);
$largestExpense = $largestExpenseStmt->fetch();

$avgExpenseTxnStmt = $pdo->prepare("SELECT COALESCE(AVG(amount),0) AS avg_expense_txn FROM transactions WHERE user_id = ? AND type = 'Expense' AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())");
$avgExpenseTxnStmt->execute([$userId]);
$avgExpenseTxn = (float)($avgExpenseTxnStmt->fetch()['avg_expense_txn'] ?? 0);

$highestSpendDayStmt = $pdo->prepare("SELECT date, SUM(amount) AS total FROM transactions WHERE user_id = ? AND type = 'Expense' AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) GROUP BY date ORDER BY total DESC, date DESC LIMIT 1");
$highestSpendDayStmt->execute([$userId]);
$highestSpendDay = $highestSpendDayStmt->fetch();

$budgetNearest = null;
if ($budgetOverview) {
    usort($budgetOverview, fn($a, $b) => (float)$b['percentage_used'] <=> (float)$a['percentage_used']);
    $budgetNearest = $budgetOverview[0];
}

$monthIncomeValue = (float)($currentMonth['month_income'] ?? 0);
$monthExpenseValue = (float)($currentMonth['month_expenses'] ?? 0);
$previousExpenseValue = (float)($previousMonth['previous_expenses'] ?? 0);
$netMonthValue = $monthIncomeValue - $monthExpenseValue;
$savingsRateScore = $monthIncomeValue > 0 ? max(min($netMonthValue / $monthIncomeValue, 1), 0) : ($monthExpenseValue <= 0 ? 1 : 0);
if ($budgetOverview) {
    $budgetRatios = array_map(function ($budget) {
        $used = (float)($budget['percentage_used'] ?? 0);
        if ($used <= 100) {
            return 1;
        }
        return max(0, 100 / max($used, 1));
    }, $budgetOverview);
    $budgetControlScore = count($budgetRatios) > 0 ? array_sum($budgetRatios) / count($budgetRatios) : 0.7;
} else {
    $budgetControlScore = $monthIncomeValue > 0 && $monthExpenseValue <= $monthIncomeValue ? 0.75 : 0.55;
}
if ($previousExpenseValue > 0) {
    $spendingStabilityScore = max(0, 1 - min(abs($monthExpenseValue - $previousExpenseValue) / $previousExpenseValue, 1));
} else {
    $spendingStabilityScore = $monthExpenseValue > 0 ? 0.65 : 1;
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

$insights = [];
if ($topCategory) {
    $insights[] = 'Highest expense category this month: ' . $topCategory['name'] . ' ($' . number_format((float)$topCategory['total'], 2) . ').';
}
if ((float)$previousMonth['previous_expenses'] > 0) {
    $insights[] = 'Expenses are ' . ($expenseDelta >= 0 ? 'up ' : 'down ') . number_format(abs($expenseDeltaPercent), 1) . '% compared with last month.';
}
if ($largestExpense) {
    $insights[] = 'Largest single expense this month: ' . $largestExpense['description'] . ' on ' . $largestExpense['date'] . ' for $' . number_format((float)$largestExpense['amount'], 2) . '.';
}
if ($avgExpenseTxn > 0) {
    $insights[] = 'Average expense transaction this month: $' . number_format($avgExpenseTxn, 2) . '.';
}
if ($highestSpendDay) {
    $insights[] = 'Highest spending day this month was ' . $highestSpendDay['date'] . ' with $' . number_format((float)$highestSpendDay['total'], 2) . ' spent.';
}
if ($budgetOverview) {
    $warningCount = count(array_filter($budgetOverview, fn($b) => in_array($b['status'], ['Warning', 'Exceeded'], true)));
    $insights[] = $warningCount > 0 ? $warningCount . ' budget categories need attention this month.' : 'All active budgets are currently on track.';
    if ($budgetNearest) {
        $insights[] = 'Closest budget limit: ' . $budgetNearest['category_name'] . ' at ' . number_format((float)$budgetNearest['percentage_used'], 1) . '% used.';
    }
}
$insights[] = 'Financial health score: ' . $healthScore . '/100 (' . $healthLabel . ').';
?>
<div class="hero mb-4">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div>
      <h1 class="h2 fw-bold mb-2">Dashboard</h1>
      <p class="text-muted-green mb-0">Track your balance, current-month breakdown, budget performance, and actionable insights.</p>
    </div>
  </div>
</div>
<div class="row g-3 mb-4 dashboard-metrics">
  <div class="col-xl col-md-6"><div class="metric-card card h-100"><div class="d-flex justify-content-between"><div><div class="text-muted-green small">Current Balance</div><div class="fs-3 fw-bold">$<?= number_format($balance, 2) ?></div></div><div class="icon"><i class="bi bi-wallet2"></i></div></div></div></div>
  <div class="col-xl col-md-6"><div class="metric-card card h-100"><div class="d-flex justify-content-between"><div><div class="text-muted-green small">Total Income</div><div class="fs-3 fw-bold">$<?= number_format($monthIncomeValue, 2) ?></div></div><div class="icon"><i class="bi bi-graph-up-arrow"></i></div></div></div></div>
  <div class="col-xl col-md-6"><div class="metric-card card h-100"><div class="d-flex justify-content-between"><div><div class="text-muted-green small">Total Expenses</div><div class="fs-3 fw-bold">$<?= number_format($monthExpenseValue, 2) ?></div></div><div class="icon"><i class="bi bi-cash-stack"></i></div></div></div></div>
  <div class="col-xl col-md-12">
    <div class="metric-card card h-100 health-card">
      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
          <div class="text-muted-green small">Financial Health Score</div>
          <div class="d-flex align-items-end gap-2">
            <div class="fs-3 fw-bold"><?= $healthScore ?>/100</div>
            <span class="badge text-bg-<?= e($healthClass) ?>"><?= e($healthLabel) ?></span>
          </div>
        </div>
        <div class="icon"><i class="bi bi-heart-pulse"></i></div>
      </div>
      <div class="progress health-progress mb-2" role="progressbar" aria-valuenow="<?= $healthScore ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar bg-<?= e($healthClass) ?>" style="width: <?= $healthScore ?>%"></div>
      </div>
      <div class="small text-muted-green">Based on this month's savings rate, budget control, and expense stability.</div>
    </div>
  </div>
</div>
<div class="row g-4 mb-4 align-items-stretch">
  <div class="col-lg-8 order-2 order-lg-1">
    <div class="card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
        <div>
          <h2 class="h5 section-title mb-1">Recent Transactions</h2>
          <div class="text-muted-green small">Search the latest records directly from the dashboard.</div>
        </div>
        <span class="badge rounded-pill badge-soft px-3 py-2"><?= (int)$totals['tx_count'] ?> total</span>
      </div>
      <form action="dashboard.php" method="get" class="row g-3 mb-3">
        <div class="col-md-9">
          <input class="form-control" type="text" name="search" value="<?= e($dashboardSearch) ?>" placeholder="Search by description, category, type, or date">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-success flex-fill" type="submit">Search</button>
          <a class="btn btn-outline-light" href="dashboard.php">Reset</a>
        </div>
      </form>
      <div class="table-scroll dashboard-table-scroll">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Date</th><th>Description</th><th>Type</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $row): ?>
            <tr>
              <td><?= e($row['date']) ?></td>
              <td>
                <div class="fw-semibold"><?= e($row['description']) ?></div>
                <div class="small text-muted-green"><?= e($row['category_name'] ?? 'Uncategorized') ?></div>
              </td>
              <td><span class="badge <?= $row['type'] === 'Saving' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= e(format_type_label($row['type'])) ?></span></td>
              <td class="text-end">$<?= number_format((float)$row['amount'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recent): ?><tr><td colspan="4" class="text-center text-muted-green py-4">No transactions matched your search.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4 order-1 order-lg-2">
    <div class="card p-4 h-100">
      <div class="mb-3 text-center">
        <h2 class="h5 section-title mb-1">Current Month Breakdown</h2>
        <div class="text-muted-green small">Current-month income and expenses.</div>
      </div>
      <div class="dashboard-pie-wrap d-flex align-items-center justify-content-center"><div class="dashboard-pie-shell"><canvas id="financeChart"></canvas></div></div>
    </div>
  </div>
</div>
<div class="row g-4 mb-4 align-items-stretch">
  <div class="col-lg-6">
    <div class="card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 section-title mb-1">Current Month Budget Status</h2>
          <div class="text-muted-green small">Expense category budgets compared with actual spending.</div>
        </div>
        <a class="btn btn-outline-light btn-sm" href="budgets.php">Open Budgets</a>
      </div>
      <?php if ($budgetOverview): ?>
        <div class="budget-list-scroll">
        <div class="row g-3">
          <?php foreach ($budgetOverview as $budget): ?>
            <div class="col-12">
              <div class="card p-3 h-100 card-hover">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <div>
                    <div class="fw-bold"><?= e($budget['category_name']) ?></div>
                    <div class="small text-muted-green">$<?= number_format((float)$budget['spent_amount'], 2) ?> of $<?= number_format((float)$budget['budget_amount'], 2) ?></div>
                  </div>
                  <span class="badge text-bg-<?= e($budget['status_class']) ?>"><?= e($budget['status']) ?></span>
                </div>
                <div class="progress budget-progress progress-lg">
                  <div class="progress-bar bg-<?= e($budget['status_class']) ?>" style="width: <?= min((float)$budget['percentage_used'], 100) ?>%"></div>
                </div>
                <div class="small text-muted-green mt-2">Remaining: $<?= number_format((float)$budget['remaining_amount'], 2) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        </div>
      <?php else: ?>
        <div class="text-center text-muted-green py-5">No budgets have been created for the current month.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 section-title mb-1">Smart Insights</h2>
          <div class="text-muted-green small">Automated commentary based on your latest activity.</div>
        </div>
        <i class="bi bi-lightbulb fs-4"></i>
      </div>
      <ul class="mb-0 ps-3">
        <?php foreach ($insights as $insight): ?>
          <li class="mb-2"><?= e($insight) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
<script>
new Chart(document.getElementById('financeChart').getContext('2d'), {
  type: 'pie',
  data: {
    labels: ['Income', 'Expenses'],
    datasets: [{
      data: [<?= json_encode((float)($currentMonth['month_income'] ?? 0)) ?>, <?= json_encode((float)($currentMonth['month_expenses'] ?? 0)) ?>],
      backgroundColor: ['rgba(47,163,107,0.9)','rgba(217,98,98,0.9)'],
      borderColor: ['rgba(47,163,107,1)','rgba(217,98,98,1)'],
      borderWidth: 1
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: getComputedStyle(document.body).color } } } }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
