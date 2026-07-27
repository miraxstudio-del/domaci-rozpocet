<?php
declare(strict_types=1);

/**
 * Zálohování a obnova. Obnova databáze se z bezpečnostních důvodů (zamykání
 * souborů na Windows) neprovádí za běhu, ale při příštím startu aplikace -
 * viz kontrola RESTORE_PENDING.json na začátku config.php.
 */

function backup_marker_path(): string
{
    return DATA_PATH . DIRECTORY_SEPARATOR . 'RESTORE_PENDING.json';
}

function backup_staging_path(): string
{
    return DATA_PATH . DIRECTORY_SEPARATOR . '_restore_pending';
}

/** Vytvoří kompletní zálohu databáze a nahraných souborů do data/backups/*.zip */
function create_backup(string $type = 'manual'): array
{
    $pdo = db();
    $tmpDbCopy = DATA_PATH . DIRECTORY_SEPARATOR . '_backup_tmp.sqlite';
    if (file_exists($tmpDbCopy)) {
        @unlink($tmpDbCopy);
    }
    // VACUUM INTO vytvoří konzistentní snímek databáze i za běhu aplikace
    $pdo->exec('VACUUM INTO ' . $pdo->quote($tmpDbCopy));

    $suffix = $type === 'auto' ? '_auto' : '';
    $zipName = 'zaloha_' . date('Y-m-d_His') . $suffix . '.zip';
    $zipPath = BACKUPS_PATH . DIRECTORY_SEPARATOR . $zipName;

    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFile($tmpDbCopy, 'data/rozpocet.sqlite');
    add_directory_to_zip($zip, UPLOADS_PATH, 'uploads');
    $zip->close();
    @unlink($tmpDbCopy);

    $size = filesize($zipPath) ?: 0;
    $stmt = $pdo->prepare('INSERT INTO backups (filename, type, size) VALUES (:f, :t, :s)');
    $stmt->execute(['f' => $zipName, 't' => $type, 's' => $size]);

    if ($type === 'auto') {
        prune_old_auto_backups(14);
    }

    return ['filename' => $zipName, 'path' => $zipPath, 'size' => $size];
}

function add_directory_to_zip(ZipArchive $zip, string $dir, string $zipPrefix): void
{
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $relative = $zipPrefix . '/' . substr($item->getPathname(), strlen($dir) + 1);
        $relative = str_replace('\\', '/', $relative);
        if ($item->isDir()) {
            $zip->addEmptyDir($relative);
        } else {
            $zip->addFile($item->getPathname(), $relative);
        }
    }
}

function prune_old_auto_backups(int $keep): void
{
    $pdo = db();
    $stmt = $pdo->prepare("SELECT filename FROM backups WHERE type = 'auto' ORDER BY created_at DESC");
    $stmt->execute();
    $all = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $toDelete = array_slice($all, $keep);
    foreach ($toDelete as $filename) {
        delete_backup($filename);
    }
}

/** Vrátí seznam záloh (spojení souborů na disku s metadaty v DB) */
function list_backups(): array
{
    $pdo = db();
    $dbRows = [];
    foreach ($pdo->query('SELECT * FROM backups ORDER BY created_at DESC') as $row) {
        $dbRows[$row['filename']] = $row;
    }

    $files = glob(BACKUPS_PATH . DIRECTORY_SEPARATOR . '*.zip') ?: [];
    $result = [];
    foreach ($files as $file) {
        $name = basename($file);
        $meta = $dbRows[$name] ?? null;
        $result[] = [
            'filename' => $name,
            'size' => filesize($file) ?: 0,
            'type' => $meta['type'] ?? (str_contains($name, '_auto') ? 'auto' : 'manual'),
            'created_at' => $meta['created_at'] ?? date('Y-m-d H:i:s', filemtime($file)),
        ];
    }
    usort($result, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return $result;
}

function delete_backup(string $filename): bool
{
    $filename = basename($filename);
    $path = BACKUPS_PATH . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
    db()->prepare('DELETE FROM backups WHERE filename = :f')->execute(['f' => $filename]);
    return true;
}

/**
 * Připraví obnovu ze zadaného ZIP souboru. Obnova se dokončí až při příštím
 * spuštění aplikace (viz začátek config.php), aby nedošlo ke kolizi se
 * zamčenými soubory za běhu.
 * @return array{ok:bool, message:string}
 */
function prepare_restore(string $zipPath): array
{
    if (!is_file($zipPath)) {
        return ['ok' => false, 'message' => 'Soubor zálohy nebyl nalezen.'];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['ok' => false, 'message' => 'Soubor není platný ZIP archiv.'];
    }
    if ($zip->locateName('data/rozpocet.sqlite') === false) {
        $zip->close();
        return ['ok' => false, 'message' => 'Záloha neobsahuje databázi (data/rozpocet.sqlite) - jde o neplatný soubor zálohy.'];
    }

    // Bezpečnostní záloha aktuálního stavu před obnovou
    create_backup('manual');

    $staging = backup_staging_path();
    if (is_dir($staging)) {
        remove_directory($staging);
    }
    mkdir($staging, 0777, true);
    $zip->extractTo($staging);
    $zip->close();

    file_put_contents(backup_marker_path(), json_encode([
        'staging' => $staging,
        'prepared_at' => date('c'),
        'source' => basename($zipPath),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return ['ok' => true, 'message' => 'Záloha byla připravena k obnovení. Restartujte aplikaci (STOP.bat a poté znovu START.bat) - obnova se dokončí automaticky při příštím spuštění.'];
}

/**
 * Provede naplánovanou obnovu, pokud existuje marker soubor. Volá se
 * na začátku config.php PŘED navázáním databázového připojení.
 */
function apply_pending_restore_if_any(): void
{
    $marker = backup_marker_path();
    if (!is_file($marker)) {
        return;
    }

    $info = json_decode((string) file_get_contents($marker), true);
    $staging = $info['staging'] ?? backup_staging_path();

    $stagedDb = $staging . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'rozpocet.sqlite';
    $liveDb = DATA_PATH . DIRECTORY_SEPARATOR . 'rozpocet.sqlite';

    if (is_file($stagedDb)) {
        foreach ([$liveDb, $liveDb . '-wal', $liveDb . '-shm'] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @copy($stagedDb, $liveDb);
    }

    $stagedUploads = $staging . DIRECTORY_SEPARATOR . 'uploads';
    if (is_dir($stagedUploads)) {
        $liveUploads = dirname(DATA_PATH) . DIRECTORY_SEPARATOR . 'uploads';
        remove_directory($liveUploads);
        copy_directory($stagedUploads, $liveUploads);
    }

    remove_directory($staging);
    @unlink($marker);
}

function remove_directory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($dir);
}

function copy_directory(string $src, string $dst): void
{
    if (!is_dir($dst)) {
        mkdir($dst, 0777, true);
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $target = $dst . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($src) + 1);
        if ($item->isDir()) {
            if (!is_dir($target)) mkdir($target, 0777, true);
        } else {
            copy($item->getPathname(), $target);
        }
    }
}
