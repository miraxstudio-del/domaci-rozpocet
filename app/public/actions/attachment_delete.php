<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('doklady');
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
$return = (string) ($_POST['return'] ?? '');

$stmt = db()->prepare('SELECT * FROM attachments WHERE id = :id');
$stmt->execute(['id' => $id]);
$attachment = $stmt->fetch();

if ($attachment) {
    db()->prepare('DELETE FROM attachments WHERE id = :id')->execute(['id' => $id]);
    delete_attachment_file($attachment);
    flash('success', 'Příloha „' . $attachment['original_name'] . '“ byla odstraněna.');
} else {
    flash('error', 'Příloha nebyla nalezena.');
}

if ($return && str_starts_with($return, '/index.php?')) {
    header('Location: ' . $return);
    exit;
}
redirect('doklady');
