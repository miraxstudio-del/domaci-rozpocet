<?php
$pageTitle = 'Měsíce';
$activeNav = 'mesice';

$monthYear = $_GET['m'] ?? current_month_year();
if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    $monthYear = current_month_year();
}
$prevM = shift_month($monthYear, -1);
$nextM = shift_month($monthYear, 1);
$compareWith = $_GET['compare'] ?? '';

$summary = month_summary($monthYear);
$monthRow = get_month_row($monthYear);
$closed = $monthRow ? (bool) $monthRow['is_closed'] : false;
$items = find_transactions(['month_year' => $monthYear], 1000, 0);
$catExpense = category_breakdown($monthYear, 'vydaj');
$catIncome = category_breakdown($monthYear, 'prijem');

$compareSummary = null;
if ($compareWith && preg_match('/^\d{4}-\d{2}$/', $compareWith)) {
    $compareSummary = month_summary($compareWith);
}
?>
<div class="topbar no-print">
  <div>
    <h1>🗓️ Měsíce</h1>
    <div class="subtitle">Podrobný měsíční přehled a uzavírání měsíců</div>
  </div>
  <div class="month-switch">
    <a class="btn outline icon-only" href="/index.php?p=mesice&m=<?= h($prevM) ?>">‹</a>
    <form method="get" action="/index.php" style="display:flex;gap:4px;">
      <input type="hidden" name="p" value="mesice">
      <input type="month" name="m" value="<?= h($monthYear) ?>" onchange="this.form.submit()">
    </form>
    <a class="btn outline icon-only" href="/index.php?p=mesice&m=<?= h($nextM) ?>">›</a>
  </div>
  <div class="btn-row">
    <button type="button" class="btn secondary" onclick="window.print()">🖨️ Tisk / PDF</button>
    <a class="btn secondary" href="/index.php?p=export&m=<?= h($monthYear) ?>">⬇️ Export</a>
  </div>
</div>

<div class="card">
  <div class="card-title-row">
    <h3><?= h(month_year_label($monthYear)) ?> <?= $closed ? '<span class="badge warning">🔒 uzavřeno</span>' : '<span class="badge income">otevřeno</span>' ?></h3>
  </div>
  <div class="grid grid-cols-4">
    <div class="stat-tile"><div class="label">Příjmy</div><div class="value income"><?= format_money($summary['income']) ?></div></div>
    <div class="stat-tile"><div class="label">Výdaje</div><div class="value expense"><?= format_money($summary['expense']) ?></div></div>
    <div class="stat-tile"><div class="label">Zbývá</div><div class="value" style="color:<?= $summary['remaining']>=0?'var(--income)':'var(--expense)' ?>"><?= format_money($summary['remaining']) ?></div></div>
    <div class="stat-tile"><div class="label">Počet položek</div><div class="value"><?= count($items) ?></div></div>
  </div>

  <?php if ($monthRow && $monthRow['closing_note']): ?>
    <div class="flash info" style="margin-top:16px;">📝 Poznámka k uzavření: <?= h($monthRow['closing_note']) ?></div>
  <?php endif; ?>

  <div class="btn-row no-print" style="margin-top:16px;">
    <?php if ($closed): ?>
      <form method="post" action="/actions/month_close.php">
        <?= csrf_field() ?>
        <input type="hidden" name="month_year" value="<?= h($monthYear) ?>">
        <input type="hidden" name="action" value="reopen">
        <button class="btn secondary">🔓 Znovu otevřít měsíc</button>
      </form>
    <?php else: ?>
      <details style="display:inline-block;">
        <summary class="btn secondary" style="display:inline-flex;cursor:pointer;">🔒 Uzavřít měsíc</summary>
        <form method="post" action="/actions/month_close.php" style="margin-top:10px;display:flex;gap:8px;align-items:flex-end;">
          <?= csrf_field() ?>
          <input type="hidden" name="month_year" value="<?= h($monthYear) ?>">
          <input type="hidden" name="action" value="close">
          <div class="field" style="min-width:260px;">
            <label>Závěrečná poznámka (nepovinné)</label>
            <input type="text" name="closing_note" value="<?= h($monthRow['closing_note'] ?? '') ?>">
          </div>
          <button class="btn" type="submit">Potvrdit uzavření</button>
        </form>
      </details>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-2" style="margin-top:16px;">
  <div class="card">
    <h3>Výdaje podle kategorií</h3>
    <?php if (!$catExpense): ?><p class="text-muted">Žádné výdaje.</p><?php endif; ?>
    <div class="table-wrap"><table><tbody>
      <?php foreach ($catExpense as $c): ?>
        <tr><td><?= h(($c['cat_icon'] ?: '📦') . ' ' . ($c['cat_name'] ?: 'Nezařazeno')) ?></td><td class="text-right mono"><?= format_money((float) $c['total']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="card">
    <h3>Příjmy podle kategorií</h3>
    <?php if (!$catIncome): ?><p class="text-muted">Žádné příjmy.</p><?php endif; ?>
    <div class="table-wrap"><table><tbody>
      <?php foreach ($catIncome as $c): ?>
        <tr><td><?= h(($c['cat_icon'] ?: '💰') . ' ' . ($c['cat_name'] ?: 'Nezařazeno')) ?></td><td class="text-right mono"><?= format_money((float) $c['total']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  </div>
</div>

<div class="card no-print" style="margin-top:16px;">
  <h3>Porovnat s jiným měsícem</h3>
  <form method="get" action="/index.php" class="btn-row" style="align-items:flex-end;">
    <input type="hidden" name="p" value="mesice">
    <input type="hidden" name="m" value="<?= h($monthYear) ?>">
    <div class="field">
      <label>Porovnat s</label>
      <select name="compare">
        <option value="">— vyberte měsíc —</option>
        <?php foreach (month_options() as $val => $label): if ($val === $monthYear) continue; ?>
          <option value="<?= h($val) ?>" <?= $val === $compareWith ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn secondary" type="submit">Porovnat</button>
  </form>

  <?php if ($compareSummary): ?>
    <div class="table-wrap" style="margin-top:16px;"><table>
      <thead><tr><th></th><th class="text-right"><?= h(month_year_label($monthYear)) ?></th><th class="text-right"><?= h(month_year_label($compareWith)) ?></th><th class="text-right">Rozdíl</th></tr></thead>
      <tbody>
        <tr><td>Příjmy</td><td class="text-right mono"><?= format_money($summary['income']) ?></td><td class="text-right mono"><?= format_money($compareSummary['income']) ?></td>
          <td class="text-right mono"><?= format_money($summary['income'] - $compareSummary['income']) ?></td></tr>
        <tr><td>Výdaje</td><td class="text-right mono"><?= format_money($summary['expense']) ?></td><td class="text-right mono"><?= format_money($compareSummary['expense']) ?></td>
          <td class="text-right mono"><?= format_money($summary['expense'] - $compareSummary['expense']) ?></td></tr>
        <tr><td>Zbývá</td><td class="text-right mono"><?= format_money($summary['remaining']) ?></td><td class="text-right mono"><?= format_money($compareSummary['remaining']) ?></td>
          <td class="text-right mono"><?= format_money($summary['remaining'] - $compareSummary['remaining']) ?></td></tr>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:16px;">
  <h3>Všechny položky měsíce</h3>
  <?php if (!$items): ?>
    <p class="text-muted">Žádné položky.</p>
  <?php else: ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Datum</th><th>Název</th><th>Kategorie</th><th>Stav</th><th class="text-right">Částka</th></tr></thead>
      <tbody>
        <?php foreach ($items as $t): ?>
          <tr>
            <td class="mono"><?= format_date_short_cz($t['payment_date']) ?></td>
            <td><a href="/index.php?p=polozka&id=<?= (int) $t['id'] ?>"><?= h($t['name']) ?></a></td>
            <td><?= h($t['category_name'] ?? 'Nezařazeno') ?></td>
            <td><span class="badge status-<?= h($t['status']) ?>"><?= h(payment_statuses()[$t['status']] ?? $t['status']) ?></span></td>
            <td class="text-right mono <?= $t['type']==='prijem'?'amount-in':'amount-out' ?>"><?= $t['type']==='prijem'?'+':'−' ?><?= format_money((float) $t['amount']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
