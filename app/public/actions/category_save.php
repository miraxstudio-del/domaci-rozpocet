<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('kategorie');
}
csrf_check();

$id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
$type = ($_POST['type'] ?? '') === 'prijem' ? 'prijem' : 'vydaj';
$name = trim((string) ($_POST['name'] ?? ''));
$icon = trim((string) ($_POST['icon'] ?? '')) ?: '📦';
$color = trim((string) ($_POST['color'] ?? '')) ?: '#6b7280';
$parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
$limitRaw = str_replace(',', '.', trim((string) ($_POST['monthly_limit'] ?? '')));
$monthlyLimit = ($limitRaw !== '' && is_numeric($limitRaw)) ? (float) $limitRaw : null;
$isActive = !empty($_POST['is_active']) ? 1 : 0;

if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#6b7280';
}
$icon = mb_substr($icon, 0, 4);

if ($name === '') {
    flash('error', 'Zadejte název kategorie.');
    redirect('kategorie', ['type' => $type] + ($id ? ['edit' => $id] : []));
}

// Podkategorie nesmí mít vlastní podkategorie (max. dvě úrovně)
if ($parentId) {
    $parent = get_category_by_id($parentId);
    if (!$parent || $parent['parent_id'] !== null || $parent['type'] !== $type) {
        $parentId = null;
    }
}

$pdo = db();
if ($id) {
    $stmt = $pdo->prepare('
        UPDATE categories SET name = :name, icon = :icon, color = :color, parent_id = :parent_id,
               monthly_limit = :limit, is_active = :active
        WHERE id = :id
    ');
    $stmt->execute([
        'name' => $name, 'icon' => $icon, 'color' => $color, 'parent_id' => $parentId,
        'limit' => $monthlyLimit, 'active' => $isActive, 'id' => $id,
    ]);
    flash('success', 'Kategorie „' . $name . '“ byla upravena.');
} else {
    $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM categories WHERE type = ' . $pdo->quote($type))->fetchColumn();
    $stmt = $pdo->prepare('
        INSERT INTO categories (type, parent_id, name, icon, color, monthly_limit, is_active, is_custom, sort_order)
        VALUES (:type, :parent_id, :name, :icon, :color, :limit, :active, 1, :sort)
    ');
    $stmt->execute([
        'type' => $type, 'parent_id' => $parentId, 'name' => $name, 'icon' => $icon, 'color' => $color,
        'limit' => $monthlyLimit, 'active' => $isActive, 'sort' => $maxSort + 1,
    ]);
    flash('success', 'Kategorie „' . $name . '“ byla vytvořena.');
}

redirect('kategorie', ['type' => $type]);
