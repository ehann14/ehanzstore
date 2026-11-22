<?php

namespace App\Support;

use App\Models\CocAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien tipis untuk API resmi Clash of Clans (https://developer.clashofclans.com).
 *
 * Token dibaca dari .env (COC_API_TOKEN) lewat config('services.clash.token'), tidak pernah ditulis di kode.
 * Token terkunci ke alamat IP yang didaftarkan di key. Ada dua cara:
 *  1) Daftarkan IP KELUAR server hosting (bila IP-nya tetap), pakai alamat API resmi (bawaan).
 *  2) Server tanpa IP tetap / dipakai dari banyak tempat: daftarkan IP proxy RoyaleAPI (45.79.218.79)
 *     di key, lalu isi COC_API_BASE_URL=https://cocproxy.royaleapi.dev/v1 di .env.
 *
 * Yang bisa diambil dari API: Town Hall, level akun, level hero, nama, clan.
 * Yang TIDAK tersedia di API: jumlah tukang (builder), gems, dan CN. Tiga data itu tetap diisi manual.
 */
class ClashApi
{
    /** Alamat API resmi. Bisa diganti lewat COC_API_BASE_URL (mis. proxy) bila server tidak punya IP tetap. */
    private const DEFAULT_BASE = 'https://api.clashofclans.com/v1';

    /** Nama hero di API (huruf kecil) => kolom di tabel coc_accounts. */
    private const HEROES = [
        'barbarian king'  => 'barbarian_king_level',
        'archer queen'    => 'archer_queen_level',
        'minion prince'   => 'minion_prince_level',
        'grand warden'    => 'grand_warden_level',
        'royal champion'  => 'royal_champion_level',
        'dragon duke'     => 'dragon_duke_level',
    ];

    /**
     * @return array{ok:true,data:array}|array{ok:false,message:string}
     */
    public static function player(string $rawTag): array
    {
        $token = trim((string) config('services.clash.token'));

        if ($token === '') {
            return self::error('COC_API_TOKEN belum diisi di file .env server.');
        }

        $tag = self::cleanTag($rawTag);

        if ($tag === null) {
            return self::error('Format tag tidak valid. Contoh: #P2LQ8Y0R');
        }

        $cacheKey = 'coc:player:' . sha1($tag);

        if ($cached = Cache::get($cacheKey)) {
            return ['ok' => true, 'data' => $cached];
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(8)
                ->get(self::baseUrl() . '/players/' . rawurlencode($tag));
        } catch (\Throwable $e) {
            report($e);

            // Penyebab asli (mis. "cURL error 60: SSL certificate problem") hanya ditampilkan saat APP_DEBUG=true
            return self::error(
                'Tidak bisa terhubung ke server Clash of Clans. Coba lagi sebentar lagi.'
                . (config('app.debug') ? ' [' . mb_substr($e->getMessage(), 0, 300) . ']' : '')
            );
        }

        if ($response->successful()) {
            $data = self::mapPlayer((array) $response->json());

            // Cache singkat supaya klik berulang tidak menghabiskan kuota API
            Cache::put($cacheKey, $data, now()->addMinute());

            return ['ok' => true, 'data' => $data];
        }

        $reason  = (string) $response->json('reason', '');
        $apiText = (string) $response->json('message', '');

        // Catat penyebab (tanpa token) untuk pemecahan masalah
        Log::warning('ClashApi: permintaan ditolak', ['status' => $response->status(), 'reason' => $reason]);

        return match (true) {
            $response->status() === 404 => self::error('Tag tidak ditemukan. Periksa lagi tag-nya.'),
            $response->status() === 403 && $reason === 'accessDenied.invalidIp' => self::error(
                'IP server tidak cocok dengan IP di API key. Daftarkan IP server di developer.clashofclans.com.'
                . ($apiText !== '' ? ' Detail dari API: ' . $apiText : '')
            ),
            $response->status() === 403 => self::error('Token API ditolak (' . ($reason ?: 'forbidden') . '). Periksa COC_API_TOKEN.'),
            $response->status() === 429 => self::error('Terlalu banyak permintaan ke API Clash of Clans. Tunggu sebentar.'),
            $response->status() === 503 => self::error('Clash of Clans sedang maintenance. Coba lagi nanti.'),
            default                     => self::error('API Clash of Clans error (' . $response->status() . ').'),
        };
    }

    private static function baseUrl(): string
    {
        $url = trim((string) config('services.clash.base_url'));

        return rtrim($url !== '' ? $url : self::DEFAULT_BASE, '/');
    }

    /** Rapikan tag ke bentuk "#XXXX" yang diterima API; null bila formatnya tidak mungkin valid. */
    public static function cleanTag(string $raw): ?string
    {
        $tag = CocAccount::normalizeTag($raw);

        if ($tag === null) {
            return null;
        }

        // Tag Clash tidak memakai huruf O (selalu angka 0)
        $tag = str_replace('O', '0', $tag);

        return preg_match('/^#[0289PYLQGRJCUV]{3,15}$/', $tag) ? $tag : null;
    }

    /** Ubah respons API menjadi data yang dipakai form akun. */
    public static function mapPlayer(array $p): array
    {
        $heroes = array_fill_keys(array_values(self::HEROES), 0);

        foreach ((array) ($p['heroes'] ?? []) as $hero) {
            // Hanya hero kampung utama (Battle Machine / Copter ada di Builder Base)
            if (($hero['village'] ?? 'home') !== 'home') {
                continue;
            }

            $column = self::HEROES[strtolower((string) ($hero['name'] ?? ''))] ?? null;

            if ($column) {
                $heroes[$column] = (int) ($hero['level'] ?? 0);
            }
        }

        return [
            'tag'           => (string) ($p['tag'] ?? ''),
            'name'          => (string) ($p['name'] ?? ''),
            'town_hall'     => (int) ($p['townHallLevel'] ?? 0),
            'account_level' => (int) ($p['expLevel'] ?? 0),
            'heroes'        => $heroes,
            'clan'          => $p['clan']['name'] ?? null,
        ];
    }

    private static function error(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}