<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('zalohy');
}
csrf_check();

try {
    $result = create_backup('manual');
    flash('success', 'Záloha „' . $result['filename'] . '“ byla vytvořena (' . human_file_size($result['size']) . ').');
} catch (Throwable $e) {
    flash('error', 'Zálohu se nepodařilo vytvořit: ' . $e->getMessage());
}

redirect('zalohy');
