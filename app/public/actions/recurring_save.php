<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pravidelne');
}
csrf_check();

$id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
$name = trim((string) ($_POST['name'] ?? ''));
$type = ($_POST['type'] ?? '') === 'prijem' ? 'prijem' : 'vydaj';
$amountRaw = str_replace(',', '.', trim((string) ($_POST['amount'] ?? '')));
$categoryId = ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null;
$dueDay = max(1, min(31, (int) ($_POST['due_day'] ?? 1)));
$frequency = (string) ($_POST['frequency'] ?? 'mesicne');
$startDate = (string) ($_POST['start_date'] ?? date('Y-m-d'));
$endDate = trim((string) ($_POST['end_date'] ?? '')) ?: null;
$autoCreate = !empty($_POST['auto_create']) ? 1 : 0;
$remindDays = max(0, min(30, (int) ($_POST['remind_days_before'] ?? 3)));
$paymentMethod = (string) ($_POST['payment_method'] ?? 'trvaly_prikaz');
$merchant = trim((string) ($_POST['merchant'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));
$isBusiness = !empty($_POST['is_business']) ? 1 : 0;
$isActive = !empty($_POST['is_active']) ? 1 : 0;

if (!array_key_exists($frequency, frequency_labels())) {
    $frequency = 'mesicne';
}
if (!array_key_exists($paymentMethod, payment_methods())) {
    $paymentMethod = 'trvaly_prikaz';
}

if ($name === '' || $amountRaw === '' || !is_numeric($amountRaw) || (float) $amountRaw <= 0) {
    flash('error', 'Zadejte platný název a kladnou částku pravidelné platby.');
    redirect('pravidelne', $id ? ['edit' => $id] : []);
}

$amount = (float) $amountRaw;
$pdo = db();

if ($id) {
    $stmt = $pdo->prepare('
        UPDATE recurring_payments SET
            name = :name, type = :type, amount = :amount, category_id = :category_id, due_day = :due_day,
            frequency = :frequency, start_date = :start_date, end_date = :end_date, auto_create = :auto_create,
            remind_days_before = :remind, payment_method = :method, merchant = :merchant, note = :note,
            is_business = :is_business, is_active = :is_active
        WHERE id = :id
    ');
    $stmt->execute([
        'name' => $name, 'type' => $type, 'amount' => $amount, 'category_id' => $categoryId, 'due_day' => $dueDay,
        'frequency' => $frequency, 'start_date' => $startDate, 'end_date' => $endDate, 'auto_create' => $autoCreate,
        'remind' => $remindDays, 'method' => $paymentMethod, 'merchant' => $merchant ?: null, 'note' => $note ?: null,
        'is_business' => $isBusiness, 'is_active' => $isActive, 'id' => $id,
    ]);
    flash('success', 'Pravidelná platba „' . $name . '“ byla upravena.');
} else {
    $stmt = $pdo->prepare('
        INSERT INTO recurring_payments
            (name, type, amount, category_id, due_day, frequency, start_date, end_date, auto_create,
             remind_days_before, payment_method, merchant, note, is_business, is_active)
        VALUES
            (:name, :type, :amount, :category_id, :due_day, :frequency, :start_date, :end_date, :auto_create,
             :remind, :method, :merchant, :note, :is_business, :is_active)
    ');
    $stmt->execute([
        'name' => $name, 'type' => $type, 'amount' => $amount, 'category_id' => $categoryId, 'due_day' => $dueDay,
        'frequency' => $frequency, 'start_date' => $startDate, 'end_date' => $endDate, 'auto_create' => $autoCreate,
        'remind' => $remindDays, 'method' => $paymentMethod, 'merchant' => $merchant ?: null, 'note' => $note ?: null,
        'is_business' => $isBusiness, 'is_active' => $isActive,
    ]);
    flash('success', 'Pravidelná platba „' . $name . '“ byla vytvořena.');

    // Rovnou vytvoříme položku pro aktuální měsíc, pokud má vzniknout automaticky
    if ($autoCreate) {
        ensure_recurring_instances_for_month(current_month_year());
    }
}

redirect('pravidelne');
