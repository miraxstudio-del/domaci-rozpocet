<dialog id="delete-transaction-dialog">
  <form method="post" action="/actions/transaction_delete.php">
    <div class="dialog-body">
      <h3>Odstranit položku?</h3>
      <p class="text-muted" id="delete-transaction-name">Opravdu chcete tuto položku odstranit? Tuto akci nelze vrátit zpět.</p>
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="delete-transaction-id" value="">
      <label class="checkbox-row" id="delete-files-row" style="display:none;">
        <input type="checkbox" name="delete_files" value="1" checked>
        Odstranit také přiložené soubory (účtenky, faktury) z disku
      </label>
      <div class="dialog-actions">
        <button type="button" class="btn secondary" onclick="document.getElementById('delete-transaction-dialog').close()">Zrušit</button>
        <button type="submit" class="btn danger">Odstranit</button>
      </div>
    </div>
  </form>
</dialog>
