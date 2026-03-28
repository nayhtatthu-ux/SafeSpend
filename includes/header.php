<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = $pageTitle ?? 'SafeSpend';
$loggedIn = !empty($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Guest';
$theme = current_theme();
?>
<!doctype html>
<html lang="en" data-bs-theme="<?= e($theme) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="theme-<?= e($theme) ?>">
<nav class="navbar navbar-expand-lg safespend-nav sticky-top border-bottom border-success-subtle">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $loggedIn ? 'dashboard.php' : 'login.php' ?>">
      <i class="bi bi-shield-check me-2"></i>SafeSpend
    </a>
    <?php if ($loggedIn): ?>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-lg-center justify-content-center safespend-nav-links">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="comparative.php">Comparative</a></li>
        <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="budgets.php">Budgets</a></li>
        <li class="nav-item"><a class="nav-link" href="goals.php">Goals</a></li>
        <li class="nav-item"><a class="nav-link" href="calendar.php">Calendar</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2 text-light small flex-wrap justify-content-end safespend-userbar">
        <form method="post" action="theme_toggle.php" class="d-inline-flex align-items-center gap-2 m-0">
          <?= csrf_field() ?>
          <input type="hidden" name="theme" value="<?= e($theme === 'dark' ? 'light' : 'dark') ?>">
          <button class="btn btn-outline-light btn-sm px-3" type="submit">
            <i class="bi bi-circle-half me-1"></i><?= $theme === 'dark' ? 'Light' : 'Dark' ?>
          </button>
        </form>
        <form method="post" action="logout.php" class="d-inline m-0">
          <?= csrf_field() ?>
          <button class="btn btn-success btn-sm px-3" type="submit">Logout</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</nav>
<div class="container py-4">
<?php if ($msg = flash_get('success')): ?><div class="alert alert-success shadow-sm"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="alert alert-danger shadow-sm"><?= e($msg) ?></div><?php endif; ?>
