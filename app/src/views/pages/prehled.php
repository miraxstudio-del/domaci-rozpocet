<?php
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

$pageTitle = 'Přehled';
$s = month_summary($period);
$closed = $view === 'month' && is_month_closed($period);
$due = upcoming_due(7);
$prevPeriod = shift_period($period, -1);
$nextPeriod = shift_period($period, 1);
$todayLinkParams = $view === 'year' ? ['view' => 'year', 'y' => date('Y')] : ['m' => current_month_year()];
$addItemMonth = $view === 'year' ? current_month_year() : $period;
$periodQuery = static fn (string $value): string => http_build_query(
    $view === 'year'
        ? ['p' => 'prehled', 'view' => 'year', 'y' => $value]
        : ['p' => 'prehled', 'm' => $value]
);

$dashboardTrend = [];
if ($view === 'year') {
    for ($month = 1; $month <= 12; $month++) {
        $monthYear = sprintf('%s-%02d', $period, $month);
        $monthSummary = month_summary($monthYear);
        $dashboardTrend[] = [
            'month_year' => $monthYear,
            'label' => month_year_label($monthYear),
            'axis_label' => sprintf('%02d', $month),
            'income' => (float) $monthSummary['income'],
            'expense' => (float) $monthSummary['expense'],
        ];
    }
} else {
    for ($offset = 5; $offset >= 0; $offset--) {
        $monthYear = shift_month($period, -$offset);
        $monthSummary = month_summary($monthYear);
        $dashboardTrend[] = [
            'month_year' => $monthYear,
            'label' => month_year_label($monthYear),
            'axis_label' => substr($monthYear, 5, 2) . '/' . substr($monthYear, 0, 4),
            'income' => (float) $monthSummary['income'],
            'expense' => (float) $monthSummary['expense'],
        ];
    }
}

$dashboardTrendSeries = [
    'income' => array_map(static fn (array $item): array => [
        'x' => $item['month_year'],
        'y' => $item['income'],
        'label' => $item['label'],
        'axisLabel' => $item['axis_label'],
        'valueLabel' => format_money($item['income']),
        'href' => '/index.php?p=prehled&m=' . $item['month_year'],
    ], $dashboardTrend),
    'expense' => array_map(static fn (array $item): array => [
        'x' => $item['month_year'],
        'y' => $item['expense'],
        'label' => $item['label'],
        'axisLabel' => $item['axis_label'],
        'valueLabel' => format_money($item['expense']),
        'href' => '/index.php?p=prehled&m=' . $item['month_year'],
    ], $dashboardTrend),
];
?>
<div class="dashboard">
  <div class="topbar dashboard-topbar">
    <div class="dashboard-heading">
      <h1>Přehled</h1>
      <div class="subtitle">Rychlý souhrn hospodaření domácnosti</div>
    </div>

    <div class="month-switch dashboard-month-switch" aria-label="Výběr období">
      <a class="dashboard-icon-button" href="/index.php?<?= h($periodQuery($prevPeriod)) ?>" title="Předchozí období" aria-label="Předchozí období">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </a>
      <div class="current"><?= h(period_label($period)) ?><?= $closed ? ' <span class="closed-mark" title="Měsíc je uzavřen">🔒</span>' : '' ?></div>
      <a class="dashboard-icon-button" href="/index.php?<?= h($periodQuery($nextPeriod)) ?>" title="Další období" aria-label="Další období">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </a>
      <a class="dashboard-today" href="/index.php?<?= h(http_build_query(array_merge(['p' => 'prehled'], $todayLinkParams))) ?>">
        <?= $view === 'year' ? 'Letošní rok' : 'Dnes' ?>
      </a>
    </div>

    <div class="dashboard-actions">
      <div class="dashboard-period-tabs" aria-label="Typ přehledu">
        <a class="<?= $view === 'month' ? 'active' : '' ?>" href="/index.php?p=prehled&amp;m=<?= h(current_month_year()) ?>">Měsíc</a>
        <a class="<?= $view === 'year' ? 'active' : '' ?>" href="/index.php?p=prehled&amp;view=year&amp;y=<?= h(date('Y')) ?>">Rok</a>
      </div>
      <a class="dashboard-add-button" href="/index.php?p=polozka&amp;type=vydaj&amp;m=<?= h($addItemMonth) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Přidat položku
      </a>
    </div>
  </div>

  <div class="dashboard-customize-row">
    <details class="dashboard-customizer">
      <summary>
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.12 2.12-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.04 1.56V20.3h-3v-.08A1.7 1.7 0 0 0 10.66 18.66a1.7 1.7 0 0 0-1.88.34l-.06.06-2.12-2.12.06-.06A1.7 1.7 0 0 0 7 15a1.7 1.7 0 0 0-1.56-1.04h-.08v-3h.08A1.7 1.7 0 0 0 7 9.92a1.7 1.7 0 0 0-.34-1.88L6.6 7.98l2.12-2.12.06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1.04-1.56v-.08h3v.08a1.7 1.7 0 0 0 1.04 1.56 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.12 2.12-.06.06A1.7 1.7 0 0 0 19.4 9.92a1.7 1.7 0 0 0 1.56 1.04h.08v3h-.08A1.7 1.7 0 0 0 19.4 15Z"/></svg>
        Upravit přehled
      </summary>
      <div class="dashboard-customizer-panel">
        <div class="dashboard-customizer-heading">
          <strong>Co chcete v Přehledu vidět?</strong>
          <p>Hlavní souhrn zůstává vždy zobrazený.</p>
        </div>
        <div class="dashboard-widget-options">
          <label><input type="checkbox" data-dashboard-toggle="secondary" checked><span>Doplňující metriky</span></label>
          <label><input type="checkbox" data-dashboard-toggle="trend" checked><span>Graf příjmů a výdajů</span></label>
          <label><input type="checkbox" data-dashboard-toggle="insights" checked><span>Důležité informace</span></label>
          <label><input type="checkbox" data-dashboard-toggle="categories" checked><span>Výdaje podle kategorií</span></label>
        </div>
        <button class="dashboard-reset-layout" type="button" data-dashboard-reset>Obnovit výchozí</button>
      </div>
    </details>
  </div>

  <section class="dashboard-primary-stats" aria-label="Hlavní souhrn">
    <article class="dashboard-stat-card dashboard-stat-card--primary">
      <div class="dashboard-stat-icon dashboard-stat-icon--income dashboard-stat-icon--illustration">
        <img src="/assets/images/income-wallet.png" alt="" width="256" height="256">
      </div>
      <div class="dashboard-stat-copy">
        <div class="dashboard-stat-label">Celkové příjmy</div>
        <div class="dashboard-stat-value income"><?= format_money($s['income']) ?></div>
        <?php if ($s['income_delta_pct'] !== null): ?>
          <div class="dashboard-stat-delta <?= $s['income_delta_pct'] >= 0 ? 'positive' : 'negative' ?>">
            <?= $s['income_delta_pct'] >= 0 ? '▲' : '▼' ?> <?= number_format(abs($s['income_delta_pct']), 1, ',', ' ') ?>&nbsp;% oproti <?= h(period_label($s['prev_month'])) ?>
          </div>
        <?php endif; ?>
      </div>
    </article>

    <article class="dashboard-stat-card dashboard-stat-card--primary">
      <div class="dashboard-stat-icon dashboard-stat-icon--expense dashboard-stat-icon--illustration">
        <img src="/assets/images/expense-receipt.png" alt="" width="256" height="256">
      </div>
      <div class="dashboard-stat-copy">
        <div class="dashboard-stat-label">Celkové výdaje</div>
        <div class="dashboard-stat-value expense"><?= format_money($s['expense']) ?></div>
        <?php if ($s['expense_delta_pct'] !== null): ?>
          <div class="dashboard-stat-delta <?= $s['expense_delta_pct'] >= 0 ? 'negative' : 'positive' ?>">
            <?= $s['expense_delta_pct'] >= 0 ? '▲' : '▼' ?> <?= number_format(abs($s['expense_delta_pct']), 1, ',', ' ') ?>&nbsp;% oproti <?= h(period_label($s['prev_month'])) ?>
          </div>
        <?php endif; ?>
      </div>
    </article>

    <article class="dashboard-stat-card dashboard-stat-card--primary">
      <div class="dashboard-stat-icon dashboard-stat-icon--balance dashboard-stat-icon--illustration">
        <img src="/assets/images/balance-chart.png" alt="" width="256" height="256">
      </div>
      <div class="dashboard-stat-copy">
        <div class="dashboard-stat-label">Zbývá z příjmů</div>
        <div class="dashboard-stat-value <?= $s['remaining'] >= 0 ? 'income' : 'expense' ?>"><?= format_money($s['remaining']) ?></div>
        <div class="dashboard-stat-delta neutral">Příjmy mínus výdaje za <?= $view === 'year' ? 'rok' : 'měsíc' ?></div>
      </div>
    </article>
  </section>

  <section class="dashboard-trend-card" data-dashboard-widget="trend" aria-labelledby="dashboard-trend-title">
    <div class="dashboard-trend-heading">
      <div>
        <h2 id="dashboard-trend-title">Vývoj příjmů a výdajů</h2>
        <p><?= $view === 'year' ? 'Jednotlivé měsíce vybraného roku' : 'Posledních šest měsíců do zvoleného období' ?></p>
      </div>
      <div class="dashboard-trend-legend" aria-label="Legenda grafu">
        <span class="income"><i></i>Příjmy</span>
        <span class="expense"><i></i>Výdaje</span>
      </div>
    </div>
    <canvas class="chart dashboard-trend-chart" id="dashboard-trend-chart"></canvas>
    <p class="dashboard-trend-hint">Kliknutím na bod grafu otevřete daný měsíc.</p>
  </section>

  <section class="dashboard-secondary-stats" data-dashboard-widget="secondary" aria-label="Doplňující souhrn">
    <article class="dashboard-stat-card dashboard-stat-card--secondary">
      <div class="dashboard-stat-icon dashboard-stat-icon--blue dashboard-stat-icon--illustration">
        <img src="/assets/images/recurring-calendar.png" alt="" width="256" height="256">
      </div>
      <div class="dashboard-stat-copy"><div class="dashboard-stat-label">Pravidelné výdaje</div><div class="dashboard-stat-value"><?= format_money($s['regular']) ?></div></div>
    </article>
    <article class="dashboard-stat-card dashboard-stat-card--secondary">
      <div class="dashboard-stat-icon dashboard-stat-icon--blue dashboard-stat-icon--illustration">
        <img src="/assets/images/onetime-calendar.png" alt="" width="256" height="256">
      </div>
      <div class="dashboard-stat-copy"><div class="dashboard-stat-label">Jednorázové výdaje</div><div class="dashboard-stat-value"><?= format_money($s['onetime']) ?></div></div>
    </article>
    <article class="dashboard-stat-card dashboard-stat-card--secondary">
      <div class="dashboard-stat-icon dashboard-stat-icon--warning dashboard-stat-icon--illustration">
        <img src="/assets/images/unpaid-hourglass.png" alt="" width="256" height="256">
      </div>
      <div class="dashboard-stat-copy">
        <div class="dashboard-stat-label">Nezaplacené platby</div>
        <div class="dashboard-stat-value warning"><?= format_money($s['unpaid']) ?></div>
        <?php if ($s['overdue_count'] > 0): ?><div class="dashboard-stat-delta negative"><?= $s['overdue_count'] ?>&nbsp;po splatnosti</div><?php endif; ?>
      </div>
    </article>
    <article class="dashboard-stat-card dashboard-stat-card--secondary">
      <div class="dashboard-stat-icon dashboard-stat-icon--purple dashboard-stat-icon--illustration">
        <img src="/assets/images/documents-folder.png" alt="" width="256" height="256">
      </div>
      <div class="dashboard-stat-copy">
        <div class="dashboard-stat-label">Uložené doklady</div>
        <div class="dashboard-stat-value"><?= $s['attachments_count'] ?></div>
        <div class="dashboard-stat-delta neutral"><a href="/index.php?p=doklady">zobrazit doklady <span aria-hidden="true">→</span></a></div>
      </div>
    </article>
  </section>

  <section class="dashboard-insights" data-dashboard-widget="insights" aria-label="Důležité informace">
    <article class="dashboard-insight-card">
      <h2><span class="dashboard-insight-icon danger">✹</span>Největší výdaj <?= $view === 'year' ? 'roku' : 'měsíce' ?></h2>
      <?php if ($s['biggest_expense']): $b = $s['biggest_expense']; ?>
        <div class="dashboard-insight-value"><?= format_money((float) $b['amount']) ?></div>
        <p><?= h($b['name']) ?> · <?= format_date_cz($b['payment_date']) ?></p>
        <a class="dashboard-text-link" href="/index.php?p=polozka&amp;id=<?= (int) $b['id'] ?>">Zobrazit položku <span aria-hidden="true">→</span></a>
      <?php else: ?>
        <p>Zatím žádný výdaj.</p>
      <?php endif; ?>
    </article>
    <article class="dashboard-insight-card">
      <h2><span class="dashboard-insight-icon trophy">🏆</span>Nejpoužívanější kategorie</h2>
      <?php if ($s['top_category']): $tc = $s['top_category']; $cat = get_category_by_id((int) $tc['category_id']); ?>
        <div class="dashboard-insight-value"><?= $cat ? h($cat['icon'] . ' ' . $cat['name']) : 'Nezařazeno' ?></div>
        <p><?= format_money((float) $tc['total']) ?> · <?= (int) $tc['cnt'] ?>&nbsp;položek</p>
        <a class="dashboard-text-link" href="/index.php?p=statistiky&amp;<?= $view === 'year' ? 'view=year&amp;y=' . h($period) : 'm=' . h($period) ?>">Zobrazit statistiky <span aria-hidden="true">→</span></a>
      <?php else: ?>
        <p>Zatím žádná data.</p>
      <?php endif; ?>
    </article>
    <article class="dashboard-insight-card">
      <h2><span class="dashboard-insight-icon pin">📌</span>Blížící se splatnosti</h2>
      <?php if ($due): ?>
        <div class="dashboard-due-list">
          <?php foreach (array_slice($due, 0, 4) as $d): ?>
            <a href="/index.php?p=polozka&amp;id=<?= (int) $d['id'] ?>">
              <span><strong><?= h($d['name']) ?></strong><small>splatnost <?= format_date_short_cz($d['due_date']) ?></small></span>
              <b><?= format_money((float) $d['amount']) ?></b>
            </a>
          <?php endforeach; ?>
        </div>
        <a class="dashboard-text-link" href="/index.php?p=polozky&amp;status=ceka">Zobrazit vše <span aria-hidden="true">→</span></a>
      <?php else: ?>
        <p>Žádné blížící se splatnosti v příštích 7 dnech. 🎉</p>
      <?php endif; ?>
    </article>
  </section>

  <?php $cats = array_slice(category_breakdown($period, 'vydaj'), 0, 8); ?>
  <section class="dashboard-category-card" data-dashboard-widget="categories" aria-labelledby="dashboard-category-title">
    <div class="dashboard-category-heading">
      <h2 id="dashboard-category-title">Výdaje podle kategorií</h2>
      <a class="dashboard-detail-button" href="/index.php?p=statistiky&amp;<?= $view === 'year' ? 'view=year&amp;y=' . h($period) : 'm=' . h($period) ?>">Podrobné statistiky <span aria-hidden="true">→</span></a>
    </div>
    <?php if (!$cats): ?>
      <p class="dashboard-empty-copy">Zatím nejsou zaznamenané žádné výdaje.</p>
    <?php else: ?>
      <div class="dashboard-category-list">
        <?php $max = max(array_column($cats, 'total')) ?: 1; ?>
        <?php foreach ($cats as $c): ?>
          <div class="dashboard-category-row">
            <div class="dashboard-category-name"><?= h(($c['cat_icon'] ?: '📦') . ' ' . ($c['cat_name'] ?: 'Nezařazeno')) ?></div>
            <div class="progress"><div style="width:<?= round(($c['total'] / $max) * 100) ?>%; background:<?= h($c['cat_color'] ?: '#6b7280') ?>;"></div></div>
            <div class="dashboard-category-total"><?= format_money((float) $c['total']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<script>
(function () {
  var canvas = document.getElementById('dashboard-trend-chart');
  var series = <?= json_encode($dashboardTrendSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  function renderTrend() {
    if (!canvas || !window.drawLineChart || canvas.closest('[hidden]')) return;
    window.drawLineChart(canvas, [
      {
        name: 'Příjmy',
        color: getComputedStyle(document.documentElement).getPropertyValue('--income').trim() || '#16a34a',
        points: series.income
      },
      {
        name: 'Výdaje',
        color: getComputedStyle(document.documentElement).getPropertyValue('--expense').trim() || '#dc2626',
        points: series.expense
      }
    ], { height: 248 });
  }

  window.addEventListener('DOMContentLoaded', renderTrend);
  window.addEventListener('dashboardwidgetchange', function (event) {
    if (!event.detail || event.detail.key === 'trend') renderTrend();
  });
})();
</script>
