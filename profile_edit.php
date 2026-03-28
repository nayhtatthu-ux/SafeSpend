<?php
$pageTitle = 'Change Password • SafeSpend';
require_once __DIR__ . '/includes/header.php';
require_auth();
$userId = current_user_id();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $userRow = $stmt->fetch();

    if (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
        flash_set('error', 'Your current password is incorrect.');
    } elseif ($newPassword !== $confirmPassword) {
        flash_set('error', 'New password and confirm password do not match.');
    } elseif ($currentPassword === $newPassword) {
        flash_set('error', 'Your new password must be different from your current password.');
    } else {
        $passwordErrors = password_strength_errors($newPassword);
        if ($passwordErrors) {
            flash_set('error', 'New password must include ' . implode(', ', $passwordErrors) . '.');
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([$hash, $userId]);
            session_regenerate_id(true);
            audit_log($pdo, $userId, 'CHANGE_PASSWORD', 'Password changed from profile edit page.');
            flash_set('success', 'Password changed successfully.');
        }
    }
    header('Location: profile_edit.php');
    exit;
}
?>
<div class="profile-shell-wide profile-page-bgfix d-flex align-items-center justify-content-center">
  <div class="card profile-auth-like">
    <div class="text-center mb-4">
      <h1 class="h2 fw-bold mb-2">Change Password</h1>
      <p class="mb-0 text-muted-green">Update your password securely using your current password.</p>
    </div>
    <form method="post" class="auth-stack-form">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Current Password</label>
        <input class="form-control form-control-lg text-center" type="password" name="current_password" required>
      </div>
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input class="form-control form-control-lg text-center" type="password" name="new_password" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm New Password</label>
        <input class="form-control form-control-lg text-center" type="password" name="confirm_password" required>
      </div>
      <div class="d-flex justify-content-center gap-2 flex-wrap mt-4">
        <button class="btn btn-success px-4" type="submit">Update Password</button>
        <a class="btn btn-outline-success px-4" href="profile.php">Back to Profile</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
