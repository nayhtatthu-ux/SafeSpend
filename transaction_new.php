<?php
$pageTitle = 'Add Transaction • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();
$categoriesStmt = $pdo->prepare('SELECT id, name, type FROM categories WHERE user_id = ? ORDER BY type, name');
$categoriesStmt->execute([$userId]);
$categories = $categoriesStmt->fetchAll();
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h1 class="h4 fw-bold mb-1">Add Transaction</h1>
          <div class="text-muted-green">Create a new entry for your account.</div>
        </div>
        <a href="transactions.php" class="btn btn-outline-light">Back</a>
      </div>
      <form method="post" action="transaction_create.php">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Description</label>
            <input class="form-control" name="description" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Amount</label>
            <input class="form-control" type="number" step="0.01" min="0.01" name="amount" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Type</label>
            <select class="form-select" id="txType" name="type" required>
              <option value="Saving">Income</option>
              <option value="Expense">Expense</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select class="form-select" name="category_id" id="categorySelect" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" data-type="<?= e($cat['type']) ?>"><?= e($cat['name']) ?> (<?= e($cat['type'] === 'Saving' ? 'Income' : $cat['type']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Date</label>
            <input class="form-control" type="date" name="date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-success">Save Transaction</button>
            <a href="categories.php" class="btn btn-outline-light">Manage Categories</a>
          </div>
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
  const firstVisible = Array.from(categorySelect.options).find(opt => !opt.hidden);
  if (firstVisible) categorySelect.value = firstVisible.value;
}
filterCategories();
typeSelect.addEventListener('change', filterCategories);
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
