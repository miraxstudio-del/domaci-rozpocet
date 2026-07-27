<?php
declare(strict_types=1);

/**
 * Vrátí sdílené PDO připojení k SQLite databázi.
 * Používá WAL režim pro odolnost proti výpadku a zapnuté kontroly cizích klíčů.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $isNew = !file_exists(DB_PATH);

        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');

        if ($isNew) {
            @chmod(DB_PATH, 0666);
        }
    }

    return $pdo;
}
