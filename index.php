<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Location: ' . (!empty($_SESSION['user_id']) ? 'dashboard.php' : 'login.php'));
exit;
