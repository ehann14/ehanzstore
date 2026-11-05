<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        'publication_status', 'publish_at', 'sale_status', 'created_by',
        'is_consignment', 'consignor_name', 'consignor_phone', 'commission_type', 'commission_value', 'consignor_email_ok', 'consignor_phone_ok',
        'submission_token', 'submission_status', 'submission_expires_at', 'submitted_at',
        'booking_enabled',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_hot_deal' => 'boolean',
        'hot_deal_ends_at' => 'datetime',
        'publish_at' => 'datetime',
        'cost_price' => 'decimal:2',
        'email_change_available_at' => 'datetime',
        'is_consignment' => 'boolean',
        'consignor_email_ok' => 'boolean',
        'consignor_phone_ok' => 'boolean',
        'commission_value' => 'decimal:2',
        'submission_expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'booking_enabled' => 'boolean',
    ];

    /** Link titip penjual: menunggu diisi penjual / sudah diisi (menunggu ditinjau admin). */
    public const SUBMISSION_PENDING   = 'pending';
    public const SUBMISSION_SUBMITTED = 'submitted';

    /** Sembunyikan placeholder yang masih menunggu diisi penjual (datanya masih kosong). */
    public function scopeNotAwaitingSeller($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('submission_status')->orWhere('submission_status', '<>', self::SUBMISSION_PENDING);
        });
    }

    /** Link titip masih bisa dipakai penjual? (belum diisi, belum kedaluwarsa) */
    public function sellerLinkIsOpen(): bool
    {
        return $this->submission_status === self::SUBMISSION_PENDING
            && filled($this->submission_token)
            && (! $this->submission_expires_at || $this->submission_expires_at->isFuture());
    }

    /** Link titip sudah lewat batas waktu tapi penjual belum mengisi. */
    public function sellerLinkIsExpired(): bool
    {
        return $this->submission_status === self::SUBMISSION_PENDING
            && $this->submission_expires_at
            && $this->submission_expires_at->isPast();
    }

    /** URL link titip untuk dibagikan ke penjual. Null bila link sudah mati. */
    public function sellerLinkUrl(): ?string
    {
        return filled($this->submission_token) ? route('seller.form', $this->submission_token) : null;
    }

    /** Token acak baru yang belum dipakai akun mana pun. */
    public static function newSubmissionToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::withTrashed()->where('submission_token', $token)->exists());

        return $token;
    }

    /** Jenis komisi titip jual: kunci => label. */
    public const COMMISSION_TYPES = [
        'percent' => 'Persen (%)',
        'fixed'   => 'Nominal (Rp)',
    ];

    /** Komisi persen maksimal (supaya bagian pemilik tidak habis). */
    public const COMMISSION_MAX_PERCENT = 90;

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

    // ------------------------------------------------------------------
    // Titip jual (konsinyasi)
    // ------------------------------------------------------------------

    /** Akun ini titipan orang lain? */
    public function isConsignment(): bool
    {
        return (bool) $this->is_consignment;
    }

    /**
     * Komisi toko (Rp) bila akun terjual di harga $salePrice.
     * Persen: dihitung dari harga jual, dibulatkan ke rupiah. Nominal: tetap, tapi tidak melebihi harga jual.
     */
    public function commissionFor(float $salePrice): float
    {
        $salePrice = max(0.0, $salePrice);
        $value     = (float) $this->commission_value;

        $commission = $this->commission_type === 'fixed'
            ? min($value, $salePrice)
            : round($salePrice * $value / 100);

        return (float) max(0.0, min($commission, $salePrice));
    }

    /** Bagian pemilik akun (Rp) = harga jual - komisi. */
    public function payoutFor(float $salePrice): float
    {
        return max(0.0, $salePrice - $this->commissionFor($salePrice));
    }

    /** Aturan komisi dalam teks singkat: "15%" atau "Rp 5.000". Null bila bukan titipan. */
    public function commissionLabel(): ?string
    {
        if (! $this->isConsignment()) {
            return null;
        }

        $value = (float) $this->commission_value;

        return $this->commission_type === 'fixed'
            ? 'Rp ' . number_format($value, 0, ',', '.')
            : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',') . '%';
    }

    /** Nomor WhatsApp pemilik (format 08xx) untuk ditampilkan. */
    public function consignorLocalPhone(): ?string
    {
        return $this->consignor_phone ? preg_replace('/^62/', '0', $this->consignor_phone) : null;
    }

    /** Link WhatsApp ke pemilik akun. Null bila nomor tidak diisi. */
    public function consignorWhatsappUrl(?string $text = null): ?string
    {
        if (! $this->consignor_phone) {
            return null;
        }

        return 'https://wa.me/' . $this->consignor_phone . ($text ? '?text=' . rawurlencode($text) : '');
    }

    /** Hanya akun titipan. */
    public function scopeConsignment($query)
    {
        return $query->where('is_consignment', true);
    }

    /**
     * Titipan Terverifikasi: akun titipan yang lolos dua syarat utama titip akun.
     * Aturannya sama dengan validasi saat simpan (AccountController::consignmentEmailErrors
     * dan consignmentPhoneErrors), dicek ulang di sini supaya data lama atau hasil edit manual
     * di database tidak lolos begitu saja:
     *  1. Gmail bisa diganti ke Gmail pembeli: admin sudah mengonfirmasi, bukan clan (clan tidak
     *     punya Gmail), status Gmail "Bisa Ganti", dan sisa hari tunggu tidak melebihi
     *     config('store.consign_max_email_wait_days').
     *  2. Bila proteksi ON, nomor HP proteksi bisa diganti ke nomor pembeli (konfirmasi admin).
     *     Bila proteksi OFF, syarat ini tidak berlaku.
     */
    public function isVerifiedConsignment(): bool
    {
        if (! $this->isConsignment() || ! $this->consignor_email_ok || $this->isClan()) {
            return false;
        }

        if ($this->email_change_status !== 'Changeable') {
            return false;
        }

        // Proteksi ON: nomor proteksi wajib bisa diganti ke nomor pembeli
        if ($this->protection_status === 'Protected' && ! $this->consignor_phone_ok) {
            return false;
        }

        $maxWait = max(0, (int) config('store.consign_max_email_wait_days', 0));

        return ($this->emailChangeDaysLeft() ?? 0) <= $maxWait;
    }

    /** Versi query dari isVerifiedConsignment() (dipakai filter katalog). */
    public function scopeVerifiedConsignment($query)
    {
        $maxWait = max(0, (int) config('store.consign_max_email_wait_days', 0));

        return $query->where('is_consignment', true)
            ->where('consignor_email_ok', true)
            ->where('listing_type', 'account')
            ->where('email_change_status', 'Changeable')
            ->where(function ($q) {
                $q->where('protection_status', '!=', 'Protected')
                  ->orWhere('consignor_phone_ok', true);
            })
            ->where(function ($q) use ($maxWait) {
                $q->whereNull('email_change_available_at')
                  ->orWhere('email_change_available_at', '<=', now()->addDays($maxWait));
            });
    }

    /**
     * Hitung ulang modal (= bagian pemilik) semua akun titipan yang belum terjual langsung di database.
     * Dipakai setelah harga berubah lewat query massal (mis. promo berakhir). Satu query.
     */
    public static function syncConsignmentCosts(): int
    {
        return DB::table('coc_accounts')
            ->where('is_consignment', 1)
            ->where('sale_status', '!=', 'sold')
            ->update([
                'cost_price' => DB::raw(
                    "CASE WHEN commission_type = 'fixed'"
                    . ' THEN (CASE WHEN commission_value > price THEN 0 ELSE price - commission_value END)'
                    . ' ELSE price - ROUND(price * commission_value / 100) END'
                ),
            ]);
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
        $ended = DB::table('coc_accounts')
            ->whereNotNull('hot_deal_ends_at')
            ->where('hot_deal_ends_at', '<=', now())
            ->where('sale_status', '!=', 'sold')
            ->update([
                'price'            => DB::raw('COALESCE(original_price, price)'),
                'original_price'   => null,
                'is_hot_deal'      => false,
                'hot_deal_ends_at' => null,
            ]);

        // Harga berubah lewat query massal (tanpa event model): akun titipan perlu hitung ulang bagian pemilik.
        if ($ended > 0) {
            static::syncConsignmentCosts();
        }

        return $ended;
    }

    // ------------------------------------------------------------------
    // Jadwal tayang (publish_at)
    // ------------------------------------------------------------------

    /**
     * Terbitkan semua akun draft yang jadwal tayangnya sudah tiba.
     * Dipanggil otomatis oleh middleware ExpireHotDeals di setiap request (tanpa cron).
     * Akun draft tanpa jadwal tidak disentuh. Jadwal dikosongkan setelah terbit.
     */
    public static function publishDue(): int
    {
        return DB::table('coc_accounts')
            ->where('publication_status', 'draft')
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->whereNull('deleted_at')
            ->update([
                'publication_status' => 'published',
                'publish_at'         => null,
                'updated_at'         => now(),
            ]);
    }

    /** Masih draft dan punya jadwal tayang di masa depan? */
    public function isScheduled(): bool
    {
        return $this->publication_status === 'draft'
            && $this->publish_at !== null
            && $this->publish_at->isFuture();
    }

    /** Teks jadwal tayang untuk admin, contoh: "30 Oct, 19:00 WIB". Null jika tidak dijadwalkan. */
    public function publishAtLabel(): ?string
    {
        return $this->publish_at
            ? $this->publish_at->clone()->timezone(Sale::DISPLAY_TZ)->format('d M, H:i') . ' WIB'
            : null;
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
        // Akun titipan: harga modal = bagian pemilik (harga - komisi), selalu dihitung otomatis
        // supaya estimasi laba = komisi dan saran harga stok lama tidak pernah menekan hak pemilik.
        // Akun yang sudah terjual tidak disentuh (angka resminya ada di snapshot transaksi).
        static::saving(function (CocAccount $account) {
            if ($account->isConsignment() && $account->sale_status !== 'sold') {
                $account->cost_price = $account->payoutFor((float) $account->price);
            }
        });

        // Sinkronkan tabel transaksi dengan status penjualan akun.
        static::updated(function (CocAccount $account) {
            if (! $account->wasChanged('sale_status')) {
                return;
            }

            if ($account->sale_status === 'sold') {
                // Akun ditandai terjual -> pastikan ada transaksi selesai
                Sale::recordForAccount($account);

                // Booking DP milik akun ini ikut diselesaikan (DP dihitung sebagai bagian pembayaran)
                Booking::settleForSoldAccount($account);
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
     * Sisa hari sampai Gmail akun bisa diganti (dibulatkan ke atas).
     * Null jika Gmail sudah bisa diganti sekarang, tidak bisa diganti, atau ini clan.
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

    /** Teks status Gmail untuk publik: "Bisa Ganti", "Bisa Ganti dalam 5 hari", "Tidak Bisa Ganti". */
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

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Saklar booking (DP) per akun, diatur admin di Kelola Akun.
     * OFF = pembeli tidak bisa booking akun ini (tombol booking diganti keterangan) dan admin tidak bisa
     * mencatat booking baru. Booking yang sudah berjalan tetap lanjut. Bawaan ON.
     * Kolom belum ada (migrasi belum dijalankan) dianggap ON supaya situs tetap jalan seperti biasa.
     */
    public function bookingEnabled(): bool
    {
        return (bool) ($this->booking_enabled ?? true);
    }

    /** Akun ini sedang ditahan booking (menunggu DP / DP diterima)? Memakai cache satu query per request. */
    public function isBooked(): bool
    {
        return $this->sale_status === 'available' && in_array((int) $this->id, Booking::heldAccountIds(), true);
    }

    /** Booking yang sedang menahan akun ini (null bila tidak ada). */
    /** Akun sedang dibooking dan DP-nya sudah TF (bukan sekadar menunggu transfer). */
    public function isBookedPaid(): bool
    {
        return $this->isBooked() && in_array((int) $this->id, Booking::paidHeldAccountIds(), true);
    }

    public function activeBooking(): ?Booking
    {
        return $this->isBooked() ? Booking::holdingFor($this->id) : null;
    }

    /** Transaksi selesai terakhir (berisi data pembeli & garansi). */
    public function latestCompletedSale()
    {
        return $this->hasOne(Sale::class)
            ->ofMany(['id' => 'max'], fn ($q) => $q->where('transaction_status', 'completed'));
    }

    /** Cache per-request: id akun terjual yang masuk etalase. */
    protected static ?array $soldShowcaseIds = null;

    /**
     * Id akun terjual yang tampil di etalase publik: akun terbit yang sudah terjual (punya transaksi
     * selesai), diambil N terbaru menurut waktu terjual (config store.sold_showcase_limit, bawaan 15).
     * Masa garansi tidak berpengaruh. Akun draft (disembunyikan manual) tidak dihitung, jadi
     * menyembunyikan satu akun membuat akun terjual berikutnya yang lebih lama bisa tampil lagi.
     * Satu query per request.
     */
    public static function soldShowcaseIds(): array
    {
        if (static::$soldShowcaseIds === null) {
            $limit = max(0, (int) config('store.sold_showcase_limit', 15));

            static::$soldShowcaseIds = DB::table('coc_accounts as a')
                ->join('sales as s', function ($join) {
                    $join->on('s.coc_account_id', '=', 'a.id')
                         ->where('s.transaction_status', 'completed');
                })
                ->where('a.publication_status', 'published')
                ->where('a.sale_status', 'sold')
                ->whereNull('a.deleted_at')
                ->groupBy('a.id')
                ->orderByRaw('MAX(COALESCE(s.sold_at, s.created_at)) DESC')
                ->orderByDesc('a.id')
                ->limit($limit)
                ->select('a.id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return static::$soldShowcaseIds;
    }

    /**
     * Akun yang tampil di katalog publik:
     *  - terbit dan masih tersedia, ATAU
     *  - terbit, sudah terjual, dan termasuk N akun terjual terbaru (lihat soldShowcaseIds()).
     * Akun terjual tidak lagi hilang saat garansi habis; admin bisa menyembunyikannya manual
     * (status draft), dan yang melebihi batas disembunyikan otomatis mulai dari yang terlama.
     */
    public function scopeListed($query)
    {
        return $query->where('publication_status', 'published')
            ->where(function ($q) {
                $q->where('sale_status', 'available')
                  ->orWhere(function ($sold) {
                      $sold->where('sale_status', 'sold')
                           ->whereIn('coc_accounts.id', static::soldShowcaseIds());
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
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(CASE WHEN is_consignment = 1 THEN 0 ELSE cost_price END), 0) as capital, COALESCE(SUM(price), 0) as value, MIN(created_at) as oldest')
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