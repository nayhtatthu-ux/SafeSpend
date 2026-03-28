<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';
$reset = null;
if ($token !== '') {
    $reset = find_valid_password_reset($pdo, $token);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    if (!$reset) {
        $error = 'This reset link is invalid or has expired.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password and confirmation password must match.';
    } else {
        $passwordErrors = password_strength_errors($password);
        if ($passwordErrors) {
            $error = 'Password must include ' . implode(', ', $passwordErrors) . '.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([$hash, (int)$reset['user_id']]);

                $invalidate = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
                $invalidate->execute([(int)$reset['user_id']]);

                audit_log($pdo, (int)$reset['user_id'], 'PASSWORD_RESET_COMPLETE', 'Password reset completed through email link.', $reset['email']);
                $pdo->commit();
                $success = 'Your password has been reset successfully. You can now sign in.';
                $reset = null;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Unable to reset password right now. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Set New Password • SafeSpend</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="auth-shell">
  <div class="auth-card">
    <div class="d-flex align-items-center gap-3 mb-4">
      <div class="auth-brand"><i class="bi bi-key"></i></div>
      <div>
        <h1 class="h3 fw-bold mb-1">Choose a New Password</h1>
        <div class="text-muted-green">Use a strong password to protect your SafeSpend account.</div>
      </div>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?><div class="mt-2"><a href="login.php">Go to Sign In</a></div></div><?php endif; ?>

    <?php if (!$success): ?>
      <?php if (!$reset): ?>
        <div class="alert alert-danger">This reset link is invalid or has expired.</div>
        <p class="text-center mb-0"><a href="forgot_password.php">Request a new reset link</a></p>
      <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input class="form-control form-control-lg" type="password" name="password" required autocomplete="new-password">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input class="form-control form-control-lg" type="password" name="confirm_password" required autocomplete="new-password">
          </div>
          <div class="small text-muted-green mb-3">Use at least 8 characters with uppercase, lowercase, and a number.</div>
          <button class="btn btn-success btn-lg w-100">Reset Password</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
