<?php
$pageTitle = 'Kategorie';
$activeNav = 'nastaveni';

$type = ($_GET['type'] ?? 'vydaj') === 'prijem' ? 'prijem' : 'vydaj';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editCat = $editId ? get_category_by_id($editId) : null;
$parentPreset = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : null;

$tree = get_categories_tree($type, false);

$formType = $editCat['type'] ?? $type;
$formName = $editCat['name'] ?? '';
$formIcon = $editCat['icon'] ?? '📦';
$formColor = $editCat['color'] ?? '#6b7280';
$formParent = $editCat['parent_id'] ?? $parentPreset;
$formLimit = $editCat['monthly_limit'] ?? '';
$formActive = $editCat ? (bool) $editCat['is_active'] : true;
?>
<div class="topbar">
  <div>
    <h1>🏷️ Kategorie</h1>
    <div class="subtitle">Spravujte kategorie a podkategorie příjmů a výdajů</div>
  </div>
  <a class="btn outline" href="/index.php?p=nastaveni">← Zpět do nastavení</a>
</div>

<div class="pill-nav">
  <a class="<?= $type === 'vydaj' ? 'active' : '' ?>" href="/index.php?p=kategorie&type=vydaj">🧾 Výdajové kategorie</a>
  <a class="<?= $type === 'prijem' ? 'active' : '' ?>" href="/index.php?p=kategorie&type=prijem">💰 Příjmové kategorie</a>
</div>

<div class="grid" style="grid-template-columns: 1.6fr 1fr; align-items:flex-start; gap:16px;">
<div>
  <?php if (!$tree['parents']): ?>
    <div class="card"><div class="empty-state"><div class="ic">🏷️</div><p>Zatím žádné kategorie tohoto typu.</p></div></div>
  <?php endif; ?>
  <?php foreach ($tree['parents'] as $parent): ?>
    <div class="card" style="<?= $parent['is_active'] ? '' : 'opacity:.55;' ?>">
      <div class="card-title-row">
        <h3><span class="dot" style="background:<?= h($parent['color']) ?>"></span>&nbsp;<?= h($parent['icon'] . ' ' . $parent['name']) ?>
          <?php if (!$parent['is_active']): ?><span class="badge">skryto</span><?php endif; ?>
          <?php if ($parent['monthly_limit']): ?><span class="badge warning">limit <?= format_money((float) $parent['monthly_limit'], 0) ?></span><?php endif; ?>
        </h3>
        <div class="btn-row">
          <a class="btn secondary sm" href="/index.php?p=kategorie&type=<?= $type ?>&parent_id=<?= (int) $parent['id'] ?>">+ Podkategorie</a>
          <a class="btn secondary sm icon-only" href="/index.php?p=kategorie&type=<?= $type ?>&edit=<?= (int) $parent['id'] ?>">✏️</a>
          <form method="post" action="/actions/category_delete.php" style="display:inline">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $parent['id'] ?>">
            <button class="btn danger sm icon-only" data-confirm="Odstranit kategorii „<?= h($parent['name']) ?>“?">🗑️</button>
          </form>
        </div>
      </div>
      <?php if (!empty($tree['children'][$parent['id']])): ?>
        <div class="table-wrap">
          <table>
            <tbody>
              <?php foreach ($tree['children'][$parent['id']] as $child): ?>
                <tr style="<?= $child['is_active'] ? '' : 'opacity:.55;' ?>">
                  <td><?= h($child['name']) ?> <?= !$child['is_active'] ? '<span class="badge">skryto</span>' : '' ?></td>
                  <td class="text-right"><?= $child['monthly_limit'] ? format_money((float) $child['monthly_limit'], 0) : '' ?></td>
                  <td class="text-right" style="width:90px;">
                    <div class="btn-row" style="justify-content:flex-end;flex-wrap:nowrap;">
                      <a class="btn secondary sm icon-only" href="/index.php?p=kategorie&type=<?= $type ?>&edit=<?= (int) $child['id'] ?>">✏️</a>
                      <form method="post" action="/actions/category_delete.php" style="display:inline">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $child['id'] ?>">
                        <button class="btn danger sm icon-only" data-confirm="Odstranit podkategorii „<?= h($child['name']) ?>“?">🗑️</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-faint" style="font-size:13px;">Zatím žádné podkategorie.</p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<div class="card" style="position:sticky; top:20px;">
  <h3><?= $editCat ? 'Upravit kategorii' : ($parentPreset ? 'Přidat podkategorii' : 'Přidat hlavní kategorii') ?></h3>
  <form method="post" action="/actions/category_save.php">
    <?= csrf_field() ?>
    <?php if ($editCat): ?><input type="hidden" name="id" value="<?= (int) $editCat['id'] ?>"><?php endif; ?>
    <input type="hidden" name="type" value="<?= h($formType) ?>">
    <div class="form-grid" style="grid-template-columns:1fr;">
      <div class="field">
        <label>Nadřazená kategorie</label>
        <select name="parent_id">
          <option value="">— hlavní kategorie (bez rodiče) —</option>
          <?php foreach ($tree['parents'] as $p): if ($editCat && (int) $editCat['id'] === (int) $p['id']) continue; ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) $formParent === (int) $p['id'] ? 'selected' : '' ?>><?= h($p['icon'] . ' ' . $p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Název *</label>
        <input type="text" name="name" required maxlength="80" value="<?= h($formName) ?>">
      </div>
      <div class="field">
        <label>Ikona (emoji)</label>
        <input type="text" name="icon" maxlength="4" value="<?= h($formIcon) ?>">
      </div>
      <div class="field">
        <label>Barva</label>
        <input type="color" name="color" value="<?= h($formColor) ?>">
      </div>
      <div class="field">
        <label>Měsíční limit (Kč, nepovinné)</label>
        <input type="number" step="1" name="monthly_limit" value="<?= h((string) $formLimit) ?>">
      </div>
      <div class="field">
        <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?= $formActive ? 'checked' : '' ?>> Aktivní (zobrazovat ve výběru)</label>
      </div>
    </div>
    <div class="btn-row" style="margin-top:16px;">
      <button class="btn" type="submit">💾 Uložit</button>
      <?php if ($editCat): ?><a class="btn outline" href="/index.php?p=kategorie&type=<?= $type ?>">Zrušit úpravu</a><?php endif; ?>
    </div>
  </form>
</div>
</div>
