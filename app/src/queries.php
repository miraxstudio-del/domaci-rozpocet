<?php
declare(strict_types=1);

/**
 * Sdílené databázové dotazy používané napříč stránkami (přehled, statistiky, měsíce).
 */

/**
 * Vrátí [SQL podmínku, hodnotu parametru] pro dané období. Období je buď
 * konkrétní měsíc "YYYY-MM" (přesná shoda), nebo celý rok "YYYY" (LIKE "YYYY-%").
 */
function period_condition(string $period, string $column = 'month_year'): array
{
    if (preg_match('/^\d{4}$/', $period)) {
        return ["$column LIKE :period", $period . '-%'];
    }
    return ["$column = :period", $period];
}

/** Posune období (měsíc nebo rok) o daný počet jednotek */
function shift_period(string $period, int $delta): string
{
    if (preg_match('/^\d{4}$/', $period)) {
        return (string) ((int) $period + $delta);
    }
    return shift_month($period, $delta);
}

function is_year_period(string $period): bool
{
    return (bool) preg_match('/^\d{4}$/', $period);
}

/**
 * Souhrn hospodaření za dané období - měsíc ("YYYY-MM") nebo celý rok ("YYYY").
 */
function month_summary(string $period): array
{
    $pdo = db();
    [$cond, $paramVal] = period_condition($period);

    $base = "SELECT COALESCE(SUM(amount),0) FROM transactions
              WHERE $cond AND status != 'zruseno' AND is_transfer = 0 AND type = :t";

    $stIncome = $pdo->prepare($base);
    $stIncome->execute(['period' => $paramVal, 't' => 'prijem']);
    $income = (float) $stIncome->fetchColumn();

    $stExpense = $pdo->prepare($base);
    $stExpense->execute(['period' => $paramVal, 't' => 'vydaj']);
    $expense = (float) $stExpense->fetchColumn();

    $stRegular = $pdo->prepare($base . " AND is_recurring = 1");
    $stRegular->execute(['period' => $paramVal, 't' => 'vydaj']);
    $regular = (float) $stRegular->fetchColumn();

    $stOnetime = $pdo->prepare($base . " AND is_recurring = 0");
    $stOnetime->execute(['period' => $paramVal, 't' => 'vydaj']);
    $onetime = (float) $stOnetime->fetchColumn();

    $stUnpaid = $pdo->prepare("
        SELECT COALESCE(SUM(CASE WHEN status = 'castecne' THEN amount - COALESCE(paid_amount,0) ELSE amount END),0)
        FROM transactions
        WHERE $cond AND type = 'vydaj' AND is_transfer = 0 AND status IN ('ceka','po_splatnosti','castecne')
    ");
    $stUnpaid->execute(['period' => $paramVal]);
    $unpaid = (float) $stUnpaid->fetchColumn();

    $stOverdue = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE $cond AND status = 'po_splatnosti'");
    $stOverdue->execute(['period' => $paramVal]);
    $overdueCount = (int) $stOverdue->fetchColumn();

    $stAttach = $pdo->prepare("
        SELECT COUNT(*) FROM attachments a
        JOIN transactions t ON t.id = a.transaction_id
        WHERE " . str_replace('month_year', 't.month_year', $cond) . "
    ");
    $stAttach->execute(['period' => $paramVal]);
    $attachCount = (int) $stAttach->fetchColumn();

    $stBiggest = $pdo->prepare("
        SELECT * FROM transactions
        WHERE $cond AND type = 'vydaj' AND status != 'zruseno' AND is_transfer = 0
        ORDER BY amount DESC LIMIT 1
    ");
    $stBiggest->execute(['period' => $paramVal]);
    $biggest = $stBiggest->fetch() ?: null;

    $stTopCat = $pdo->prepare("
        SELECT category_id, SUM(amount) total, COUNT(*) cnt
        FROM transactions
        WHERE $cond AND type = 'vydaj' AND status != 'zruseno' AND is_transfer = 0 AND category_id IS NOT NULL
        GROUP BY category_id ORDER BY total DESC LIMIT 1
    ");
    $stTopCat->execute(['period' => $paramVal]);
    $topCategory = $stTopCat->fetch() ?: null;

    $prevPeriod = shift_period($period, -1);
    [$prevCond, $prevParamVal] = period_condition($prevPeriod);
    $prevBase = "SELECT COALESCE(SUM(amount),0) FROM transactions
              WHERE $prevCond AND status != 'zruseno' AND is_transfer = 0 AND type = :t";
    $stPrev = $pdo->prepare($prevBase);
    $stPrev->execute(['period' => $prevParamVal, 't' => 'vydaj']);
    $prevExpense = (float) $stPrev->fetchColumn();
    $stPrevInc = $pdo->prepare($prevBase);
    $stPrevInc->execute(['period' => $prevParamVal, 't' => 'prijem']);
    $prevIncome = (float) $stPrevInc->fetchColumn();

    return [
        'income' => $income,
        'expense' => $expense,
        'remaining' => $income - $expense,
        'regular' => $regular,
        'onetime' => $onetime,
        'unpaid' => $unpaid,
        'overdue_count' => $overdueCount,
        'attachments_count' => $attachCount,
        'biggest_expense' => $biggest,
        'top_category' => $topCategory,
        'prev_month' => $prevPeriod,
        'prev_income' => $prevIncome,
        'prev_expense' => $prevExpense,
        'expense_delta_pct' => $prevExpense > 0 ? (($expense - $prevExpense) / $prevExpense) * 100 : null,
        'income_delta_pct' => $prevIncome > 0 ? (($income - $prevIncome) / $prevIncome) * 100 : null,
    ];
}

/** Blížící se splatnosti - položky i pravidelné platby, které ještě nemají vygenerovanou položku */
function upcoming_due(int $withinDays = 7): array
{
    $pdo = db();
    $until = date('Y-m-d', strtotime("+$withinDays days"));

    $stmt = $pdo->prepare("
        SELECT * FROM transactions
        WHERE status IN ('ceka','po_splatnosti','castecne')
          AND due_date IS NOT NULL AND due_date != ''
          AND due_date <= :until
        ORDER BY due_date ASC
        LIMIT 15
    ");
    $stmt->execute(['until' => $until]);
    return $stmt->fetchAll();
}

/** Součty výdajů podle hlavních kategorií pro daný měsíc (pro grafy) */
function category_breakdown(string $period, string $type = 'vydaj'): array
{
    $pdo = db();
    [$cond, $paramVal] = period_condition($period, 't.month_year');
    $stmt = $pdo->prepare("
        SELECT
          COALESCE(p.id, c.id) AS cat_id,
          COALESCE(p.name, c.name) AS cat_name,
          COALESCE(p.color, c.color) AS cat_color,
          COALESCE(p.icon, c.icon) AS cat_icon,
          SUM(t.amount) AS total
        FROM transactions t
        LEFT JOIN categories c ON c.id = t.category_id
        LEFT JOIN categories p ON p.id = c.parent_id
        WHERE $cond AND t.type = :type AND t.status != 'zruseno' AND t.is_transfer = 0
        GROUP BY cat_id
        ORDER BY total DESC
    ");
    $stmt->execute(['period' => $paramVal, 'type' => $type]);
    return $stmt->fetchAll();
}

/** Vývoj příjmů/výdajů posledních N měsíců */
function monthly_trend(int $months = 6): array
{
    $current = current_month_year();
    $result = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $my = shift_month($current, -$i);
        $s = month_summary($my);
        $result[] = [
            'month_year' => $my,
            'label' => month_year_label($my),
            'income' => $s['income'],
            'expense' => $s['expense'],
        ];
    }
    return $result;
}

/** Sestaví hlavičky a řádky pro export seznamu položek (CSV/XLSX/tisk) */
function build_transaction_export_rows(array $items): array
{
    $headers = ['Datum platby', 'Měsíc', 'Typ', 'Název', 'Kategorie', 'Podkategorie', 'Částka (Kč)',
        'Způsob platby', 'Stav', 'Obchodník/příjemce', 'Podnikatelské', 'Pravidelné', 'Číslo dokladu', 'Poznámka', 'Štítky'];
    $rows = [];
    foreach ($items as $t) {
        $rows[] = [
            format_date_cz($t['payment_date']),
            $t['month_year'],
            $t['type'] === 'prijem' ? 'Příjem' : 'Výdaj',
            $t['name'],
            $t['parent_category_name'] ?: ($t['category_name'] ?? ''),
            $t['parent_category_name'] ? $t['category_name'] : '',
            number_format((float) $t['amount'], 2, ',', ''),
            payment_methods()[$t['payment_method']] ?? $t['payment_method'],
            payment_statuses()[$t['status']] ?? $t['status'],
            $t['merchant'] ?? '',
            $t['is_business'] ? 'Ano' : 'Ne',
            $t['is_recurring'] ? 'Ano' : 'Ne',
            $t['invoice_number'] ?? '',
            $t['note'] ?? '',
            implode(', ', get_transaction_tags((int) $t['id'])),
        ];
    }
    return ['headers' => $headers, 'rows' => $rows];
}

/** Sestaví export "přehled podle kategorií" pro daný měsíc (příjmy i výdaje) */
function build_category_summary_export_rows(string $monthYear): array
{
    $headers = ['Typ', 'Kategorie', 'Celková částka (Kč)'];
    $rows = [];
    foreach (category_breakdown($monthYear, 'vydaj') as $c) {
        $rows[] = ['Výdaj', $c['cat_name'] ?: 'Nezařazeno', number_format((float) $c['total'], 2, ',', '')];
    }
    foreach (category_breakdown($monthYear, 'prijem') as $c) {
        $rows[] = ['Příjem', $c['cat_name'] ?: 'Nezařazeno', number_format((float) $c['total'], 2, ',', '')];
    }
    return ['headers' => $headers, 'rows' => $rows];
}

function payment_method_breakdown(string $period, string $type = 'vydaj'): array
{
    [$cond, $paramVal] = period_condition($period);
    $stmt = db()->prepare("
        SELECT payment_method, SUM(amount) AS total, COUNT(*) AS cnt
        FROM transactions
        WHERE $cond AND type = :type AND status != 'zruseno' AND is_transfer = 0
        GROUP BY payment_method ORDER BY total DESC
    ");
    $stmt->execute(['period' => $paramVal, 'type' => $type]);
    return $stmt->fetchAll();
}

function business_vs_personal(string $period): array
{
    [$cond, $paramVal] = period_condition($period);
    $stmt = db()->prepare("
        SELECT is_business, SUM(amount) AS total
        FROM transactions
        WHERE $cond AND type = 'vydaj' AND status != 'zruseno' AND is_transfer = 0
        GROUP BY is_business
    ");
    $stmt->execute(['period' => $paramVal]);
    $result = ['business' => 0.0, 'personal' => 0.0];
    foreach ($stmt->fetchAll() as $row) {
        if ($row['is_business']) {
            $result['business'] = (float) $row['total'];
        } else {
            $result['personal'] = (float) $row['total'];
        }
    }
    return $result;
}

function top_expenses(string $period, int $limit = 10): array
{
    [$cond, $paramVal] = period_condition($period, 't.month_year');
    $stmt = db()->prepare("
        SELECT t.*, c.name AS category_name, c.icon AS category_icon
        FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
        WHERE $cond AND t.type = 'vydaj' AND t.status != 'zruseno' AND t.is_transfer = 0
        ORDER BY t.amount DESC LIMIT :lim
    ");
    $stmt->bindValue('period', $paramVal);
    $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Filtrovaný seznam položek (pro Příjmy a výdaje, Doklady, globální vyhledávání) */
function find_transactions(array $filters = [], int $limit = 500, int $offset = 0): array
{
    $pdo = db();
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['month_year'])) {
        $where[] = 't.month_year = :month_year';
        $params['month_year'] = $filters['month_year'];
    }
    if (!empty($filters['year'])) {
        $where[] = 't.month_year LIKE :year';
        $params['year'] = $filters['year'] . '-%';
    }
    if (!empty($filters['date_from'])) {
        $where[] = 't.payment_date >= :date_from';
        $params['date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 't.payment_date <= :date_to';
        $params['date_to'] = $filters['date_to'];
    }
    if (!empty($filters['type'])) {
        $where[] = 't.type = :type';
        $params['type'] = $filters['type'];
    }
    if (!empty($filters['q'])) {
        $where[] = '(t.name LIKE :q OR t.merchant LIKE :q OR t.note LIKE :q OR t.invoice_number LIKE :q)';
        $params['q'] = '%' . $filters['q'] . '%';
    }
    if (isset($filters['amount_min']) && $filters['amount_min'] !== '') {
        $where[] = 't.amount >= :amount_min';
        $params['amount_min'] = $filters['amount_min'];
    }
    if (isset($filters['amount_max']) && $filters['amount_max'] !== '') {
        $where[] = 't.amount <= :amount_max';
        $params['amount_max'] = $filters['amount_max'];
    }
    if (!empty($filters['category_id'])) {
        $where[] = '(t.category_id = :category_id OR t.category_id IN (SELECT id FROM categories WHERE parent_id = :category_id2))';
        $params['category_id'] = $filters['category_id'];
        $params['category_id2'] = $filters['category_id'];
    }
    if (!empty($filters['payment_method'])) {
        $where[] = 't.payment_method = :payment_method';
        $params['payment_method'] = $filters['payment_method'];
    }
    if (!empty($filters['status'])) {
        $where[] = 't.status = :status';
        $params['status'] = $filters['status'];
    }
    if (isset($filters['is_business']) && $filters['is_business'] !== '') {
        $where[] = 't.is_business = :is_business';
        $params['is_business'] = (int) $filters['is_business'];
    }
    if (isset($filters['is_recurring']) && $filters['is_recurring'] !== '') {
        $where[] = 't.is_recurring = :is_recurring';
        $params['is_recurring'] = (int) $filters['is_recurring'];
    }
    if (!empty($filters['merchant'])) {
        $where[] = 't.merchant LIKE :merchant';
        $params['merchant'] = '%' . $filters['merchant'] . '%';
    }
    if (!empty($filters['has_attachment'])) {
        $where[] = ($filters['has_attachment'] === '1')
            ? 't.id IN (SELECT transaction_id FROM attachments)'
            : 't.id NOT IN (SELECT transaction_id FROM attachments)';
    }
    if (!empty($filters['tag'])) {
        $where[] = 't.id IN (SELECT transaction_id FROM transaction_tags tt JOIN tags tg ON tg.id = tt.tag_id WHERE tg.name = :tag)';
        $params['tag'] = $filters['tag'];
    }
    if (!empty($filters['exclude_transfers'])) {
        $where[] = 't.is_transfer = 0';
    }

    $orderBy = $filters['order_by'] ?? 't.payment_date DESC, t.id DESC';
    $allowedOrder = [
        't.payment_date DESC, t.id DESC', 't.payment_date ASC, t.id ASC',
        't.amount DESC', 't.amount ASC', 't.name ASC',
    ];
    if (!in_array($orderBy, $allowedOrder, true)) {
        $orderBy = 't.payment_date DESC, t.id DESC';
    }

    $sql = "SELECT t.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
                   p.name AS parent_category_name,
                   (SELECT COUNT(*) FROM attachments a WHERE a.transaction_id = t.id) AS attachment_count
            FROM transactions t
            LEFT JOIN categories c ON c.id = t.category_id
            LEFT JOIN categories p ON p.id = c.parent_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_transactions(array $filters = []): int
{
    // Využijeme find_transactions bez limitu pro jednoduchost (lokální DB, malý objem dat)
    return count(find_transactions($filters, 100000, 0));
}

function get_transaction(int $id): ?array
{
    $stmt = db()->prepare("
        SELECT t.*, c.name AS category_name, c.icon AS category_icon
        FROM transactions t
        LEFT JOIN categories c ON c.id = t.category_id
        WHERE t.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_transaction_tags(int $transactionId): array
{
    $stmt = db()->prepare("
        SELECT tg.name FROM tags tg
        JOIN transaction_tags tt ON tt.tag_id = tg.id
        WHERE tt.transaction_id = :id ORDER BY tg.name
    ");
    $stmt->execute(['id' => $transactionId]);
    return array_column($stmt->fetchAll(), 'name');
}

/** Rozdělí textový vstup se štítky (oddělené čárkou) a propojí je s položkou */
function sync_transaction_tags(int $transactionId, string $tagsInput): void
{
    $pdo = db();
    $names = array_filter(array_unique(array_map('trim', explode(',', $tagsInput))), fn ($n) => $n !== '');

    $pdo->prepare('DELETE FROM transaction_tags WHERE transaction_id = :id')->execute(['id' => $transactionId]);

    $findStmt = $pdo->prepare('SELECT id FROM tags WHERE name = :name');
    $insertStmt = $pdo->prepare('INSERT INTO tags (name) VALUES (:name)');
    $linkStmt = $pdo->prepare('INSERT OR IGNORE INTO transaction_tags (transaction_id, tag_id) VALUES (:tx, :tag)');

    foreach ($names as $name) {
        $name = mb_substr($name, 0, 40);
        $findStmt->execute(['name' => $name]);
        $tagId = $findStmt->fetchColumn();
        if ($tagId === false) {
            $insertStmt->execute(['name' => $name]);
            $tagId = (int) $pdo->lastInsertId();
        }
        $linkStmt->execute(['tx' => $transactionId, 'tag' => $tagId]);
    }
}

/** Vyhledávání dokladů (příloh) podle data, částky, obchodníka, kategorie a typu přílohy */
function find_attachments(array $filters = []): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['folder'])) {
        $where[] = 'a.folder = :folder';
        $params['folder'] = $filters['folder'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 't.payment_date >= :date_from';
        $params['date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 't.payment_date <= :date_to';
        $params['date_to'] = $filters['date_to'];
    }
    if (isset($filters['amount_min']) && $filters['amount_min'] !== '') {
        $where[] = 't.amount >= :amount_min';
        $params['amount_min'] = $filters['amount_min'];
    }
    if (isset($filters['amount_max']) && $filters['amount_max'] !== '') {
        $where[] = 't.amount <= :amount_max';
        $params['amount_max'] = $filters['amount_max'];
    }
    if (!empty($filters['merchant'])) {
        $where[] = 't.merchant LIKE :merchant';
        $params['merchant'] = '%' . $filters['merchant'] . '%';
    }
    if (!empty($filters['category_id'])) {
        $where[] = 't.category_id = :category_id';
        $params['category_id'] = $filters['category_id'];
    }
    if (!empty($filters['q'])) {
        $where[] = '(a.original_name LIKE :q OR t.name LIKE :q OR t.invoice_number LIKE :q)';
        $params['q'] = '%' . $filters['q'] . '%';
    }

    $sql = "SELECT a.*, t.name AS transaction_name, t.payment_date, t.amount AS transaction_amount,
                   t.merchant, t.category_id, c.name AS category_name, c.icon AS category_icon
            FROM attachments a
            JOIN transactions t ON t.id = a.transaction_id
            LEFT JOIN categories c ON c.id = t.category_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.uploaded_at DESC
            LIMIT 300";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_attachments(int $transactionId): array
{
    $stmt = db()->prepare("SELECT * FROM attachments WHERE transaction_id = :id ORDER BY uploaded_at DESC");
    $stmt->execute(['id' => $transactionId]);
    return $stmt->fetchAll();
}

/**
 * Automaticky vytvoří položky pro aktivní pravidelné platby s auto_create=1
 * pro daný měsíc, pokud ještě nebyly vytvořeny. Bezpečné volat opakovaně.
 */
function ensure_recurring_instances_for_month(string $monthYear): int
{
    $pdo = db();
    $created = 0;

    $stmt = $pdo->query("SELECT * FROM recurring_payments WHERE is_active = 1 AND auto_create = 1");
    $recurring = $stmt->fetchAll();

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE recurring_id = :rid AND month_year = :m");
    $insertStmt = $pdo->prepare("
        INSERT INTO transactions
            (type, name, amount, payment_date, month_year, category_id, payment_method, merchant, note,
             due_date, status, is_recurring, recurring_id, is_business)
        VALUES
            (:type, :name, :amount, :payment_date, :month_year, :category_id, :payment_method, :merchant, :note,
             :due_date, 'ceka', 1, :recurring_id, :is_business)
    ");

    foreach ($recurring as $r) {
        if ($r['start_date'] && $r['start_date'] > $monthYear . '-31') {
            continue;
        }
        if ($r['end_date'] && $r['end_date'] < $monthYear . '-01') {
            continue;
        }
        if (in_array($r['frequency'], ['ctvrtletne', 'rocne'], true)) {
            $startTs = strtotime($r['start_date']);
            $monthStartTs = strtotime($monthYear . '-01');
            $monthsDiff = ((int) date('Y', $monthStartTs) - (int) date('Y', $startTs)) * 12
                + ((int) date('n', $monthStartTs) - (int) date('n', $startTs));
            $step = $r['frequency'] === 'ctvrtletne' ? 3 : 12;
            if ($monthsDiff < 0 || $monthsDiff % $step !== 0) {
                continue;
            }
        }
        $checkStmt->execute(['rid' => $r['id'], 'm' => $monthYear]);
        if ((int) $checkStmt->fetchColumn() > 0) {
            continue;
        }
        $daysInMonth = (int) date('t', strtotime($monthYear . '-01'));
        $day = max(1, min((int) $r['due_day'], $daysInMonth));
        $dueDate = sprintf('%s-%02d', $monthYear, $day);

        $insertStmt->execute([
            'type' => $r['type'], 'name' => $r['name'], 'amount' => $r['amount'],
            'payment_date' => $dueDate, 'month_year' => $monthYear, 'category_id' => $r['category_id'],
            'payment_method' => $r['payment_method'], 'merchant' => $r['merchant'], 'note' => $r['note'],
            'due_date' => $dueDate, 'recurring_id' => $r['id'], 'is_business' => $r['is_business'],
        ]);
        $created++;
    }

    return $created;
}

function all_active_recurring(): array
{
    $stmt = db()->query("
        SELECT r.*, c.name AS category_name, c.icon AS category_icon
        FROM recurring_payments r
        LEFT JOIN categories c ON c.id = r.category_id
        WHERE r.is_active = 1
        ORDER BY r.due_day, r.name
    ");
    return $stmt->fetchAll();
}

function get_month_row(string $monthYear): ?array
{
    $stmt = db()->prepare("SELECT * FROM months WHERE month_year = :m");
    $stmt->execute(['m' => $monthYear]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function is_month_closed(string $monthYear): bool
{
    $row = get_month_row($monthYear);
    return $row ? (bool) $row['is_closed'] : false;
}

/** Skutečně utracené částky podle konkrétní (ne sloučené) kategorie pro daný měsíc */
function category_actual_spend(string $monthYear, string $type = 'vydaj'): array
{
    $stmt = db()->prepare("
        SELECT category_id, SUM(amount) AS total
        FROM transactions
        WHERE month_year = :m AND type = :type AND status != 'zruseno' AND is_transfer = 0 AND category_id IS NOT NULL
        GROUP BY category_id
    ");
    $stmt->execute(['m' => $monthYear, 'type' => $type]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int) $row['category_id']] = (float) $row['total'];
    }
    return $result;
}

function upsert_budget(string $monthYear, ?int $categoryId, float $amount): void
{
    $pdo = db();
    // SQLite považuje každý NULL v UNIQUE indexu za odlišný, takže ON CONFLICT
    // pro celkový rozpočet (category_id IS NULL) nefunguje - ošetříme ručně.
    if ($categoryId === null) {
        $check = $pdo->prepare('SELECT id FROM budgets WHERE month_year = :m AND category_id IS NULL');
        $check->execute(['m' => $monthYear]);
        $existingId = $check->fetchColumn();
        if ($existingId) {
            $pdo->prepare('UPDATE budgets SET planned_amount = :a WHERE id = :id')
                ->execute(['a' => $amount, 'id' => $existingId]);
        } else {
            $pdo->prepare('INSERT INTO budgets (month_year, category_id, planned_amount) VALUES (:m, NULL, :a)')
                ->execute(['m' => $monthYear, 'a' => $amount]);
        }
        return;
    }
    $stmt = $pdo->prepare('
        INSERT INTO budgets (month_year, category_id, planned_amount) VALUES (:m, :c, :a)
        ON CONFLICT(month_year, category_id) DO UPDATE SET planned_amount = excluded.planned_amount
    ');
    $stmt->execute(['m' => $monthYear, 'c' => $categoryId, 'a' => $amount]);
}

function upsert_month_plan(string $monthYear, ?float $plannedIncome, ?float $minRemaining, ?float $reserve): void
{
    $stmt = db()->prepare('
        INSERT INTO months (month_year, planned_income, min_remaining, reserve_amount) VALUES (:m, :i, :r, :res)
        ON CONFLICT(month_year) DO UPDATE SET
            planned_income = excluded.planned_income,
            min_remaining = excluded.min_remaining,
            reserve_amount = excluded.reserve_amount
    ');
    $stmt->execute(['m' => $monthYear, 'i' => $plannedIncome, 'r' => $minRemaining, 'res' => $reserve]);
}

function get_budgets(string $monthYear): array
{
    $stmt = db()->prepare("SELECT * FROM budgets WHERE month_year = :m");
    $stmt->execute(['m' => $monthYear]);
    $rows = $stmt->fetchAll();
    $result = ['total' => null, 'categories' => []];
    foreach ($rows as $r) {
        if ($r['category_id'] === null) {
            $result['total'] = (float) $r['planned_amount'];
        } else {
            $result['categories'][(int) $r['category_id']] = (float) $r['planned_amount'];
        }
    }
    return $result;
}
