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
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $error = 'Username must be 3 to 30 characters and contain only letters, numbers, or underscores.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password and Confirm Password do not match.';
    } else {
        $passwordErrors = password_strength_errors($password);
        if ($passwordErrors) {
            $error = 'Password must include ' . implode(', ', $passwordErrors) . '.';
        } else {
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
                $insert->execute([$username, $email, $hash]);
                $userId = (int)$pdo->lastInsertId();
                $balance = $pdo->prepare('INSERT INTO bankbalance (user_id, balance, confirmed) VALUES (?, 0, 1)');
                $balance->execute([$userId]);
                audit_log($pdo, $userId, 'REGISTER', 'New account created.');
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                flash_set('success', 'Account created successfully.');
                header('Location: dashboard.php');
                exit;
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
  <title>Register • SafeSpend</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="auth-shell">
  <div class="auth-card">
    <div class="d-flex align-items-center gap-3 mb-4">
      <div class="auth-brand"><i class="bi bi-person-plus"></i></div>
      <div>
        <h1 class="h3 fw-bold mb-1">Create Account</h1>
        <div class="text-muted-green">Start using SafeSpend.</div>
      </div>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
      <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" autocomplete="new-password" required></div>
      <div class="mb-3"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="confirm_password" autocomplete="new-password" required></div>
      <div class="small text-muted-green mb-3">Use at least 8 characters with uppercase, lowercase, and a number.</div>
      <button class="btn btn-success w-100">Create Account</button>
    </form>
    <p class="text-center mt-4 mb-0 text-muted-green">Already registered? <a href="login.php">Sign in</a></p>
  </div>
</div>
</body>
</html>
