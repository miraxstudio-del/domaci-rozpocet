<?php
$pageTitle = 'Příjmy a výdaje';
$activeNav = 'polozky';

$filters = [
    'month_year' => $_GET['month_year'] ?? '',
    'type' => $_GET['type'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'status' => $_GET['status'] ?? '',
    'payment_method' => $_GET['payment_method'] ?? '',
    'q' => trim($_GET['q'] ?? ''),
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'amount_min' => $_GET['amount_min'] ?? '',
    'amount_max' => $_GET['amount_max'] ?? '',
    'merchant' => trim($_GET['merchant'] ?? ''),
    'tag' => trim($_GET['tag'] ?? ''),
    'is_business' => $_GET['is_business'] ?? '',
    'is_recurring' => $_GET['is_recurring'] ?? '',
    'has_attachment' => $_GET['has_attachment'] ?? '',
];
$hasActiveFilter = (bool) array_filter($filters);

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$activeFilters = array_filter($filters, fn ($v) => $v !== '');
$items = find_transactions($activeFilters, $perPage, ($page - 1) * $perPage);
$totalCount = count_transactions($activeFilters);
$totalPages = max(1, (int) ceil($totalCount / $perPage));
$sumShown = array_sum(array_map(fn ($t) => $t['type'] === 'prijem' ? (float) $t['amount'] : -(float) $t['amount'], $items));
?>
<div class="transactions-page">
<div class="topbar transactions-topbar">
  <div>
    <h1>Příjmy a výdaje</h1>
    <div class="subtitle"><?= $totalCount ?> položek<?= $hasActiveFilter ? ' (filtrováno)' : '' ?></div>
  </div>
  <div class="btn-row">
    <a class="transactions-add-button" href="/index.php?p=polozka">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
      Přidat položku
    </a>
  </div>
</div>

<div class="card transactions-filter-card">
  <form method="get" action="/index.php">
    <input type="hidden" name="p" value="polozky">
    <div class="filters-bar transactions-filter-grid">
      <div class="field">
        <label>Měsíc</label>
        <select name="month_year">
          <option value="">Všechny měsíce</option>
          <?php foreach (month_options() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $filters['month_year'] ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Typ</label>
        <select name="type">
          <option value="">Vše</option>
          <option value="prijem" <?= $filters['type'] === 'prijem' ? 'selected' : '' ?>>Příjem</option>
          <option value="vydaj" <?= $filters['type'] === 'vydaj' ? 'selected' : '' ?>>Výdaj</option>
        </select>
      </div>
      <div class="field">
        <label>Kategorie</label>
        <?= category_select_html($filters['category_id'] !== '' ? (int) $filters['category_id'] : null, null, 'filter_category', 'category_id') ?>
      </div>
      <div class="field">
        <label>Stav</label>
        <select name="status">
          <option value="">Vše</option>
          <?php foreach (payment_statuses() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field transactions-search-field">
        <label>Hledat</label>
        <span class="transactions-search-input"><input type="search" name="q" value="<?= h($filters['q']) ?>" placeholder="Název, obchodník, doklad..."><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="5.5"/><path d="m16 16 4 4"/></svg></span>
      </div>
      <div class="field">
        <button class="btn" type="submit">Filtrovat</button>
      </div>
      <?php if ($hasActiveFilter): ?>
        <div class="field">
          <button type="button" class="btn secondary" data-clear-filters="/index.php?p=polozky">Zrušit filtry</button>
        </div>
      <?php endif; ?>
    </div>

    <details class="transactions-advanced-filters">
      <summary>Rozšířené filtry</summary>
      <div class="filters-bar transactions-advanced-grid">
        <div class="field">
          <label>Datum od</label>
          <input type="date" name="date_from" value="<?= h($filters['date_from']) ?>">
        </div>
        <div class="field">
          <label>Datum do</label>
          <input type="date" name="date_to" value="<?= h($filters['date_to']) ?>">
        </div>
        <div class="field">
          <label>Částka od</label>
          <input type="number" step="0.01" name="amount_min" value="<?= h($filters['amount_min']) ?>">
        </div>
        <div class="field">
          <label>Částka do</label>
          <input type="number" step="0.01" name="amount_max" value="<?= h($filters['amount_max']) ?>">
        </div>
        <div class="field">
          <label>Způsob platby</label>
          <select name="payment_method">
            <option value="">Vše</option>
            <?php foreach (payment_methods() as $val => $label): ?>
              <option value="<?= h($val) ?>" <?= $filters['payment_method'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Obchodník / příjemce</label>
          <input type="text" name="merchant" value="<?= h($filters['merchant']) ?>">
        </div>
        <div class="field">
          <label>Štítek</label>
          <input type="text" name="tag" value="<?= h($filters['tag']) ?>">
        </div>
        <?php if (get_setting('show_business', '1') === '1'): ?>
        <div class="field">
          <label>Soukromé / podnikatelské</label>
          <select name="is_business">
            <option value="">Vše</option>
            <option value="0" <?= $filters['is_business'] === '0' ? 'selected' : '' ?>>Soukromé</option>
            <option value="1" <?= $filters['is_business'] === '1' ? 'selected' : '' ?>>Podnikatelské</option>
          </select>
        </div>
        <?php endif; ?>
        <div class="field">
          <label>Pravidelnost</label>
          <select name="is_recurring">
            <option value="">Vše</option>
            <option value="1" <?= $filters['is_recurring'] === '1' ? 'selected' : '' ?>>Pravidelné</option>
            <option value="0" <?= $filters['is_recurring'] === '0' ? 'selected' : '' ?>>Jednorázové</option>
          </select>
        </div>
        <div class="field">
          <label>Přílohy</label>
          <select name="has_attachment">
            <option value="">Vše</option>
            <option value="1" <?= $filters['has_attachment'] === '1' ? 'selected' : '' ?>>S přílohou</option>
            <option value="0" <?= $filters['has_attachment'] === '0' ? 'selected' : '' ?>>Bez přílohy</option>
          </select>
        </div>
        <div class="field"><button class="btn secondary" type="submit">Použít</button></div>
      </div>
    </details>
  </form>
</div>

<div class="card transactions-results-card">
  <?php if (!$items): ?>
    <div class="empty-state transactions-empty-state">
      <div class="transactions-empty-illustration" aria-hidden="true">
        <svg viewBox="0 0 160 128">
          <path class="transactions-spark" d="M80 13v17M64 20l7 12M96 20l-7 12"/>
          <circle class="transactions-dot" cx="43" cy="46" r="3"/><circle class="transactions-dot" cx="117" cy="47" r="3"/><circle class="transactions-dot" cx="128" cy="79" r="2.4"/>
          <path class="transactions-box-side" d="m48 71 32 13 32-13v35l-32 11-32-11Z"/>
          <path class="transactions-box-front" d="m48 71 32 13 32-13v35l-32 11-32-11Z"/>
          <path class="transactions-box-left" d="m48 71 32 13v33l-32-11Z"/>
          <path class="transactions-box-right" d="m112 71-32 13v33l32-11Z"/>
          <path class="transactions-box-lid-left" d="m48 71 18-25 22 18-8 20Z"/>
          <path class="transactions-box-lid-right" d="m112 71-18-25-22 18 8 20Z"/>
          <path class="transactions-box-lid-top" d="m66 46 14-12 14 12-14 18Z"/>
        </svg>
      </div>
      <h3>Žádné položky nenalezeny</h3>
      <p>Zkuste upravit filtry nebo přidejte novou položku.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Datum</th><th>Název</th><th>Kategorie</th><th>Platba</th><th>Stav</th><th></th><th class="text-right">Částka</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $t): ?>
            <tr>
              <td class="mono"><?= format_date_short_cz($t['payment_date']) ?></td>
              <td>
                <a href="/index.php?p=polozka&id=<?= (int) $t['id'] ?>"><?= h($t['name']) ?></a>
                <?php if ($t['is_recurring']): ?><span title="Pravidelná platba">🔁</span><?php endif; ?>
                <?php if ($t['is_business']): ?><span title="Podnikatelské">💼</span><?php endif; ?>
                <?php if ($t['merchant']): ?><div class="text-faint" style="font-size:12px;"><?= h($t['merchant']) ?></div><?php endif; ?>
              </td>
              <td><?= h(($t['category_icon'] ?? '') . ' ' . ($t['parent_category_name'] ? $t['parent_category_name'] . ' › ' . $t['category_name'] : ($t['category_name'] ?? 'Nezařazeno'))) ?></td>
              <td><?= h(payment_methods()[$t['payment_method']] ?? $t['payment_method']) ?></td>
              <td><span class="badge status-<?= h($t['status']) ?>"><?= h(payment_statuses()[$t['status']] ?? $t['status']) ?></span></td>
              <td><?= $t['attachment_count'] > 0 ? '📎' . (int) $t['attachment_count'] : '' ?></td>
              <td class="text-right mono <?= $t['type'] === 'prijem' ? 'amount-in' : 'amount-out' ?>">
                <?= $t['type'] === 'prijem' ? '+' : '−' ?><?= format_money((float) $t['amount']) ?>
              </td>
              <td>
                <div class="btn-row" style="flex-wrap:nowrap;">
                  <a class="btn secondary sm icon-only" title="Upravit" href="/index.php?p=polozka&id=<?= (int) $t['id'] ?>">✏️</a>
                  <a class="btn secondary sm icon-only" title="Kopírovat" href="/index.php?p=polozka&duplicate_from=<?= (int) $t['id'] ?>">📄</a>
                  <button type="button" class="btn danger sm icon-only" title="Smazat"
                    data-delete-transaction="<?= (int) $t['id'] ?>"
                    data-transaction-name="<?= h($t['name']) ?>"
                    data-has-attachments="<?= $t['attachment_count'] > 0 ? '1' : '0' ?>">🗑️</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;flex-wrap:wrap;gap:10px;">
      <div class="text-muted">Součet zobrazených položek: <strong class="mono" style="color:<?= $sumShown >= 0 ? 'var(--income)' : 'var(--expense)' ?>"><?= format_money($sumShown) ?></strong></div>
      <?php if ($totalPages > 1): ?>
        <div class="btn-row">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a class="btn sm <?= $p === $page ? '' : 'secondary' ?>" href="/index.php?p=polozky&<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</div>
