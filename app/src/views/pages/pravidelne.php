<?php
$pageTitle = 'Pravidelné platby';
$activeNav = 'pravidelne';

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editRec = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM recurring_payments WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $editRec = $stmt->fetch() ?: null;
}

$stmt = db()->query('
    SELECT r.*, c.name AS category_name, c.icon AS category_icon
    FROM recurring_payments r LEFT JOIN categories c ON c.id = r.category_id
    ORDER BY r.is_active DESC, r.due_day, r.name
');
$list = $stmt->fetchAll();

$f = [
    'name' => $editRec['name'] ?? '', 'type' => $editRec['type'] ?? 'vydaj',
    'amount' => $editRec['amount'] ?? '', 'category_id' => $editRec['category_id'] ?? null,
    'due_day' => $editRec['due_day'] ?? 1, 'frequency' => $editRec['frequency'] ?? 'mesicne',
    'start_date' => $editRec['start_date'] ?? date('Y-m-d'), 'end_date' => $editRec['end_date'] ?? '',
    'auto_create' => $editRec ? (bool) $editRec['auto_create'] : true,
    'remind_days_before' => $editRec['remind_days_before'] ?? 3,
    'payment_method' => $editRec['payment_method'] ?? 'trvaly_prikaz',
    'merchant' => $editRec['merchant'] ?? '', 'note' => $editRec['note'] ?? '',
    'is_business' => $editRec['is_business'] ?? 0,
    'is_active' => $editRec ? (bool) $editRec['is_active'] : true,
];
$currentMonth = current_month_year();
?>
<div class="topbar">
  <div>
    <h1>🔁 Pravidelné platby</h1>
    <div class="subtitle">Nájem, energie, pojištění a další opakované platby</div>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1.6fr 1fr; align-items:flex-start; gap:16px;">
<div>
  <?php if (!$list): ?>
    <div class="card"><div class="empty-state"><div class="ic">🔁</div><p>Zatím nemáte žádné pravidelné platby.</p></div></div>
  <?php endif; ?>
  <?php foreach ($list as $r): ?>
    <div class="card" style="<?= $r['is_active'] ? '' : 'opacity:.55;' ?>">
      <div class="card-title-row">
        <h3>
          <?= h(($r['category_icon'] ?: '🔁') . ' ' . $r['name']) ?>
          <?php if (!$r['is_active']): ?><span class="badge">pozastaveno</span><?php endif; ?>
          <span class="badge <?= $r['type'] === 'prijem' ? 'income' : 'expense' ?>"><?= $r['type'] === 'prijem' ? 'příjem' : 'výdaj' ?></span>
        </h3>
        <div class="mono" style="font-weight:700;font-size:16px;"><?= format_money((float) $r['amount']) ?></div>
      </div>
      <p class="text-muted" style="font-size:13.5px;margin-bottom:10px;">
        <?= h(frequency_labels()[$r['frequency']] ?? $r['frequency']) ?> · splatnost <?= (int) $r['due_day'] ?>. den v měsíci
        · od <?= format_date_cz($r['start_date']) ?><?= $r['end_date'] ? ' do ' . format_date_cz($r['end_date']) : '' ?>
        <?= $r['auto_create'] ? ' · automatické vytváření zapnuto' : ' · automatické vytváření vypnuto' ?>
      </p>
      <div class="btn-row">
        <a class="btn secondary sm" href="/index.php?p=polozka&from_recurring=<?= (int) $r['id'] ?>&m=<?= $currentMonth ?>">➕ Vytvořit za tento měsíc</a>
        <a class="btn secondary sm" href="/index.php?p=pravidelne&edit=<?= (int) $r['id'] ?>">✏️ Upravit</a>
        <form method="post" action="/actions/recurring_toggle.php" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <button class="btn secondary sm"><?= $r['is_active'] ? '⏸️ Pozastavit' : '▶️ Aktivovat' ?></button>
        </form>
        <form method="post" action="/actions/recurring_delete.php" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <button class="btn danger sm" data-confirm="Odstranit pravidelnou platbu „<?= h($r['name']) ?>“? Již vytvořené položky zůstanou zachovány.">🗑️ Odstranit</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card" style="position:sticky; top:20px;">
  <h3><?= $editRec ? 'Upravit pravidelnou platbu' : 'Přidat pravidelnou platbu' ?></h3>
  <form method="post" action="/actions/recurring_save.php">
    <?= csrf_field() ?>
    <?php if ($editRec): ?><input type="hidden" name="id" value="<?= (int) $editRec['id'] ?>"><?php endif; ?>
    <div class="form-grid" style="grid-template-columns:1fr;">
      <div class="field">
        <label>Typ</label>
        <div class="radio-pills" id="rec-type-radios">
          <label class="radio-pill"><input type="radio" name="type" value="vydaj" <?= $f['type'] === 'vydaj' ? 'checked' : '' ?>><span>🧾 Výdaj</span></label>
          <label class="radio-pill"><input type="radio" name="type" value="prijem" <?= $f['type'] === 'prijem' ? 'checked' : '' ?>><span>💰 Příjem</span></label>
        </div>
      </div>
      <div class="field">
        <label>Název *</label>
        <input type="text" name="name" required maxlength="150" value="<?= h((string) $f['name']) ?>" placeholder="např. Elektřina">
      </div>
      <div class="field">
        <label>Částka (Kč) *</label>
        <input type="number" step="0.01" name="amount" required value="<?= h((string) $f['amount']) ?>">
        <div class="hint">Skutečnou částku lze při vytvoření položky pro daný měsíc upravit.</div>
      </div>
      <div class="field">
        <label>Kategorie</label>
        <?= category_select_html($f['category_id'] !== null ? (int) $f['category_id'] : null, null, 'rec_category_id', 'category_id') ?>
      </div>
      <div class="field">
        <label>Den splatnosti v měsíci</label>
        <input type="number" name="due_day" min="1" max="31" value="<?= (int) $f['due_day'] ?>">
      </div>
      <div class="field">
        <label>Frekvence opakování</label>
        <select name="frequency">
          <?php foreach (frequency_labels() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $f['frequency'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Datum začátku</label>
        <input type="date" name="start_date" value="<?= h((string) $f['start_date']) ?>">
      </div>
      <div class="field">
        <label>Datum ukončení (nepovinné)</label>
        <input type="date" name="end_date" value="<?= h((string) $f['end_date']) ?>">
      </div>
      <div class="field">
        <label>Upozornit před splatností (dní)</label>
        <input type="number" name="remind_days_before" min="0" max="30" value="<?= (int) $f['remind_days_before'] ?>">
      </div>
      <div class="field">
        <label>Způsob platby</label>
        <select name="payment_method">
          <?php foreach (payment_methods() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $f['payment_method'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Obchodník / příjemce</label>
        <input type="text" name="merchant" value="<?= h((string) $f['merchant']) ?>">
      </div>
      <div class="field">
        <label>Poznámka</label>
        <textarea name="note" rows="2"><?= h((string) $f['note']) ?></textarea>
      </div>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="auto_create" value="1" <?= $f['auto_create'] ? 'checked' : '' ?>> Automaticky vytvořit položku v novém měsíci</label>
      </div>
      <?php if (get_setting('show_business', '1') === '1'): ?>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="is_business" value="1" <?= $f['is_business'] ? 'checked' : '' ?>> Podnikatelský výdaj/příjem</label>
      </div>
      <?php endif; ?>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?= $f['is_active'] ? 'checked' : '' ?>> Aktivní</label>
      </div>
    </div>
    <div class="btn-row" style="margin-top:16px;">
      <button class="btn" type="submit">💾 Uložit</button>
      <?php if ($editRec): ?><a class="btn outline" href="/index.php?p=pravidelne">Zrušit úpravu</a><?php endif; ?>
    </div>
  </form>
</div>
</div>

<script>initCategoryTypeFilter('rec_category_id', 'type');</script>
