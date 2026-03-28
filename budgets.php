<?php
$pageTitle = 'Budgets • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();

$selectedMonth = $_GET['month'] ?? date('Y-m');
if (!valid_month_string($selectedMonth)) {
    $selectedMonth = date('Y-m');
}
[$selectedYear, $selectedMonthNumber] = array_map('intval', explode('-', $selectedMonth));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $budgetYear = (int)($_POST['budget_year'] ?? date('Y'));
        if ($categoryId > 0 && $amount > 0 && $amount <= 9999999.99 && valid_year_value($budgetYear)) {
            $ownsCategory = $pdo->prepare("SELECT id, name FROM categories WHERE id = ? AND user_id = ? AND type = 'Expense' LIMIT 1");
            $ownsCategory->execute([$categoryId, $userId]);
            $category = $ownsCategory->fetch();
            if ($category) {
                $stmt = $pdo->prepare('INSERT INTO budgets (user_id, category_id, amount, budget_month, budget_year) VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE amount = VALUES(amount)');
                for ($month = 1; $month <= 12; $month++) {
                    $stmt->execute([$userId, $categoryId, $amount, $month, $budgetYear]);
                }
                audit_log($pdo, $userId, 'SAVE_BUDGET', 'Saved annual budget for category ' . $category['name'] . ' in ' . $budgetYear . '.');
                flash_set('success', 'Budget saved for all months of ' . $budgetYear . '.');
            } else {
                flash_set('error', 'Please choose a valid expense category.');
            }
        } else {
            flash_set('error', 'Please enter a valid budget amount and year.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM budgets WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
            audit_log($pdo, $userId, 'DELETE_BUDGET', 'Deleted budget #' . $id . '.');
            flash_set('success', 'Budget deleted.');
        }
    }
    header('Location: budgets.php?month=' . urlencode($selectedMonth));
    exit;
}

$expenseCategoriesStmt = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = ? AND type = 'Expense' ORDER BY name");
$expenseCategoriesStmt->execute([$userId]);
$expenseCategories = $expenseCategoriesStmt->fetchAll();
$budgets = get_budget_overview($pdo, $userId, $selectedYear, $selectedMonthNumber);
$totalBudget = array_sum(array_map(fn($b) => (float)$b['budget_amount'], $budgets));
$totalSpent = array_sum(array_map(fn($b) => (float)$b['spent_amount'], $budgets));
$totalRemaining = array_sum(array_map(fn($b) => (float)$b['remaining_amount'], $budgets));
$budgetVsActualLabels = array_map(fn($b) => $b['category_name'], $budgets);
$budgetVsActualBudget = array_map(fn($b) => (float)$b['budget_amount'], $budgets);
$budgetVsActualSpent = array_map(fn($b) => (float)$b['spent_amount'], $budgets);
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h1 class="h3 fw-bold mb-1">Budgets</h1>
    <p class="text-muted-green mb-0">Create annual category budgets and review monthly performance with budget-vs-actual visuals.</p>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="metric-card card"><div class="text-muted-green small">Loaded Month Budget</div><div class="fs-3 fw-bold">$<?= number_format($totalBudget, 2) ?></div></div></div>
  <div class="col-md-4"><div class="metric-card card"><div class="text-muted-green small">Loaded Month Spent</div><div class="fs-3 fw-bold">$<?= number_format($totalSpent, 2) ?></div></div></div>
  <div class="col-md-4"><div class="metric-card card"><div class="text-muted-green small">Loaded Month Remaining</div><div class="fs-3 fw-bold">$<?= number_format($totalRemaining, 2) ?></div></div></div>
</div>
<div class="row g-4 align-items-stretch">
  <div class="col-lg-6 d-flex">
    <div class="card p-4 h-100 w-100 budget-split-card">
      <div class="card-body-stretch">
      <h2 class="h5 fw-bold mb-3">Set Budget</h2>
      <form method="post" action="budgets.php?month=<?= e($selectedMonth) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <div class="mb-3">
          <label class="form-label">Expense Category</label>
          <select class="form-select" name="category_id" required>
            <option value="">Choose a category</option>
            <?php foreach ($expenseCategories as $category): ?>
              <option value="<?= (int)$category['id'] ?>"><?= e($category['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Budget Amount</label><input class="form-control" type="number" name="amount" min="0.01" step="0.01" required></div>
        <div class="mb-3"><label class="form-label">Budget Year</label><input class="form-control" type="number" name="budget_year" min="2000" max="2100" value="<?= (int)$selectedYear ?>" required></div>
        <div class="small text-muted-green mb-3">Saving this budget will create or update the same amount for all 12 months of the selected year.</div>
        <button class="btn btn-success w-100 mt-auto" type="submit">Save Year Budget</button>
      </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6 d-flex">
    <div class="card p-4 h-100 w-100 budget-split-card">
      <div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
        <div>
          <h2 class="h5 fw-bold mb-1">Budget Performance</h2>
          <div class="text-muted-green small">Progress for <?= date('F Y', strtotime($selectedMonth . '-01')) ?>.</div>
        </div>
        <form method="get" action="budgets.php" class="d-flex align-items-end gap-2 ms-lg-auto">
          <div>
            <label class="form-label mb-1">Load Month</label>
            <input class="form-control" type="month" name="month" value="<?= e($selectedMonth) ?>">
          </div>
          <button class="btn btn-outline-light" type="submit">Load</button>
        </form>
      </div>
      <div class="card-body-stretch">
      <?php if ($budgets): ?>
        <div class="budget-list-scroll">
          <div class="row g-3">
            <?php foreach ($budgets as $budget): ?>
              <div class="col-12">
                <div class="card p-3 card-hover">
                  <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <div>
                      <div class="fw-bold"><?= e($budget['category_name']) ?></div>
                      <div class="small text-muted-green">Budget: $<?= number_format((float)$budget['budget_amount'], 2) ?> • Spent: $<?= number_format((float)$budget['spent_amount'], 2) ?> • Remaining: $<?= number_format((float)$budget['remaining_amount'], 2) ?></div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                      <span class="badge text-bg-<?= e($budget['status_class']) ?>"><?= e($budget['status']) ?></span>
                      <form method="post" action="budgets.php?month=<?= e($selectedMonth) ?>" onsubmit="return confirm('Delete this budget?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$budget['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </div>
                  <div class="progress budget-progress progress-lg"><div class="progress-bar bg-<?= e($budget['status_class']) ?>" role="progressbar" style="width: <?= min((float)$budget['percentage_used'], 100) ?>%"></div></div>
                  <div class="small text-muted-green mt-2"><?= number_format((float)$budget['percentage_used'], 1) ?>% used</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?><div class="text-center text-muted-green py-5">No budgets have been set for this month yet.</div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-1">Budget vs Actual</h2>
          <div class="text-muted-green small">Compare category budgets with actual expenses for the loaded month.</div>
        </div>
      </div>
      <div class="chart-panel chart-panel-tall"><canvas id="budgetCompareChart"></canvas></div>
    </div>
  </div>
</div>
<script>
new Chart(document.getElementById('budgetCompareChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($budgetVsActualLabels) ?>,
    datasets: [
      { label: 'Budget', data: <?= json_encode($budgetVsActualBudget) ?>, backgroundColor: 'rgba(47,163,107,0.65)', borderColor: 'rgba(47,163,107,1)', borderWidth: 1 },
      { label: 'Actual Spend', data: <?= json_encode($budgetVsActualSpent) ?>, backgroundColor: 'rgba(217,98,98,0.65)', borderColor: 'rgba(217,98,98,1)', borderWidth: 1 }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: getComputedStyle(document.body).color } } },
    scales: {
      x: { ticks: { color: getComputedStyle(document.body).color }, grid: { color: 'rgba(116,216,156,0.08)' } },
      y: { ticks: { color: getComputedStyle(document.body).color }, grid: { color: 'rgba(116,216,156,0.08)' }, beginAtZero: true }
    }
  }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
