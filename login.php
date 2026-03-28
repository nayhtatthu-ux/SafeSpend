<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } elseif (login_rate_limited($pdo, $username)) {
        $error = 'Too many failed sign-in attempts. Please wait 2 minutes and try again.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            audit_log($pdo, (int)$user['id'], 'LOGIN_SUCCESS', 'User signed in successfully.', $username);
            flash_set('success', 'Welcome back.');
            header('Location: dashboard.php');
            exit;
        }
        
        audit_log($pdo, null, 'LOGIN_FAIL', 'Invalid login credentials.', $username);
        $error = 'Invalid login credentials.';
    }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login • SafeSpend</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>

<body>
<div class="auth-shell">
    <div class="auth-card">
    <div class="d-flex align-items-center gap-3 mb-4">

      <div class="auth-brand"><i class="bi bi-shield-lock"></i></div>
        <div>
         <h1 class="h3 fw-bold mb-1">SafeSpend</h1>
        <div class="text-muted-green">Sign in to continue.</div>
      </div>
      </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>

      <div class="mb-3">
          <label class="form-label">Username</label>
        <input class="form-control form-control-lg" name="username" autocomplete="username" required>
      </div>

      <div class="mb-3">
         <label class="form-label">Password</label>
        <input class="form-control form-control-lg" type="password" name="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-success btn-lg w-100">Sign In</button>
    </form>
    <p class="text-center mt-4 mb-0 text-muted-green">Need an account? <a href="register.php">Create one</a></p>
  </div>
</div>
</body>
</html>
