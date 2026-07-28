<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

$scope = (string) ($_GET['scope'] ?? 'month');
$monthYear = (string) ($_GET['monthYear'] ?? current_month_year());
if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    $monthYear = current_month_year();
}
$dateFrom = (string) ($_GET['dateFrom'] ?? '');
$dateTo = (string) ($_GET['dateTo'] ?? '');
$year = (string) ($_GET['year'] ?? date('Y'));
if (!preg_match('/^\d{4}$/', $year)) {
    $year = date('Y');
}

$filters = [];
$titleParts = [APP_NAME];
switch ($scope) {
    case 'month':
        $filters['month_year'] = $monthYear;
        $titleParts[] = month_year_label($monthYear);
        break;
    case 'year':
        $filters['year'] = $year;
        $titleParts[] = 'Rok ' . $year;
        break;
    case 'range':
        if ($dateFrom) $filters['date_from'] = $dateFrom;
        if ($dateTo) $filters['date_to'] = $dateTo;
        $titleParts[] = 'Období ' . ($dateFrom ? format_date_cz($dateFrom) : '…') . ' – ' . ($dateTo ? format_date_cz($dateTo) : '…');
        break;
    case 'income':
        $filters['type'] = 'prijem';
        $titleParts[] = 'Příjmy';
        break;
    case 'expense':
        $filters['type'] = 'vydaj';
        $titleParts[] = 'Výdaje';
        break;
    case 'business':
        $filters['is_business'] = '1';
        $titleParts[] = 'Podnikatelské položky';
        break;
    case 'categories':
        $titleParts[] = 'Přehled podle kategorií – ' . month_year_label($monthYear);
        break;
    case 'all':
    default:
        $titleParts[] = 'Všechny položky';
        break;
}

if ($scope === 'categories') {
    $export = build_category_summary_export_rows($monthYear);
} else {
    $items = find_transactions($filters, 100000, 0);
    $export = build_transaction_export_rows($items);
}

$totalSum = 0.0;
if ($scope !== 'categories' && isset($items)) {
    foreach ($items as $t) {
        $totalSum += $t['type'] === 'prijem' ? (float) $t['amount'] : -(float) $t['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<title><?= h(implode(' · ', $titleParts)) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
  body { padding: 30px; max-width: 1000px; margin: 0 auto; }
  .print-toolbar { margin-bottom: 20px; }
  table { font-size: 12.5px; }
  th, td { padding: 6px 8px !important; }
</style>
</head>
<body>
  <div class="print-toolbar no-print btn-row">
    <button class="btn" onclick="window.print()">🖨️ Vytisknout / Uložit jako PDF</button>
    <button class="btn secondary" onclick="window.close()">Zavřít</button>
  </div>
  <h1><?= h(APP_NAME) ?></h1>
  <p class="text-muted"><?= h(implode(' · ', array_slice($titleParts, 1))) ?> · vygenerováno <?= format_date_cz(date('Y-m-d')) ?></p>

  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <?php foreach ($export['headers'] as $h): ?><th style="border-bottom:2px solid #ccc;text-align:left;"><?= h($h) ?></th><?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($export['rows'] as $row): ?>
        <tr>
          <?php foreach ($row as $cell): ?><td style="border-bottom:1px solid #eee;"><?= h((string) $cell) ?></td><?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($scope !== 'categories'): ?>
    <p style="margin-top:16px;"><strong>Počet položek: <?= count($export['rows']) ?> · Bilance: <?= format_money($totalSum) ?></strong></p>
  <?php endif; ?>

  <p class="text-faint" style="margin-top:30px;font-size:11px;">Vygenerováno aplikací Domácí rozpočet.</p>
</body>
</html>
