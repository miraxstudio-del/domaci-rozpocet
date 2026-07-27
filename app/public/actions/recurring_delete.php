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
    db()->prepare('DELETE FROM recurring_payments WHERE id = :id')->execute(['id' => $id]);
    flash('success', 'Pravidelná platba „' . $rec['name'] . '“ byla odstraněna. Již vytvořené položky zůstávají zachovány.');
} else {
    flash('error', 'Pravidelná platba nebyla nalezena.');
}

redirect('pravidelne');
