<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transactions.php');
    exit;
}
verify_csrf();
$userId = current_user_id();
$id = (int)($_POST['id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);
$type = $_POST['type'] ?? '';
$categoryId = (int)($_POST['category_id'] ?? 0);
$date = $_POST['date'] ?? '';
$error = $id <= 0 ? 'Invalid transaction update.' : validate_transaction_payload($description, $amount, $type, $categoryId, $date);
if ($error !== null) {
    flash_set('error', $error);
    header('Location: transactions.php');
    exit;
}
$owns = $pdo->prepare('SELECT id FROM transactions WHERE id = ? AND user_id = ?');
$owns->execute([$id, $userId]);
if (!$owns->fetch()) {
    flash_set('error', 'Transaction not found.');
    header('Location: transactions.php');
    exit;
}
$cat = $pdo->prepare('SELECT id, name FROM categories WHERE id = ? AND user_id = ? AND type = ?');
$cat->execute([$categoryId, $userId, $type]);
$category = $cat->fetch();
if (!$category) {
    flash_set('error', 'Selected category does not match the transaction type.');
    header('Location: transaction_edit.php?id=' . $id);
    exit;
}
$update = $pdo->prepare('UPDATE transactions SET description = ?, amount = ?, type = ?, category_id = ?, date = ? WHERE id = ? AND user_id = ?');
$update->execute([$description, $amount, $type, $categoryId, $date, $id, $userId]);
recalculate_balance($pdo, $userId);
audit_log($pdo, $userId, 'UPDATE_TRANSACTION', 'Updated transaction #' . $id . ' for category ' . $category['name'] . '.');
flash_set('success', 'Transaction updated successfully.');
header('Location: transactions.php');
