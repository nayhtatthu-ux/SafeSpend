<?php
$pageTitle = 'Savings Goals • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $goalName = trim($_POST['goal_name'] ?? '');
        $targetAmount = (float)($_POST['target_amount'] ?? 0);
        $currentAmount = (float)($_POST['current_amount'] ?? 0);
        $deadline = $_POST['deadline'] ?? null;
        if ($goalName !== '' && mb_strlen($goalName) <= 100 && $targetAmount > 0 && $targetAmount <= 9999999.99 && $currentAmount >= 0 && ($deadline === null || $deadline === '' || valid_date_string($deadline))) {
            $stmt = $pdo->prepare('INSERT INTO saving_goals (user_id, goal_name, target_amount, current_amount, deadline) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $goalName, $targetAmount, max($currentAmount, 0), $deadline !== '' ? $deadline : null]);
            audit_log($pdo, $userId, 'CREATE_GOAL', 'Created savings goal ' . $goalName . '.');
            flash_set('success', 'Savings goal created.');
        } else {
            flash_set('error', 'Please enter a valid goal name, amounts, and deadline.');
        }
    } elseif ($action === 'contribute') {
        $id = (int)($_POST['id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        if ($id && $amount > 0 && $amount <= 9999999.99) {
            $stmt = $pdo->prepare('UPDATE saving_goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$amount, $id, $userId]);
            audit_log($pdo, $userId, 'GOAL_CONTRIBUTION', 'Added a contribution to goal #' . $id . '.');
            flash_set('success', 'Goal contribution added.');
        } else {
            flash_set('error', 'Please enter a valid contribution amount.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM saving_goals WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
            audit_log($pdo, $userId, 'DELETE_GOAL', 'Deleted savings goal #' . $id . '.');
            flash_set('success', 'Goal removed.');
        }
    }
    header('Location: goals.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM saving_goals WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$stmt->execute([$userId]);
$goals = $stmt->fetchAll();
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card p-4">
      <h1 class="h5 fw-bold mb-3">Create Savings Goal</h1>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="mb-3"><label class="form-label">Goal Name</label><input class="form-control" name="goal_name" required></div>
        <div class="mb-3"><label class="form-label">Target Amount</label><input class="form-control" type="number" step="0.01" min="0.01" name="target_amount" required></div>
        <div class="mb-3"><label class="form-label">Starting Amount</label><input class="form-control" type="number" step="0.01" min="0" name="current_amount" value="0"></div>
        <div class="mb-3"><label class="form-label">Deadline</label><input class="form-control" type="date" name="deadline"></div>
        <button class="btn btn-success w-100">Create Goal</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-1">Goal Progress</h2>
          <div class="text-muted-green small">Track progress against each savings target.</div>
        </div>
      </div>
      <div class="row g-3">
        <?php foreach ($goals as $goal): $progress = $goal['target_amount'] > 0 ? min(($goal['current_amount'] / $goal['target_amount']) * 100, 100) : 0; ?>
        <div class="col-12">
          <div class="card p-3 card-hover">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-2 flex-wrap">
              <div>
                <div class="fw-bold"><?= e($goal['goal_name']) ?></div>
                <div class="small text-muted-green">Saved $<?= number_format((float)$goal['current_amount'], 2) ?> of $<?= number_format((float)$goal['target_amount'], 2) ?><?= $goal['deadline'] ? ' • Deadline: ' . e($goal['deadline']) : '' ?></div>
              </div>
              <span class="badge text-bg-success"><?= number_format($progress, 1) ?>%</span>
            </div>
            <div class="progress budget-progress progress-lg"><div class="progress-bar bg-success" style="width: <?= $progress ?>%"></div></div>
            <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
              <form method="post" class="d-flex gap-2 flex-wrap align-items-end">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="contribute">
                <input type="hidden" name="id" value="<?= (int)$goal['id'] ?>">
                <div><label class="form-label mb-1">Add Contribution</label><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="amount" required></div>
                <button class="btn btn-sm btn-success">Add</button>
              </form>
              <form method="post" onsubmit="return confirm('Delete this goal?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$goal['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$goals): ?><div class="text-center text-muted-green py-5">No savings goals created yet.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
