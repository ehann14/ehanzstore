<?php

namespace App\Support\Export;

/**
 * Penulis file Excel (.xlsx) sederhana tanpa library tambahan dan tanpa ekstensi zip.
 * Mendukung beberapa sheet, lebar kolom, gabung sel, freeze baris, dan beberapa gaya.
 *
 * Isi sel: skalar (string / angka / null) atau [nilai, 'gaya'].
 * Gaya: title, bold, header, text, textBold, int, intBold, money, moneyBold.
 */
class XlsxWriter
{
    private const STYLES = [
        'default'   => 0,
        'bold'      => 1,
        'title'     => 2,
        'header'    => 3,
        'money'     => 4,
        'moneyBold' => 5,
        'int'       => 6,
        'text'      => 7,
        'textBold'  => 8,
        'intBold'   => 9,
    ];

    /** @var array<int, array{name:string, rows:array, widths:array, merges:array, freeze:int}> */
    private array $sheets = [];

    /**
     * @param array $rows   baris demi baris; tiap baris adalah array sel
     * @param array $widths lebar kolom (karakter), urut dari kolom A
     * @param array $merges contoh: ['A1:F1']
     * @param int   $freeze jumlah baris teratas yang dibekukan (0 = tidak)
     */
    public function addSheet(string $name, array $rows, array $widths = [], array $merges = [], int $freeze = 0): static
    {
        $name = trim(preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', $name));
        $name = mb_substr($name === '' ? 'Sheet' . (count($this->sheets) + 1) : $name, 0, 31);

        $this->sheets[] = compact('name', 'rows', 'widths', 'merges', 'freeze');

        return $this;
    }

    public function output(): string
    {
        if (! $this->sheets) {
            $this->addSheet('Sheet1', []);
        }

        $n = count($this->sheets);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $contentTypes .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $contentTypes .= '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $sheet) {
            $id = $i + 1;
            $workbook .= '<sheet name="' . $this->esc($sheet['name']) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
            $wbRels   .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
        }
        $workbook .= '</sheets></workbook>';
        $wbRels   .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $files = [
            '[Content_Types].xml'        => $contentTypes,
            '_rels/.rels'                => $rels,
            'xl/workbook.xml'            => $workbook,
            'xl/_rels/workbook.xml.rels' => $wbRels,
            'xl/styles.xml'              => $this->stylesXml(),
        ];

        foreach ($this->sheets as $i => $sheet) {
            $files['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $this->sheetXml($sheet);
        }

        return $this->zip($files);
    }

    // ------------------------------------------------------------------

    private function sheetXml(array $sheet): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if ($sheet['freeze'] > 0) {
            $f = (int) $sheet['freeze'];
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $f . '" topLeftCell="A' . ($f + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        }

        if ($sheet['widths']) {
            $xml .= '<cols>';
            foreach (array_values($sheet['widths']) as $i => $w) {
                $c = $i + 1;
                $xml .= '<col min="' . $c . '" max="' . $c . '" width="' . (float) $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach (array_values($sheet['rows']) as $r => $row) {
            $rowNum = $r + 1;
            $xml .= '<row r="' . $rowNum . '">';
            foreach (array_values((array) $row) as $c => $cell) {
                $xml .= $this->cellXml($this->col($c) . $rowNum, $cell);
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if ($sheet['merges']) {
            $xml .= '<mergeCells count="' . count($sheet['merges']) . '">';
            foreach ($sheet['merges'] as $m) {
                $xml .= '<mergeCell ref="' . $this->esc($m) . '"/>';
            }
            $xml .= '</mergeCells>';
        }

        return $xml . '</worksheet>';
    }

    private function cellXml(string $ref, mixed $cell): string
    {
        $style = null;
        $value = $cell;

        if (is_array($cell)) {
            $value = $cell[0] ?? null;
            $style = $cell[1] ?? null;
        }

        $s = isset(self::STYLES[$style]) ? ' s="' . self::STYLES[$style] . '"' : '';

        if ($value === null || $value === '') {
            return $s ? '<c r="' . $ref . '"' . $s . '/>' : '';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $ref . '"' . $s . '><v>' . $value . '</v></c>';
        }

        return '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">' . $this->esc((string) $value) . '</t></is></c>';
    }

    /** 0 -> A, 25 -> Z, 26 -> AA */
    private function col(int $i): string
    {
        $name = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $name = chr(65 + ($i - 1) % 26) . $name;
        }

        return $name;
    }

    private function esc(string $s): string
    {
        // buang karakter kontrol yang tidak valid di XML
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0;[Red]\-&quot;Rp &quot;#,##0"/></numFmts>'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEDEDED"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFBFBFBF"/></left><right style="thin"><color rgb="FFBFBFBF"/></right><top style="thin"><color rgb="FFBFBFBF"/></top><bottom style="thin"><color rgb="FFBFBFBF"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="10">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            . '<xf numFmtId="164" fontId="1" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="3" fontId="1" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /** ZIP tanpa kompresi (method "stored"), cukup untuk file xlsx kecil. */
    private function zip(array $files): string
    {
        $out     = '';
        $central = '';
        $count   = 0;
        $time    = (int) date('His');
        $date    = (int) date('Ymd');
        $dosTime = ((int) substr(sprintf('%06d', $time), 0, 2) << 11)
                 | ((int) substr(sprintf('%06d', $time), 2, 2) << 5)
                 | intdiv((int) substr(sprintf('%06d', $time), 4, 2), 2);
        $dosDate = ((max(1980, (int) substr((string) $date, 0, 4)) - 1980) << 9)
                 | ((int) substr((string) $date, 4, 2) << 5)
                 | (int) substr((string) $date, 6, 2);

        foreach ($files as $name => $data) {
            $crc    = crc32($data) & 0xFFFFFFFF;
            $size   = strlen($data);
            $offset = strlen($out);

            $out .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($name), 0)
                  . $name . $data;

            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 0, $offset)
                      . $name;
            $count++;
        }

        return $out . $central
            . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($out), 0);
    }
}