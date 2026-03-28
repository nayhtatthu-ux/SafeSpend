<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$userId = current_user_id();
$search = trim($_GET['search'] ?? '');
$filterBy = $_GET['filter_by'] ?? 'month';
if (!in_array($filterBy, ['date', 'month', 'year'], true)) {
    $filterBy = 'month';
}
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedYear = $_GET['year'] ?? date('Y');
$typeFilter = $_GET['type'] ?? '';
if (!in_array($typeFilter, ['', 'Saving', 'Expense'], true)) {
    $typeFilter = '';
}
$categoryFilter = (int)($_GET['category_id'] ?? 0);
$minAmount = trim($_GET['min_amount'] ?? '');
$maxAmount = trim($_GET['max_amount'] ?? '');
$sortBy = $_GET['sort'] ?? 'date_desc';
if (!in_array($sortBy, ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'], true)) {
    $sortBy = 'date_desc';
}

$sql = "SELECT t.date, t.description, COALESCE(c.name, 'Uncategorized') AS category_name, t.type, t.amount
        FROM transactions t
        LEFT JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = :user_id";
$params = ['user_id' => $userId];
if ($search !== '') {
    $sql .= " AND (t.description LIKE :search_desc OR COALESCE(c.name, '') LIKE :search_cat OR t.type LIKE :search_type OR t.date LIKE :search_date)";
    $searchWildcard = '%' . $search . '%';
    $params['search_desc'] = $searchWildcard;
    $params['search_cat'] = $searchWildcard;
    $params['search_type'] = $searchWildcard;
    $params['search_date'] = $searchWildcard;
}
if ($typeFilter !== '') {
    $sql .= ' AND t.type = :type_filter';
    $params['type_filter'] = $typeFilter;
}
if ($categoryFilter > 0) {
    $sql .= ' AND t.category_id = :category_filter';
    $params['category_filter'] = $categoryFilter;
}
if ($minAmount !== '' && is_numeric($minAmount)) {
    $sql .= ' AND t.amount >= :min_amount';
    $params['min_amount'] = (float)$minAmount;
}
if ($maxAmount !== '' && is_numeric($maxAmount)) {
    $sql .= ' AND t.amount <= :max_amount';
    $params['max_amount'] = (float)$maxAmount;
}
switch ($filterBy) {
    case 'date':
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = date('Y-m-d');
        }
        $sql .= ' AND t.date = :selected_date';
        $params['selected_date'] = $selectedDate;
        break;
    case 'year':
        if (!preg_match('/^\d{4}$/', $selectedYear)) {
            $selectedYear = date('Y');
        }
        $sql .= ' AND YEAR(t.date) = :selected_year';
        $params['selected_year'] = (int)$selectedYear;
        break;
    case 'month':
    default:
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }
        [$yearPart, $monthPart] = explode('-', $selectedMonth);
        $sql .= ' AND YEAR(t.date) = :selected_year AND MONTH(t.date) = :selected_month';
        $params['selected_year'] = (int)$yearPart;
        $params['selected_month'] = (int)$monthPart;
        break;
}
$orderSql = match ($sortBy) {
    'date_asc' => ' ORDER BY t.date ASC, t.id ASC',
    'amount_desc' => ' ORDER BY t.amount DESC, t.date DESC',
    'amount_asc' => ' ORDER BY t.amount ASC, t.date DESC',
    default => ' ORDER BY t.date DESC, t.id DESC',
};
$sql .= $orderSql;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filenameBase = 'transactions_' . date('Ymd_His');
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Description', 'Category', 'Type', 'Amount']);
foreach ($rows as $row) {
    fputcsv($output, [$row['date'], $row['description'], $row['category_name'], format_type_label($row['type']), number_format((float)$row['amount'], 2, '.', '')]);
}
fclose($output);
exit;
