<?php
$pageTitle = 'Doklady';
$activeNav = 'doklady';

$filters = [
    'folder' => $_GET['folder'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'amount_min' => $_GET['amount_min'] ?? '',
    'amount_max' => $_GET['amount_max'] ?? '',
    'merchant' => trim($_GET['merchant'] ?? ''),
    'category_id' => $_GET['category_id'] ?? '',
    'q' => trim($_GET['q'] ?? ''),
];
$hasActiveFilter = (bool) array_filter($filters);
$activeFilters = array_filter($filters, fn ($v) => $v !== '');
$items = find_attachments($activeFilters);
?>
<div class="topbar">
  <div>
    <h1>🧾 Doklady</h1>
    <div class="subtitle"><?= count($items) ?> nahraných dokladů</div>
  </div>
</div>

<div class="card">
  <form method="get" action="/index.php">
    <input type="hidden" name="p" value="doklady">
    <div class="filters-bar">
      <div class="field">
        <label>Typ přílohy</label>
        <select name="folder">
          <option value="">Vše</option>
          <?php foreach (UPLOAD_FOLDERS as $key => $label): ?>
            <option value="<?= h($key) ?>" <?= $filters['folder'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Datum od</label><input type="date" name="date_from" value="<?= h($filters['date_from']) ?>"></div>
      <div class="field"><label>Datum do</label><input type="date" name="date_to" value="<?= h($filters['date_to']) ?>"></div>
      <div class="field"><label>Částka od</label><input type="number" step="0.01" name="amount_min" value="<?= h($filters['amount_min']) ?>"></div>
      <div class="field"><label>Částka do</label><input type="number" step="0.01" name="amount_max" value="<?= h($filters['amount_max']) ?>"></div>
      <div class="field">
        <label>Kategorie</label>
        <?= category_select_html($filters['category_id'] !== '' ? (int) $filters['category_id'] : null, null, 'filter_att_category', 'category_id') ?>
      </div>
      <div class="field" style="min-width:200px;"><label>Hledat</label><input type="search" name="q" value="<?= h($filters['q']) ?>" placeholder="Název souboru, obchodník, doklad..."></div>
      <div class="field"><button class="btn" type="submit">Filtrovat</button></div>
      <?php if ($hasActiveFilter): ?>
        <div class="field"><button type="button" class="btn secondary" data-clear-filters="/index.php?p=doklady">Zrušit filtry</button></div>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <?php if (!$items): ?>
    <div class="empty-state"><div class="ic">🧾</div><h3>Žádné doklady nenalezeny</h3><p>Přílohy přidáte při vytváření nebo úpravě položky.</p></div>
  <?php else: ?>
    <div class="attach-grid">
      <?php foreach ($items as $a): ?>
        <?php $ext = strtolower(pathinfo($a['stored_path'], PATHINFO_EXTENSION)); $isImage = in_array($ext, ['jpg','jpeg','png','webp','gif'], true); ?>
        <div class="attach-card">
          <a href="/soubor.php?action=view&id=<?= (int) $a['id'] ?>" target="_blank" class="attach-thumb">
            <?php if ($isImage): ?>
              <img src="/soubor.php?action=view&id=<?= (int) $a['id'] ?>" alt="" loading="lazy">
            <?php elseif ($ext === 'pdf'): ?>📄<?php else: ?>🗂️<?php endif; ?>
          </a>
          <div class="attach-info">
            <div class="name" title="<?= h($a['original_name']) ?>"><?= h($a['original_name']) ?></div>
            <div class="meta"><?= format_date_short_cz($a['payment_date']) ?> · <?= format_money((float) $a['transaction_amount']) ?></div>
            <div class="meta"><a href="/index.php?p=polozka&id=<?= (int) $a['transaction_id'] ?>"><?= h($a['transaction_name']) ?></a></div>
          </div>
          <div class="attach-actions">
            <a class="btn secondary sm" href="/soubor.php?action=download&id=<?= (int) $a['id'] ?>">⬇️</a>
            <form method="post" action="/actions/attachment_delete.php" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <input type="hidden" name="return" value="/index.php?p=doklady">
              <button class="btn danger sm" data-confirm="Odstranit tuto přílohu?">🗑️</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
