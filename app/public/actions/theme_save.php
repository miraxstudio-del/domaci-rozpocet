<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$theme = (string) ($_POST['theme'] ?? 'system');
if (!in_array($theme, ['light', 'dark', 'system'], true)) {
    $theme = 'system';
}
set_setting('theme', $theme);

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
