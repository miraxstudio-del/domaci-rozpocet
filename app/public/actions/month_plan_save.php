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

function num_or_null(string $key): ?float
{
    $v = str_replace(',', '.', trim((string) ($_POST[$key] ?? '')));
    return ($v !== '' && is_numeric($v)) ? (float) $v : null;
}

$plannedIncome = num_or_null('planned_income');
$minRemaining = num_or_null('min_remaining');
$reserve = num_or_null('reserve_amount');
$totalBudget = num_or_null('total_budget');

upsert_month_plan($monthYear, $plannedIncome, $minRemaining, $reserve);
if ($totalBudget !== null) {
    upsert_budget($monthYear, null, $totalBudget);
}

flash('success', 'Měsíční plán byl uložen.');
redirect('rozpocty', ['m' => $monthYear]);
