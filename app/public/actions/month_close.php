<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('mesice');
}
csrf_check();

$monthYear = (string) ($_POST['month_year'] ?? current_month_year());
if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    $monthYear = current_month_year();
}
$action = (string) ($_POST['action'] ?? 'close');
$note = trim((string) ($_POST['closing_note'] ?? ''));

$pdo = db();
$stmt = $pdo->prepare('
    INSERT INTO months (month_year, is_closed, closing_note) VALUES (:m, :closed, :note)
    ON CONFLICT(month_year) DO UPDATE SET is_closed = excluded.is_closed, closing_note = excluded.closing_note
');
$stmt->execute([
    'm' => $monthYear,
    'closed' => $action === 'close' ? 1 : 0,
    'note' => $note ?: null,
]);

flash('success', $action === 'close'
    ? 'Měsíc ' . month_year_label($monthYear) . ' byl uzavřen. Kdykoliv jej můžete znovu otevřít.'
    : 'Měsíc ' . month_year_label($monthYear) . ' byl znovu otevřen.');
redirect('mesice', ['m' => $monthYear]);
