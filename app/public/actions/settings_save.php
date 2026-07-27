<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('nastaveni');
}
csrf_check();

set_setting('household_name', trim((string) ($_POST['household_name'] ?? 'Naše domácnost')) ?: 'Naše domácnost');
set_setting('currency', trim((string) ($_POST['currency'] ?? 'Kč')) ?: 'Kč');

$startDay = max(1, min(28, (int) ($_POST['month_start_day'] ?? 1)));
set_setting('month_start_day', (string) $startDay);

$theme = (string) ($_POST['theme'] ?? 'system');
set_setting('theme', in_array($theme, ['light', 'dark', 'system'], true) ? $theme : 'system');

$dateFormat = (string) ($_POST['date_format'] ?? 'j. n. Y');
set_setting('date_format', array_key_exists($dateFormat, date_format_options()) ? $dateFormat : 'j. n. Y');

$decimals = max(0, min(2, (int) ($_POST['decimal_places'] ?? 2)));
set_setting('decimal_places', (string) $decimals);

set_setting('confirm_delete', !empty($_POST['confirm_delete']) ? '1' : '0');
set_setting('show_business', !empty($_POST['show_business']) ? '1' : '0');
set_setting('auto_backup', !empty($_POST['auto_backup']) ? '1' : '0');

$exportFolder = trim((string) ($_POST['export_folder'] ?? 'exporty'));
$exportFolder = preg_replace('/[^a-zA-Z0-9 _\-]/', '', $exportFolder) ?: 'exporty';
set_setting('export_folder', $exportFolder);

$customMethods = trim((string) ($_POST['custom_payment_methods'] ?? ''));
set_setting('custom_payment_methods', $customMethods);

flash('success', 'Nastavení bylo uloženo.');
redirect('nastaveni');
