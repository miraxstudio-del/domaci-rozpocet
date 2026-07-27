<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('kategorie');
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
$cat = $id ? get_category_by_id($id) : null;

if (!$cat) {
    flash('error', 'Kategorie nebyla nalezena.');
    redirect('kategorie');
}

$pdo = db();

$childCount = (int) $pdo->query('SELECT COUNT(*) FROM categories WHERE parent_id = ' . (int) $id)->fetchColumn();
$stmtUse = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE category_id = :id');
$stmtUse->execute(['id' => $id]);
$useCount = (int) $stmtUse->fetchColumn();

if ($childCount > 0) {
    flash('error', 'Kategorii „' . $cat['name'] . '“ nelze smazat, obsahuje podkategorie. Nejprve je odstraňte nebo přesuňte.');
} elseif ($useCount > 0) {
    flash('error', 'Kategorii „' . $cat['name'] . '“ nelze smazat, je použita u ' . $useCount . ' položek. Místo smazání ji můžete deaktivovat (skrýt).');
} else {
    $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
    flash('success', 'Kategorie „' . $cat['name'] . '“ byla odstraněna.');
}

redirect('kategorie', ['type' => $cat['type']]);
