<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

$allowedPages = [
    'prehled', 'polozky', 'polozka', 'pravidelne', 'pravidelna', 'rozpocty',
    'doklady', 'statistiky', 'mesice', 'export', 'zalohy', 'nastaveni', 'kategorie',
];

$page = $_GET['p'] ?? 'prehled';
if (!in_array($page, $allowedPages, true)) {
    $page = 'prehled';
}

$pageFile = SRC_PATH . '/views/pages/' . $page . '.php';

$pageTitle = APP_NAME;
$activeNav = $page;

ob_start();
if (is_file($pageFile)) {
    include $pageFile;
} else {
    echo '<div class="empty-state"><div class="ic">🔍</div><h3>Stránka nenalezena</h3></div>';
}
$content = ob_get_clean();

include SRC_PATH . '/views/layout.php';
