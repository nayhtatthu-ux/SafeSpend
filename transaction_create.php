<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transaction_new.php');
    exit;
}
verify_csrf();
$userId = current_user_id();
$description = trim($_POST['description'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);
$type = $_POST['type'] ?? '';
$categoryId = (int)($_POST['category_id'] ?? 0);
$date = $_POST['date'] ?? '';
$error = validate_transaction_payload($description, $amount, $type, $categoryId, $date);
if ($error !== null) {
    flash_set('error', $error);
    header('Location: transaction_new.php');
    exit;
}
$cat = $pdo->prepare('SELECT id, name FROM categories WHERE id = ? AND user_id = ? AND type = ?');
$cat->execute([$categoryId, $userId, $type]);
$category = $cat->fetch();
if (!$category) {
    flash_set('error', 'Selected category does not match the transaction type.');
    header('Location: transaction_new.php');
    exit;
}
$insert = $pdo->prepare('INSERT INTO transactions (description, amount, type, category_id, user_id, date) VALUES (?, ?, ?, ?, ?, ?)');
$insert->execute([$description, $amount, $type, $categoryId, $userId, $date]);
recalculate_balance($pdo, $userId);
audit_log($pdo, $userId, 'ADD_TRANSACTION', 'Added ' . format_type_label($type) . ' transaction for category ' . $category['name'] . '.');
if ($type === 'Expense') {
    $budgetStmt = $pdo->prepare('SELECT amount FROM budgets WHERE user_id = ? AND category_id = ? AND budget_year = ? AND budget_month = ? LIMIT 1');
    $budgetStmt->execute([$userId, $categoryId, (int)date('Y', strtotime($date)), (int)date('n', strtotime($date))]);
    $budget = $budgetStmt->fetch();
    if ($budget) {
        $spentStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS spent FROM transactions WHERE user_id = ? AND category_id = ? AND type = 'Expense' AND YEAR(date)=? AND MONTH(date)=?");
        $spentStmt->execute([$userId, $categoryId, (int)date('Y', strtotime($date)), (int)date('n', strtotime($date))]);
        $spent = (float)($spentStmt->fetch()['spent'] ?? 0);
        $percentage = ((float)$budget['amount']) > 0 ? ($spent / (float)$budget['amount']) * 100 : 0;
        if ($percentage >= 100) {
            flash_set('success', 'Transaction added. Warning: this category budget has been exceeded.');
        } elseif ($percentage >= 80) {
            flash_set('success', 'Transaction added. Notice: this category has used more than 80% of its budget.');
        } else {
            flash_set('success', 'Transaction added successfully.');
        }
    } else {
        flash_set('success', 'Transaction added successfully.');
    }
} else {
    flash_set('success', 'Transaction added successfully.');
}
header('Location: transactions.php');
