<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CocAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_code', 'name', 'player_tag', 'clan_tag', 'town_hall', 'account_level',
        'barbarian_king_level', 'archer_queen_level', 'minion_prince_level',
        'grand_warden_level', 'royal_champion_level', 'dragon_duke_level',
        'builder_count', 'gems', 'cn_information',
        'listing_type', 'clan_level', 'capital_hall_level', 'clan_members', 'clan_league',
        'protection_status', 'email_change_status', 'email_change_available_at', 'price', 'original_price', 'is_hot_deal', 'hot_deal_ends_at', 'cost_price', 'description',
        'publication_status', 'sale_status', 'created_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_hot_deal' => 'boolean',
        'hot_deal_ends_at' => 'datetime',
        'cost_price' => 'decimal:2',
        'email_change_available_at' => 'datetime',
    ];

    /** Liga Clan War League dari yang terendah ke tertinggi (urutan dipakai untuk filter "liga minimum"). */
    public const CLAN_LEAGUES = [
        'Unranked',
        'Bronze League III', 'Bronze League II', 'Bronze League I',
        'Silver League III', 'Silver League II', 'Silver League I',
        'Gold League III', 'Gold League II', 'Gold League I',
        'Crystal League III', 'Crystal League II', 'Crystal League I',
        'Master League III', 'Master League II', 'Master League I',
        'Champion League III', 'Champion League II', 'Champion League I',
    ];

    public const CLAN_MAX_MEMBERS = 50;

    /** Estimasi laba kalau terjual sesuai harga listing (harga - modal). */
    public function estimatedProfit(): float
    {
        return (float) $this->price - (float) $this->cost_price;
    }

    /** Ada harga coret? (harga normal lebih tinggi dari harga jual sekarang) */
    public function hasDiscount(): bool
    {
        return $this->original_price !== null
            && (float) $this->original_price > (float) $this->price
            && ! $this->dealHasEnded();
    }

    /** Persen diskon (dibulatkan, minimal 1% bila ada diskon). 0 jika tidak ada diskon. */
    public function discountPercent(): int
    {
        if (! $this->hasDiscount()) {
            return 0;
        }

        $pct = (1 - ((float) $this->price / (float) $this->original_price)) * 100;

        return max(1, (int) round($pct));
    }

    /** Nominal hemat dalam rupiah. 0 jika tidak ada diskon. */
    public function discountAmount(): float
    {
        return $this->hasDiscount()
            ? (float) $this->original_price - (float) $this->price
            : 0.0;
    }

    /** Label Hot Deal hanya relevan selama akun masih tersedia. */
    public function isHotDeal(): bool
    {
        return (bool) $this->is_hot_deal
            && $this->sale_status === 'available'
            && ! $this->dealHasEnded();
    }

    // ------------------------------------------------------------------
    // Masa berlaku Hot Deal / diskon
    // ------------------------------------------------------------------

    /** Masa berlaku promo sudah lewat? (false jika tanpa batas waktu) */
    public function dealHasEnded(): bool
    {
        return $this->hot_deal_ends_at !== null && now()->gte($this->hot_deal_ends_at);
    }

    /** Waktu berakhir promo yang masih berjalan. Null jika tanpa batas waktu, sudah lewat, atau tidak ada promo. */
    public function dealEndsAt(): ?Carbon
    {
        if (! $this->hot_deal_ends_at
            || $this->dealHasEnded()
            || $this->sale_status !== 'available') {
            return null;
        }

        return ($this->hasDiscount() || $this->is_hot_deal) ? $this->hot_deal_ends_at : null;
    }

    /** Teks sisa waktu promo: "Berakhir dalam 2 hari" / "5 jam" / "20 menit". Null jika tidak ada batas waktu. */
    public function dealRemainingText(): ?string
    {
        $end = $this->dealEndsAt();

        if (! $end) {
            return null;
        }

        $seconds = max(1, $end->getTimestamp() - now()->getTimestamp());

        if ($seconds >= 86400) {
            return 'Berakhir dalam ' . (int) ceil($seconds / 86400) . ' hari';
        }

        if ($seconds >= 3600) {
            return 'Berakhir dalam ' . (int) ceil($seconds / 3600) . ' jam';
        }

        return 'Berakhir dalam ' . max(1, (int) ceil($seconds / 60)) . ' menit';
    }

    /** Atribut HTML untuk hitung mundur berjalan (public/js/live-time.js). Kosong jika tidak ada batas waktu. */
    public function dealCountdownAttrs(): string
    {
        $end = $this->dealEndsAt();

        if (! $end) {
            return '';
        }

        return 'data-countdown="' . e($end->toIso8601String()) . '"'
            . ' data-prefix="Berakhir dalam "'
            . ' data-expired="Promo berakhir"';
    }

    /**
     * Akhiri semua promo yang masa berlakunya lewat: harga kembali ke harga normal
     * (harga coret), label Hot Deal dan batas waktunya dihapus.
     * Akun terjual tidak disentuh supaya harga historisnya tetap.
     * Dipanggil otomatis oleh middleware ExpireHotDeals di setiap request.
     *
     * Urutan kolom di array update sengaja begini: price dihitung dari original_price
     * sebelum original_price dikosongkan.
     */
    public static function expireHotDeals(): int
    {
        return DB::table('coc_accounts')
            ->whereNotNull('hot_deal_ends_at')
            ->where('hot_deal_ends_at', '<=', now())
            ->where('sale_status', '!=', 'sold')
            ->update([
                'price'            => DB::raw('COALESCE(original_price, price)'),
                'original_price'   => null,
                'is_hot_deal'      => false,
                'hot_deal_ends_at' => null,
            ]);
    }

    /** Tag yang relevan untuk jenis listing ini (tag clan untuk clan, tag akun untuk akun pemain). */
    public function tag(): ?string
    {
        return $this->isClan() ? $this->clan_tag : $this->player_tag;
    }

    /** Rapikan input tag: huruf besar, tanpa spasi, selalu diawali #. Kosong -> null. */
    public static function normalizeTag(?string $tag): ?string
    {
        $clean = strtoupper(preg_replace('/[\s#]+/', '', (string) $tag));

        return $clean === '' ? null : '#' . $clean;
    }

    public function isClan(): bool
    {
        return $this->listing_type === 'clan';
    }

    /** Label singkat untuk badge: "CLAN" atau "TH 16". */
    public function badgeLabel(): string
    {
        return $this->isClan() ? 'CLAN' : 'TH ' . $this->town_hall;
    }

    /** Kata benda untuk teks: "clan" atau "akun". */
    public function noun(): string
    {
        return $this->isClan() ? 'clan' : 'akun';
    }

    protected static function booted(): void
    {
        // Sinkronkan tabel transaksi dengan status penjualan akun.
        static::updated(function (CocAccount $account) {
            if (! $account->wasChanged('sale_status')) {
                return;
            }

            if ($account->sale_status === 'sold') {
                // Akun ditandai terjual -> pastikan ada transaksi selesai
                Sale::recordForAccount($account);
            } elseif ($account->getOriginal('sale_status') === 'sold') {
                // Status terjual dibatalkan -> transaksi lama dibatalkan
                // agar pendapatan tidak dihitung dua kali
                $account->sales()
                    ->where('transaction_status', 'completed')
                    ->update([
                        'transaction_status' => 'cancelled',
                        'payment_status'     => 'unpaid',
                    ]);
            }
        });
    }

    /**
     * Sisa hari sampai email akun bisa diganti (dibulatkan ke atas).
     * Null jika email sudah bisa diganti sekarang, tidak bisa diganti, atau ini clan.
     * Otomatis kembali null saat tanggalnya lewat, tanpa perlu cron.
     */
    public function emailChangeDaysLeft(): ?int
    {
        if ($this->isClan()
            || $this->email_change_status !== 'Changeable'
            || ! $this->email_change_available_at) {
            return null;
        }

        $seconds = $this->email_change_available_at->getTimestamp() - now()->getTimestamp();

        return $seconds > 0 ? (int) ceil($seconds / 86400) : null;
    }

    /** Teks status email untuk publik: "Bisa Ganti", "Bisa Ganti dalam 5 hari", "Tidak Bisa Ganti". */
    public function emailStatusLabel(): string
    {
        if ($this->email_change_status !== 'Changeable') {
            return 'Tidak Bisa Ganti';
        }

        $days = $this->emailChangeDaysLeft();

        return $days ? "Bisa Ganti dalam {$days} hari" : 'Bisa Ganti';
    }

    public function images()
    {
        return $this->hasMany(CocAccountImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(CocAccountImage::class)->where('is_primary', true);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /** Transaksi selesai terakhir (berisi data pembeli & garansi). */
    public function latestCompletedSale()
    {
        return $this->hasOne(Sale::class)
            ->ofMany(['id' => 'max'], fn ($q) => $q->where('transaction_status', 'completed'));
    }

    /**
     * Akun yang tampil di katalog publik:
     *  - terbit dan masih tersedia, ATAU
     *  - terbit, sudah terjual, dan masa garansi transaksinya belum berakhir.
     * Setelah garansi berakhir akun otomatis hilang dari katalog (tanpa perlu cron).
     */
    public function scopeListed($query)
    {
        return $query->where('publication_status', 'published')
            ->where(function ($q) {
                $q->where('sale_status', 'available')
                  ->orWhere(function ($sold) {
                      $sold->where('sale_status', 'sold')
                           ->whereHas('sales', function ($s) {
                               $s->where('transaction_status', 'completed')
                                 ->where('warranty_expires_at', '>', now());
                           });
                  });
            });
    }

    /** Akun yang ditandai admin sebagai Hot Deal dan masa berlakunya belum lewat. */
    public function scopeHotDeal($query)
    {
        return $query->where('is_hot_deal', true)
            ->where(function ($q) {
                $q->whereNull('hot_deal_ends_at')
                  ->orWhere('hot_deal_ends_at', '>', now());
            });
    }

    /** Urutkan dari persen diskon terbesar; yang tidak sedang diskon di bawah. */
    public function scopeOrderByDiscount($query)
    {
        return $query->orderByRaw(
            'CASE WHEN original_price IS NOT NULL AND original_price > price'
            . ' AND (hot_deal_ends_at IS NULL OR hot_deal_ends_at > ?)'
            . ' THEN (original_price - price) / original_price ELSE 0 END DESC',
            [now()]
        );
    }

    // ------------------------------------------------------------------
    // Akun serupa (ditampilkan di bawah halaman detail)
    // ------------------------------------------------------------------

    /**
     * Akun/clan lain yang masih tersedia dengan Town Hall dan rentang harga mirip.
     * Dicari bertahap sampai $limit terpenuhi, yang paling mirip dulu:
     *   akun : TH sama harga +/-30%  ->  TH sama +/-60%  ->  TH +/-1 harga +/-40%  ->  TH sama berapa pun
     *   clan : harga +/-30%  ->  harga +/-60%  ->  clan lain berapa pun
     * Di tiap tahap diurutkan dari selisih harga terkecil. Akun ini sendiri tidak ikut.
     */
    public function similarListings(int $limit = 4): Collection
    {
        $price = (float) $this->price;
        $type  = $this->listing_type ?: 'account';

        // [filter Town Hall, persen rentang harga (null = bebas)]
        $tiers = $this->isClan()
            ? [[null, 30], [null, 60], [null, null]]
            : [[[$this->town_hall], 30], [[$this->town_hall], 60],
               [[$this->town_hall - 1, $this->town_hall + 1], 40], [[$this->town_hall], null]];

        $picked = collect();

        foreach ($tiers as [$townHalls, $percent]) {
            $need = $limit - $picked->count();

            if ($need < 1) {
                break;
            }

            $query = static::available()
                ->where('listing_type', $type)
                ->where('id', '!=', $this->id)
                ->when($picked->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $picked->pluck('id')))
                ->with(['primaryImage', 'latestCompletedSale.approvedReview']);

            if ($townHalls !== null) {
                $query->whereIn('town_hall', $townHalls);
            }

            if ($percent !== null) {
                $query->whereBetween('price', [
                    max(0, $price * (1 - $percent / 100)),
                    $price * (1 + $percent / 100),
                ]);
            }

            $picked = $picked->concat(
                $query->orderByRaw('ABS(price - ?)', [$price])
                      ->orderByDesc('created_at')
                      ->limit($need)
                      ->get()
            );
        }

        return $picked->take($limit)->values();
    }

    // ------------------------------------------------------------------
    // Pengingat stok lama
    // ------------------------------------------------------------------

    /** Terbit, masih tersedia, dan sudah terpajang lebih dari N hari (default config store.stale_days). */
    public function scopeStale($query, ?int $days = null)
    {
        $days ??= (int) config('store.stale_days', 30);

        return $query->where('publication_status', 'published')
            ->where('sale_status', 'available')
            ->where('created_at', '<=', now()->subDays($days));
    }

    /** Sudah berapa hari akun ini terpajang (sejak dibuat). */
    public function daysListed(): int
    {
        return $this->created_at
            ? max(0, (int) floor((now()->getTimestamp() - $this->created_at->getTimestamp()) / 86400))
            : 0;
    }

    /**
     * Saran harga baru untuk stok lama. Makin lama belum laku, makin besar penurunannya
     * (lihat config store.stale_price_tiers). Dibulatkan ke kelipatan yang rapi dan tidak
     * pernah di bawah harga modal. Null bila tidak ada ruang turun lagi.
     *
     * @return array{percent: float, price: int, profit: float, at_cost: bool, cost_known: bool}|null
     */
    public function priceSuggestion(): ?array
    {
        $price = (float) $this->price;

        if ($price <= 0) {
            return null;
        }

        $tiers = (array) config('store.stale_price_tiers', [30 => 5]);
        krsort($tiers);

        $days    = $this->daysListed();
        $percent = (float) min($tiers);

        foreach ($tiers as $minDays => $pct) {
            if ($days >= $minDays) {
                $percent = (float) $pct;
                break;
            }
        }

        $step      = $price >= 1_000_000 ? 25_000 : ($price >= 300_000 ? 10_000 : 5_000);
        $suggested = (int) (round($price * (1 - $percent / 100) / $step) * $step);

        $cost      = (float) $this->cost_price;
        $costKnown = $cost > 0;
        $atCost    = false;

        // Jangan sampai rugi: batasi sampai harga modal
        if ($costKnown && $suggested < $cost) {
            $suggested = (int) ceil($cost);
            $atCost    = true;
        }

        if ($suggested >= $price) {
            return null;
        }

        return [
            'percent'    => round((1 - $suggested / $price) * 100, 1),
            'price'      => $suggested,
            'profit'     => $suggested - $cost,
            'at_cost'    => $atCost,
            'cost_known' => $costKnown,
        ];
    }

    /**
     * Ringkasan stok lama untuk kartu pengingat di dashboard.
     * $ownerId diisi untuk admin biasa (hanya akun miliknya); null untuk Super Admin (semua).
     *
     * @return array{count: int, capital: float, value: float, oldest_days: int}
     */
    public static function staleSummary(?int $ownerId = null): array
    {
        $row = static::stale()
            ->when($ownerId, fn ($q) => $q->where('created_by', $ownerId))
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(cost_price), 0) as capital, COALESCE(SUM(price), 0) as value, MIN(created_at) as oldest')
            ->first();

        $oldest = $row?->oldest ? Carbon::parse($row->oldest) : null;

        return [
            'count'       => (int) ($row->total ?? 0),
            'capital'     => (float) ($row->capital ?? 0),
            'value'       => (float) ($row->value ?? 0),
            'oldest_days' => $oldest ? max(0, (int) floor((now()->getTimestamp() - $oldest->getTimestamp()) / 86400)) : 0,
        ];
    }

    // Scope untuk publik (hanya yang masih bisa dibeli)
    public function scopeAvailable($query)
    {
        return $query->where('publication_status', 'published')
                     ->where('sale_status', 'available');
    }
}