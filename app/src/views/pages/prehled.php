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
?>
<div class="topbar">
  <div>
    <h1>Přehled</h1>
    <div class="subtitle">Rychlý souhrn hospodaření domácnosti</div>
  </div>
  <div class="month-switch">
    <a class="btn outline icon-only" href="/index.php?<?= h(http_build_query($view === 'year' ? ['p' => 'prehled', 'view' => 'year', 'y' => $prevPeriod] : ['p' => 'prehled', 'm' => $prevPeriod])) ?>" title="Předchozí">‹</a>
    <div class="current"><?= h(period_label($period)) ?><?= $closed ? ' 🔒' : '' ?></div>
    <a class="btn outline icon-only" href="/index.php?<?= h(http_build_query($view === 'year' ? ['p' => 'prehled', 'view' => 'year', 'y' => $nextPeriod] : ['p' => 'prehled', 'm' => $nextPeriod])) ?>" title="Další">›</a>
    <a class="btn secondary sm" href="/index.php?<?= h(http_build_query(array_merge(['p' => 'prehled'], $todayLinkParams))) ?>"><?= $view === 'year' ? 'Letošní rok' : 'Dnes' ?></a>
  </div>
  <div class="btn-row">
    <div class="pill-nav" style="margin:0;">
      <a class="<?= $view === 'month' ? 'active' : '' ?>" href="/index.php?p=prehled&m=<?= h(current_month_year()) ?>">Měsíc</a>
      <a class="<?= $view === 'year' ? 'active' : '' ?>" href="/index.php?p=prehled&view=year&y=<?= h(date('Y')) ?>">Rok</a>
    </div>
    <a class="btn" href="/index.php?p=polozka&type=vydaj&m=<?= h($addItemMonth) ?>">➕ Přidat položku</a>
  </div>
</div>

<div class="grid grid-cols-3">
  <div class="stat-tile">
    <div class="label">💰 Celkové příjmy</div>
    <div class="value income"><?= format_money($s['income']) ?></div>
    <?php if ($s['income_delta_pct'] !== null): ?>
      <div class="delta <?= $s['income_delta_pct'] >= 0 ? 'down' : 'up' ?>">
        <?= $s['income_delta_pct'] >= 0 ? '▲' : '▼' ?> <?= number_format(abs($s['income_delta_pct']), 1, ',', ' ') ?>&nbsp;% oproti <?= h(period_label($s['prev_month'])) ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="stat-tile">
    <div class="label">🧾 Celkové výdaje</div>
    <div class="value expense"><?= format_money($s['expense']) ?></div>
    <?php if ($s['expense_delta_pct'] !== null): ?>
      <div class="delta <?= $s['expense_delta_pct'] >= 0 ? 'up' : 'down' ?>">
        <?= $s['expense_delta_pct'] >= 0 ? '▲' : '▼' ?> <?= number_format(abs($s['expense_delta_pct']), 1, ',', ' ') ?>&nbsp;% oproti <?= h(period_label($s['prev_month'])) ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="stat-tile">
    <div class="label">🏁 Zbývá z příjmů</div>
    <div class="value" style="color: <?= $s['remaining'] >= 0 ? 'var(--income)' : 'var(--expense)' ?>"><?= format_money($s['remaining']) ?></div>
    <div class="delta">Příjmy mínus výdaje za <?= $view === 'year' ? 'rok' : 'měsíc' ?></div>
  </div>
</div>

<div class="grid grid-cols-4" style="margin-top:16px;">
  <div class="stat-tile">
    <div class="label">🔁 Pravidelné výdaje</div>
    <div class="value"><?= format_money($s['regular']) ?></div>
  </div>
  <div class="stat-tile">
    <div class="label">🛍️ Jednorázové výdaje</div>
    <div class="value"><?= format_money($s['onetime']) ?></div>
  </div>
  <div class="stat-tile">
    <div class="label">⏳ Nezaplacené platby</div>
    <div class="value" style="color:var(--warning)"><?= format_money($s['unpaid']) ?></div>
    <?php if ($s['overdue_count'] > 0): ?>
      <div class="delta up"><?= $s['overdue_count'] ?>&nbsp;po splatnosti</div>
    <?php endif; ?>
  </div>
  <div class="stat-tile">
    <div class="label">🧾 Uložené doklady</div>
    <div class="value"><?= $s['attachments_count'] ?></div>
    <div class="delta"><a href="/index.php?p=doklady">zobrazit doklady →</a></div>
  </div>
</div>

<div class="grid grid-cols-3" style="margin-top:16px;">
  <div class="card">
    <h3>💥 Největší výdaj <?= $view === 'year' ? 'roku' : 'měsíce' ?></h3>
    <?php if ($s['biggest_expense']): $b = $s['biggest_expense']; ?>
      <div style="font-size:19px;font-weight:700;"><?= format_money((float) $b['amount']) ?></div>
      <div class="text-muted"><?= h($b['name']) ?> · <?= format_date_cz($b['payment_date']) ?></div>
      <a class="btn secondary sm" style="margin-top:10px;" href="/index.php?p=polozka&id=<?= (int) $b['id'] ?>">Zobrazit položku</a>
    <?php else: ?>
      <p class="text-muted">Zatím žádný výdaj.</p>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>🏆 Nejpoužívanější kategorie</h3>
    <?php if ($s['top_category']): $tc = $s['top_category']; $cat = get_category_by_id((int) $tc['category_id']); ?>
      <div style="font-size:19px;font-weight:700;"><?= $cat ? h($cat['icon'] . ' ' . $cat['name']) : 'Nezařazeno' ?></div>
      <div class="text-muted"><?= format_money((float) $tc['total']) ?> · <?= (int) $tc['cnt'] ?>&nbsp;položek</div>
      <a class="btn secondary sm" style="margin-top:10px;" href="/index.php?p=statistiky&<?= $view === 'year' ? 'view=year&y=' . h($period) : 'm=' . h($period) ?>">Zobrazit statistiky</a>
    <?php else: ?>
      <p class="text-muted">Zatím žádná data.</p>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>📌 Blížící se splatnosti</h3>
    <?php if ($due): ?>
      <?php foreach (array_slice($due, 0, 4) as $d): ?>
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--card-border);">
          <div>
            <a href="/index.php?p=polozka&id=<?= (int) $d['id'] ?>"><?= h($d['name']) ?></a>
            <div class="text-faint" style="font-size:12px;">splatnost <?= format_date_short_cz($d['due_date']) ?></div>
          </div>
          <div class="amount-out mono"><?= format_money((float) $d['amount']) ?></div>
        </div>
      <?php endforeach; ?>
      <a class="btn secondary sm" style="margin-top:10px;" href="/index.php?p=polozky&status=ceka">Zobrazit vše</a>
    <?php else: ?>
      <p class="text-muted">Žádné blížící se splatnosti v příštích 7 dnech. 🎉</p>
    <?php endif; ?>
  </div>
</div>

<?php $cats = array_slice(category_breakdown($period, 'vydaj'), 0, 8); ?>
<div class="card" style="margin-top:16px;">
  <div class="card-title-row">
    <h3>Výdaje podle kategorií</h3>
    <a href="/index.php?p=statistiky&<?= $view === 'year' ? 'view=year&y=' . h($period) : 'm=' . h($period) ?>" class="btn secondary sm">Podrobné statistiky →</a>
  </div>
  <?php if (!$cats): ?>
    <p class="text-muted">Zatím nejsou zaznamenané žádné výdaje.</p>
  <?php else: ?>
    <?php $max = max(array_column($cats, 'total')) ?: 1; ?>
    <?php foreach ($cats as $c): ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div style="width:150px;flex-shrink:0;font-size:13.5px;"><?= h(($c['cat_icon'] ?: '📦') . ' ' . ($c['cat_name'] ?: 'Nezařazeno')) ?></div>
        <div class="progress" style="flex:1;">
          <div style="width:<?= round(($c['total'] / $max) * 100) ?>%; background:<?= h($c['cat_color'] ?: '#6b7280') ?>;"></div>
        </div>
        <div class="mono" style="width:110px;text-align:right;font-weight:600;font-size:13.5px;"><?= format_money((float) $c['total']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
