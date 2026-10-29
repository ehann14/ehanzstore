<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Booking akun dengan DP.
 *
 * Alur status:
 *   pending   -> booking dibuat, menunggu DP dibayar (batas: store.booking_pay_hours)
 *   active    -> DP diterima admin, akun ditahan (batas pelunasan: store.booking_hold_days)
 *   completed -> pelunasan selesai, akun terjual (DP dihitung sebagai bagian pembayaran)
 *   cancelled -> dibatalkan (pembeli / toko)
 *   expired   -> lewat batas waktu (DP belum dibayar, atau lewat batas pelunasan)
 *
 * Status DP (dp_state): unpaid | held | applied | forfeited | refunded
 */
class Booking extends Model
{
    public const PENDING   = 'pending';
    public const ACTIVE    = 'active';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';
    public const EXPIRED   = 'expired';

    public const DP_UNPAID    = 'unpaid';
    public const DP_HELD      = 'held';
    public const DP_APPLIED   = 'applied';
    public const DP_FORFEITED = 'forfeited';
    public const DP_REFUNDED  = 'refunded';

    public const STATUS_LABELS = [
        self::PENDING   => 'Menunggu DP',
        self::ACTIVE    => 'DP diterima',
        self::COMPLETED => 'Selesai',
        self::CANCELLED => 'Dibatalkan',
        self::EXPIRED   => 'Kedaluwarsa',
    ];

    public const DP_LABELS = [
        self::DP_UNPAID    => 'Belum dibayar',
        self::DP_HELD      => 'Ditahan',
        self::DP_APPLIED   => 'Dipotong dari harga',
        self::DP_FORFEITED => 'Hangus',
        self::DP_REFUNDED  => 'Dikembalikan',
    ];

    protected $fillable = [
        'booking_code', 'token', 'coc_account_id', 'account_code_snapshot', 'account_name_snapshot',
        'buyer_name', 'buyer_phone', 'price_snapshot', 'dp_percent', 'dp_amount',
        'status', 'dp_state', 'expires_at', 'dp_paid_at', 'confirmed_by', 'completed_at',
        'cancelled_at', 'cancel_reason', 'sale_id', 'notes', 'terms_accepted_at', 'ip_address',
    ];

    protected $casts = [
        'price_snapshot'    => 'decimal:2',
        'dp_amount'         => 'decimal:2',
        'expires_at'        => 'datetime',
        'dp_paid_at'        => 'datetime',
        'completed_at'      => 'datetime',
        'cancelled_at'      => 'datetime',
        'terms_accepted_at' => 'datetime',
    ];

    /** Cache per-request: id akun yang sedang ditahan booking. */
    protected static ?array $heldIds = null;

    protected static function booted(): void
    {
        static::creating(function (Booking $b) {
            if (empty($b->token)) {
                $b->token = Str::random(40);
            }
            if (empty($b->booking_code)) {
                $b->booking_code = static::generateCode();
            }
        });
    }

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    public function account()
    {
        return $this->belongsTo(CocAccount::class, 'coc_account_id')->withTrashed();
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // ------------------------------------------------------------------
    // Hitungan DP
    // ------------------------------------------------------------------

    public static function dpPercent(): int
    {
        return max(1, min(90, (int) config('store.booking_dp_percent', 20)));
    }

    /** DP = persen dari harga, dibulatkan ke atas ke kelipatan Rp 1.000, tidak melebihi harga. */
    public static function calculateDp(float $price): int
    {
        $dp = (int) (ceil($price * static::dpPercent() / 100 / 1000) * 1000);

        return (int) min($dp, (int) $price);
    }

    public function remaining(): float
    {
        return max(0, (float) $this->price_snapshot - (float) $this->dp_amount);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'BK-' . strtoupper(Str::random(6));
        } while (static::where('booking_code', $code)->exists());

        return $code;
    }

    // ------------------------------------------------------------------
    // Scope & pengecekan akun ditahan
    // ------------------------------------------------------------------

    /** Booking yang sedang menahan akun (menunggu DP / DP diterima) dan belum lewat batas waktu. */
    public function scopeHolding($query)
    {
        return $query->whereIn('status', [self::PENDING, self::ACTIVE])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /** Daftar id akun yang sedang dibooking. Satu query per request; gagal = dianggap kosong. */
    public static function heldAccountIds(): array
    {
        if (static::$heldIds === null) {
            try {
                static::$heldIds = static::holding()->pluck('coc_account_id')->map(fn ($id) => (int) $id)->all();
            } catch (Throwable $e) {
                // Tabel belum dibuat (migrasi belum dijalankan): situs tetap jalan tanpa fitur booking.
                static::$heldIds = [];
            }
        }

        return static::$heldIds;
    }

    public static function forgetCache(): void
    {
        static::$heldIds = null;
    }

    public static function holdingFor(int $accountId): ?self
    {
        try {
            return static::holding()->where('coc_account_id', $accountId)->latest('id')->first();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function isHolding(): bool
    {
        return in_array($this->status, [self::PENDING, self::ACTIVE], true)
            && (! $this->expires_at || now()->lt($this->expires_at));
    }

    // ------------------------------------------------------------------
    // Label & tampilan
    // ------------------------------------------------------------------

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function dpLabel(): string
    {
        return self::DP_LABELS[$this->dp_state] ?? $this->dp_state;
    }

    /** Kelas warna lencana status. */
    public function statusTone(): string
    {
        return match ($this->status) {
            self::PENDING   => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
            self::ACTIVE    => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            self::COMPLETED => 'bg-electric/15 text-blue-300 border-electric/30',
            default         => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
        };
    }

    public function publicUrl(): string
    {
        return route('booking.show', $this->token);
    }

    public function localPhone(): string
    {
        return preg_replace('/^62/', '0', (string) $this->buyer_phone);
    }

    public function deadlineLabel(): ?string
    {
        return $this->expires_at
            ? $this->expires_at->clone()->timezone(Sale::DISPLAY_TZ)->format('d M Y, H:i') . ' WIB'
            : null;
    }

    public function createdLabel(): string
    {
        return $this->created_at->clone()->timezone(Sale::DISPLAY_TZ)->format('d M Y, H:i');
    }

    /** Atribut hitung mundur realtime (dibaca live-time.js). Kosong bila tidak ada batas waktu aktif. */
    public function countdownAttrs(string $prefix = 'sisa ', string $expired = 'Waktu habis'): string
    {
        if (! $this->isHolding() || ! $this->expires_at) {
            return '';
        }

        return 'data-countdown="' . e($this->expires_at->toIso8601String()) . '"'
            . ' data-prefix="' . e($prefix) . '"'
            . ' data-expired="' . e($expired) . '"';
    }

    /** Link WhatsApp dari admin ke pembeli. */
    public function buyerWhatsappUrl(?string $text = null): string
    {
        return 'https://wa.me/' . $this->buyer_phone . ($text ? '?text=' . rawurlencode($text) : '');
    }

    // ------------------------------------------------------------------
    // Membuat booking
    // ------------------------------------------------------------------

    /**
     * Buat booking untuk sebuah akun. Atomik: akun dikunci supaya tidak ada dua booking sekaligus.
     *
     * @return array{0: ?self, 1: ?string} [booking, pesan error]
     */
    public static function place(string $accountCode, string $name, string $phone, ?string $ip = null): array
    {
        try {
            return DB::transaction(function () use ($accountCode, $name, $phone, $ip) {
                $account = CocAccount::lockForUpdate()
                    ->where('account_code', $accountCode)
                    ->where('publication_status', 'published')
                    ->first();

                if (! $account || $account->sale_status !== 'available') {
                    return [null, 'Maaf, akun ini sudah tidak tersedia untuk dibooking.'];
                }

                if (static::holding()->where('coc_account_id', $account->id)->exists()) {
                    return [null, 'Akun ini sedang dibooking pembeli lain. Coba lagi nanti atau pilih akun lain.'];
                }

                $max = max(1, (int) config('store.booking_max_per_phone', 2));

                if (static::holding()->where('buyer_phone', $phone)->count() >= $max) {
                    return [null, "Nomor WhatsApp ini sudah memiliki {$max} booking aktif. Selesaikan atau tunggu booking sebelumnya berakhir."];
                }

                $price = (float) $account->price;
                $dp    = static::calculateDp($price);

                if ($price <= 0 || $dp <= 0) {
                    return [null, 'Harga akun belum valid untuk booking. Hubungi admin.'];
                }

                $booking = static::create([
                    'coc_account_id'        => $account->id,
                    'account_code_snapshot' => $account->account_code,
                    'account_name_snapshot' => $account->name,
                    'buyer_name'            => $name,
                    'buyer_phone'           => $phone,
                    'price_snapshot'        => $price,
                    'dp_percent'            => static::dpPercent(),
                    'dp_amount'             => $dp,
                    'status'                => self::PENDING,
                    'dp_state'              => self::DP_UNPAID,
                    'expires_at'            => now()->addHours(max(1, (int) config('store.booking_pay_hours', 24))),
                    'terms_accepted_at'     => now(),
                    'ip_address'            => $ip,
                ]);

                static::forgetCache();

                return [$booking, null];
            });
        } catch (Throwable $e) {
            report($e);

            return [null, 'Booking belum bisa diproses saat ini. Silakan coba lagi atau hubungi admin via WhatsApp.'];
        }
    }

    // ------------------------------------------------------------------
    // Tindakan admin
    // ------------------------------------------------------------------

    /**
     * Admin menandai DP sudah diterima. Booking aktif dan akun ditahan sampai batas pelunasan.
     * Booking yang baru saja kedaluwarsa (DP belum dibayar) boleh diaktifkan lagi selama akun masih bebas.
     *
     * @return string|null pesan error, null jika berhasil
     */
    public function confirmDp(?int $userId): ?string
    {
        return DB::transaction(function () use ($userId) {
            $account = CocAccount::lockForUpdate()->find($this->coc_account_id);

            if (! $account || $account->sale_status !== 'available') {
                return 'Akun sudah tidak tersedia.';
            }

            $reopen = $this->status === self::EXPIRED && $this->dp_state === self::DP_UNPAID;

            if ($this->status !== self::PENDING && ! $reopen) {
                return 'Booking ini tidak sedang menunggu DP.';
            }

            $other = static::holding()
                ->where('coc_account_id', $this->coc_account_id)
                ->where('id', '!=', $this->id)
                ->exists();

            if ($other) {
                return 'Akun ini sudah dibooking pembeli lain.';
            }

            $this->update([
                'status'        => self::ACTIVE,
                'dp_state'      => self::DP_HELD,
                'dp_paid_at'    => now(),
                'confirmed_by'  => $userId,
                'cancelled_at'  => null,
                'cancel_reason' => null,
                'expires_at'    => now()->addDays(max(1, (int) config('store.booking_hold_days', 3))),
            ]);

            static::forgetCache();

            return null;
        });
    }

    /** Tambah masa pelunasan (hari) untuk booking aktif. */
    public function extend(int $days): void
    {
        if ($this->status !== self::ACTIVE) {
            return;
        }

        $base = $this->expires_at && $this->expires_at->isFuture() ? $this->expires_at : now();

        $this->update(['expires_at' => $base->clone()->addDays(max(1, $days))]);
        static::forgetCache();
    }

    /**
     * Batalkan booking.
     * - Belum bayar DP: batal tanpa dampak uang.
     * - Sudah bayar DP: $byStore = false -> DP hangus (pembeli batal); true -> DP dikembalikan.
     */
    public function cancel(bool $byStore, ?string $reason = null): void
    {
        if (! in_array($this->status, [self::PENDING, self::ACTIVE], true)) {
            return;
        }

        $dp = $this->dp_state;

        if ($this->status === self::ACTIVE) {
            $dp = $byStore ? self::DP_REFUNDED : self::DP_FORFEITED;
        }

        $this->update([
            'status'        => self::CANCELLED,
            'dp_state'      => $dp,
            'cancelled_at'  => now(),
            'cancel_reason' => $reason ?: ($byStore ? 'Dibatalkan oleh toko' : 'Dibatalkan oleh pembeli'),
        ]);

        static::forgetCache();
    }

    /** Putuskan nasib DP pada booking kedaluwarsa yang DP-nya masih ditahan. */
    public function resolveHeldDp(bool $forfeit): void
    {
        if ($this->dp_state !== self::DP_HELD || $this->status === self::ACTIVE) {
            return;
        }

        $this->update(['dp_state' => $forfeit ? self::DP_FORFEITED : self::DP_REFUNDED]);
    }

    // ------------------------------------------------------------------
    // Otomatis (tanpa cron)
    // ------------------------------------------------------------------

    /**
     * Booking yang lewat batas waktu dilepas supaya akun bisa dibeli lagi.
     * DP yang sudah diterima TIDAK otomatis hangus: tetap berstatus "ditahan"
     * sampai admin memutuskan (hangus / dikembalikan).
     */
    public static function expireOverdue(): int
    {
        try {
            $count = static::whereIn('status', [self::PENDING, self::ACTIVE])
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->update([
                    'status'       => self::EXPIRED,
                    'cancelled_at' => now(),
                ]);
        } catch (Throwable $e) {
            return 0; // tabel bookings belum dibuat: abaikan
        }

        if ($count) {
            static::forgetCache();
        }

        return $count;
    }

    /**
     * Dipanggil saat sebuah akun berubah menjadi terjual:
     *  - booking aktif milik akun itu menjadi selesai, DP dihitung sebagai bagian pembayaran
     *  - booking yang masih menunggu DP dibatalkan
     */
    public static function settleForSoldAccount(CocAccount $account): void
    {
        try {
            $sale = $account->sales()->where('transaction_status', 'completed')->latest('id')->first();

            foreach (static::where('coc_account_id', $account->id)->whereIn('status', [self::PENDING, self::ACTIVE])->get() as $booking) {
                if ($booking->status === self::ACTIVE) {
                    $booking->update([
                        'status'       => self::COMPLETED,
                        'dp_state'     => self::DP_APPLIED,
                        'completed_at' => now(),
                        'sale_id'      => $sale?->id,
                    ]);

                    if ($sale && ! str_contains((string) $sale->notes, $booking->booking_code)) {
                        $line = "Booking {$booking->booking_code}: DP Rp " . number_format((float) $booking->dp_amount, 0, ',', '.')
                            . ' sudah diterima, sisa pelunasan Rp ' . number_format($booking->remaining(), 0, ',', '.') . '.';

                        $sale->update(['notes' => trim(($sale->notes ? $sale->notes . "\n" : '') . $line)]);
                    }
                } else {
                    $booking->update([
                        'status'        => self::CANCELLED,
                        'cancelled_at'  => now(),
                        'cancel_reason' => 'Akun sudah terjual',
                    ]);
                }
            }

            static::forgetCache();
        } catch (Throwable $e) {
            report($e);
        }
    }
}