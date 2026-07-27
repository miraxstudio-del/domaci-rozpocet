<?php
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$duplicateFrom = isset($_GET['duplicate_from']) ? (int) $_GET['duplicate_from'] : null;
$fromRecurring = isset($_GET['from_recurring']) ? (int) $_GET['from_recurring'] : null;
$targetMonth = $_GET['m'] ?? month_year_for_date(date('Y-m-d'));

$editing = false;
$tx = null;
$tags = [];
$attachments = [];

$defaults = [
    'type' => $_GET['type'] ?? 'vydaj',
    'name' => '', 'amount' => '', 'payment_date' => date('Y-m-d'),
    'month_year' => $targetMonth, 'category_id' => null,
    'payment_method' => 'hotovost', 'merchant' => '', 'note' => '',
    'due_date' => '', 'status' => 'zaplaceno', 'paid_amount' => '',
    'is_recurring' => 0, 'recurring_id' => null, 'recurring_frequency' => 'mesicne',
    'invoice_number' => '', 'is_business' => 0, 'is_transfer' => 0,
];

if ($id) {
    $tx = get_transaction($id);
    if (!$tx) {
        flash('error', 'Položka nebyla nalezena.');
        redirect('polozky');
    }
    $editing = true;
    $defaults = array_merge($defaults, $tx);
    $tags = get_transaction_tags($id);
    $attachments = get_attachments($id);
} elseif ($duplicateFrom) {
    $src = get_transaction($duplicateFrom);
    if ($src) {
        $defaults = array_merge($defaults, $src, [
            'payment_date' => date('Y-m-d'),
            'month_year' => month_year_for_date(date('Y-m-d')),
            'status' => 'zaplaceno',
            'due_date' => '',
        ]);
        $tags = get_transaction_tags($duplicateFrom);
        flash('info', 'Položka byla předvyplněna podle „' . $src['name'] . '“. Upravte údaje a uložte.');
    }
} elseif ($fromRecurring) {
    $stmt = db()->prepare('SELECT * FROM recurring_payments WHERE id = :id');
    $stmt->execute(['id' => $fromRecurring]);
    $rec = $stmt->fetch();
    if ($rec) {
        $day = min((int) $rec['due_day'], (int) date('t', strtotime($targetMonth . '-01')));
        $dueDate = sprintf('%s-%02d', $targetMonth, $day);
        $defaults = array_merge($defaults, [
            'type' => $rec['type'], 'name' => $rec['name'], 'amount' => $rec['amount'],
            'category_id' => $rec['category_id'], 'payment_method' => $rec['payment_method'],
            'merchant' => $rec['merchant'], 'note' => $rec['note'],
            'payment_date' => $dueDate, 'due_date' => $dueDate, 'month_year' => $targetMonth,
            'status' => 'ceka', 'is_recurring' => 1, 'recurring_id' => $rec['id'],
            'is_business' => $rec['is_business'],
        ]);
        flash('info', 'Položka byla předvyplněna z pravidelné platby „' . $rec['name'] . '“.');
    }
}

$pageTitle = $editing ? 'Upravit položku' : 'Přidat položku';
$activeNav = 'polozka';
$closed = is_month_closed($defaults['month_year']);
?>
<div class="topbar">
  <div>
    <h1><?= $editing ? '✏️ Upravit položku' : '➕ Přidat novou položku' ?></h1>
    <div class="subtitle">Zaznamenejte příjem nebo výdaj domácnosti</div>
  </div>
  <?php if ($editing): ?>
    <div class="btn-row">
      <a class="btn secondary sm" href="/index.php?p=polozka&duplicate_from=<?= $id ?>">📄 Kopírovat</a>
      <button type="button" class="btn danger sm"
        data-delete-transaction="<?= $id ?>"
        data-transaction-name="<?= h((string) $defaults['name']) ?>"
        data-has-attachments="<?= $attachments ? '1' : '0' ?>">🗑️ Odstranit</button>
    </div>
  <?php endif; ?>
</div>

<?php if ($closed): ?>
  <div class="flash info">🔒 Měsíc <?= h(month_year_label($defaults['month_year'])) ?> je uzavřený. Úpravy jsou možné, ale nezapomeňte měsíc případně znovu zkontrolovat.</div>
<?php endif; ?>

<div class="card">
  <form method="post" action="/actions/transaction_save.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
    <?php if ($defaults['recurring_id']): ?><input type="hidden" name="recurring_id" value="<?= (int) $defaults['recurring_id'] ?>"><?php endif; ?>

    <div class="field" style="margin-bottom:16px;">
      <label>Typ položky</label>
      <div class="radio-pills" id="type-radios">
        <label class="radio-pill">
          <input type="radio" name="type" value="vydaj" <?= $defaults['type'] === 'vydaj' ? 'checked' : '' ?>>
          <span>🧾 Výdaj</span>
        </label>
        <label class="radio-pill">
          <input type="radio" name="type" value="prijem" <?= $defaults['type'] === 'prijem' ? 'checked' : '' ?>>
          <span>💰 Příjem</span>
        </label>
      </div>
    </div>

    <div class="form-grid">
      <div class="field span-2">
        <label for="name">Název položky *</label>
        <input type="text" id="name" name="name" required maxlength="200" value="<?= h((string) $defaults['name']) ?>" placeholder="např. Nákup potravin, Elektřina...">
      </div>

      <div class="field">
        <label for="amount">Částka (Kč) *</label>
        <input type="number" id="amount" name="amount" required step="0.01" value="<?= h((string) $defaults['amount']) ?>" placeholder="0,00">
        <div class="hint">Pro opravu nebo vrácení peněz lze zadat i zápornou částku.</div>
      </div>

      <div class="field">
        <label for="category_id">Kategorie</label>
        <?= category_select_html($defaults['category_id'] !== null ? (int) $defaults['category_id'] : null, null, 'category_id', 'category_id') ?>
      </div>

      <div class="field">
        <label for="payment_date">Datum platby *</label>
        <input type="date" id="payment_date" name="payment_date" required value="<?= h((string) $defaults['payment_date']) ?>">
      </div>

      <div class="field">
        <label for="month_year">Započítat do měsíce *</label>
        <select id="month_year" name="month_year">
          <?php foreach (month_options() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $defaults['month_year'] ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="payment_method">Způsob platby</label>
        <select id="payment_method" name="payment_method">
          <?php foreach (payment_methods() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $defaults['payment_method'] ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="merchant">Obchodník / příjemce</label>
        <input type="text" id="merchant" name="merchant" maxlength="150" value="<?= h((string) $defaults['merchant']) ?>">
      </div>

      <div class="field">
        <label for="invoice_number">Číslo dokladu / faktury</label>
        <input type="text" id="invoice_number" name="invoice_number" maxlength="80" value="<?= h((string) $defaults['invoice_number']) ?>">
      </div>

      <div class="field">
        <label for="status">Stav platby</label>
        <select id="status" name="status">
          <?php foreach (payment_statuses() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $defaults['status'] ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field" id="paid-amount-field" style="display:none;">
        <label for="paid_amount">Již uhrazená částka</label>
        <input type="number" id="paid_amount" name="paid_amount" step="0.01" value="<?= h((string) $defaults['paid_amount']) ?>">
      </div>

      <div class="field">
        <label for="due_date">Datum splatnosti</label>
        <input type="date" id="due_date" name="due_date" value="<?= h((string) $defaults['due_date']) ?>">
      </div>

      <div class="field">
        <label for="tags">Štítky</label>
        <input type="text" id="tags" name="tags" value="<?= h(implode(', ', $tags)) ?>" placeholder="oddělte čárkou, např. dovolená, dárek">
      </div>

      <div class="field span-3">
        <label for="note">Poznámka</label>
        <textarea id="note" name="note" rows="2"><?= h((string) $defaults['note']) ?></textarea>
      </div>

      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="is_recurring" value="1" id="is_recurring" <?= $defaults['is_recurring'] ? 'checked' : '' ?> <?= $defaults['recurring_id'] ? 'disabled' : '' ?>> Jde o pravidelnou platbu</label>
        <?php if ($defaults['recurring_id']): ?>
          <div class="hint">Napojeno na šablonu v sekci Pravidelné platby.</div>
          <input type="hidden" name="is_recurring" value="1">
        <?php endif; ?>
      </div>

      <div class="field" id="recurring-frequency-field" style="display:none;">
        <label for="recurring_frequency">Frekvence opakování</label>
        <select id="recurring_frequency" name="recurring_frequency">
          <?php foreach (frequency_labels() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $defaults['recurring_frequency'] ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="hint">Vytvoří se šablona v sekci Pravidelné platby.</div>
      </div>

      <?php if (get_setting('show_business', '1') === '1'): ?>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="is_business" value="1" <?= $defaults['is_business'] ? 'checked' : '' ?>> Podnikatelský výdaj/příjem</label>
      </div>
      <?php endif; ?>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="is_transfer" value="1" <?= $defaults['is_transfer'] ? 'checked' : '' ?>> Převod mezi vlastními účty</label>
        <div class="hint">Nezapočítá se do celkových příjmů ani výdajů.</div>
      </div>

      <div class="field">
        <label for="attachment_folder">Typ přílohy</label>
        <select id="attachment_folder" name="attachment_folder">
          <?php foreach (UPLOAD_FOLDERS as $key => $label): ?>
            <option value="<?= h($key) ?>"><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-2">
        <label for="attachments">Přílohy (účtenka, faktura, smlouva...)</label>
        <input type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx">
        <div class="hint">Povolené formáty: PDF, JPG, PNG, WEBP, DOC, XLS a další. Max. 25&nbsp;MB na soubor.</div>
      </div>
    </div>

    <?php if ($editing && $attachments): ?>
      <div class="section-title">Nahrané přílohy</div>
      <div class="attach-grid">
        <?php foreach ($attachments as $a): ?>
          <?php include SRC_PATH . '/views/partials/attachment_card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="btn-row" style="margin-top:22px;">
      <button type="submit" class="btn">💾 Uložit položku</button>
      <a class="btn outline" href="/index.php?p=polozky">Zrušit</a>
    </div>
  </form>
</div>

<script>
initCategoryTypeFilter('category_id', 'type');
initConditional('#status', '#paid-amount-field', function (v) { return v === 'castecne'; });
initConditional('#is_recurring', '#recurring-frequency-field', function () {
  return document.getElementById('is_recurring').checked;
});
</script>
