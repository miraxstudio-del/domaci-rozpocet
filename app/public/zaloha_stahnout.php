<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

$filename = basename((string) ($_GET['f'] ?? ''));
$path = BACKUPS_PATH . DIRECTORY_SEPARATOR . $filename;

if (!$filename || !is_file($path)) {
    http_response_code(404);
    die('Záloha nebyla nalezena.');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
