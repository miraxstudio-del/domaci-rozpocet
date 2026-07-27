<?php
declare(strict_types=1);

/**
 * Minimalistický generátor XLSX souborů bez externích knihoven (jen ZipArchive z jádra PHP).
 * Používá inline řetězce (inlineStr), takže nejsou potřeba sdílené řetězce ani styly navíc.
 * Podporuje plně UTF-8 (české znaky) a základní typy: text a čísla.
 */
function write_xlsx_file(string $path, string $sheetTitle, array $headers, array $rows): void
{
    if (file_exists($path)) {
        @unlink($path);
    }

    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
        '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
        '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
        '</Types>');

    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
        '</Relationships>');

    $safeTitle = mb_substr(preg_replace('/[\[\]\*\/\\\\\?:]/', ' ', $sheetTitle), 0, 31);
    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' .
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
        '<sheets><sheet name="' . xml_escape($safeTitle) . '" sheetId="1" r:id="rId1"/></sheets>' .
        '</workbook>');

    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
        '</Relationships>');

    $zip->addFromString('xl/styles.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
        '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>' .
        '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>' .
        '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>' .
        '<borders count="1"><border/></borders>' .
        '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0"/></cellStyleXfs>' .
        '<cellXfs count="2"><xf numFmtId="0" fontId="0" xfId="0"/><xf numFmtId="0" fontId="1" xfId="0" applyFont="1"/></cellXfs>' .
        '</styleSheet>');

    $colCount = count($headers);
    $lastCol = xlsx_col_letter($colCount);

    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $sheet .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $sheet .= '<dimension ref="A1:' . $lastCol . (count($rows) + 1) . '"/>';
    $sheet .= '<sheetData>';

    $sheet .= '<row r="1">';
    foreach ($headers as $i => $h) {
        $ref = xlsx_col_letter($i + 1) . '1';
        $sheet .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t xml:space="preserve">' . xml_escape((string) $h) . '</t></is></c>';
    }
    $sheet .= '</row>';

    foreach ($rows as $rIdx => $row) {
        $r = $rIdx + 2;
        $sheet .= '<row r="' . $r . '">';
        foreach ($row as $i => $val) {
            $ref = xlsx_col_letter($i + 1) . $r;
            if (is_numeric($val) && $val !== '' && !preg_match('/^0[0-9]/', (string) $val)) {
                $sheet .= '<c r="' . $ref . '"><v>' . xml_escape((string) $val) . '</v></c>';
            } else {
                $sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . xml_escape((string) $val) . '</t></is></c>';
            }
        }
        $sheet .= '</row>';
    }

    $sheet .= '</sheetData></worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

    $zip->close();
}

function xlsx_col_letter(int $index): string
{
    $letter = '';
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $index = (int) (($index - $mod) / 26);
    }
    return $letter;
}

function xml_escape(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
