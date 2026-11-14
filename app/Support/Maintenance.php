<?php

namespace App\Support;

use App\Models\StoreSetting;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Mode maintenance berbasis database (tabel store_settings), bisa dinyalakan
 * dari panel Super Admin tanpa SSH / "php artisan down".
 *
 * Kunci yang dipakai di store_settings:
 *   maintenance_mode, maintenance_title, maintenance_message,
 *   maintenance_until, maintenance_image (path relatif, mis. "maintenance/xxx.webp"),
 *   maintenance_theme (dev = perbaikan sistem, promo = persiapan promo besar)
 */
class Maintenance
{
    public const DEFAULT_TITLE   = 'Sedang Maintenance';
    public const DEFAULT_MESSAGE = 'Kami sedang melakukan perawatan sistem supaya belanja kamu makin nyaman dan aman. Website akan segera kembali normal.';

    public const DEFAULT_THEME = 'dev';

    /**
     * Tema halaman maintenance.
     *   dev   : dipakai saat mengerjakan kode / database (biru, roda gigi).
     *   promo : dipakai saat menyiapkan promo besar (hangat, hadiah & label diskon).
     * Judul dan pesan bawaan ikut berganti sesuai tema selama belum diubah manual.
     */
    public const THEMES = [
        'dev' => [
            'label'    => 'Perbaikan Sistem',
            'hint'     => 'Dipakai saat kamu mengerjakan kode atau database. Nuansa biru dengan ilustrasi roda gigi.',
            'title'    => self::DEFAULT_TITLE,
            'message'  => self::DEFAULT_MESSAGE,
            'pill'     => 'Sedang dalam perbaikan',
            'eta'      => 'Perkiraan selesai',
            'eta_done' => 'Segera kembali...',
            'wa_label' => 'Chat Admin',
            'wa_text'  => 'Halo Admin EHANZSTORE, saya mau tanya soal akun.',
            'color'    => '#020617',
        ],
        'promo' => [
            'label'    => 'Persiapan Promo Besar',
            'hint'     => 'Dipakai saat menyiapkan promo besar. Nuansa hangat oranye-pink dengan ilustrasi hadiah dan label diskon.',
            'title'    => 'Promo Besar Segera Hadir',
            'message'  => 'Kami sedang menyiapkan promo besar: harga akun spesial dan kejutan lainnya. Website ditutup sebentar supaya semuanya siap. Pantau terus ya!',
            'pill'     => 'Persiapan promo',
            'eta'      => 'Promo dimulai',
            'eta_done' => 'Promo segera dimulai!',
            'wa_label' => 'Tanya Promo',
            'wa_text'  => 'Halo Admin EHANZSTORE, saya mau tanya soal promo.',
            'color'    => '#0a0510',
        ],
    ];

    protected static ?array $cache = null;

    /** Semua pengaturan maintenance (satu query per request). */
    public static function settings(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $raw = [];

        try {
            $raw = StoreSetting::where('key', 'like', 'maintenance\_%')->pluck('value', 'key')->all();
        } catch (Throwable $e) {
            // Tabel belum ada / database bermasalah: anggap maintenance mati supaya situs tidak terkunci.
        }

        $until = null;

        if (! empty($raw['maintenance_until'])) {
            try {
                $until = Carbon::parse($raw['maintenance_until']);
            } catch (Throwable $e) {
                $until = null;
            }
        }

        // Gambar kustom hanya diterima bila berada di folder uploads/maintenance.
        $image = Uploads::normalize($raw['maintenance_image'] ?? '');

        if (! preg_match('#^maintenance/[^/]+$#', $image)) {
            $image = '';
        }

        $theme = (string) ($raw['maintenance_theme'] ?? self::DEFAULT_THEME);
        $theme = isset(self::THEMES[$theme]) ? $theme : self::DEFAULT_THEME;

        $titleRaw   = trim((string) ($raw['maintenance_title'] ?? ''));
        $messageRaw = trim((string) ($raw['maintenance_message'] ?? ''));

        return static::$cache = [
            'active'    => ($raw['maintenance_mode'] ?? '0') === '1',
            'theme'     => $theme,
            'title'     => self::resolveText($titleRaw, 'title', $theme),
            'message'   => self::resolveText($messageRaw, 'message', $theme),
            'title_raw'   => $titleRaw,
            'message_raw' => $messageRaw,
            'until'     => $until,
            'image'     => $image,
            'image_url' => $image !== '' ? '/uploads/' . $image : '',
        ];
    }

    /**
     * Pengaturan dengan tema tertentu (dipakai pratinjau). Tema tidak dikenal / kosong = tema tersimpan.
     * Judul dan pesan yang masih berupa teks bawaan salah satu tema ikut memakai teks bawaan tema ini.
     */
    public static function forTheme(?string $theme): array
    {
        $s     = static::settings();
        $theme = isset(self::THEMES[(string) $theme]) ? (string) $theme : $s['theme'];

        return array_merge($s, [
            'theme'   => $theme,
            'title'   => self::resolveText($s['title_raw'], 'title', $theme),
            'message' => self::resolveText($s['message_raw'], 'message', $theme),
        ]);
    }

    /** Teks kosong atau masih teks bawaan salah satu tema -> teks bawaan tema $theme; selain itu dipakai apa adanya. */
    private static function resolveText(string $raw, string $field, string $theme): string
    {
        $defaults = array_column(self::THEMES, $field);

        return ($raw === '' || in_array($raw, $defaults, true))
            ? self::THEMES[$theme][$field]
            : $raw;
    }

    public static function isActive(): bool
    {
        return static::settings()['active'];
    }

    public static function forget(): void
    {
        static::$cache = null;
    }

    /**
     * Simpan pengaturan. $until berupa waktu WIB (atau null).
     *
     * $image: null = gambar tidak diubah, '' = hapus gambar kustom (kembali ke ilustrasi bawaan),
     * "maintenance/xxx.webp" = pakai gambar baru.
     *
     * $theme: null = tema tidak diubah, 'dev' / 'promo' = ganti tema.
     */
    public static function save(bool $active, ?string $title, ?string $message, ?Carbon $until, ?string $image = null, ?string $theme = null): void
    {
        StoreSetting::set('maintenance_mode', $active ? '1' : '0');
        StoreSetting::set('maintenance_title', (string) $title);
        StoreSetting::set('maintenance_message', (string) $message);
        StoreSetting::set('maintenance_until', $until ? $until->clone()->utc()->toIso8601String() : '');

        if ($image !== null) {
            StoreSetting::set('maintenance_image', $image);
        }

        if ($theme !== null && isset(self::THEMES[$theme])) {
            StoreSetting::set('maintenance_theme', $theme);
        }

        static::forget();
    }
}