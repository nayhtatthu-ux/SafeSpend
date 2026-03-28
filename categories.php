<?php
$pageTitle = 'Categories • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        if ($name !== '' && mb_strlen($name) <= 100 && valid_transaction_type($type)) {
            $check = $pdo->prepare('SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?');
            $check->execute([$userId, $name, $type]);
            if ($check->fetch()) {
                flash_set('error', 'That category already exists.');
            } else {
                $insert = $pdo->prepare('INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)');
                $insert->execute([$userId, $name, $type]);
                audit_log($pdo, $userId, 'CREATE_CATEGORY', 'Created category ' . $name . '.');
                flash_set('success', 'Category added.');
            }
        }
        header('Location: categories.php');
        exit;
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        if ($id <= 0 || $name === '' || mb_strlen($name) > 100 || !valid_transaction_type($type)) {
            flash_set('error', 'Please enter a valid category name and type.');
            header('Location: categories.php');
            exit;
        }
        $update = $pdo->prepare('UPDATE categories SET name = ?, type = ? WHERE id = ? AND user_id = ?');
        $update->execute([$name, $type, $id, $userId]);
        audit_log($pdo, $userId, 'UPDATE_CATEGORY', 'Updated category #' . $id . '.');
        flash_set('success', 'Category updated.');
        header('Location: categories.php');
        exit;
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $check = $pdo->prepare('SELECT COUNT(*) AS total FROM transactions WHERE category_id = ? AND user_id = ?');
        $check->execute([$id, $userId]);
        $count = (int)($check->fetch()['total'] ?? 0);
        if ($count > 0) {
            flash_set('error', 'Cannot delete category while transactions still use it.');
        } else {
            $delete = $pdo->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
            $delete->execute([$id, $userId]);
            audit_log($pdo, $userId, 'DELETE_CATEGORY', 'Deleted category #' . $id . '.');
            flash_set('success', 'Category deleted.');
        }
        header('Location: categories.php');
        exit;
    }
}

$stmt = $pdo->prepare('SELECT c.* FROM categories c WHERE c.user_id = ? ORDER BY c.type, c.name');
$stmt->execute([$userId]);
$categories = $stmt->fetchAll();
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card p-4">
      <h1 class="h5 fw-bold mb-3">Add Category</h1>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
        <div class="mb-3"><label class="form-label">Type</label><select class="form-select" name="type"><option value="Saving">Income</option><option value="Expense">Expense</option></select></div>
        <button class="btn btn-success btn-white-text w-100">Create Category</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-1">Manage Categories</h2>
          <div class="text-muted-green small">Manage your income and expense categories.</div>
        </div>
      </div>
      <div class="table-scroll">
        <table class="table align-middle mb-0">
          <thead><tr><th>Name</th><th>Type</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td>
                <form method="post" class="d-flex gap-2 align-items-center">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                  <input class="form-control form-control-sm" name="name" value="<?= e($cat['name']) ?>" required>
              </td>
              <td>
                  <select class="form-select form-select-sm" name="type">
                    <option value="Saving" <?= $cat['type'] === 'Saving' ? 'selected' : '' ?>>Income</option>
                    <option value="Expense" <?= $cat['type'] === 'Expense' ? 'selected' : '' ?>>Expense</option>
                  </select>
              </td>
              <td class="text-end">
                  <button class="btn btn-sm btn-outline-light">Save</button>
                </form>
                <form method="post" class="d-inline-block mt-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$categories): ?><tr><td colspan="3" class="text-center text-muted-green py-5">No categories yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
