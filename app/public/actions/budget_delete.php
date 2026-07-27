<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('rozpocty');
}
csrf_check();

$monthYear = (string) ($_POST['month_year'] ?? current_month_year());
$categoryId = (int) ($_POST['category_id'] ?? 0);

$stmt = db()->prepare('DELETE FROM budgets WHERE month_year = :m AND category_id = :c');
$stmt->execute(['m' => $monthYear, 'c' => $categoryId]);

flash('success', 'Rozpočet kategorie byl odstraněn (bude se používat výchozí limit kategorie, pokud je nastaven).');
redirect('rozpocty', ['m' => $monthYear]);
