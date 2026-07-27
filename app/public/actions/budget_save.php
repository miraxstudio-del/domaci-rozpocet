<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('rozpocty');
}
csrf_check();

$monthYear = (string) ($_POST['month_year'] ?? current_month_year());
if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    $monthYear = current_month_year();
}

$categoryId = ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null;
$amountRaw = str_replace(',', '.', trim((string) ($_POST['planned_amount'] ?? '')));

if ($amountRaw === '' || !is_numeric($amountRaw) || (float) $amountRaw < 0) {
    flash('error', 'Zadejte platnou částku rozpočtu.');
    redirect('rozpocty', ['m' => $monthYear]);
}

upsert_budget($monthYear, $categoryId, (float) $amountRaw);
flash('success', 'Rozpočet byl uložen.');
redirect('rozpocty', ['m' => $monthYear]);
