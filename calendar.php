<?php
$pageTitle = 'Calendar View • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();
$selectedMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}
[$year, $month] = array_map('intval', explode('-', $selectedMonth));
$firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$daysInMonth = (int)$firstDay->format('t');
$startWeekday = (int)$firstDay->format('N');

$dataStmt = $pdo->prepare("SELECT date,
    COUNT(*) AS tx_count,
    COALESCE(SUM(CASE WHEN type='Saving' THEN amount ELSE 0 END),0) AS income,
    COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS expenses
    FROM transactions
    WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?
    GROUP BY date");
$dataStmt->execute([$userId, $year, $month]);
$calendarData = [];
foreach ($dataStmt->fetchAll() as $row) {
    $calendarData[$row['date']] = $row;
}
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h1 class="h3 fw-bold mb-1">Calendar View</h1>
    <p class="text-muted-green mb-0">See transaction activity day by day for the selected month.</p>
  </div>
  <form method="get" class="d-flex gap-2 align-items-end">
    <div><label class="form-label mb-1">Month</label><input class="form-control" type="month" name="month" value="<?= e($selectedMonth) ?>"></div>
    <button class="btn btn-success">Load Calendar</button>
  </form>
</div>
<div class="card p-4">
  <div class="calendar-grid mb-3 fw-semibold text-center text-muted-green">
    <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
  </div>
  <div class="calendar-grid">
    <?php for ($i = 1; $i < $startWeekday; $i++): ?><div class="calendar-cell empty"></div><?php endfor; ?>
    <?php for ($day = 1; $day <= $daysInMonth; $day++): $date = sprintf('%04d-%02d-%02d', $year, $month, $day); $meta = $calendarData[$date] ?? null; ?>
      <div class="calendar-cell">
        <div class="calendar-day"><?= $day ?></div>
        <?php if ($meta): ?>
          <div class="calendar-meta mt-2"><?= (int)$meta['tx_count'] ?> transaction(s)</div>
          <div class="calendar-meta">Income: $<?= number_format((float)$meta['income'], 2) ?></div>
          <div class="calendar-meta">Expenses: $<?= number_format((float)$meta['expenses'], 2) ?></div>
        <?php else: ?>
          <div class="calendar-meta mt-2">No activity</div>
        <?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
