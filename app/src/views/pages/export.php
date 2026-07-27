<?php
$pageTitle = 'Export';
$activeNav = 'export';
$currentMonth = current_month_year();
?>
<div class="topbar">
  <div>
    <h1>⬇️ Export dat</h1>
    <div class="subtitle">Stáhněte si data ve formátu CSV, Excel (XLSX) nebo je vytiskněte / uložte jako PDF</div>
  </div>
</div>

<div class="card">
  <form method="get" action="/export_run.php" target="_blank">
    <div class="form-grid">
      <div class="field span-3">
        <label>Co exportovat</label>
        <div class="radio-pills" id="scope-radios">
          <label class="radio-pill"><input type="radio" name="scope" value="month" checked><span>📅 Aktuální / vybraný měsíc</span></label>
          <label class="radio-pill"><input type="radio" name="scope" value="range"><span>📆 Vybrané období</span></label>
          <label class="radio-pill"><input type="radio" name="scope" value="all"><span>📋 Všechny položky</span></label>
          <label class="radio-pill"><input type="radio" name="scope" value="income"><span>💰 Pouze příjmy</span></label>
          <label class="radio-pill"><input type="radio" name="scope" value="expense"><span>🧾 Pouze výdaje</span></label>
          <label class="radio-pill"><input type="radio" name="scope" value="business"><span>💼 Pouze podnikatelské</span></label>
          <label class="radio-pill"><input type="radio" name="scope" value="categories"><span>🏷️ Přehled podle kategorií</span></label>
        </div>
      </div>

      <div class="field" id="field-month">
        <label>Měsíc</label>
        <select name="m">
          <?php foreach (month_options() as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $currentMonth ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" id="field-from" style="display:none;">
        <label>Datum od</label>
        <input type="date" name="date_from">
      </div>
      <div class="field" id="field-to" style="display:none;">
        <label>Datum do</label>
        <input type="date" name="date_to">
      </div>

      <div class="field span-3">
        <label>Formát</label>
        <div class="radio-pills">
          <label class="radio-pill"><input type="radio" name="format" value="csv" checked><span>📄 CSV</span></label>
          <label class="radio-pill"><input type="radio" name="format" value="xlsx"><span>📊 Excel (XLSX)</span></label>
          <label class="radio-pill"><input type="radio" name="format" value="print"><span>🖨️ Tisk / PDF</span></label>
        </div>
      </div>
    </div>
    <div class="btn-row" style="margin-top:18px;">
      <button class="btn" type="submit">⬇️ Vygenerovat export</button>
    </div>
    <p class="hint" style="margin-top:10px;">CSV a Excel soubory se zároveň uloží do složky <code>exporty</code> v aplikaci. Tisková varianta se otevře v novém okně, kde zvolíte „Uložit jako PDF“.</p>
  </form>
</div>

<script>
document.querySelectorAll('#scope-radios input').forEach(function (r) {
  r.addEventListener('change', function () {
    document.getElementById('field-month').style.display = (this.value === 'month' || this.value === 'categories') ? '' : 'none';
    document.getElementById('field-from').style.display = this.value === 'range' ? '' : 'none';
    document.getElementById('field-to').style.display = this.value === 'range' ? '' : 'none';
  });
});
</script>
