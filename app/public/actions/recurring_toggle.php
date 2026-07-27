<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pravidelne');
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM recurring_payments WHERE id = :id');
$stmt->execute(['id' => $id]);
$rec = $stmt->fetch();

if ($rec) {
    $newState = $rec['is_active'] ? 0 : 1;
    db()->prepare('UPDATE recurring_payments SET is_active = :a WHERE id = :id')->execute(['a' => $newState, 'id' => $id]);
    flash('success', 'Pravidelná platba „' . $rec['name'] . '“ byla ' . ($newState ? 'aktivována' : 'pozastavena') . '.');
}

redirect('pravidelne');
