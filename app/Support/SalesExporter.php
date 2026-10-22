<?php

namespace App\Support;

use App\Support\Export\PdfWriter;
use App\Support\Export\XlsxWriter;
use Illuminate\Support\Carbon;

/**
 * Membuat file Excel (.xlsx) dan PDF untuk daftar transaksi dan laporan bulanan.
 * Semua method mengembalikan isi file (string biner).
 */
class SalesExporter
{
    private static function stamp(): string
    {
        return Carbon::now(SalesReport::TZ)->locale('id')->translatedFormat('d M Y H:i') . ' WIB';
    }

    private static function rp(float|int $n): string
    {
        return SalesReport::rupiah($n);
    }

    // ==================================================================
    // DAFTAR TRANSAKSI
    // ==================================================================

    /**
     * @param array $rows   hasil SalesReport::rows()
     * @param string $subtitle contoh: "Periode: 01 Okt 2026 - 31 Okt 2026 | Status: Selesai"
     */
    public static function transactionsXlsx(array $rows, string $subtitle): string
    {
        $totals = SalesReport::totals($rows);

        $data = [
            [['Daftar Transaksi - EHANZSTORE.ID', 'title']],
            [[$subtitle, 'bold']],
            [['Dibuat: ' . self::stamp(), 'default']],
            [],
            self::headerRow(['No', 'Tanggal (WIB)', 'Kode Transaksi', 'Kode Akun', 'Nama Akun/Clan', 'Jenis', 'TH', 'Pembeli', 'Harga Jual', 'Modal', 'Laba', 'Status', 'Pembayaran', 'Metode', 'Diproses Oleh']),
        ];

        foreach ($rows as $i => $r) {
            $data[] = [
                [$i + 1, 'int'], [$r['time'], 'text'], [$r['code'], 'text'], [$r['acc_code'], 'text'],
                [$r['name'], 'text'], [$r['type'], 'text'], [$r['th'], 'text'], [$r['buyer'], 'text'],
                [$r['price'], 'money'], [$r['cost'], 'money'], [$r['profit'], 'money'],
                [$r['status'], 'text'], [$r['payment'], 'text'], [$r['method'], 'text'], [$r['processor'], 'text'],
            ];
        }

        $data[] = [
            ['', 'textBold'], ['', 'textBold'], ['', 'textBold'], ['', 'textBold'],
            ['TOTAL (' . $totals['count'] . ' transaksi selesai)', 'textBold'], ['', 'textBold'], ['', 'textBold'], ['', 'textBold'],
            [$totals['price'], 'moneyBold'], [$totals['cost'], 'moneyBold'], [$totals['profit'], 'moneyBold'],
            ['', 'textBold'], ['', 'textBold'], ['', 'textBold'], ['', 'textBold'],
        ];

        return (new XlsxWriter())
            ->addSheet('Transaksi', $data, [5, 18, 16, 12, 28, 8, 8, 20, 16, 16, 16, 12, 13, 14, 16], ['A1:O1'], 5)
            ->output();
    }

    public static function transactionsPdf(array $rows, string $subtitle): string
    {
        $totals = SalesReport::totals($rows);

        $pdf = new PdfWriter(true);
        $pdf->setFooter('EHANZSTORE.ID - Daftar Transaksi - dibuat ' . self::stamp());
        $pdf->addPage();
        $pdf->title('Daftar Transaksi');
        $pdf->line($subtitle, 9, true);
        $pdf->line('Dibuat: ' . self::stamp() . '   |   Omzet = Harga Jual - Modal (transaksi selesai)', 8);
        $pdf->gap(6);

        $table = [];
        foreach ($rows as $i => $r) {
            $table[] = [
                (string) ($i + 1), $r['time'], $r['code'], $r['acc_code'], $r['name'] . ($r['th'] !== '-' ? ' (' . $r['th'] . ')' : ' (' . $r['type'] . ')'),
                $r['buyer'], self::rp($r['price']), self::rp($r['cost']), self::rp($r['profit']), $r['status'],
            ];
        }
        $table[] = [
            'cells' => ['', '', '', '', 'TOTAL (' . $totals['count'] . ' selesai)', '', self::rp($totals['price']), self::rp($totals['cost']), self::rp($totals['profit']), ''],
            'bold'  => true,
        ];

        $pdf->table([
            ['title' => 'No', 'w' => 4],
            ['title' => 'Tanggal', 'w' => 12],
            ['title' => 'Kode Transaksi', 'w' => 12],
            ['title' => 'Kode Akun', 'w' => 9],
            ['title' => 'Akun / Clan', 'w' => 24],
            ['title' => 'Pembeli', 'w' => 13],
            ['title' => 'Harga Jual', 'w' => 12, 'align' => 'R'],
            ['title' => 'Modal', 'w' => 12, 'align' => 'R'],
            ['title' => 'Laba', 'w' => 12, 'align' => 'R'],
            ['title' => 'Status', 'w' => 8],
        ], $table, 7.5);

        return $pdf->output();
    }

    // ==================================================================
    // LAPORAN BULANAN
    // ==================================================================

    public static function monthlyXlsx(array $report): string
    {
        $s    = $report['summary'];
        $p    = $report['prev'];
        $label = $report['range']['label'];

        $summarySheet = [
            [['Laporan Bulanan - EHANZSTORE.ID', 'title']],
            [['Periode: ' . $label, 'bold']],
            [['Dibuat: ' . self::stamp() . '  |  Omzet = Harga Jual - Modal (transaksi selesai)', 'default']],
            [],
            self::headerRow(['Ringkasan', $label, $report['prevLabel']]),
            [['Jumlah terjual (akun + clan)', 'text'], [$s['sold'], 'int'], [$p['sold'], 'int']],
            [['   Akun pemain', 'text'], [$s['accounts'], 'int'], [$p['accounts'], 'int']],
            [['   Clan', 'text'], [$s['clans'], 'int'], [$p['clans'], 'int']],
            [['Total harga jual', 'text'], [$s['revenue'], 'money'], [$p['revenue'], 'money']],
            [['Total modal', 'text'], [$s['cost'], 'money'], [$p['cost'], 'money']],
            [['OMZET (Harga Jual - Modal)', 'textBold'], [$s['profit'], 'moneyBold'], [$p['profit'], 'moneyBold']],
            [],
            [['TH Terlaris: ' . ($report['topTh'] ? implode(', ', array_map(fn ($t) => 'TH ' . $t, $report['topTh'])) : '-'), 'bold']],
            self::headerRow(['Peringkat', 'Town Hall', 'Jumlah Terjual', 'Total Harga Jual', 'Omzet']),
        ];

        if ($report['townHalls']->isEmpty()) {
            $summarySheet[] = [['Belum ada akun pemain terjual pada bulan ini.', 'text']];
        }

        foreach ($report['townHalls'] as $i => $t) {
            $summarySheet[] = [[$i + 1, 'int'], ['TH ' . $t->th, 'text'], [$t->sold, 'int'], [$t->revenue, 'money'], [$t->profit, 'money']];
        }

        if ($s['clans'] > 0) {
            $summarySheet[] = [];
            $summarySheet[] = [['Catatan: ' . $s['clans'] . ' clan terjual dihitung terpisah dan tidak masuk peringkat TH.', 'default']];
        }

        $rows   = SalesReport::rows($report['transactions']);
        $totals = SalesReport::totals($rows);

        $tx = [
            [['Transaksi Selesai - ' . $label, 'title']],
            [],
            self::headerRow(['No', 'Tanggal (WIB)', 'Kode Transaksi', 'Kode Akun', 'Nama Akun/Clan', 'Jenis', 'TH', 'Pembeli', 'Harga Jual', 'Modal', 'Omzet']),
        ];
        foreach ($rows as $i => $r) {
            $tx[] = [
                [$i + 1, 'int'], [$r['time'], 'text'], [$r['code'], 'text'], [$r['acc_code'], 'text'],
                [$r['name'], 'text'], [$r['type'], 'text'], [$r['th'], 'text'], [$r['buyer'], 'text'],
                [$r['price'], 'money'], [$r['cost'], 'money'], [$r['profit'], 'money'],
            ];
        }
        $tx[] = [
            ['', 'textBold'], ['', 'textBold'], ['', 'textBold'], ['', 'textBold'],
            ['TOTAL', 'textBold'], ['', 'textBold'], ['', 'textBold'], ['', 'textBold'],
            [$totals['price'], 'moneyBold'], [$totals['cost'], 'moneyBold'], [$totals['profit'], 'moneyBold'],
        ];

        return (new XlsxWriter())
            ->addSheet('Ringkasan', $summarySheet, [34, 22, 22, 20, 20], ['A1:E1'])
            ->addSheet('Transaksi', $tx, [5, 18, 16, 12, 28, 8, 8, 20, 16, 16, 16], ['A1:K1'], 3)
            ->output();
    }

    public static function monthlyPdf(array $report): string
    {
        $s     = $report['summary'];
        $p     = $report['prev'];
        $label = $report['range']['label'];

        $pdf = new PdfWriter(false);
        $pdf->setFooter('EHANZSTORE.ID - Laporan Bulanan ' . $label . ' - dibuat ' . self::stamp());
        $pdf->addPage();
        $pdf->title('Laporan Bulanan ' . $label);
        $pdf->line('EHANZSTORE.ID   |   Dibuat: ' . self::stamp(), 8.5);
        $pdf->line('Omzet = Harga Jual - Modal, dihitung dari transaksi selesai.', 8.5);

        $pdf->heading('Ringkasan');
        $pdf->table([
            ['title' => 'Keterangan', 'w' => 40],
            ['title' => $label, 'w' => 30, 'align' => 'R'],
            ['title' => $report['prevLabel'], 'w' => 30, 'align' => 'R'],
        ], [
            ['Jumlah terjual (akun + clan)', (string) $s['sold'], (string) $p['sold']],
            ['   Akun pemain', (string) $s['accounts'], (string) $p['accounts']],
            ['   Clan', (string) $s['clans'], (string) $p['clans']],
            ['Total harga jual', self::rp($s['revenue']), self::rp($p['revenue'])],
            ['Total modal', self::rp($s['cost']), self::rp($p['cost'])],
            ['cells' => ['OMZET (Harga Jual - Modal)', self::rp($s['profit']), self::rp($p['profit'])], 'bold' => true],
        ], 9, 18);

        $top = $report['topTh'] ? implode(', ', array_map(fn ($t) => 'TH ' . $t, $report['topTh'])) : '-';
        $pdf->heading('TH Terlaris: ' . $top);

        if ($report['townHalls']->isEmpty()) {
            $pdf->line('Belum ada akun pemain terjual pada bulan ini.', 9);
        } else {
            $th = [];
            foreach ($report['townHalls'] as $i => $t) {
                $th[] = [(string) ($i + 1), 'TH ' . $t->th, (string) $t->sold, self::rp($t->revenue), self::rp($t->profit)];
            }
            $pdf->table([
                ['title' => 'Peringkat', 'w' => 12],
                ['title' => 'Town Hall', 'w' => 16],
                ['title' => 'Jumlah Terjual', 'w' => 20, 'align' => 'R'],
                ['title' => 'Total Harga Jual', 'w' => 26, 'align' => 'R'],
                ['title' => 'Omzet', 'w' => 26, 'align' => 'R'],
            ], $th, 9, 18);
        }

        if ($s['clans'] > 0) {
            $pdf->line($s['clans'] . ' clan terjual dihitung terpisah dan tidak masuk peringkat TH.', 8.5);
        }

        $rows   = SalesReport::rows($report['transactions']);
        $totals = SalesReport::totals($rows);

        $pdf->heading('Daftar Transaksi (' . count($rows) . ')');
        if (! $rows) {
            $pdf->line('Tidak ada transaksi selesai pada bulan ini.', 9);
        } else {
            $table = [];
            foreach ($rows as $r) {
                $table[] = [
                    $r['time'], $r['code'], $r['acc_code'] . ' ' . $r['name'] . ' (' . ($r['th'] !== '-' ? $r['th'] : $r['type']) . ')',
                    self::rp($r['price']), self::rp($r['cost']), self::rp($r['profit']),
                ];
            }
            $table[] = ['cells' => ['TOTAL', '', '', self::rp($totals['price']), self::rp($totals['cost']), self::rp($totals['profit'])], 'bold' => true];

            $pdf->table([
                ['title' => 'Tanggal', 'w' => 15],
                ['title' => 'Kode', 'w' => 15],
                ['title' => 'Akun / Clan', 'w' => 31],
                ['title' => 'Harga Jual', 'w' => 14, 'align' => 'R'],
                ['title' => 'Modal', 'w' => 13, 'align' => 'R'],
                ['title' => 'Omzet', 'w' => 13, 'align' => 'R'],
            ], $table, 7);
        }

        return $pdf->output();
    }

    // ------------------------------------------------------------------

    private static function headerRow(array $titles): array
    {
        return array_map(fn ($t) => [$t, 'header'], $titles);
    }
}