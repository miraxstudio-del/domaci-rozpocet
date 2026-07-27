<?php
declare(strict_types=1);

/**
 * Zpracuje nahrané soubory z $_FILES[$fieldName] a uloží je do uploads/{$folder}/.
 * Vrací pole ['saved' => int, 'errors' => string[]].
 */
function handle_uploaded_attachments(int $transactionId, string $folder, string $fieldName = 'attachments'): array
{
    $result = ['saved' => 0, 'errors' => []];

    if (!array_key_exists($folder, UPLOAD_FOLDERS)) {
        $folder = 'ostatni';
    }
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'] ?? null)) {
        return $result;
    }

    $targetDir = UPLOADS_PATH . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $files = $_FILES[$fieldName];
    $count = count($files['name']);

    $stmt = db()->prepare("
        INSERT INTO attachments (transaction_id, original_name, stored_path, folder, file_type, file_size)
        VALUES (:tx, :orig, :path, :folder, :type, :size)
    ");

    for ($i = 0; $i < $count; $i++) {
        $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $originalName = (string) $files['name'][$i];

        if ($error !== UPLOAD_ERR_OK) {
            $result['errors'][] = "Soubor „$originalName“ se nepodařilo nahrát (chyba uploadu).";
            continue;
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_UPLOAD_EXT, true)) {
            $result['errors'][] = "Soubor „$originalName“ má nepovolený typ přílohy.";
            continue;
        }

        $tmpPath = $files['tmp_name'][$i];
        if (!is_uploaded_file($tmpPath)) {
            $result['errors'][] = "Soubor „$originalName“ se nepodařilo zpracovat.";
            continue;
        }

        $size = (int) $files['size'][$i];
        if ($size > 25 * 1024 * 1024) {
            $result['errors'][] = "Soubor „$originalName“ přesahuje limit 25 MB.";
            continue;
        }

        // Bezpečný jedinečný název souboru - zabrání přepsání a nebezpečným cestám
        do {
            $newName = safe_unique_filename($originalName);
            $destination = $targetDir . DIRECTORY_SEPARATOR . $newName;
        } while (file_exists($destination));

        if (!move_uploaded_file($tmpPath, $destination)) {
            $result['errors'][] = "Soubor „$originalName“ se nepodařilo uložit na disk.";
            continue;
        }

        $relativePath = 'uploads/' . $folder . '/' . $newName;
        $mimeType = function_exists('mime_content_type') ? (mime_content_type($destination) ?: null) : null;

        $stmt->execute([
            'tx' => $transactionId,
            'orig' => $originalName,
            'path' => $relativePath,
            'folder' => $folder,
            'type' => $mimeType,
            'size' => $size,
        ]);
        $result['saved']++;
    }

    return $result;
}

/** Smaže soubor přílohy z disku (bezpečně, pouze uvnitř uploads/) */
function delete_attachment_file(array $attachment): bool
{
    $full = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . $attachment['stored_path']);
    $uploadsReal = realpath(UPLOADS_PATH);
    if ($full === false || $uploadsReal === false || strpos($full, $uploadsReal) !== 0) {
        return false;
    }
    if (is_file($full)) {
        return @unlink($full);
    }
    return true;
}
