<?php
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrf_input(): string
{
    $token = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verify_csrf(): void
{
    $posted = $_POST['csrf_token'] ?? '';
    $session = $_SESSION['csrf_token'] ?? '';
    if (!$posted || !$session || !hash_equals($session, $posted)) {
        http_response_code(403);
        exit('Invalid request token.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function log_action(PDO $pdo, ?int $userId, string $action, string $description, string $ip = 'unknown', ?string $usernameAttempt = null): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO audit_logs (user_id, action, description, ip_address, username_attempt)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $action, $description, $ip, $usernameAttempt]);
}
