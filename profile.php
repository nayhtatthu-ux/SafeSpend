<?php
$pageTitle = 'Profile • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();
$stmt = $pdo->prepare('SELECT username, email, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$budgetRows = get_budget_overview($pdo, $userId, (int)date('Y'), (int)date('m'));
$currentBudget = array_sum(array_map(fn($r) => (float)$r['budget_amount'], $budgetRows));
?>
<div class="profile-shell-wide profile-page-bgfix d-flex align-items-center justify-content-center">
  <div class="card profile-auth-like profile-auth-like-wide">
    <div class="text-center mb-4">
      <h1 class="h2 fw-bold mb-2">Profile</h1>
    </div>

    <div class="row g-3 mb-4 profile-inline-stats">
      <div class="col-md-6">
        <div class="profile-compact-box text-center h-100">
          <div class="profile-label-sm">Username</div>
          <div class="profile-value-lg"><?= e($user['username'] ?? '') ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="profile-compact-box text-center h-100">
          <div class="profile-label-sm">Email Address</div>
          <div class="profile-value-lg"><?= e($user['email'] ?? '') ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="profile-compact-box text-center h-100">
          <div class="profile-label-sm">Member Since</div>
          <div class="profile-value-lg small"><?= e(date('F j, Y', strtotime((string)($user['created_at'] ?? 'now')))) ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="profile-compact-box text-center h-100">
          <div class="profile-label-sm">Current Budget</div>
          <div class="profile-value-lg">$<?= number_format($currentBudget, 2) ?></div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-center gap-2 flex-wrap">
      <a class="btn btn-success px-4" href="profile_edit.php"><i class="bi bi-key me-2"></i>Change Password</a>
      <a class="btn btn-outline-success px-4" href="dashboard.php">Back to Dashboard</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
