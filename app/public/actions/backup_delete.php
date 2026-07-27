<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('zalohy');
}
csrf_check();

$filename = basename((string) ($_POST['filename'] ?? ''));
if ($filename) {
    delete_backup($filename);
    flash('success', 'Záloha byla odstraněna.');
}

redirect('zalohy');
