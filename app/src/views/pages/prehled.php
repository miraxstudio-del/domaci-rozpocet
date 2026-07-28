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

  <section class="dashboard-primary-stats" aria-label="Hlavní souhrn">
    <article class="dashboard-stat-card dashboard-stat-card--primary">
      <div class="dashboard-stat-icon dashboard-stat-icon--income">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 14v2a4 4 0 0 0 4 4h6a4 4 0 0 0 4-4v-2"/></svg>
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
      <div class="dashboard-stat-icon dashboard-stat-icon--expense">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21V9m0 0-4 4m4-4 4 4M5 10V8a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/></svg>
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
      <div class="dashboard-stat-icon dashboard-stat-icon--balance">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 15 4-4 4 4 6-7m0 0v5m0-5h-5"/></svg>
      </div>
      <div class="dashboard-stat-copy">
        <div class="dashboard-stat-label">Zbývá z příjmů</div>
        <div class="dashboard-stat-value <?= $s['remaining'] >= 0 ? 'income' : 'expense' ?>"><?= format_money($s['remaining']) ?></div>
        <div class="dashboard-stat-delta neutral">Příjmy mínus výdaje za <?= $view === 'year' ? 'rok' : 'měsíc' ?></div>
      </div>
    </article>
  </section>

  <section class="dashboard-secondary-stats" aria-label="Doplňující souhrn">
    <article class="dashboard-stat-card dashboard-stat-card--secondary">
      <div class="dashboard-stat-icon dashboard-stat-icon--blue">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
      </div>
      <div class="dashboard-stat-copy"><div class="dashboard-stat-label">Pravidelné výdaje</div><div class="dashboard-stat-value"><?= format_money($s['regular']) ?></div></div>
    </article>
    <article class="dashboard-stat-card dashboard-stat-card--secondary">
      <div class="dashboard-stat-icon dashboard-stat-icon--blue">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4m8-4v4M3 10h18"/></svg>
      </div>
      <div class="dashboard-stat-copy"><div class="dashboard-stat-label">Jednorázové výdaje</div><div class="dashboard-stat-value"><?= format_money($s['onetime']) ?></div></div>
    </article>
    <article class="dashboard-stat-card dashboard-stat-card--secondary">
      <div class="dashboard-stat-icon dashboard-stat-icon--warning">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10M7 21h10M8 3c0 5 3 5 3 9s-3 4-3 9m8-18c0 5-3 5-3 9s3 4 3 9M8 12h8"/></svg>
      </div>
      <div class="dashboard-stat-copy">
        <div class="dashboard-stat-label">Nezaplacené platby</div>
        <div class="dashboard-stat-value warning"><?= format_money($s['unpaid']) ?></div>
        <?php if ($s['overdue_count'] > 0): ?><div class="dashboard-stat-delta negative"><?= $s['overdue_count'] ?>&nbsp;po splatnosti</div><?php endif; ?>
      </div>
    </article>
    <article class="dashboard-stat-card dashboard-stat-card--secondary">
      <div class="dashboard-stat-icon dashboard-stat-icon--purple">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5Z"/><path d="M14 2v5h5M9 13h6m-6 4h6"/></svg>
      </div>
      <div class="dashboard-stat-copy">
        <div class="dashboard-stat-label">Uložené doklady</div>
        <div class="dashboard-stat-value"><?= $s['attachments_count'] ?></div>
        <div class="dashboard-stat-delta neutral"><a href="/index.php?p=doklady">zobrazit doklady <span aria-hidden="true">→</span></a></div>
      </div>
    </article>
  </section>

  <section class="dashboard-insights" aria-label="Důležité informace">
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
  <section class="dashboard-category-card" aria-labelledby="dashboard-category-title">
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
