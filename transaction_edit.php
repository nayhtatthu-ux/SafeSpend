<?php
$pageTitle = 'Edit Transaction • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();
$id = (int)($_GET['id'] ?? 0);
$txStmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ?');
$txStmt->execute([$id, $userId]);
$tx = $txStmt->fetch();
if (!$tx) {
    flash_set('error', 'Transaction not found.');
    header('Location: transactions.php');
    exit;
}
$categoriesStmt = $pdo->prepare('SELECT id, name, type FROM categories WHERE user_id = ? ORDER BY type, name');
$categoriesStmt->execute([$userId]);
$categories = $categoriesStmt->fetchAll();
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card p-4">
      <h1 class="h4 fw-bold mb-3">Edit Transaction</h1>
      <form method="post" action="transaction_update.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$tx['id'] ?>">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Description</label><input class="form-control" name="description" value="<?= e($tx['description']) ?>" required></div>
          <div class="col-md-3"><label class="form-label">Amount</label><input class="form-control" type="number" step="0.01" min="0.01" name="amount" value="<?= e((string)$tx['amount']) ?>" required></div>
          <div class="col-md-3"><label class="form-label">Type</label>
            <select class="form-select" id="txType" name="type">
              <option value="Saving" <?= $tx['type'] === 'Saving' ? 'selected' : '' ?>>Income</option>
              <option value="Expense" <?= $tx['type'] === 'Expense' ? 'selected' : '' ?>>Expense</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Category</label>
            <select class="form-select" name="category_id" id="categorySelect" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" data-type="<?= e($cat['type']) ?>" <?= (int)$tx['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?> (<?= e($cat['type'] === 'Saving' ? 'Income' : $cat['type']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Date</label><input class="form-control" type="date" name="date" value="<?= e($tx['date']) ?>" required></div>
          <div class="col-12 d-flex gap-2"><button class="btn btn-success">Update Transaction</button><a href="transactions.php" class="btn btn-outline-light">Cancel</a></div>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
const typeSelect = document.getElementById('txType');
const categorySelect = document.getElementById('categorySelect');
function filterCategories() {
  const selected = typeSelect.value;
  Array.from(categorySelect.options).forEach(opt => {
    opt.hidden = opt.dataset.type !== selected;
  });
  const current = categorySelect.selectedOptions[0];
  if (!current || current.hidden) {
    const firstVisible = Array.from(categorySelect.options).find(opt => !opt.hidden);
    if (firstVisible) categorySelect.value = firstVisible.value;
  }
}
filterCategories();
typeSelect.addEventListener('change', filterCategories);
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
