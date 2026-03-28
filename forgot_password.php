<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            create_password_reset($pdo, (int)$user['id'], $user['email'], $user['username']);
            audit_log($pdo, (int)$user['id'], 'PASSWORD_RESET_REQUEST', 'Password reset requested by email.', $user['email']);
        }
    }
    $message = 'If that email exists in SafeSpend, a password reset link has been sent.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password • SafeSpend</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="auth-shell">
  <div class="auth-card">
    <div class="d-flex align-items-center gap-3 mb-4">
      <div class="auth-brand"><i class="bi bi-envelope-lock"></i></div>
      <div>
        <h1 class="h3 fw-bold mb-1">Reset Password</h1>
        <div class="text-muted-green">Enter your email to receive a secure reset link.</div>
      </div>
    </div>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input class="form-control form-control-lg" type="email" name="email" required autocomplete="email">
      </div>
      <button class="btn btn-success btn-lg w-100">Send Reset Link</button>
    </form>
    <p class="text-center mt-4 mb-0 text-muted-green"><a href="login.php">Back to Sign In</a></p>
  </div>
</div>
</body>
</html>
