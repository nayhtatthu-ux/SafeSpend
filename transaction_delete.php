<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transactions.php');
    exit;
}
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$userId = current_user_id();
if ($id) {
    $lookup = $pdo->prepare('SELECT description FROM transactions WHERE id = ? AND user_id = ? LIMIT 1');
    $lookup->execute([$id, $userId]);
    $tx = $lookup->fetch();
    $delete = $pdo->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
    $delete->execute([$id, $userId]);
    recalculate_balance($pdo, $userId);
    audit_log($pdo, $userId, 'DELETE_TRANSACTION', 'Deleted transaction #' . $id . ($tx ? ' (' . $tx['description'] . ')' : '') . '.');
    flash_set('success', 'Transaction deleted successfully.');
}
header('Location: transactions.php');
