<?php
declare(strict_types=1);

/**
 * Vytvoří databázové tabulky, pokud ještě neexistují, a při prvním spuštění
 * vloží výchozí české kategorie, způsoby platby a nastavení.
 */
function migrate_database(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            type           TEXT NOT NULL CHECK(type IN ('prijem','vydaj')),
            parent_id      INTEGER NULL REFERENCES categories(id) ON DELETE CASCADE,
            name           TEXT NOT NULL,
            icon           TEXT NOT NULL DEFAULT '💰',
            color          TEXT NOT NULL DEFAULT '#6b7280',
            monthly_limit  REAL NULL,
            is_active      INTEGER NOT NULL DEFAULT 1,
            is_custom      INTEGER NOT NULL DEFAULT 0,
            sort_order     INTEGER NOT NULL DEFAULT 0,
            created_at     TEXT DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS recurring_payments (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT,
            name                TEXT NOT NULL,
            type                TEXT NOT NULL DEFAULT 'vydaj' CHECK(type IN ('prijem','vydaj')),
            amount              REAL NOT NULL,
            category_id         INTEGER NULL REFERENCES categories(id),
            due_day             INTEGER NOT NULL DEFAULT 1,
            frequency           TEXT NOT NULL DEFAULT 'mesicne' CHECK(frequency IN ('tydne','mesicne','ctvrtletne','rocne')),
            start_date          TEXT NOT NULL,
            end_date            TEXT NULL,
            auto_create         INTEGER NOT NULL DEFAULT 1,
            remind_days_before  INTEGER NOT NULL DEFAULT 3,
            payment_method      TEXT NOT NULL DEFAULT 'trvaly_prikaz',
            merchant            TEXT NULL,
            note                TEXT NULL,
            is_business         INTEGER NOT NULL DEFAULT 0,
            is_active           INTEGER NOT NULL DEFAULT 1,
            created_at          TEXT DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            type            TEXT NOT NULL CHECK(type IN ('prijem','vydaj')),
            name            TEXT NOT NULL,
            amount          REAL NOT NULL,
            payment_date    TEXT NOT NULL,
            month_year      TEXT NOT NULL,
            category_id     INTEGER NULL REFERENCES categories(id),
            payment_method  TEXT NOT NULL DEFAULT 'hotovost',
            merchant        TEXT NULL,
            note            TEXT NULL,
            due_date        TEXT NULL,
            status          TEXT NOT NULL DEFAULT 'zaplaceno' CHECK(status IN ('zaplaceno','ceka','po_splatnosti','zruseno','castecne')),
            paid_amount     REAL NULL,
            is_recurring    INTEGER NOT NULL DEFAULT 0,
            recurring_id    INTEGER NULL REFERENCES recurring_payments(id) ON DELETE SET NULL,
            invoice_number  TEXT NULL,
            is_business     INTEGER NOT NULL DEFAULT 0,
            is_transfer     INTEGER NOT NULL DEFAULT 0,
            created_at      TEXT DEFAULT (datetime('now')),
            updated_at      TEXT DEFAULT (datetime('now'))
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transactions_month ON transactions(month_year)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transactions_category ON transactions(category_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transaction_tags (
            transaction_id INTEGER NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
            tag_id         INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
            PRIMARY KEY (transaction_id, tag_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attachments (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id  INTEGER NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
            original_name   TEXT NOT NULL,
            stored_path     TEXT NOT NULL,
            folder          TEXT NOT NULL,
            file_type       TEXT NULL,
            file_size       INTEGER NULL,
            uploaded_at     TEXT DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS budgets (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            month_year      TEXT NOT NULL,
            category_id     INTEGER NULL REFERENCES categories(id) ON DELETE CASCADE,
            planned_amount  REAL NOT NULL,
            UNIQUE(month_year, category_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS months (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            month_year      TEXT UNIQUE NOT NULL,
            is_closed       INTEGER NOT NULL DEFAULT 0,
            closing_note    TEXT NULL,
            planned_income  REAL NULL,
            min_remaining   REAL NULL,
            reserve_amount  REAL NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backups (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            filename    TEXT NOT NULL,
            type        TEXT NOT NULL DEFAULT 'manual' CHECK(type IN ('manual','auto')),
            size        INTEGER NULL,
            created_at  TEXT DEFAULT (datetime('now'))
        )
    ");

    // Výchozí nastavení (pouze pokud ještě neexistuje)
    $defaults = [
        'household_name'      => 'Naše domácnost',
        'currency'             => 'Kč',
        'month_start_day'      => '1',
        'theme'                => 'system',
        'date_format'          => 'd. m. Y',
        'decimal_places'       => '2',
        'confirm_delete'       => '1',
        'show_business'        => '1',
        'auto_backup'          => '1',
        'export_folder'        => 'exporty',
        'db_schema_version'    => '1',
        'last_auto_backup'     => '',
        'demo_data_loaded'     => '0',
        'onboarded'            => '0',
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:k, :v)");
    foreach ($defaults as $k => $v) {
        $stmt->execute(['k' => $k, 'v' => $v]);
    }

    seed_default_categories($pdo);
}

function seed_default_categories(PDO $pdo): void
{
    $already = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($already > 0) {
        return;
    }

    $expenseGroups = [
        'Potraviny' => ['icon' => '🛒', 'color' => '#f59e0b', 'children' => [
            'Chléb a pečivo', 'Rohlíky', 'Maso', 'Ovoce a zelenina', 'Mléčné výrobky',
            'Nápoje', 'Sladkosti', 'Drogerie nakoupená s potravinami', 'Restaurace', 'Rozvoz jídla',
        ]],
        'Bydlení' => ['icon' => '🏠', 'color' => '#3b82f6', 'children' => [
            'Nájem nebo hypotéka', 'Elektřina', 'Plyn', 'Voda', 'Topení', 'Odpad',
            'Fond oprav', 'Pojištění domácnosti', 'Opravy', 'Vybavení domácnosti',
        ]],
        'Telefon a internet' => ['icon' => '📱', 'color' => '#06b6d4', 'children' => [
            'Mobilní telefon', 'Internet', 'Televize', 'Předplatné a online služby',
        ]],
        'Doprava' => ['icon' => '🚗', 'color' => '#8b5cf6', 'children' => [
            'Palivo', 'Servis vozidla', 'Pojištění vozidla', 'Dálniční známka',
            'Parkování', 'Veřejná doprava', 'Taxi', 'Náhradní díly',
        ]],
        'Podnikání' => ['icon' => '💼', 'color' => '#0f766e', 'children' => [
            'Sociální pojištění', 'Zdravotní pojištění', 'Zálohy na daň', 'Účetní',
            'Software', 'Internetové služby', 'Domény a hosting', 'Pracovní vybavení',
            'Reklama', 'Pohonné hmoty', 'Kancelářské potřeby', 'Bankovní poplatky',
        ]],
        'Rodina a děti' => ['icon' => '👨‍👩‍👧', 'color' => '#ec4899', 'children' => [
            'Škola a vzdělávání', 'Učebnice', 'Kroužky', 'Oblečení', 'Hračky',
            'Kapesné', 'Výlety', 'Hlídání',
        ]],
        'Zdraví' => ['icon' => '💊', 'color' => '#ef4444', 'children' => [
            'Léky', 'Lékaři', 'Rehabilitace', 'Zdravotní pomůcky', 'Vitamíny', 'Pojištění',
        ]],
        'Osobní výdaje' => ['icon' => '👤', 'color' => '#a855f7', 'children' => [
            'Oblečení', 'Obuv', 'Kosmetika', 'Kadeřník', 'Osobní péče',
        ]],
        'Volný čas' => ['icon' => '🎉', 'color' => '#22c55e', 'children' => [
            'Kino', 'Kultura', 'Výlety', 'Dovolená', 'Sport', 'Restaurace', 'Oslavy', 'Koníčky',
        ]],
        'Finanční výdaje' => ['icon' => '🏦', 'color' => '#64748b', 'children' => [
            'Splátky úvěrů', 'Pojištění', 'Spoření', 'Investice', 'Bankovní poplatky', 'Převody do rezervy',
        ]],
        'Ostatní' => ['icon' => '📦', 'color' => '#78716c', 'children' => [
            'Dárky', 'Charita', 'Nečekané výdaje', 'Nezařazené položky',
        ]],
    ];

    $incomeCats = [
        'Mzda' => '💰', 'Příjem z podnikání' => '💼', 'Důchod nebo dávky' => '🧾',
        'Rodičovský příspěvek' => '👶', 'Příspěvek na bydlení' => '🏠', 'Vratka' => '↩️',
        'Prodej věci' => '🏷️', 'Dar' => '🎁', 'Investiční příjem' => '📈', 'Ostatní příjem' => '➕',
    ];

    $insertCat = $pdo->prepare("
        INSERT INTO categories (type, parent_id, name, icon, color, is_custom, sort_order)
        VALUES (:type, :parent_id, :name, :icon, :color, 0, :sort_order)
    ");

    $sort = 0;
    foreach ($expenseGroups as $name => $def) {
        $sort++;
        $insertCat->execute([
            'type' => 'vydaj', 'parent_id' => null, 'name' => $name,
            'icon' => $def['icon'], 'color' => $def['color'], 'sort_order' => $sort,
        ]);
        $parentId = (int) $pdo->lastInsertId();
        $childSort = 0;
        foreach ($def['children'] as $child) {
            $childSort++;
            $insertCat->execute([
                'type' => 'vydaj', 'parent_id' => $parentId, 'name' => $child,
                'icon' => $def['icon'], 'color' => $def['color'], 'sort_order' => $childSort,
            ]);
        }
    }

    $sort = 0;
    foreach ($incomeCats as $name => $icon) {
        $sort++;
        $insertCat->execute([
            'type' => 'prijem', 'parent_id' => null, 'name' => $name,
            'icon' => $icon, 'color' => '#16a34a', 'sort_order' => $sort,
        ]);
    }
}
