<?php
$pageTitle = 'Statistiky';
$activeNav = 'statistiky';

$view = ($_GET['view'] ?? 'month') === 'year' ? 'year' : 'month';

if ($view === 'year') {
    $period = $_GET['y'] ?? date('Y');
    if (!preg_match('/^\d{4}$/', $period)) {
        $period = date('Y');
    }
} else {
    $period = $_GET['m'] ?? current_month_year();
    if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
        $period = current_month_year();
    }
}

$trendMonths = max(3, min(24, (int) ($_GET['obdobi'] ?? 6)));
$prevPeriod = shift_period($period, -1);
$nextPeriod = shift_period($period, 1);
$showBusiness = get_setting('show_business', '1') === '1';

// Filtr na "Příjmy a výdaje" se pro měsíc a rok liší (month_year= vs year=)
$listFilterParam = $view === 'year' ? 'year=' . $period : 'month_year=' . $period;

$summary = month_summary($period);
$catBreakdown = category_breakdown($period, 'vydaj');
$methodBreakdown = payment_method_breakdown($period, 'vydaj');
$bizBreakdown = business_vs_personal($period);
$trend = monthly_trend($trendMonths);
$topExpenses = top_expenses($period, 10);
$avgExpense = $trend ? array_sum(array_column($trend, 'expense')) / count($trend) : 0;

$catChartData = [];
foreach (array_slice($catBreakdown, 0, 8) as $i => $c) {
    $catChartData[] = [
        'label' => $c['cat_name'] ?: 'Nezařazeno',
        'value' => (float) $c['total'],
        'valueLabel' => format_money((float) $c['total']),
        'color' => palette_color($i),
        'href' => '/index.php?p=polozky&' . $listFilterParam . '&category_id=' . (int) $c['cat_id'],
    ];
}
$methodChartData = [];
foreach ($methodBreakdown as $i => $m) {
    $methodChartData[] = [
        'label' => payment_methods()[$m['payment_method']] ?? $m['payment_method'],
        'value' => (float) $m['total'],
        'valueLabel' => format_money((float) $m['total']),
        'href' => '/index.php?p=polozky&' . $listFilterParam . '&payment_method=' . $m['payment_method'],
    ];
}

// Plánovaný vs skutečný rozpočet - u roku sečteme rozpočty a skutečnost napříč všemi měsíci
$planned = 0.0;
$actual = 0.0;
$totalPlanned = null;
if ($view === 'year') {
    for ($mi = 1; $mi <= 12; $mi++) {
        $my = sprintf('%s-%02d', $period, $mi);
        $budgetsM = get_budgets($my);
        $actualByCategoryM = category_actual_spend($my, 'vydaj');
        foreach (get_categories('vydaj', true) as $cat) {
            $p = $budgetsM['categories'][$cat['id']] ?? ($cat['monthly_limit'] !== null ? (float) $cat['monthly_limit'] : null);
            if ($p !== null) {
                $planned += $p;
                $actual += $actualByCategoryM[(int) $cat['id']] ?? 0;
            }
        }
        if ($budgetsM['total']) {
            $totalPlanned = ($totalPlanned ?? 0) + $budgetsM['total'];
        }
    }
} else {
    $budgets = get_budgets($period);
    $actualByCategory = category_actual_spend($period, 'vydaj');
    foreach (get_categories('vydaj', true) as $cat) {
        $p = $budgets['categories'][$cat['id']] ?? ($cat['monthly_limit'] !== null ? (float) $cat['monthly_limit'] : null);
        if ($p !== null) {
            $planned += $p;
            $actual += $actualByCategory[(int) $cat['id']] ?? 0;
        }
    }
    $totalPlanned = $budgets['total'];
}
?>
<div class="analytics-page">
<div class="topbar analytics-topbar">
  <div>
    <h1>Statistiky</h1>
    <div class="subtitle">Podrobný pohled na hospodaření domácnosti</div>
  </div>
  <div class="month-switch analytics-month-switch">
    <a class="btn outline icon-only" href="/index.php?<?= h(http_build_query($view === 'year' ? ['p' => 'statistiky', 'view' => 'year', 'y' => $prevPeriod] : ['p' => 'statistiky', 'm' => $prevPeriod, 'obdobi' => $trendMonths])) ?>">‹</a>
    <div class="current"><?= h(period_label($period)) ?></div>
    <a class="btn outline icon-only" href="/index.php?<?= h(http_build_query($view === 'year' ? ['p' => 'statistiky', 'view' => 'year', 'y' => $nextPeriod] : ['p' => 'statistiky', 'm' => $nextPeriod, 'obdobi' => $trendMonths])) ?>">›</a>
  </div>
  <div class="pill-nav analytics-view-tabs" style="margin:0;">
    <a class="<?= $view === 'month' ? 'active' : '' ?>" href="/index.php?p=statistiky&m=<?= h(current_month_year()) ?>">Měsíc</a>
    <a class="<?= $view === 'year' ? 'active' : '' ?>" href="/index.php?p=statistiky&view=year&y=<?= h(date('Y')) ?>">Rok</a>
  </div>
</div>

<div class="grid grid-cols-3 analytics-overview-grid">
  <div class="card analytics-chart-card">
    <h3>Příjmy proti výdajům</h3>
    <canvas class="chart" id="chart-income-expense"></canvas>
    <?= chart_legend_html([
        ['label' => 'Příjmy', 'color' => 'var(--income)', 'value' => format_money($summary['income'])],
        ['label' => 'Výdaje', 'color' => 'var(--expense)', 'value' => format_money($summary['expense'])],
    ]) ?>
  </div>
  <div class="card analytics-chart-card">
    <h3>Pravidelné proti jednorázovým</h3>
    <canvas class="chart" id="chart-regular-onetime"></canvas>
    <?= chart_legend_html([
        ['label' => 'Pravidelné', 'color' => palette_color(0), 'value' => format_money($summary['regular'])],
        ['label' => 'Jednorázové', 'color' => palette_color(1), 'value' => format_money($summary['onetime'])],
    ]) ?>
  </div>
  <div class="card analytics-chart-card">
    <h3>Způsob placení</h3>
    <canvas class="chart" id="chart-methods"></canvas>
    <?php if ($methodChartData): ?>
      <?= chart_legend_html(array_map(fn ($m, $i) => [
          'label' => $m['label'], 'color' => palette_color($i), 'value' => $m['valueLabel'],
      ], $methodChartData, array_keys($methodChartData))) ?>
    <?php else: ?>
      <p class="text-muted" style="font-size:13px;margin-top:10px;">Zatím žádné výdaje.</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($showBusiness && ($bizBreakdown['business'] > 0 || $bizBreakdown['personal'] > 0)): ?>
<div class="card analytics-section-card" style="margin-top:16px;">
  <h3>Soukromé proti podnikatelským výdajům</h3>
  <div class="grid grid-cols-2">
    <canvas class="chart" id="chart-business" style="max-width:320px;"></canvas>
    <div>
      <?= chart_legend_html([
          ['label' => 'Soukromé', 'color' => palette_color(0), 'value' => format_money($bizBreakdown['personal'])],
          ['label' => 'Podnikatelské', 'color' => palette_color(1), 'value' => format_money($bizBreakdown['business'])],
      ]) ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card analytics-section-card" style="margin-top:16px;">
  <div class="card-title-row"><h3>Výdaje podle kategorií</h3></div>
  <canvas class="chart" id="chart-categories" style="height:260px;"></canvas>
</div>

<div class="card analytics-section-card" style="margin-top:16px;">
  <div class="card-title-row">
    <h3>Vývoj příjmů a výdajů</h3>
    <div class="pill-nav" style="margin:0;">
      <a class="<?= $trendMonths === 6 ? 'active' : '' ?>" href="/index.php?p=statistiky&<?= $view === 'year' ? 'view=year&y=' . $period : 'm=' . $period ?>&obdobi=6">6 měsíců</a>
      <a class="<?= $trendMonths === 12 ? 'active' : '' ?>" href="/index.php?p=statistiky&<?= $view === 'year' ? 'view=year&y=' . $period : 'm=' . $period ?>&obdobi=12">12 měsíců</a>
      <a class="<?= $trendMonths === 24 ? 'active' : '' ?>" href="/index.php?p=statistiky&<?= $view === 'year' ? 'view=year&y=' . $period : 'm=' . $period ?>&obdobi=24">24 měsíců</a>
    </div>
  </div>
  <canvas class="chart" id="chart-trend" style="height:260px;"></canvas>
  <?= chart_legend_html([
      ['label' => 'Příjmy', 'color' => 'var(--income)'],
      ['label' => 'Výdaje', 'color' => 'var(--expense)'],
  ]) ?>
  <div class="text-muted" style="margin-top:8px;font-size:13px;">Průměrný měsíční výdaj za sledované období: <strong><?= format_money($avgExpense) ?></strong></div>
</div>

<div class="grid grid-cols-2 analytics-summary-grid" style="margin-top:16px;">
  <div class="card analytics-summary-card">
    <h3>💥 Největší výdaje <?= $view === 'year' ? 'roku' : 'měsíce' ?></h3>
    <?php if (!$topExpenses): ?>
      <p class="text-muted">Zatím žádné výdaje.</p>
    <?php else: ?>
      <div class="table-wrap"><table>
        <tbody>
          <?php foreach ($topExpenses as $t): ?>
            <tr>
              <td><a href="/index.php?p=polozka&id=<?= (int) $t['id'] ?>"><?= h($t['name']) ?></a><div class="text-faint" style="font-size:12px;"><?= h(($t['category_icon'] ?? '') . ' ' . ($t['category_name'] ?? 'Nezařazeno')) ?></div></td>
              <td class="text-right mono amount-out"><?= format_money((float) $t['amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>

  <div class="card analytics-summary-card">
    <h3>🎯 Plánovaný proti skutečnému rozpočtu</h3>
    <?php if ($planned <= 0 && !$totalPlanned): ?>
      <p class="text-muted">Zatím nemáte nastavené rozpočty. <a href="/index.php?p=rozpocty">Nastavit rozpočty →</a></p>
    <?php else: ?>
      <?php $totalPlannedShown = $totalPlanned ?: $planned; $pct = $totalPlannedShown > 0 ? ($summary['expense'] / $totalPlannedShown) * 100 : 0; ?>
      <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;">
        <span class="text-muted">Skutečnost</span><strong class="mono"><?= format_money($summary['expense']) ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;">
        <span class="text-muted">Plán</span><strong class="mono"><?= format_money($totalPlannedShown) ?></strong>
      </div>
      <div class="progress <?= $pct > 100 ? 'over' : '' ?>"><div style="width:<?= min(100, $pct) ?>%"></div></div>
      <a class="btn secondary sm" style="margin-top:12px;" href="/index.php?p=rozpocty<?= $view === 'month' ? '&m=' . $period : '' ?>">Podrobnosti rozpočtů →</a>
    <?php endif; ?>
  </div>
</div>

<script>
var pal = window.CHART_PALETTE;

drawDonutChart(document.getElementById('chart-income-expense'), [
  { label: 'Příjmy', value: <?= (float) $summary['income'] ?>, color: getComputedStyle(document.documentElement).getPropertyValue('--income') || '#16a34a', valueLabel: <?= json_encode(format_money($summary['income'])) ?>, href: '/index.php?p=polozky&<?= $listFilterParam ?>&type=prijem' },
  { label: 'Výdaje', value: <?= (float) $summary['expense'] ?>, color: getComputedStyle(document.documentElement).getPropertyValue('--expense') || '#dc2626', valueLabel: <?= json_encode(format_money($summary['expense'])) ?>, href: '/index.php?p=polozky&<?= $listFilterParam ?>&type=vydaj' }
], { centerLabel: <?= json_encode(format_money($summary['remaining'], 0)) ?>, centerSubLabel: 'zbývá' });

drawDonutChart(document.getElementById('chart-regular-onetime'), [
  { label: 'Pravidelné', value: <?= (float) $summary['regular'] ?>, color: pal[0], valueLabel: <?= json_encode(format_money($summary['regular'])) ?>, href: '/index.php?p=polozky&<?= $listFilterParam ?>&is_recurring=1' },
  { label: 'Jednorázové', value: <?= (float) $summary['onetime'] ?>, color: pal[1], valueLabel: <?= json_encode(format_money($summary['onetime'])) ?>, href: '/index.php?p=polozky&<?= $listFilterParam ?>&is_recurring=0' }
]);

drawDonutChart(document.getElementById('chart-methods'), <?= json_encode($methodChartData) ?>);

<?php if ($showBusiness): ?>
drawDonutChart(document.getElementById('chart-business'), [
  { label: 'Soukromé', value: <?= (float) $bizBreakdown['personal'] ?>, color: pal[0], valueLabel: <?= json_encode(format_money($bizBreakdown['personal'])) ?>, href: '/index.php?p=polozky&<?= $listFilterParam ?>&is_business=0' },
  { label: 'Podnikatelské', value: <?= (float) $bizBreakdown['business'] ?>, color: pal[1], valueLabel: <?= json_encode(format_money($bizBreakdown['business'])) ?>, href: '/index.php?p=polozky&<?= $listFilterParam ?>&is_business=1' }
]);
<?php endif; ?>

drawBarChart(document.getElementById('chart-categories'), <?= json_encode($catChartData) ?>, { height: 240 });

drawLineChart(document.getElementById('chart-trend'), [
  {
    name: 'Příjmy', color: getComputedStyle(document.documentElement).getPropertyValue('--income') || '#16a34a',
    points: <?= json_encode(array_map(fn ($t) => [
        'x' => $t['month_year'], 'y' => $t['income'],
        'label' => month_year_label($t['month_year']),
        'valueLabel' => format_money($t['income']),
        'href' => '/index.php?p=prehled&m=' . $t['month_year'],
    ], $trend)) ?>
  },
  {
    name: 'Výdaje', color: getComputedStyle(document.documentElement).getPropertyValue('--expense') || '#dc2626',
    points: <?= json_encode(array_map(fn ($t) => [
        'x' => $t['month_year'], 'y' => $t['expense'],
        'label' => month_year_label($t['month_year']),
        'valueLabel' => format_money($t['expense']),
        'href' => '/index.php?p=prehled&m=' . $t['month_year'],
    ], $trend)) ?>
  }
], { height: 240 });
</script>
</div>
