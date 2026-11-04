<?php

namespace App\Support;

use App\Models\PushSubscription;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Web Push tanpa library tambahan (cukup ekstensi openssl bawaan PHP).
 *
 * Cara kerja: server mengirim "ketukan" kosong ke layanan push (Google/Mozilla/Apple) yang
 * ditandatangani kunci VAPID. Service worker di HP lalu mengambil isi notifikasinya dari
 * server (/admin/notifications/push/pending) dan menampilkannya. Jadi isi notifikasi tidak
 * lewat pihak ketiga, dan tidak perlu enkripsi payload.
 *
 * Kunci VAPID dibuat otomatis sekali dan disimpan di tabel store_settings.
 */
class WebPush
{
    private const KEY_PRIVATE = 'webpush_vapid_private';
    private const KEY_PUBLIC  = 'webpush_vapid_public';

    protected static ?array $keys = null;

    /** Kunci publik (base64url) untuk applicationServerKey di browser. Null bila tidak tersedia. */
    public static function publicKey(): ?string
    {
        try {
            return static::keys()['public'];
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Kirim "ketukan" push ke semua perangkat milik user-user ini.
     *
     * Dikirim langsung (bukan menunggu akhir request) supaya pasti jalan di hosting mana pun.
     * Timeout dibuat pendek (maks ~4 dtk per perangkat) dan semua error ditelan, jadi pembeli
     * yang booking/mengirim ulasan tidak pernah gagal gara-gara notifikasi.
     *
     * @param array<int,int> $userIds
     */
    public static function notifyUsers(array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));

        if (! $userIds) {
            return;
        }

        try {
            PushSubscription::whereIn('user_id', $userIds)->get()->each(fn ($sub) => static::send($sub));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Kirim sekarang ke semua perangkat satu user, lengkap dengan hasil per perangkat (untuk tombol "Kirim tes").
     *
     * @return array<int, array{ok:bool,status:int|null,message:string,device:string}>
     */
    public static function sendNowToUser(int $userId): array
    {
        $results = [];

        foreach (PushSubscription::where('user_id', $userId)->get() as $sub) {
            $results[] = static::deliver($sub) + ['device' => static::deviceLabel($sub)];
        }

        return $results;
    }

    /** Kirim satu ketukan push. Mengembalikan true bila diterima layanan push. */
    public static function send(PushSubscription $sub): bool
    {
        return static::deliver($sub)['ok'];
    }

    /**
     * Kirim satu ketukan push dan laporkan hasilnya. Langganan yang sudah dicabut (404/410) dihapus otomatis.
     *
     * @return array{ok:bool,status:int|null,message:string}
     */
    public static function deliver(PushSubscription $sub): array
    {
        try {
            $origin = static::origin($sub->endpoint);
            $keys   = static::keys();

            $response = Http::withHeaders([
                'Authorization'  => 'vapid t=' . static::jwt($origin, $keys['pem']) . ', k=' . $keys['public'],
                'TTL'            => '86400',
                'Urgency'        => 'high',
                'Content-Length' => '0',
            ])->connectTimeout(2)->timeout(4)->send('POST', $sub->endpoint, ['body' => '']);

            $status = $response->status();

            if (in_array($status, [404, 410], true)) {
                $sub->delete();

                return ['ok' => false, 'status' => $status, 'message' => 'Langganan di HP ini sudah kedaluwarsa. Matikan lalu aktifkan lagi.'];
            }

            if ($response->successful()) {
                $sub->forceFill(['last_sent_at' => now()])->saveQuietly();

                return ['ok' => true, 'status' => $status, 'message' => 'Diterima layanan push (' . static::host($sub->endpoint) . ').'];
            }

            report(new \RuntimeException('Web Push ditolak (' . $status . ') ' . $origin . ': ' . mb_substr($response->body(), 0, 200)));

            return ['ok' => false, 'status' => $status, 'message' => static::explain($status)];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'status' => null, 'message' => 'Server tidak bisa menghubungi layanan push: ' . mb_substr($e->getMessage(), 0, 140)];
        }
    }

    protected static function explain(int $status): string
    {
        return match (true) {
            in_array($status, [401, 403], true) => "Ditolak layanan push ({$status}): kunci VAPID tidak cocok. Matikan lalu aktifkan lagi notifikasi di HP.",
            $status === 400 => 'Ditolak layanan push (400): permintaan tidak valid.',
            $status === 413 => 'Ditolak layanan push (413).',
            $status === 429 => 'Terlalu banyak permintaan ke layanan push (429). Coba lagi sebentar.',
            $status >= 500  => "Layanan push sedang bermasalah ({$status}). Coba lagi nanti.",
            default         => "Ditolak layanan push (kode {$status}).",
        };
    }

    protected static function host(string $endpoint): string
    {
        return (string) (parse_url($endpoint, PHP_URL_HOST) ?: 'push');
    }

    protected static function deviceLabel(PushSubscription $sub): string
    {
        $ua = (string) $sub->user_agent;

        $os      = str_contains($ua, 'Android') ? 'Android' : (preg_match('/iPhone|iPad/', $ua) ? 'iPhone/iPad' : (str_contains($ua, 'Windows') ? 'Windows' : (str_contains($ua, 'Mac') ? 'Mac' : 'Perangkat')));
        $browser = str_contains($ua, 'Edg/') ? 'Edge' : (str_contains($ua, 'Firefox') ? 'Firefox' : (str_contains($ua, 'Chrome') ? 'Chrome' : 'Browser'));

        return "{$os} · {$browser}";
    }

    // ------------------------------------------------------------------
    // VAPID
    // ------------------------------------------------------------------

    /** @return array{pem:string, public:string} */
    protected static function keys(): array
    {
        if (static::$keys !== null) {
            return static::$keys;
        }

        $pem = StoreSetting::get(self::KEY_PRIVATE);
        $pub = StoreSetting::get(self::KEY_PUBLIC);

        if (! $pem || ! $pub) {
            [$pem, $pub] = static::generate();
            StoreSetting::set(self::KEY_PRIVATE, base64_encode($pem));
            StoreSetting::set(self::KEY_PUBLIC, $pub);
        } else {
            $pem = base64_decode($pem);
        }

        return static::$keys = ['pem' => $pem, 'public' => $pub];
    }

    /** @return array{0:string,1:string} [PEM privat, kunci publik base64url] */
    protected static function generate(): array
    {
        $res = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);

        if (! $res || ! openssl_pkey_export($res, $pem)) {
            throw new \RuntimeException('Gagal membuat kunci VAPID (ekstensi openssl PHP belum aktif?).');
        }

        $ec  = openssl_pkey_get_details($res)['ec'];
        $pub = "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);

        return [$pem, static::b64u($pub)];
    }

    protected static function jwt(string $audience, string $pem): string
    {
        $signing = static::b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256']))
            . '.' . static::b64u(json_encode([
                'aud' => $audience,
                'exp' => time() + 12 * 3600,
                'sub' => 'mailto:admin@' . (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'ehanzstore.id'),
            ]));

        if (! openssl_sign($signing, $der, $pem, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Gagal menandatangani token VAPID.');
        }

        return $signing . '.' . static::b64u(static::derToRaw($der));
    }

    /** Tanda tangan ECDSA DER -> format mentah R||S (64 byte) yang diminta JWT ES256. */
    public static function derToRaw(string $der): string
    {
        $pos = 2;

        if (ord($der[1]) & 0x80) {                    // panjang bentuk panjang (0x81 xx)
            $pos += ord($der[1]) & 0x7f;
        }

        $parts = [];

        for ($i = 0; $i < 2; $i++) {
            $len     = ord($der[$pos + 1]);
            $int     = substr($der, $pos + 2, $len);
            $parts[] = str_pad(ltrim($int, "\0"), 32, "\0", STR_PAD_LEFT);
            $pos    += 2 + $len;
        }

        return $parts[0] . $parts[1];
    }

    protected static function origin(string $endpoint): string
    {
        $p = parse_url($endpoint);

        if (! $p || empty($p['scheme']) || empty($p['host']) || $p['scheme'] !== 'https') {
            throw new \InvalidArgumentException('Endpoint push tidak valid.');
        }

        return $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
    }

    protected static function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}