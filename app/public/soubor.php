<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

$id = (int) ($_GET['id'] ?? 0);
$action = ($_GET['action'] ?? 'view') === 'download' ? 'download' : 'view';

$stmt = db()->prepare('SELECT * FROM attachments WHERE id = :id');
$stmt->execute(['id' => $id]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    die('Příloha nebyla nalezena.');
}

// Bezpečnostní kontrola: povolíme přístup pouze k souborům uvnitř uploads/
$full = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . $attachment['stored_path']);
$uploadsReal = realpath(UPLOADS_PATH);
if ($full === false || $uploadsReal === false || strpos($full, $uploadsReal) !== 0 || !is_file($full)) {
    http_response_code(404);
    die('Soubor nebyl na disku nalezen.');
}

$mime = $attachment['file_type'] ?: (function_exists('mime_content_type') ? mime_content_type($full) : 'application/octet-stream');
$disposition = $action === 'download' ? 'attachment' : 'inline';
$filename = str_replace(['"', "\r", "\n"], '', $attachment['original_name']);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full));
header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

readfile($full);
