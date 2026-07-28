<?php
/** @var string $content */
/** @var string $pageTitle */
/** @var string $activeNav */
$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?> · <?= h(APP_NAME) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<script>
(function () {
  var saved = localStorage.getItem('theme') || 'system';
  if (saved !== 'system') {
    document.documentElement.setAttribute('data-theme', saved);
  }
})();
</script>
</head>
<body>
<div class="app-shell">
  <button class="sidebar-backdrop" type="button" data-sidebar-close tabindex="-1" aria-label="Zavřít navigační menu"></button>
  <?php include __DIR__ . '/partials/nav.php'; ?>
  <main class="main">
    <div class="mobile-app-header">
      <button class="mobile-menu-toggle" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Otevřít navigační menu">
        <span></span><span></span><span></span>
      </button>
      <a class="mobile-app-brand" href="/index.php?p=prehled" aria-label="Domácí rozpočet – Přehled">
        <span aria-hidden="true">🏠</span> Domácí rozpočet
      </a>
    </div>
    <?php foreach (get_flashes() as $f): ?>
      <div class="flash <?= h($f['type']) ?>"><?= h($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
    <?php include __DIR__ . '/partials/footer.php'; ?>
  </main>
</div>
<script>window.APP_SETTINGS = { confirmDelete: <?= get_setting('confirm_delete', '1') === '1' ? 'true' : 'false' ?> };</script>
<?php include __DIR__ . '/partials/delete_dialog.php'; ?>
<script src="/assets/js/charts.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
