<?php
declare(strict_types=1);

/** Seznam způsobů platby v českém jazyce - doplněný o vlastní způsoby z nastavení */
function payment_methods(): array
{
    $methods = [
        'hotovost'        => 'Hotovost',
        'karta'           => 'Platební karta',
        'prevod'          => 'Bankovní převod',
        'inkaso'          => 'Inkaso',
        'trvaly_prikaz'   => 'Trvalý příkaz',
        'online'          => 'Online platba',
        'jiny'            => 'Jiný způsob',
    ];
    $custom = array_filter(array_map('trim', explode(',', get_setting('custom_payment_methods', ''))));
    foreach ($custom as $name) {
        $key = 'vlastni_' . substr(preg_replace('/[^a-z0-9]+/', '', strtolower($name)), 0, 20);
        if ($key !== 'vlastni_' && !isset($methods[$key])) {
            $methods[$key] = $name;
        }
    }
    return $methods;
}

/** Seznam stavů platby v českém jazyce */
function payment_statuses(): array
{
    return [
        'zaplaceno'      => 'Zaplaceno',
        'ceka'           => 'Čeká na zaplacení',
        'po_splatnosti'  => 'Po splatnosti',
        'zruseno'        => 'Zrušeno',
        'castecne'       => 'Částečně zaplaceno',
    ];
}

function frequency_labels(): array
{
    return [
        'tydne'      => 'Týdně',
        'mesicne'    => 'Měsíčně',
        'ctvrtletne' => 'Čtvrtletně',
        'rocne'      => 'Ročně',
    ];
}

/** Formátování částky do českého formátu, např. 12 490,50 Kč */
function format_money(float $amount, ?int $decimals = null): string
{
    if ($decimals === null) {
        $decimals = (int) get_setting('decimal_places', '2');
    }
    $formatted = number_format($amount, $decimals, ',', ' ');
    return $formatted . ' ' . get_setting('currency', 'Kč');
}

/** Formátování data podle formátu zvoleného v nastavení (výchozí: 27. 7. 2026) */
function format_date_cz(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }
    $format = get_setting('date_format', 'j. n. Y');
    return date($format, $ts);
}

/** Dostupné formáty data pro nastavení: [formát => ukázka] */
function date_format_options(): array
{
    $sample = mktime(0, 0, 0, 7, 27, 2026);
    $formats = ['j. n. Y', 'd.m.Y', 'Y-m-d'];
    $out = [];
    foreach ($formats as $f) {
        $out[$f] = date($f, $sample);
    }
    return $out;
}

function format_date_short_cz(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }
    return date('j. n.', $ts);
}

$GLOBALS['__cz_months'] = [
    1 => 'leden', 2 => 'únor', 3 => 'březen', 4 => 'duben', 5 => 'květen', 6 => 'červen',
    7 => 'červenec', 8 => 'srpen', 9 => 'září', 10 => 'říjen', 11 => 'listopad', 12 => 'prosinec',
];

function month_name_cz(int $month): string
{
    return $GLOBALS['__cz_months'][$month] ?? (string) $month;
}

function month_year_label(string $monthYear): string
{
    [$y, $m] = array_map('intval', explode('-', $monthYear . '-01'));
    return ucfirst(month_name_cz($m)) . ' ' . $y;
}

/** Popisek období - funguje pro měsíc ("YYYY-MM") i celý rok ("YYYY") */
function period_label(string $period): string
{
    if (preg_match('/^\d{4}$/', $period)) {
        return 'Rok ' . $period;
    }
    return month_year_label($period);
}

function current_month_year(): string
{
    return date('Y-m');
}

/**
 * Spočítá, do kterého měsíce položka standardně spadá, s ohledem na nastavený
 * "první den měsíčního období" (např. pokud rodina účtuje období od 15. do 14.).
 * Jde jen o výchozí návrh - měsíc lze ve formuláři vždy ručně přepsat.
 */
function month_year_for_date(string $date): string
{
    $startDay = max(1, min(28, (int) get_setting('month_start_day', '1')));
    $ts = strtotime($date);
    if ($ts === false) {
        return current_month_year();
    }
    if ($startDay <= 1) {
        return date('Y-m', $ts);
    }
    $day = (int) date('j', $ts);
    $monthYear = date('Y-m', $ts);
    return $day < $startDay ? shift_month($monthYear, -1) : $monthYear;
}

/** Cesta ke složce pro export (dle nastavení, výchozí "exporty" v kořeni aplikace) */
function get_export_dir(): string
{
    $folder = trim(get_setting('export_folder', 'exporty')) ?: 'exporty';
    $folder = preg_replace('/[^a-zA-Z0-9 _\-]/', '', $folder) ?: 'exporty';
    $path = ROOT_PATH . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($path)) {
        @mkdir($path, 0777, true);
    }
    return $path;
}

function shift_month(string $monthYear, int $delta): string
{
    $ts = strtotime($monthYear . '-01');
    $ts = strtotime(($delta >= 0 ? "+$delta months" : $delta . ' months'), $ts);
    return date('Y-m', $ts);
}

/** Načte hodnotu nastavení z DB */
function get_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (key, value) VALUES (:k, :v)
        ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute(['k' => $key, 'v' => $value]);
}

/** Bezpečný výstup HTML */
function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Přesměrování na jinou stránku aplikace */
function redirect(string $page, array $params = []): void
{
    $params['p'] = $page;
    header('Location: /index.php?' . http_build_query($params));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Vrátí všechny kategorie seskupené podle rodiče pro daný typ */
function get_categories(string $type, bool $onlyActive = true): array
{
    $sql = 'SELECT * FROM categories WHERE type = :type';
    if ($onlyActive) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY parent_id IS NOT NULL, sort_order, name';
    $stmt = db()->prepare($sql);
    $stmt->execute(['type' => $type]);
    return $stmt->fetchAll();
}

/** Vrátí kategorie stromově: [parent => [...], 'children' => [parent_id => [...]]] */
function get_categories_tree(string $type, bool $onlyActive = true): array
{
    $all = get_categories($type, $onlyActive);
    $parents = [];
    $children = [];
    foreach ($all as $cat) {
        if ($cat['parent_id'] === null) {
            $parents[] = $cat;
        } else {
            $children[$cat['parent_id']][] = $cat;
        }
    }
    return ['parents' => $parents, 'children' => $children];
}

function get_category_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function category_full_name(?int $id): string
{
    if (!$id) {
        return 'Nezařazeno';
    }
    $cat = get_category_by_id($id);
    if (!$cat) {
        return 'Nezařazeno';
    }
    if ($cat['parent_id']) {
        $parent = get_category_by_id((int) $cat['parent_id']);
        if ($parent) {
            return $parent['name'] . ' › ' . $cat['name'];
        }
    }
    return $cat['name'];
}

/** Vygeneruje bezpečný jedinečný název souboru a zachová příponu */
function safe_unique_filename(string $originalName): string
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    $base = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    return $ext ? "$base.$ext" : $base;
}

function human_file_size(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

/** CSRF ochrana - jednoduchý token pro formuláře v rámci lokální aplikace */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        die('Neplatný bezpečnostní token. Načtěte prosím stránku znovu.');
    }
}

/**
 * Vykreslí <select name="category_id"> se skupinami (optgroup) podle hlavní kategorie.
 * Pokud je $onlyType null, zahrne oba typy a označí optgroup atributem data-type
 * pro JS filtrování podle zvoleného typu položky (příjem/výdaj).
 */
function category_select_html(?int $selectedId, ?string $onlyType = null, string $id = 'category_id', string $name = 'category_id'): string
{
    $types = $onlyType ? [$onlyType] : ['vydaj', 'prijem'];
    $out = '<select id="' . h($id) . '" name="' . h($name) . '">';
    $out .= '<option value="">— Nezařazeno —</option>';
    foreach ($types as $type) {
        $tree = get_categories_tree($type, false);
        foreach ($tree['parents'] as $parent) {
            if (!$parent['is_active']) continue;
            $label = $parent['icon'] . ' ' . $parent['name'];
            $out .= '<optgroup label="' . h($label) . '"' . ($onlyType ? '' : ' data-type="' . h($type) . '"') . '>';
            $selAttrParent = ((int) $parent['id'] === $selectedId) ? ' selected' : '';
            $out .= '<option value="' . (int) $parent['id'] . '"' . $selAttrParent . '>' . h($parent['name']) . ' (obecně)</option>';
            foreach ($tree['children'][$parent['id']] ?? [] as $child) {
                if (!$child['is_active']) continue;
                $selAttr = ((int) $child['id'] === $selectedId) ? ' selected' : '';
                $out .= '<option value="' . (int) $child['id'] . '"' . $selAttr . '>' . h($child['name']) . '</option>';
            }
            $out .= '</optgroup>';
        }
    }
    $out .= '</select>';
    return $out;
}

/** Validovaná kategorická paleta (CVD-safe pořadí), shodná s CHART_PALETTE v charts.js */
const CHART_PALETTE = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];

function palette_color(int $i): string
{
    return CHART_PALETTE[$i % count(CHART_PALETTE)];
}

/** Vykreslí jednoduchou legendu ke grafu: [['label'=>'', 'color'=>'', 'value'=>'']] */
function chart_legend_html(array $items): string
{
    $out = '<div style="display:flex;flex-wrap:wrap;gap:10px 16px;margin-top:10px;">';
    foreach ($items as $it) {
        $out .= '<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;">';
        $out .= '<span class="dot" style="background:' . h($it['color']) . '"></span>';
        $out .= '<span class="text-muted">' . h($it['label']) . '</span>';
        if (isset($it['value'])) {
            $out .= '<strong class="mono">' . h($it['value']) . '</strong>';
        }
        $out .= '</div>';
    }
    $out .= '</div>';
    return $out;
}

/** Vrátí popisný název měsíce pro select input, klíč YYYY-MM */
function month_options(int $back = 24, int $forward = 3): array
{
    $options = [];
    $current = current_month_year();
    for ($i = -$back; $i <= $forward; $i++) {
        $my = shift_month($current, $i);
        $options[$my] = month_year_label($my);
    }
    return $options;
}
