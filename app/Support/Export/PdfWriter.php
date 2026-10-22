<?php

namespace App\Support\Export;

/**
 * Penulis PDF sederhana tanpa library tambahan (font bawaan Helvetica, hanya teks ASCII).
 * Cukup untuk laporan tabel: judul, baris info, tabel dengan header berulang tiap halaman,
 * dan nomor halaman. Koordinat Y dihitung dari atas halaman (satuan point).
 */
class PdfWriter
{
    /** Lebar karakter Helvetica (per 1000 unit) untuk kode 32..126. */
    private const W_REGULAR = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    /** Lebar karakter Helvetica-Bold. */
    private const W_BOLD = [
        278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611,
        975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556,
        333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611,
        611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584,
    ];

    private float $w;
    private float $h;
    private float $margin;

    /** @var string[] isi (content stream) tiap halaman */
    private array $pages = [];
    private int $cur = -1;

    /** posisi Y (dari atas) untuk penulisan berikutnya */
    public float $y = 0;

    private string $footerLeft = '';

    public function __construct(bool $landscape = false, float $margin = 36)
    {
        $this->w      = $landscape ? 841.89 : 595.28;
        $this->h      = $landscape ? 595.28 : 841.89;
        $this->margin = $margin;
    }

    public function setFooter(string $left): static
    {
        $this->footerLeft = $left;

        return $this;
    }

    public function contentWidth(): float
    {
        return $this->w - 2 * $this->margin;
    }

    public function addPage(): static
    {
        $this->pages[] = '';
        $this->cur     = count($this->pages) - 1;
        $this->y       = $this->margin;

        return $this;
    }

    /** Ubah teks ke ASCII yang aman untuk font bawaan PDF. */
    public static function clean(string $s): string
    {
        $map = [
            "\u{2013}" => '-', "\u{2014}" => '-', "\u{2212}" => '-', "\u{00B7}" => '-', "\u{2022}" => '-',
            "\u{2018}" => "'", "\u{2019}" => "'", "\u{201C}" => '"', "\u{201D}" => '"', "\u{00A0}" => ' ',
        ];
        $s = strtr($s, $map);
        $s = preg_replace('/[\r\n\t]+/', ' ', $s) ?? '';
        $s = preg_replace('/[^\x20-\x7E]/u', '', $s) ?? '';

        return $s;
    }

    public function width(string $s, float $size, bool $bold = false): float
    {
        $table = $bold ? self::W_BOLD : self::W_REGULAR;
        $sum   = 0;
        $len   = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c    = ord($s[$i]);
            $sum += $table[$c - 32] ?? 556;
        }

        return $sum * $size / 1000;
    }

    /** Potong teks dengan ".." jika melebihi lebar maksimum. */
    public function fit(string $s, float $max, float $size, bool $bold = false): string
    {
        $s = self::clean($s);
        if ($this->width($s, $size, $bold) <= $max) {
            return $s;
        }
        while ($s !== '' && $this->width($s . '..', $size, $bold) > $max) {
            $s = substr($s, 0, -1);
        }

        return $s . '..';
    }

    /**
     * Tulis teks. $align: L / R / C relatif terhadap kotak selebar $boxW yang mulai di $x.
     * $color: [r,g,b] 0..1
     */
    public function text(float $x, float $y, string $s, float $size = 10, bool $bold = false, string $align = 'L', float $boxW = 0, array $color = [0, 0, 0]): void
    {
        $s = self::clean($s);
        if ($s === '') {
            return;
        }

        $tw = $this->width($s, $size, $bold);
        if ($boxW > 0) {
            if ($align === 'R') {
                $x += $boxW - $tw;
            } elseif ($align === 'C') {
                $x += ($boxW - $tw) / 2;
            }
        }

        $py  = $this->h - $y - $size * 0.8;
        $esc = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);

        $this->pages[$this->cur] .= sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET\n",
            $bold ? 'F2' : 'F1', $size, $color[0], $color[1], $color[2], $x, $py, $esc
        );
    }

    public function rect(float $x, float $y, float $w, float $h, ?array $fill = null, ?array $stroke = null): void
    {
        $py  = $this->h - $y - $h;
        $out = '';
        if ($fill) {
            $out .= sprintf("%.3F %.3F %.3F rg ", $fill[0], $fill[1], $fill[2]);
        }
        if ($stroke) {
            $out .= sprintf("%.3F %.3F %.3F RG 0.5 w ", $stroke[0], $stroke[1], $stroke[2]);
        }
        $op = $fill && $stroke ? 'B' : ($fill ? 'f' : 'S');
        $this->pages[$this->cur] .= $out . sprintf("%.2F %.2F %.2F %.2F re %s\n", $x, $py, $w, $h, $op);
    }

    public function hline(float $x1, float $x2, float $y, array $color = [0.75, 0.75, 0.75]): void
    {
        $py = $this->h - $y;
        $this->pages[$this->cur] .= sprintf(
            "%.3F %.3F %.3F RG 0.5 w %.2F %.2F m %.2F %.2F l S\n",
            $color[0], $color[1], $color[2], $x1, $py, $x2, $py
        );
    }

    // ------------------------------------------------------------------
    // Alur dokumen
    // ------------------------------------------------------------------

    public function ensureSpace(float $needed): void
    {
        if ($this->cur < 0) {
            $this->addPage();

            return;
        }
        if ($this->y + $needed > $this->h - $this->margin - 18) {
            $this->addPage();
        }
    }

    public function title(string $s, float $size = 16): void
    {
        $this->ensureSpace($size + 8);
        $this->text($this->margin, $this->y, $s, $size, true);
        $this->y += $size + 6;
    }

    public function line(string $s, float $size = 9, bool $bold = false, array $color = [0.25, 0.25, 0.25]): void
    {
        $this->ensureSpace($size + 5);
        $this->text($this->margin, $this->y, $s, $size, $bold, 'L', 0, $color);
        $this->y += $size + 4;
    }

    public function gap(float $pts = 8): void
    {
        $this->y += $pts;
    }

    public function heading(string $s, float $size = 11): void
    {
        $this->ensureSpace($size + 30);
        $this->gap(6);
        $this->text($this->margin, $this->y, $s, $size, true, 'L', 0, [0.12, 0.31, 0.47]);
        $this->y += $size + 4;
        $this->hline($this->margin, $this->margin + $this->contentWidth(), $this->y - 1, [0.12, 0.31, 0.47]);
        $this->y += 5;
    }

    /**
     * Tabel dengan header berulang.
     *
     * @param array $columns [['title'=>'Kode','w'=>70,'align'=>'L'], ...] (w = bobot relatif)
     * @param array $rows    tiap baris: array string, atau ['cells'=>[...], 'bold'=>true]
     */
    public function table(array $columns, array $rows, float $size = 8, float $rowH = 16): void
    {
        $total = array_sum(array_column($columns, 'w')) ?: 1;
        $avail = $this->contentWidth();
        $xs    = [];
        $ws    = [];
        $x     = $this->margin;
        foreach ($columns as $i => $col) {
            $ws[$i] = $avail * $col['w'] / $total;
            $xs[$i] = $x;
            $x     += $ws[$i];
        }

        $drawHeader = function () use ($columns, $xs, $ws, $size, $rowH, $avail) {
            $this->rect($this->margin, $this->y, $avail, $rowH + 2, [0.12, 0.31, 0.47]);
            foreach ($columns as $i => $col) {
                $this->text($xs[$i] + 4, $this->y + 4, $this->fit($col['title'], $ws[$i] - 8, $size, true), $size, true, $col['align'] ?? 'L', $ws[$i] - 8, [1, 1, 1]);
            }
            $this->y += $rowH + 2;
        };

        $this->ensureSpace($rowH * 3);
        $drawHeader();

        foreach ($rows as $n => $row) {
            $cells = $row['cells'] ?? $row;
            $bold  = (bool) ($row['bold'] ?? false);

            if ($this->y + $rowH > $this->h - $this->margin - 18) {
                $this->addPage();
                $drawHeader();
            }

            if ($bold) {
                $this->rect($this->margin, $this->y, $avail, $rowH, [0.93, 0.93, 0.93]);
            } elseif ($n % 2 === 1) {
                $this->rect($this->margin, $this->y, $avail, $rowH, [0.97, 0.97, 0.97]);
            }

            foreach ($columns as $i => $col) {
                $val = (string) ($cells[$i] ?? '');
                $this->text($xs[$i] + 4, $this->y + 4, $this->fit($val, $ws[$i] - 8, $size, $bold), $size, $bold, $col['align'] ?? 'L', $ws[$i] - 8);
            }

            $this->hline($this->margin, $this->margin + $avail, $this->y + $rowH, [0.88, 0.88, 0.88]);
            $this->y += $rowH;
        }

        $this->y += 6;
    }

    // ------------------------------------------------------------------

    public function output(): string
    {
        if ($this->cur < 0) {
            $this->addPage();
        }

        // Footer + nomor halaman (setelah jumlah halaman diketahui)
        $total = count($this->pages);
        foreach ($this->pages as $i => $_) {
            $this->cur = $i;
            $y         = $this->h - $this->margin + 4;
            $this->hline($this->margin, $this->w - $this->margin, $y - 4);
            $this->text($this->margin, $y, $this->footerLeft, 7.5, false, 'L', 0, [0.4, 0.4, 0.4]);
            $this->text($this->margin, $y, 'Halaman ' . ($i + 1) . ' dari ' . $total, 7.5, false, 'R', $this->contentWidth(), [0.4, 0.4, 0.4]);
        }

        $objs   = [];
        $objs[] = '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];
        for ($i = 0; $i < $total; $i++) {
            $kids[] = (5 + $i * 2) . ' 0 R';
        }
        $objs[] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $total . ' >>';
        $objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->pages as $i => $content) {
            $contentObj = 6 + $i * 2;
            $objs[]     = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                $this->w, $this->h, $contentObj
            );
            $objs[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . 'endstream';
        }

        $pdf     = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf      .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }
}