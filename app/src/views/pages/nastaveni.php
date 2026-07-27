<?php
$pageTitle = 'Nastavení';
$activeNav = 'nastaveni';
?>
<div class="topbar">
  <div>
    <h1>⚙️ Nastavení</h1>
    <div class="subtitle">Přizpůsobte si aplikaci podle svých potřeb</div>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; align-items:flex-start;">
<div class="card">
  <h3>Obecné</h3>
  <form method="post" action="/actions/settings_save.php">
    <?= csrf_field() ?>
    <div class="form-grid" style="grid-template-columns:1fr;">
      <div class="field">
        <label>Název domácnosti</label>
        <input type="text" name="household_name" value="<?= h(get_setting('household_name', 'Naše domácnost')) ?>">
      </div>
      <div class="field">
        <label>Výchozí měna</label>
        <input type="text" name="currency" maxlength="6" value="<?= h(get_setting('currency', 'Kč')) ?>">
      </div>
      <div class="field">
        <label>První den měsíčního období</label>
        <input type="number" name="month_start_day" min="1" max="28" value="<?= h(get_setting('month_start_day', '1')) ?>">
        <div class="hint">Pokud rodina účtuje např. od 15. do 14., nastavte 15. Ovlivňuje výchozí návrh měsíce u nových položek (lze vždy ručně přepsat).</div>
      </div>
      <div class="field">
        <label>Formát data</label>
        <select name="date_format">
          <?php foreach (date_format_options() as $val => $sample): ?>
            <option value="<?= h($val) ?>" <?= get_setting('date_format', 'j. n. Y') === $val ? 'selected' : '' ?>><?= h($sample) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Počet desetinných míst u částek</label>
        <select name="decimal_places">
          <option value="0" <?= get_setting('decimal_places', '2') === '0' ? 'selected' : '' ?>>0 (12 490 Kč)</option>
          <option value="2" <?= get_setting('decimal_places', '2') === '2' ? 'selected' : '' ?>>2 (12 490,50 Kč)</option>
        </select>
      </div>
      <div class="field">
        <label>Vlastní způsoby platby</label>
        <input type="text" name="custom_payment_methods" value="<?= h(get_setting('custom_payment_methods', '')) ?>" placeholder="např. Stravenky, Kryptoměna">
        <div class="hint">Oddělte čárkou. Doplní se do nabídky vedle běžných způsobů platby.</div>
      </div>
      <div class="field">
        <label>Složka pro exporty</label>
        <input type="text" name="export_folder" value="<?= h(get_setting('export_folder', 'exporty')) ?>">
        <div class="hint">Podsložka v kořeni aplikace, kam se ukládají vygenerované CSV/XLSX soubory.</div>
      </div>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="confirm_delete" value="1" <?= get_setting('confirm_delete', '1') === '1' ? 'checked' : '' ?>> Vždy potvrzovat před odstraněním</label>
      </div>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="show_business" value="1" <?= get_setting('show_business', '1') === '1' ? 'checked' : '' ?>> Zobrazovat podnikatelské funkce (rozlišení soukromé/podnikatelské)</label>
      </div>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="auto_backup" value="1" <?= get_setting('auto_backup', '1') === '1' ? 'checked' : '' ?>> Automatická denní záloha při spuštění</label>
      </div>
    </div>
    <div class="btn-row" style="margin-top:16px;">
      <button class="btn" type="submit">💾 Uložit nastavení</button>
    </div>
  </form>
  <p class="hint" style="margin-top:14px;">Světlý / tmavý / systémový vzhled přepnete tlačítky vlevo dole v postranním panelu.</p>
</div>

<div>
  <div class="card">
    <h3>🏷️ Kategorie</h3>
    <p class="text-muted">Spravujte vlastní kategorie a podkategorie příjmů a výdajů, ikony, barvy a měsíční limity.</p>
    <a class="btn secondary" href="/index.php?p=kategorie">Spravovat kategorie →</a>
  </div>

  <div class="card">
    <h3>🧪 Ukázková data</h3>
    <p class="text-muted">Pro vyzkoušení aplikace můžete volitelně načíst ukázkové položky (nákupy, energie, pojištění...). Skutečná data zůstanou zachována - ukázková data lze kdykoliv opět odstranit.</p>
    <div class="btn-row">
      <form method="post" action="/actions/demo_data.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="load">
        <button class="btn secondary">🧪 Načíst ukázková data</button>
      </form>
      <form method="post" action="/actions/demo_data.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="remove">
        <button class="btn danger" data-confirm="Odstranit všechna ukázková data?">🗑️ Odstranit ukázková data</button>
      </form>
    </div>
  </div>

  <div class="card">
    <h3>ℹ️ O aplikaci</h3>
    <p class="text-muted" style="font-size:13.5px;">
      Databáze: <code>data/rozpocet.sqlite</code><br>
      Doklady: <code>uploads/</code><br>
      Zálohy: <code>data/backups/</code><br>
      Exporty: <code><?= h(get_setting('export_folder', 'exporty')) ?>/</code>
    </p>
    <p class="text-faint" style="font-size:12px;">Všechna data zůstávají pouze na tomto počítači.</p>
  </div>
</div>
</div>
