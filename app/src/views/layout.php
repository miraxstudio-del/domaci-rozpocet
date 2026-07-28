<?php
/** @var string $content */
/** @var string $pageTitle */
/** @var string $activeNav */
$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
$assetsPath = dirname(__DIR__, 2) . '/public/assets';
$styleVersion = (string) (filemtime($assetsPath . '/css/style.css') ?: 1);
$chartsVersion = (string) (filemtime($assetsPath . '/js/charts.js') ?: 1);
$appVersion = (string) (filemtime($assetsPath . '/js/app.js') ?: 1);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?> · <?= h(APP_NAME) ?></title>
<link rel="stylesheet" href="/assets/css/style.css?v=<?= h($styleVersion) ?>">
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
<script src="/assets/js/charts.js?v=<?= h($chartsVersion) ?>"></script>
<script src="/assets/js/app.js?v=<?= h($appVersion) ?>"></script>
</body>
</html>
