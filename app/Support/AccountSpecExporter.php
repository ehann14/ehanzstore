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
 * Tata letak & tampilan mengikuti contoh TRX_AKUN_OTOMATIS.xlsx (sheet "SPEK COC"):
 * satu akun = satu kotak (label + isi), 6 kotak per baris, tiap kotak berisi
 * KODE, email, SPEK AKUN (tag), TH, LEVEL, HERO, TUKANG, GEMS, CN,
 * KEAMANAN AKUN (protek, ganti gmail) dan Harga.
 * Semua sel kotak bergaris tabel (termasuk baris kosong), teks rata kiri,
 * dan baris KODE berwarna biru seperti contoh.
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
        $grid[1][0] = ['1. KOTA UTAMA, 2. Profile, 3. Aula Pahlawan, 4. GEAR, 5. CN, 6. Latar', 'specNote'];

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
            array_push($widths, 25.7, 19.7, 8.7);
        }

        $lastCol = XlsxWriter::columnName(min($maxCol, 4));

        return (new XlsxWriter())
            ->addSheet('SPEK COC', $rows, $widths, ['A1:' . $lastCol . '1', 'A2:' . $lastCol . '2'])
            ->output();
    }

    /**
     * Satu kotak akun: [offset baris => [kolom 0 (label), kolom 1 (isi)]].
     * Setiap baris 0-14 selalu terisi (walau kosong) supaya garis tabelnya utuh.
     */
    private static function block(CocAccount $a): array
    {
        $statusText = self::status($a);
        $blank      = ['', 'specText'];

        return [
            0  => [['KODE :', 'specHead'], [$a->account_code . ' (' . $statusText . ')', 'specHead']],
            1  => [[$a->accountEmail?->email ?? '', 'specText'], $blank],
            2  => [['SPEK AKUN :', 'specText'], [$a->player_tag ?? '', 'specText']],
            3  => [['TH', 'specText'], [self::num($a->town_hall), 'specNum']],
            4  => [['LEVEL AKUN', 'specText'], [self::num($a->account_level), 'specNum']],
            5  => [['HERO', 'specText'], [self::heroes($a), 'specText']],
            6  => [['TUKANG', 'specText'], [self::num($a->builder_count), 'specNum']],
            7  => [['GEMS', 'specText'], [self::num($a->gems), 'specNum']],
            8  => [['CN', 'specText'], [self::cn($a->cn_information), 'specNum']],
            9  => [$blank, $blank],
            10 => [['KEAMANAN AKUN :', 'specText'], $blank],
            11 => [['Protek on/off', 'specText'], [$a->protection_status === 'Protected' ? 'on' : 'off', 'specText']],
            12 => [['change gmail/Take sgmail', 'specText'], [self::emailChange($a), 'specText']],
            13 => [$blank, $blank],
            14 => [['Harga Akun :', 'specText'], [self::num($a->price), 'specInt']],
        ];
    }

    /** Teks status di sebelah kode: Sold / Arsip / DP / Ready. */
    private static function status(CocAccount $a): string
    {
        if ($a->sale_status === 'sold') {
            return 'Sold';
        }

        if ($a->sale_status === 'archived') {
            return 'Arsip';
        }

        return $a->isBooked() ? 'DP' : 'Ready';
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