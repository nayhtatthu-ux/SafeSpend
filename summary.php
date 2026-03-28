<?php
$pageTitle = 'Financial Summary • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();
$selectedMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}
[$year, $month] = array_map('intval', explode('-', $selectedMonth));

$summaryStmt = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN type='Saving' THEN amount ELSE 0 END),0) AS total_income,
    COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS total_expenses,
    COUNT(*) AS total_transactions,
    COALESCE(AVG(CASE WHEN type='Expense' THEN amount END),0) AS average_expense
    FROM transactions WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
$summaryStmt->execute([$userId, $year, $month]);
$summary = $summaryStmt->fetch();
$net = (float)$summary['total_income'] - (float)$summary['total_expenses'];

$categoryStmt = $pdo->prepare("SELECT c.name, COALESCE(SUM(t.amount),0) AS total
    FROM transactions t
    INNER JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = ? AND t.type='Expense' AND YEAR(t.date)=? AND MONTH(t.date)=?
    GROUP BY c.id, c.name
    ORDER BY total DESC");
$categoryStmt->execute([$userId, $year, $month]);
$categories = $categoryStmt->fetchAll();
$highestCategory = $categories[0]['name'] ?? 'None';

$dayStmt = $pdo->prepare("SELECT date, COALESCE(SUM(amount),0) AS total
    FROM transactions
    WHERE user_id = ? AND type='Expense' AND YEAR(date)=? AND MONTH(date)=?
    GROUP BY date ORDER BY total DESC LIMIT 1");
$dayStmt->execute([$userId, $year, $month]);
$highestDay = $dayStmt->fetch();
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h1 class="h3 fw-bold mb-1">Financial Summary Report</h1>
    <p class="text-muted-green mb-0">A monthly roll-up of income, expenses, patterns, and key highlights.</p>
  </div>
  <form method="get" class="d-flex gap-2 align-items-end">
    <div><label class="form-label mb-1">Month</label><input class="form-control" type="month" name="month" value="<?= e($selectedMonth) ?>"></div>
    <button class="btn btn-success">Load Report</button>
  </form>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card report-stat"><div class="text-muted-green small">Income</div><div class="fs-4 fw-bold">$<?= number_format((float)$summary['total_income'], 2) ?></div></div></div>
  <div class="col-md-3"><div class="card report-stat"><div class="text-muted-green small">Expenses</div><div class="fs-4 fw-bold">$<?= number_format((float)$summary['total_expenses'], 2) ?></div></div></div>
  <div class="col-md-3"><div class="card report-stat"><div class="text-muted-green small">Net Balance</div><div class="fs-4 fw-bold">$<?= number_format($net, 2) ?></div></div></div>
  <div class="col-md-3"><div class="card report-stat"><div class="text-muted-green small">Transactions</div><div class="fs-4 fw-bold"><?= (int)$summary['total_transactions'] ?></div></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="card p-4 h-100">
      <h2 class="h5 fw-bold mb-3">Highlights</h2>
      <ul class="mb-0 ps-3">
        <li class="mb-2">Highest expense category: <?= e($highestCategory) ?></li>
        <li class="mb-2">Average expense transaction: $<?= number_format((float)$summary['average_expense'], 2) ?></li>
        <li class="mb-2">Most expensive day: <?= $highestDay ? e($highestDay['date']) . ' ($' . number_format((float)$highestDay['total'], 2) . ')' : 'No expense activity' ?></li>
        <li class="mb-2">Month selected: <?= e(date('F Y', strtotime($selectedMonth . '-01'))) ?></li>
      </ul>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card p-4 h-100">
      <h2 class="h5 fw-bold mb-3">Expense Category Ranking</h2>
      <div class="table-scroll">
        <table class="table align-middle mb-0">
          <thead><tr><th>Category</th><th class="text-end">Expense Total</th></tr></thead>
          <tbody>
            <?php foreach ($categories as $category): ?>
              <tr><td><?= e($category['name']) ?></td><td class="text-end">$<?= number_format((float)$category['total'], 2) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$categories): ?><tr><td colspan="2" class="text-center text-muted-green py-5">No expense data available for this month.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
