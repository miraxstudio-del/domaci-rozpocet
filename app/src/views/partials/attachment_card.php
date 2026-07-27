<?php
/** @var array $a jeden řádek z tabulky attachments (musí být v proměnné $a) */
$ext = strtolower(pathinfo($a['stored_path'], PATHINFO_EXTENSION));
$isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
?>
<div class="attach-card">
  <a href="/soubor.php?action=view&id=<?= (int) $a['id'] ?>" target="_blank" class="attach-thumb">
    <?php if ($isImage): ?>
      <img src="/soubor.php?action=view&id=<?= (int) $a['id'] ?>" alt="<?= h($a['original_name']) ?>" loading="lazy">
    <?php elseif ($ext === 'pdf'): ?>
      📄
    <?php else: ?>
      🗂️
    <?php endif; ?>
  </a>
  <div class="attach-info">
    <div class="name" title="<?= h($a['original_name']) ?>"><?= h($a['original_name']) ?></div>
    <div class="meta"><?= h(UPLOAD_FOLDERS[$a['folder']] ?? $a['folder']) ?> · <?= human_file_size((int) $a['file_size']) ?></div>
  </div>
  <div class="attach-actions">
    <a class="btn secondary sm" href="/soubor.php?action=download&id=<?= (int) $a['id'] ?>">⬇️</a>
    <form method="post" action="/actions/attachment_delete.php" style="display:inline">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
      <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">
      <button class="btn danger sm" data-confirm="Odstranit tuto přílohu?">🗑️</button>
    </form>
  </div>
</div>
