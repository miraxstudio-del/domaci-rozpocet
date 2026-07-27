<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('nastaveni');
}
csrf_check();

$action = (string) ($_POST['action'] ?? '');
$pdo = db();

function find_cat_id(string $name): ?int
{
    $stmt = db()->prepare('SELECT id FROM categories WHERE name = :n LIMIT 1');
    $stmt->execute(['n' => $name]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

if ($action === 'load') {
    if (get_setting('demo_data_loaded', '0') === '1') {
        flash('info', 'Ukázková data už jsou načtená.');
        redirect('nastaveni');
    }

    $m = current_month_year();
    $today = date('Y-m-d');
    $txIds = [];
    $recIds = [];

    $insertTx = $pdo->prepare("
        INSERT INTO transactions (type, name, amount, payment_date, month_year, category_id, payment_method,
            merchant, status, is_recurring, is_business)
        VALUES (:type, :name, :amount, :date, :m, :cat, :method, :merchant, :status, :rec, :biz)
    ");
    $insertRec = $pdo->prepare("
        INSERT INTO recurring_payments (name, type, amount, category_id, due_day, frequency, start_date, auto_create, payment_method, is_business)
        VALUES (:name, 'vydaj', :amount, :cat, :day, 'mesicne', :start, 0, :method, :biz)
    ");

    $demoItems = [
        ['Nákup pečiva', 185, 'Chléb a pečivo', 'hotovost', date('Y-m-d', strtotime('-2 days')), 0],
        ['Nákup potravin', 1240, 'Ovoce a zelenina', 'karta', date('Y-m-d', strtotime('-5 days')), 0],
        ['Mzda', 42000, 'Mzda', 'prevod', date('Y-m-01'), 0, 'prijem'],
        ['Příjem z podnikání', 18500, 'Příjem z podnikání', 'prevod', date('Y-m-03'), 1, 'prijem'],
    ];
    foreach ($demoItems as $item) {
        [$name, $amount, $catName, $method, $date, $biz] = $item;
        $type = $item[6] ?? 'vydaj';
        $insertTx->execute([
            'type' => $type, 'name' => $name, 'amount' => $amount, 'date' => $date, 'm' => $m,
            'cat' => find_cat_id($catName), 'method' => $method, 'merchant' => null,
            'status' => 'zaplaceno', 'rec' => 0, 'biz' => $biz,
        ]);
        $txIds[] = (int) $pdo->lastInsertId();
    }

    $demoRecurring = [
        ['Elektřina', 2800, 'Elektřina', 15, 'trvaly_prikaz', 0],
        ['Plyn', 1950, 'Plyn', 15, 'trvaly_prikaz', 0],
        ['Mobilní telefon', 749, 'Mobilní telefon', 20, 'inkaso', 0],
        ['Internet', 499, 'Internet', 20, 'inkaso', 0],
        ['Sociální pojištění OSVČ', 3852, 'Sociální pojištění', 20, 'prevod', 1],
        ['Zdravotní pojištění OSVČ', 2848, 'Zdravotní pojištění', 8, 'prevod', 1],
        ['Předplatné streamovací služby', 219, 'Předplatné a online služby', 5, 'karta', 0],
    ];
    foreach ($demoRecurring as [$name, $amount, $catName, $day, $method, $biz]) {
        $insertRec->execute([
            'name' => $name, 'amount' => $amount, 'cat' => find_cat_id($catName), 'day' => $day,
            'start' => date('Y-m-01'), 'method' => $method, 'biz' => $biz,
        ]);
        $recId = (int) $pdo->lastInsertId();
        $recIds[] = $recId;

        // Rovnou vytvoříme položku pro aktuální měsíc, ať je vidět v přehledu
        $dueDate = sprintf('%s-%02d', $m, min($day, (int) date('t')));
        $insertTx->execute([
            'type' => 'vydaj', 'name' => $name, 'amount' => $amount, 'date' => $dueDate, 'm' => $m,
            'cat' => find_cat_id($catName), 'method' => $method, 'merchant' => null,
            'status' => $dueDate <= $today ? 'zaplaceno' : 'ceka', 'rec' => 1, 'biz' => $biz,
        ]);
        $lastTxId = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE transactions SET recurring_id = :r WHERE id = :id')->execute(['r' => $recId, 'id' => $lastTxId]);
        $txIds[] = $lastTxId;
    }

    set_setting('demo_data_loaded', '1');
    set_setting('demo_transaction_ids', implode(',', $txIds));
    set_setting('demo_recurring_ids', implode(',', $recIds));

    flash('success', 'Ukázková data byla načtena. Až si aplikaci vyzkoušíte, můžete je v Nastavení opět odstranit.');
} elseif ($action === 'remove') {
    $txIds = array_filter(explode(',', get_setting('demo_transaction_ids', '')));
    $recIds = array_filter(explode(',', get_setting('demo_recurring_ids', '')));

    if ($txIds) {
        $placeholders = implode(',', array_fill(0, count($txIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id IN ($placeholders)");
        $stmt->execute(array_values($txIds));
    }
    if ($recIds) {
        $placeholders = implode(',', array_fill(0, count($recIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM recurring_payments WHERE id IN ($placeholders)");
        $stmt->execute(array_values($recIds));
    }

    set_setting('demo_data_loaded', '0');
    set_setting('demo_transaction_ids', '');
    set_setting('demo_recurring_ids', '');

    flash('success', 'Ukázková data byla odstraněna.');
}

redirect('nastaveni');
