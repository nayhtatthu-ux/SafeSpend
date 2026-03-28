<?php
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const DB_HOST = 'localhost';
const DB_NAME = 'safespend_db';
const DB_USER = 'root';
const DB_PASS = '';
const LOGIN_RATE_WINDOW_MINUTES = 2;
const LOGIN_RATE_MAX_ATTEMPTS = 5;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check includes/bootstrap.php settings.');
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token. Refresh the page and try again.');
    }
}

function flash_set(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string {
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }
    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

function require_auth(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function client_ip(): string {
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function audit_log(PDO $pdo, ?int $userId, string $action, string $description = '', ?string $usernameAttempt = null): void {
    static $stmt = null;
    if ($stmt === null) {
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, description, ip_address, username_attempt) VALUES (?, ?, ?, ?, ?)');
    }
    $stmt->execute([$userId, substr($action, 0, 100), mb_substr($description, 0, 1000), client_ip(), $usernameAttempt !== null ? mb_substr($usernameAttempt, 0, 100) : null]);
}

function login_attempts_remaining(PDO $pdo, string $username): array {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM audit_logs WHERE action = ? AND ip_address = ? AND username_attempt = ? AND created_at >= (NOW() - INTERVAL ? MINUTE)');
    $stmt->execute(['LOGIN_FAIL', client_ip(), $username, LOGIN_RATE_WINDOW_MINUTES]);
    $attempts = (int)($stmt->fetch()['total'] ?? 0);
    $remaining = max(0, LOGIN_RATE_MAX_ATTEMPTS - $attempts);
    return [$attempts, $remaining];
}

function login_rate_limited(PDO $pdo, string $username): bool {
    [$attempts] = login_attempts_remaining($pdo, $username);
    return $attempts >= LOGIN_RATE_MAX_ATTEMPTS;
}

function password_strength_errors(string $password): array {
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = 'at least 8 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'one lowercase letter';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'one number';
    }
    return $errors;
}

function valid_date_string(string $date): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function valid_month_string(string $value): bool {
    if (!preg_match('/^\d{4}-\d{2}$/', $value)) {
        return false;
    }
    [$year, $month] = array_map('intval', explode('-', $value));
    return $year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12;
}

function valid_year_value(int $year): bool {
    return $year >= 2000 && $year <= 2100;
}

function valid_transaction_type(string $type): bool {
    return in_array($type, ['Saving', 'Expense'], true);
}

function validate_transaction_payload(string $description, float $amount, string $type, int $categoryId, string $date): ?string {
    if ($description === '' || mb_strlen($description) > 255) {
        return 'Description is required and must be 255 characters or fewer.';
    }
    if ($amount <= 0 || $amount > 9999999.99) {
        return 'Amount must be greater than 0 and within a valid range.';
    }
    if (!valid_transaction_type($type)) {
        return 'Transaction type is invalid.';
    }
    if ($categoryId <= 0) {
        return 'Please choose a valid category.';
    }
    if (!valid_date_string($date)) {
        return 'Please choose a valid date.';
    }
    return null;
}

function recalculate_balance(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'Saving' THEN amount ELSE -amount END), 0) AS ledger_total FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $ledgerTotal = (float)($stmt->fetch()['ledger_total'] ?? 0);

    $stmt = $pdo->prepare('SELECT id FROM bankbalance WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if ($row) {
        $update = $pdo->prepare('UPDATE bankbalance SET balance = ?, confirmed = 1 WHERE id = ?');
        $update->execute([$ledgerTotal, (int)$row['id']]);
    } else {
        $insert = $pdo->prepare('INSERT INTO bankbalance (user_id, balance, confirmed) VALUES (?, ?, 1)');
        $insert->execute([$userId, $ledgerTotal]);
    }
}

function get_balance(PDO $pdo, int $userId): float {
    $stmt = $pdo->prepare('SELECT COALESCE(balance, 0) AS balance FROM bankbalance WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    return (float)($stmt->fetch()['balance'] ?? 0);
}

function get_budget_period_clause(string $periodType, string $value): array {
    switch ($periodType) {
        case 'year':
            $year = preg_match('/^\d{4}$/', $value) ? $value : date('Y');
            return ["YEAR(t.date) = ?", [(int)$year], $year, null];
        case 'date':
            $date = valid_date_string($value) ? $value : date('Y-m-d');
            return ["t.date = ?", [$date], $date, null];
        case 'month':
        default:
            $month = valid_month_string($value) ? $value : date('Y-m');
            [$y, $m] = explode('-', $month);
            return ["YEAR(t.date) = ? AND MONTH(t.date) = ?", [(int)$y, (int)$m], $month, ['year' => (int)$y, 'month' => (int)$m]];
    }
}

function get_budget_overview(PDO $pdo, int $userId, int $year, int $month): array {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.amount AS budget_amount, c.id AS category_id, c.name AS category_name,
                COALESCE(SUM(CASE WHEN t.type='Expense' THEN t.amount ELSE 0 END),0) AS spent_amount
         FROM budgets b
         INNER JOIN categories c ON c.id = b.category_id AND c.user_id = b.user_id
         LEFT JOIN transactions t ON t.category_id = b.category_id AND t.user_id = b.user_id
             AND t.type='Expense' AND YEAR(t.date)=? AND MONTH(t.date)=?
         WHERE b.user_id = ? AND b.budget_year = ? AND b.budget_month = ?
         GROUP BY b.id, c.id, c.name, b.amount
         ORDER BY c.name"
    );
    $stmt->execute([$year, $month, $userId, $year, $month]);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $budget = (float)$row['budget_amount'];
        $spent = (float)$row['spent_amount'];
        $remaining = $budget - $spent;
        $percentage = $budget > 0 ? min(($spent / $budget) * 100, 999) : 0;
        if ($percentage >= 100) {
            $status = 'Exceeded';
            $status_class = 'danger';
        } elseif ($percentage >= 80) {
            $status = 'Warning';
            $status_class = 'warning';
        } else {
            $status = 'On Track';
            $status_class = 'success';
        }
        $row['budget_amount'] = $budget;
        $row['spent_amount'] = $spent;
        $row['remaining_amount'] = $remaining;
        $row['percentage_used'] = $percentage;
        $row['status'] = $status;
        $row['status_class'] = $status_class;
        $rows[] = $row;
    }
    return $rows;
}

function current_theme(): string {
    return ($_SESSION['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
}

function format_type_label(string $type): string {
    return $type === 'Saving' ? 'Income' : $type;
}
