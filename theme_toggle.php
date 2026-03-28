<?php
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $theme = ($_POST['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
    $_SESSION['theme'] = $theme;
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || str_contains($accept, 'application/json');
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'theme' => $theme]);
    } else {
        $back = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
        header('Location: ' . $back);
    }
    exit;
}
http_response_code(405);
