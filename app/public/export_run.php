<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

$scope = (string) ($_GET['scope'] ?? 'month');
$format = (string) ($_GET['format'] ?? 'csv');
$monthYear = (string) ($_GET['m'] ?? current_month_year());
if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    $monthYear = current_month_year();
}
$dateFrom = (string) ($_GET['date_from'] ?? '');
$dateTo = (string) ($_GET['date_to'] ?? '');
$year = (string) ($_GET['y'] ?? date('Y'));
if (!preg_match('/^\d{4}$/', $year)) {
    $year = date('Y');
}

if ($format === 'print') {
    header('Location: /tisk.php?' . http_build_query(compact('scope', 'monthYear', 'dateFrom', 'dateTo', 'year')));
    exit;
}

$filters = [];
$label = 'export';
switch ($scope) {
    case 'month':
        $filters['month_year'] = $monthYear;
        $label = 'mesic_' . $monthYear;
        break;
    case 'year':
        $filters['year'] = $year;
        $label = 'rok_' . $year;
        break;
    case 'range':
        if ($dateFrom) $filters['date_from'] = $dateFrom;
        if ($dateTo) $filters['date_to'] = $dateTo;
        $label = 'obdobi_' . ($dateFrom ?: 'od') . '_' . ($dateTo ?: 'do');
        break;
    case 'income':
        $filters['type'] = 'prijem';
        $label = 'prijmy';
        break;
    case 'expense':
        $filters['type'] = 'vydaj';
        $label = 'vydaje';
        break;
    case 'business':
        $filters['is_business'] = '1';
        $label = 'podnikatelske';
        break;
    case 'categories':
        $label = 'kategorie_' . $monthYear;
        break;
    case 'all':
    default:
        $label = 'vsechny_polozky';
        break;
}

if ($scope === 'categories') {
    $export = build_category_summary_export_rows($monthYear);
} else {
    $items = find_transactions($filters, 100000, 0);
    $export = build_transaction_export_rows($items);
}

$filenameBase = 'rozpocet_' . $label . '_' . date('Y-m-d_His');

if ($format === 'xlsx') {
    $path = get_export_dir() . DIRECTORY_SEPARATOR . $filenameBase . '.xlsx';
    write_xlsx_file($path, 'Export', $export['headers'], $export['rows']);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.xlsx"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// CSV (výchozí) - s BOM pro správné zobrazení české diakritiky v Excelu
$path = get_export_dir() . DIRECTORY_SEPARATOR . $filenameBase . '.csv';
$fh = fopen($path, 'w');
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, $export['headers'], ';');
foreach ($export['rows'] as $row) {
    fputcsv($fh, $row, ';');
}
fclose($fh);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');
header('Content-Length: ' . filesize($path));
readfile($path);
