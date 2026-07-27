<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('polozky');
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
if (!$id) {
    redirect('polozky');
}

$tx = get_transaction($id);
if (!$tx) {
    flash('error', 'Položka nebyla nalezena.');
    redirect('polozky');
}

$deleteFiles = !empty($_POST['delete_files']);
$attachments = get_attachments($id);

$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->prepare('DELETE FROM transactions WHERE id = :id')->execute(['id' => $id]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error', 'Odstranění se nezdařilo: ' . $e->getMessage());
    redirect('polozka', ['id' => $id]);
}

// Databázové záznamy příloh se smazaly kaskádově (ON DELETE CASCADE); soubory na disku smažeme volitelně
if ($deleteFiles || $attachments) {
    foreach ($attachments as $a) {
        delete_attachment_file($a);
    }
}

flash('success', 'Položka „' . $tx['name'] . '“ byla odstraněna.');
redirect('polozky');
