<?php
$pageTitle = 'Rozpočty';
$activeNav = 'rozpocty';

$monthYear = $_GET['m'] ?? current_month_year();
if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    $monthYear = current_month_year();
}
$prevM = shift_month($monthYear, -1);
$nextM = shift_month($monthYear, 1);

$monthRow = get_month_row($monthYear) ?? ['planned_income' => null, 'min_remaining' => null, 'reserve_amount' => null];
$budgets = get_budgets($monthYear);
$summary = month_summary($monthYear);
$actualByCategory = category_actual_spend($monthYear, 'vydaj');

// Sestavíme seznam kategorií s efektivním rozpočtem (přednost má měsíční override, jinak výchozí limit kategorie)
$allExpenseCats = get_categories('vydaj', true);
$rows = [];
foreach ($allExpenseCats as $cat) {
    $hasOverride = array_key_exists((int) $cat['id'], $budgets['categories']);
    $planned = $hasOverride ? $budgets['categories'][$cat['id']] : ($cat['monthly_limit'] !== null ? (float) $cat['monthly_limit'] : null);
    if ($planned === null) {
        continue;
    }
    $rows[] = [
        'cat' => $cat,
        'planned' => $planned,
        'has_override' => $hasOverride,
        'actual' => $actualByCategory[(int) $cat['id']] ?? 0.0,
    ];
}
usort($rows, fn ($a, $b) => $b['actual'] <=> $a['actual']);

$categoriesWithoutBudget = array_filter($allExpenseCats, function ($c) use ($budgets) {
    return !array_key_exists((int) $c['id'], $budgets['categories']) && $c['monthly_limit'] === null;
});

$totalBudget = $budgets['total'];
$totalPct = $totalBudget ? min(999, ($summary['expense'] / $totalBudget) * 100) : null;
?>
<div class="topbar">
  <div>
    <h1>🎯 Rozpočty</h1>
    <div class="subtitle">Plánování a čerpání rozpočtu domácnosti</div>
  </div>
  <div class="month-switch">
    <a class="btn outline icon-only" href="/index.php?p=rozpocty&m=<?= h($prevM) ?>">‹</a>
    <div class="current"><?= h(month_year_label($monthYear)) ?></div>
    <a class="btn outline icon-only" href="/index.php?p=rozpocty&m=<?= h($nextM) ?>">›</a>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr;">
  <div class="card">
    <h3>Měsíční plán</h3>
    <?php if ($totalBudget): ?>
      <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:6px;">
          <span>Čerpání celkového rozpočtu</span>
          <strong><?= format_money($summary['expense']) ?> / <?= format_money($totalBudget) ?></strong>
        </div>
        <div class="progress <?= $totalPct > 100 ? 'over' : '' ?>"><div style="width:<?= min(100, $totalPct) ?>%"></div></div>
      </div>
    <?php endif; ?>
    <form method="post" action="/actions/month_plan_save.php">
      <?= csrf_field() ?>
      <input type="hidden" name="month_year" value="<?= h($monthYear) ?>">
      <div class="form-grid" style="grid-template-columns:1fr 1fr;">
        <div class="field">
          <label>Celkový měsíční rozpočet (Kč)</label>
          <input type="number" step="1" name="total_budget" value="<?= h((string) ($totalBudget ?? '')) ?>">
        </div>
        <div class="field">
          <label>Plánované příjmy (Kč)</label>
          <input type="number" step="1" name="planned_income" value="<?= h((string) ($monthRow['planned_income'] ?? '')) ?>">
        </div>
        <div class="field">
          <label>Min. zůstatek na konci měsíce (Kč)</label>
          <input type="number" step="1" name="min_remaining" value="<?= h((string) ($monthRow['min_remaining'] ?? '')) ?>">
        </div>
        <div class="field">
          <label>Finanční rezerva (Kč)</label>
          <input type="number" step="1" name="reserve_amount" value="<?= h((string) ($monthRow['reserve_amount'] ?? '')) ?>">
        </div>
      </div>
      <div class="btn-row" style="margin-top:14px;"><button class="btn" type="submit">💾 Uložit plán</button></div>
    </form>
  </div>

  <div class="card">
    <h3>Shrnutí měsíce</h3>
    <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;">
      <div style="display:flex;justify-content:space-between;"><span class="text-muted">Skutečné příjmy</span><strong class="amount-in"><?= format_money($summary['income']) ?></strong></div>
      <div style="display:flex;justify-content:space-between;"><span class="text-muted">Plánované příjmy</span><strong><?= $monthRow['planned_income'] !== null ? format_money((float) $monthRow['planned_income']) : '—' ?></strong></div>
      <hr class="divider" style="margin:4px 0;">
      <div style="display:flex;justify-content:space-between;"><span class="text-muted">Skutečné výdaje</span><strong class="amount-out"><?= format_money($summary['expense']) ?></strong></div>
      <div style="display:flex;justify-content:space-between;"><span class="text-muted">Celkový rozpočet</span><strong><?= $totalBudget !== null ? format_money($totalBudget) : '—' ?></strong></div>
      <hr class="divider" style="margin:4px 0;">
      <div style="display:flex;justify-content:space-between;"><span class="text-muted">Zbývá reálně</span><strong style="color:<?= $summary['remaining'] >= 0 ? 'var(--income)' : 'var(--expense)' ?>"><?= format_money($summary['remaining']) ?></strong></div>
      <?php if ($monthRow['min_remaining'] !== null): ?>
        <div style="display:flex;justify-content:space-between;">
          <span class="text-muted">Vůči min. zůstatku</span>
          <strong style="color:<?= $summary['remaining'] >= (float) $monthRow['min_remaining'] ? 'var(--income)' : 'var(--warning)' ?>">
            <?= $summary['remaining'] >= (float) $monthRow['min_remaining'] ? 'Splněno ✅' : 'Pod cílem ⚠️' ?>
          </strong>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card" style="margin-top:16px;">
  <h3>Rozpočty jednotlivých kategorií</h3>
  <?php if (!$rows): ?>
    <p class="text-muted">Zatím nemáte nastavené žádné limity kategorií. Přidejte je níže nebo v sekci Kategorie.</p>
  <?php else: ?>
    <?php foreach ($rows as $r): $pct = $r['planned'] > 0 ? ($r['actual'] / $r['planned']) * 100 : 0; ?>
      <div style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:6px;align-items:center;">
          <span><?= h(($r['cat']['icon'] ?: '📦') . ' ' . $r['cat']['name']) ?> <?= $r['has_override'] ? '<span class="badge">upraveno pro měsíc</span>' : '' ?></span>
          <span>
            <strong class="mono"><?= format_money($r['actual']) ?></strong> / <?= format_money($r['planned']) ?>
            <?php if ($pct > 100): ?><span class="badge warning">překročeno</span><?php endif; ?>
          </span>
        </div>
        <div class="progress <?= $pct > 100 ? 'over' : '' ?>"><div style="width:<?= min(100, $pct) ?>%"></div></div>
        <details style="margin-top:4px;">
          <summary class="text-faint" style="cursor:pointer;font-size:12px;">upravit rozpočet</summary>
          <div class="btn-row" style="margin-top:6px;">
            <form method="post" action="/actions/budget_save.php" style="display:flex;gap:6px;">
              <?= csrf_field() ?>
              <input type="hidden" name="month_year" value="<?= h($monthYear) ?>">
              <input type="hidden" name="category_id" value="<?= (int) $r['cat']['id'] ?>">
              <input type="number" step="1" name="planned_amount" value="<?= h((string) $r['planned']) ?>" style="width:120px;">
              <button class="btn secondary sm" type="submit">Uložit</button>
            </form>
            <?php if ($r['has_override']): ?>
              <form method="post" action="/actions/budget_delete.php">
                <?= csrf_field() ?>
                <input type="hidden" name="month_year" value="<?= h($monthYear) ?>">
                <input type="hidden" name="category_id" value="<?= (int) $r['cat']['id'] ?>">
                <button class="btn danger sm" type="submit">Zrušit úpravu pro měsíc</button>
              </form>
            <?php endif; ?>
          </div>
        </details>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($categoriesWithoutBudget): ?>
    <hr class="divider">
    <form method="post" action="/actions/budget_save.php" class="btn-row" style="align-items:flex-end;">
      <?= csrf_field() ?>
      <input type="hidden" name="month_year" value="<?= h($monthYear) ?>">
      <div class="field">
        <label>Přidat rozpočet kategorie</label>
        <select name="category_id">
          <?php foreach ($categoriesWithoutBudget as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= h(($c['icon'] ?: '') . ' ' . $c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="width:140px;">
        <label>Částka (Kč)</label>
        <input type="number" step="1" name="planned_amount" required>
      </div>
      <button class="btn secondary" type="submit">+ Přidat</button>
    </form>
  <?php endif; ?>
</div>
