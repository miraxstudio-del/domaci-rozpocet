<?php
/**
 * Základní konfigurace aplikace Domácí rozpočet.
 * Všechny cesty jsou odvozené od umístění tohoto souboru (__DIR__),
 * takže aplikace funguje po přesunutí do libovolné složky nebo počítače.
 */

declare(strict_types=1);

error_reporting(E_ALL & ~E_DEPRECATED);

// Kořen projektu = o dvě úrovně výš než app/src
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('SRC_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'src');
define('DATA_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'data');
define('BACKUPS_PATH', DATA_PATH . DIRECTORY_SEPARATOR . 'backups');
define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('EXPORTS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'exporty');
define('DB_PATH', DATA_PATH . DIRECTORY_SEPARATOR . 'rozpocet.sqlite');
define('SESSIONS_PATH', DATA_PATH . DIRECTORY_SEPARATOR . 'sessions');

// Zajistíme, že potřebné složky existují (i po přenosu na jiný počítač)
foreach ([
    DATA_PATH,
    BACKUPS_PATH,
    SESSIONS_PATH,
    UPLOADS_PATH,
    UPLOADS_PATH . DIRECTORY_SEPARATOR . 'uctenky',
    UPLOADS_PATH . DIRECTORY_SEPARATOR . 'faktury',
    UPLOADS_PATH . DIRECTORY_SEPARATOR . 'smlouvy',
    UPLOADS_PATH . DIRECTORY_SEPARATOR . 'ostatni',
    EXPORTS_PATH,
] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');

session_save_path(SESSIONS_PATH);
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

const APP_NAME = 'Domácí rozpočet';
const APP_VERSION = '1.00';
const ALLOWED_UPLOAD_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'doc', 'docx', 'xls', 'xlsx'];
const UPLOAD_FOLDERS = ['uctenky' => 'Účtenky', 'faktury' => 'Faktury', 'smlouvy' => 'Smlouvy', 'ostatni' => 'Ostatní'];

// Zálohy musí být vyřešené DŘÍVE, než se otevře databázové připojení -
// pokud čeká naplánovaná obnova ze zálohy, provede se teď (viz lib/backup.php)
require_once SRC_PATH . '/lib/backup.php';
apply_pending_restore_if_any();

require_once SRC_PATH . '/db.php';
require_once SRC_PATH . '/functions.php';
require_once SRC_PATH . '/migrate.php';
require_once SRC_PATH . '/queries.php';
require_once SRC_PATH . '/lib/uploads.php';
require_once SRC_PATH . '/lib/xlsx.php';

// Migrace databáze - bezpečné opakované volání (CREATE TABLE IF NOT EXISTS)
migrate_database(db());

// Automatické vytvoření položek z aktivních pravidelných plateb pro aktuální měsíc
if (!is_month_closed(current_month_year())) {
    ensure_recurring_instances_for_month(current_month_year());
}

// Automatická denní záloha (pokud je zapnutá v nastavení)
if (get_setting('auto_backup', '1') === '1' && get_setting('last_auto_backup', '') !== date('Y-m-d')) {
    try {
        create_backup('auto');
        set_setting('last_auto_backup', date('Y-m-d'));
    } catch (Throwable $e) {
        // Záloha se nezdařila (např. plný disk) - aplikace pokračuje dál, nejde o kritickou chybu
        error_log('Automatická záloha selhala: ' . $e->getMessage());
    }
}
