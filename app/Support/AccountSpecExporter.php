<?php

namespace App\Support;

use App\Models\CocAccount;
use App\Models\Sale;
use App\Support\Export\XlsxWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Membuat file Excel (.xlsx) "Spek Akun" untuk dibagikan / diposting.
 *
 * Tata letak mengikuti contoh TRX_AKUN_OTOMATIS.xlsx (sheet "SPEK COC"):
 * satu akun = satu kotak (label + isi), 6 kotak per baris, tiap kotak berisi
 * KODE, email, SPEK AKUN (tag), TH, LEVEL, HERO, TUKANG, GEMS, CN,
 * KEAMANAN AKUN (protek, ganti gmail) dan Harga.
 *
 * Mengembalikan isi file (string biner), sama seperti SalesExporter.
 */
class AccountSpecExporter
{
    /** Jumlah kotak akun per baris. */
    private const PER_ROW = 6;

    /** Lebar satu kotak: kolom label, kolom isi, kolom pemisah. */
    private const BLOCK_COLS = 3;

    /** Tinggi satu kotak termasuk satu baris kosong pemisah. */
    private const BLOCK_ROWS = 16;

    /** Baris pertama kotak (0 = baris 1). Baris 1-3 dipakai judul. */
    private const FIRST_ROW = 3;

    private const MONTHS = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];

    /**
     * @param Collection<int, CocAccount> $accounts akun pemain (clan tidak ikut), sebaiknya sudah di-eager-load accountEmail
     * @param string $subtitle contoh: "Akun Ready"
     */
    public static function xlsx(Collection $accounts, string $subtitle = ''): string
    {
        $accounts = $accounts->values();
        $stamp    = Carbon::now(Sale::DISPLAY_TZ)->format('d M Y H:i') . ' WIB';

        $grid = [];

        $grid[0][0] = ['Spek Akun - EHANZSTORE.ID' . ($subtitle !== '' ? ' - ' . $subtitle : '') . ' (' . $accounts->count() . ' akun, dibuat ' . $stamp . ')', 'bold'];
        $grid[1][0] = ['1. KOTA UTAMA, 2. Profile, 3. Aula Pahlawan, 4. GEAR, 5. CN, 6. Latar', 'default'];

        if ($accounts->isEmpty()) {
            $grid[self::FIRST_ROW][0] = ['Tidak ada akun yang cocok untuk diexport.', 'default'];
        }

        foreach ($accounts as $i => $account) {
            $top  = self::FIRST_ROW + intdiv($i, self::PER_ROW) * self::BLOCK_ROWS;
            $left = ($i % self::PER_ROW) * self::BLOCK_COLS;

            foreach (self::block($account) as $offset => $cells) {
                foreach ($cells as $c => $cell) {
                    $grid[$top + $offset][$left + $c] = $cell;
                }
            }
        }

        // Ubah grid renggang menjadi baris padat (sel kosong = null)
        $maxRow = $grid ? max(array_keys($grid)) : 0;
        $maxCol = (min(max($accounts->count(), 1), self::PER_ROW) * self::BLOCK_COLS) - 1;
        $rows   = [];

        for ($r = 0; $r <= $maxRow; $r++) {
            $row = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $row[] = $grid[$r][$c] ?? null;
            }
            $rows[] = $row;
        }

        $widths = [];
        for ($b = 0; $b < min(max($accounts->count(), 1), self::PER_ROW); $b++) {
            array_push($widths, 26, 18, 4);
        }

        $lastCol = XlsxWriter::columnName(min($maxCol, 4));

        return (new XlsxWriter())
            ->addSheet('SPEK COC', $rows, $widths, ['A1:' . $lastCol . '1', 'A2:' . $lastCol . '2'])
            ->output();
    }

    /**
     * Satu kotak akun: [offset baris => [kolom 0 (label), kolom 1 (isi)]].
     */
    private static function block(CocAccount $a): array
    {
        [$statusText, $statusStyle] = self::status($a);

        return [
            0  => [['KODE :', 'textBold'], [$a->account_code . ' (' . $statusText . ')', $statusStyle]],
            1  => [[$a->accountEmail?->email ?? '', 'text'], ['', 'text']],
            2  => [['SPEK AKUN :', 'textBold'], [$a->player_tag ?? '', 'text']],
            3  => [['TH', 'text'], [self::num($a->town_hall), 'int']],
            4  => [['LEVEL AKUN', 'text'], [self::num($a->account_level), 'int']],
            5  => [['HERO', 'text'], [self::heroes($a), 'text']],
            6  => [['TUKANG', 'text'], [self::num($a->builder_count), 'int']],
            7  => [['GEMS', 'text'], [self::num($a->gems), 'int']],
            8  => [['CN', 'text'], [self::cn($a->cn_information), 'text']],
            10 => [['KEAMANAN AKUN :', 'textBold'], ['', 'textBold']],
            11 => [['Protek on/off', 'text'], [$a->protection_status === 'Protected' ? 'on' : 'off', 'text']],
            12 => [['change gmail/Take sgmail', 'text'], [self::emailChange($a), 'text']],
            14 => [['Harga Akun :', 'textBold'], [self::num($a->price), 'int']],
        ];
    }

    /** @return array{0:string,1:string} teks status + gaya sel */
    private static function status(CocAccount $a): array
    {
        if ($a->sale_status === 'sold') {
            return ['Sold', 'codeSold'];
        }

        if ($a->sale_status === 'archived') {
            return ['Arsip', 'textBold'];
        }

        if ($a->isBooked()) {
            return ['DP', 'codeDp'];
        }

        return ['Ready', 'codeReady'];
    }

    /** Level hero dipisah "/", urut BK/AQ/MP/GW/RC/DD; hero yang belum ada dilewati. Contoh: 76/66/55/45/28/10 */
    private static function heroes(CocAccount $a): string
    {
        $levels = [
            $a->barbarian_king_level,
            $a->archer_queen_level,
            $a->minion_prince_level,
            $a->grand_warden_level,
            $a->royal_champion_level,
            $a->dragon_duke_level,
        ];

        return implode('/', array_map('intval', array_filter($levels, fn ($v) => $v !== null && (int) $v > 0)));
    }

    /** Status ganti Gmail: "take sgmail", "change", atau "change 25 okt" / "change 8 nov 21.07". */
    private static function emailChange(CocAccount $a): string
    {
        if ($a->email_change_status !== 'Changeable') {
            return 'take sgmail';
        }

        $at = $a->email_change_available_at;

        if (! $at) {
            return 'change';
        }

        $d    = $at->clone()->timezone(Sale::DISPLAY_TZ);
        $text = 'change ' . $d->day . ' ' . self::MONTHS[$d->month - 1];

        return $d->format('Hi') === '0000' ? $text : $text . ' ' . $d->format('H.i');
    }

    /** Info CN: angka murni jadi angka (500), selain itu apa adanya (Free). */
    private static function cn(?string $cn): int|string
    {
        $cn = trim((string) $cn);

        return ctype_digit($cn) ? (int) $cn : $cn;
    }

    /** Angka bulat atau kosong bila belum diisi. */
    private static function num(mixed $value): int|string
    {
        return $value === null || $value === '' ? '' : (int) round((float) $value);
    }
}