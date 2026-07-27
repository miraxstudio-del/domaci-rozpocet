<?php
$pageTitle = 'Zálohy';
$activeNav = 'zalohy';
$backups = list_backups();
?>
<div class="topbar">
  <div>
    <h1>💾 Zálohy</h1>
    <div class="subtitle">Zálohování a obnova databáze i nahraných dokladů</div>
  </div>
  <div class="btn-row">
    <form method="post" action="/actions/backup_create.php">
      <?= csrf_field() ?>
      <button class="btn">💾 Vytvořit zálohu nyní</button>
    </form>
  </div>
</div>

<div class="card">
  <p class="text-muted">
    Zálohy obsahují kompletní databázi i všechny nahrané doklady a ukládají se do složky
    <code>data/backups</code>. Automatická záloha se vytváří jednou denně při spuštění aplikace
    (lze vypnout v Nastavení).
  </p>

  <?php if (!$backups): ?>
    <div class="empty-state"><div class="ic">💾</div><p>Zatím nemáte žádné zálohy.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Soubor</th><th>Typ</th><th>Vytvořeno</th><th class="text-right">Velikost</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($backups as $b): ?>
            <tr>
              <td class="mono"><?= h($b['filename']) ?></td>
              <td><span class="badge <?= $b['type'] === 'auto' ? '' : 'income' ?>"><?= $b['type'] === 'auto' ? 'automatická' : 'ruční' ?></span></td>
              <td><?= h($b['created_at']) ?></td>
              <td class="text-right mono"><?= human_file_size((int) $b['size']) ?></td>
              <td>
                <div class="btn-row" style="flex-wrap:nowrap;justify-content:flex-end;">
                  <a class="btn secondary sm" href="/zaloha_stahnout.php?f=<?= urlencode($b['filename']) ?>">⬇️ Stáhnout</a>
                  <button type="button" class="btn secondary sm"
                    data-restore-backup="<?= h($b['filename']) ?>">♻️ Obnovit</button>
                  <form method="post" action="/actions/backup_delete.php" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="filename" value="<?= h($b['filename']) ?>">
                    <button class="btn danger sm" data-confirm="Odstranit zálohu „<?= h($b['filename']) ?>“?">🗑️</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <h3>📥 Importovat zálohu z jiného počítače</h3>
  <p class="text-muted">Vyberte soubor <code>.zip</code> vytvořený touto aplikací (např. po přenesení na jiný počítač).</p>
  <form method="post" action="/actions/backup_restore.php" enctype="multipart/form-data" class="btn-row" style="align-items:flex-end;">
    <?= csrf_field() ?>
    <div class="field">
      <label>Soubor zálohy (.zip)</label>
      <input type="file" name="backup_file" accept=".zip" required>
    </div>
    <button class="btn secondary" type="submit" data-confirm="Obnovení přepíše aktuální data v aplikaci (před tím se automaticky vytvoří bezpečnostní záloha). Pokračovat?">📥 Importovat a obnovit</button>
  </form>
</div>

<form method="post" action="/actions/backup_restore.php" id="restore-form">
  <?= csrf_field() ?>
  <input type="hidden" name="filename" id="restore-filename" value="">
</form>

<dialog id="restore-dialog">
  <div class="dialog-body">
    <h3>Obnovit zálohu?</h3>
    <p class="text-muted" id="restore-dialog-text">Obnovení přepíše aktuální databázi i doklady zvolenou zálohou. Před obnovou se automaticky vytvoří bezpečnostní záloha aktuálního stavu. Dokončení vyžaduje restart aplikace (STOP.bat a poté znovu START.bat).</p>
    <div class="dialog-actions">
      <button type="button" class="btn secondary" onclick="document.getElementById('restore-dialog').close()">Zrušit</button>
      <button type="button" class="btn danger" id="restore-confirm-btn">Obnovit zálohu</button>
    </div>
  </div>
</dialog>

<script>
document.addEventListener('click', function (e) {
  var el = e.target.closest('[data-restore-backup]');
  if (!el) return;
  var filename = el.getAttribute('data-restore-backup');
  document.getElementById('restore-dialog-text').textContent =
    'Obnovit zálohu „' + filename + '“? Přepíše aktuální databázi i doklady. Před obnovou se automaticky vytvoří bezpečnostní záloha. Dokončení vyžaduje restart aplikace (STOP.bat a poté znovu START.bat).';
  document.getElementById('restore-filename').value = filename;
  document.getElementById('restore-dialog').showModal();
});
document.getElementById('restore-confirm-btn').addEventListener('click', function () {
  document.getElementById('restore-form').submit();
});
</script>
