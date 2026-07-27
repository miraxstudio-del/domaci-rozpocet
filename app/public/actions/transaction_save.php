<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('polozky');
}
csrf_check();

$id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
$errors = [];

$type = ($_POST['type'] ?? '') === 'prijem' ? 'prijem' : 'vydaj';
$name = trim((string) ($_POST['name'] ?? ''));
$amountRaw = str_replace(',', '.', trim((string) ($_POST['amount'] ?? '')));
$paymentDate = (string) ($_POST['payment_date'] ?? '');
$monthYear = (string) ($_POST['month_year'] ?? '');
$categoryId = ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null;
$paymentMethod = (string) ($_POST['payment_method'] ?? 'hotovost');
$merchant = trim((string) ($_POST['merchant'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));
$dueDate = trim((string) ($_POST['due_date'] ?? '')) ?: null;
$status = (string) ($_POST['status'] ?? 'zaplaceno');
$paidAmountRaw = str_replace(',', '.', trim((string) ($_POST['paid_amount'] ?? '')));
$invoiceNumber = trim((string) ($_POST['invoice_number'] ?? ''));
$isBusiness = !empty($_POST['is_business']) ? 1 : 0;
$isTransfer = !empty($_POST['is_transfer']) ? 1 : 0;
$isRecurring = !empty($_POST['is_recurring']) ? 1 : 0;
$recurringId = !empty($_POST['recurring_id']) ? (int) $_POST['recurring_id'] : null;
$recurringFrequency = (string) ($_POST['recurring_frequency'] ?? 'mesicne');
$tagsInput = (string) ($_POST['tags'] ?? '');
$attachmentFolder = (string) ($_POST['attachment_folder'] ?? 'uctenky');

if ($name === '') {
    $errors[] = 'Zadejte název položky.';
}
if ($amountRaw === '' || !is_numeric($amountRaw) || (float) $amountRaw == 0.0) {
    $errors[] = 'Zadejte platnou nenulovou částku.';
}
if (!$paymentDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)) {
    $errors[] = 'Zadejte platné datum platby.';
}
if (!$monthYear || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    $monthYear = $paymentDate ? month_year_for_date($paymentDate) : current_month_year();
}
if (!array_key_exists($paymentMethod, payment_methods())) {
    $paymentMethod = 'jiny';
}
if (!array_key_exists($status, payment_statuses())) {
    $status = 'zaplaceno';
}

$amount = $errors ? 0.0 : (float) $amountRaw;
$paidAmount = ($status === 'castecne' && $paidAmountRaw !== '' && is_numeric($paidAmountRaw)) ? (float) $paidAmountRaw : null;

if ($errors) {
    foreach ($errors as $e) {
        flash('error', $e);
    }
    redirect('polozka', $id ? ['id' => $id] : []);
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Pokud uživatel označil položku jako pravidelnou a zatím neexistuje šablona, vytvoříme ji
    if ($isRecurring && !$recurringId) {
        $dueDay = $dueDate ? (int) date('j', strtotime($dueDate)) : (int) date('j', strtotime($paymentDate));
        $stmtRec = $pdo->prepare("
            INSERT INTO recurring_payments
                (name, type, amount, category_id, due_day, frequency, start_date, auto_create, payment_method, merchant, note, is_business, is_active)
            VALUES (:name, :type, :amount, :cat, :day, :freq, :start, 1, :method, :merchant, :note, :biz, 1)
        ");
        $stmtRec->execute([
            'name' => $name, 'type' => $type, 'amount' => $amount, 'cat' => $categoryId,
            'day' => max(1, min(31, $dueDay)), 'freq' => $recurringFrequency, 'start' => $paymentDate,
            'method' => $paymentMethod, 'merchant' => $merchant ?: null, 'note' => $note ?: null, 'biz' => $isBusiness,
        ]);
        $recurringId = (int) $pdo->lastInsertId();
    }

    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE transactions SET
                type = :type, name = :name, amount = :amount, payment_date = :payment_date,
                month_year = :month_year, category_id = :category_id, payment_method = :payment_method,
                merchant = :merchant, note = :note, due_date = :due_date, status = :status,
                paid_amount = :paid_amount, is_recurring = :is_recurring, recurring_id = :recurring_id,
                invoice_number = :invoice_number, is_business = :is_business, is_transfer = :is_transfer,
                updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->execute([
            'type' => $type, 'name' => $name, 'amount' => $amount, 'payment_date' => $paymentDate,
            'month_year' => $monthYear, 'category_id' => $categoryId, 'payment_method' => $paymentMethod,
            'merchant' => $merchant ?: null, 'note' => $note ?: null, 'due_date' => $dueDate, 'status' => $status,
            'paid_amount' => $paidAmount, 'is_recurring' => $isRecurring, 'recurring_id' => $recurringId,
            'invoice_number' => $invoiceNumber ?: null, 'is_business' => $isBusiness, 'is_transfer' => $isTransfer,
            'id' => $id,
        ]);
        $txId = $id;
        $message = 'Položka byla úspěšně upravena.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO transactions
                (type, name, amount, payment_date, month_year, category_id, payment_method, merchant, note,
                 due_date, status, paid_amount, is_recurring, recurring_id, invoice_number, is_business, is_transfer)
            VALUES
                (:type, :name, :amount, :payment_date, :month_year, :category_id, :payment_method, :merchant, :note,
                 :due_date, :status, :paid_amount, :is_recurring, :recurring_id, :invoice_number, :is_business, :is_transfer)
        ");
        $stmt->execute([
            'type' => $type, 'name' => $name, 'amount' => $amount, 'payment_date' => $paymentDate,
            'month_year' => $monthYear, 'category_id' => $categoryId, 'payment_method' => $paymentMethod,
            'merchant' => $merchant ?: null, 'note' => $note ?: null, 'due_date' => $dueDate, 'status' => $status,
            'paid_amount' => $paidAmount, 'is_recurring' => $isRecurring, 'recurring_id' => $recurringId,
            'invoice_number' => $invoiceNumber ?: null, 'is_business' => $isBusiness, 'is_transfer' => $isTransfer,
        ]);
        $txId = (int) $pdo->lastInsertId();
        $message = 'Položka byla úspěšně uložena.';
    }

    sync_transaction_tags($txId, $tagsInput);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error', 'Uložení se nezdařilo: ' . $e->getMessage());
    redirect('polozka', $id ? ['id' => $id] : []);
}

$uploadResult = handle_uploaded_attachments($txId, $attachmentFolder);
foreach ($uploadResult['errors'] as $err) {
    flash('error', $err);
}

flash('success', $message . ($uploadResult['saved'] > 0 ? ' Nahráno příloh: ' . $uploadResult['saved'] . '.' : ''));
redirect('polozka', ['id' => $txId]);
