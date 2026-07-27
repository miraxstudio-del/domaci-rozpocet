<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('zalohy');
}
csrf_check();

$zipPath = null;

if (!empty($_POST['filename'])) {
    $filename = basename((string) $_POST['filename']);
    $candidate = BACKUPS_PATH . DIRECTORY_SEPARATOR . $filename;
    if (is_file($candidate)) {
        $zipPath = $candidate;
    }
} elseif (!empty($_FILES['backup_file']['name'])) {
    if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Nahrání souboru zálohy se nezdařilo.');
        redirect('zalohy');
    }
    $ext = strtolower(pathinfo((string) $_FILES['backup_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'zip') {
        flash('error', 'Záloha musí být ve formátu ZIP.');
        redirect('zalohy');
    }
    $importPath = DATA_PATH . DIRECTORY_SEPARATOR . '_import_upload.zip';
    if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $importPath)) {
        $zipPath = $importPath;
    }
}

if (!$zipPath) {
    flash('error', 'Nebyla vybrána žádná záloha k obnovení.');
    redirect('zalohy');
}

$result = prepare_restore($zipPath);
flash($result['ok'] ? 'success' : 'error', $result['message']);
redirect('zalohy');
