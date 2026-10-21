<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class Sale extends Model
{
    /** Lama garansi (hari) sejak serah terima akun. */
    public const WARRANTY_DAYS = 7;

    /**
     * Zona waktu untuk input & tampilan admin.
     * Database tetap menyimpan UTC (sesuai config/app.php), jadi data lama aman.
     */
    public const DISPLAY_TZ = 'Asia/Jakarta';

    public const PAYMENT_METHODS = ['Transfer Bank', 'QRIS', 'E-Wallet', 'Lainnya'];

    protected $fillable = [
        'transaction_code', 'coc_account_id', 'account_code_snapshot',
        'buyer_name', 'buyer_phone',
        'sale_price', 'transaction_status', 'payment_status', 'payment_method',
        'processed_by', 'sold_at', 'handover_at', 'warranty_expires_at', 'notes', 'review_token',
    ];

    protected $casts = [
        'sale_price'          => 'decimal:2',
        'sold_at'             => 'datetime',
        'handover_at'         => 'datetime',
        'warranty_expires_at' => 'datetime',
    ];

    public function account()
    {
        // withTrashed: transaksi tetap menampilkan nama akun walau akunnya sudah dihapus
        return $this->belongsTo(CocAccount::class, 'coc_account_id')->withTrashed();
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    protected static function booted(): void
    {
        // Setiap transaksi baru otomatis punya token untuk link form ulasan pembeli.
        static::creating(function (Sale $sale) {
            if (empty($sale->review_token)) {
                $sale->review_token = Str::random(40);
            }
        });
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    /** Ulasan yang sudah disetujui super admin (tampil di publik). */
    public function approvedReview()
    {
        return $this->hasOne(Review::class)->where('status', 'approved');
    }

    /** Link form ulasan yang dikirim admin ke pembeli. Null bila belum ada token. */
    public function reviewUrl(): ?string
    {
        return $this->review_token ? route('review.form', $this->review_token) : null;
    }

    /**
     * Buat kode transaksi unik.
     */
    public static function generateCode(): string
    {
        do {
            $code = 'TRX-' . strtoupper(Str::random(8));
        } while (static::where('transaction_code', $code)->exists());

        return $code;
    }

    // ------------------------------------------------------------------
    // Nomor WhatsApp
    // ------------------------------------------------------------------

    /**
     * Rapikan nomor ke format internasional tanpa tanda plus:
     * 0812-xxx / +62 812 xxx / 812xxx  ->  62812xxx
     */
    public static function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    /** 62812xxx -> 0812xxx (untuk ditampilkan / diisi ulang ke form). */
    public function localPhone(): ?string
    {
        return $this->buyer_phone ? preg_replace('/^62/', '0', $this->buyer_phone) : null;
    }

    public function whatsappUrl(?string $text = null): ?string
    {
        if (! $this->buyer_phone) {
            return null;
        }

        return 'https://wa.me/' . $this->buyer_phone . ($text ? '?text=' . rawurlencode($text) : '');
    }

    // ------------------------------------------------------------------
    // Garansi
    // ------------------------------------------------------------------

    public function hasBuyerData(): bool
    {
        return filled($this->buyer_name);
    }

    public function warrantyIsActive(): bool
    {
        return $this->transaction_status === 'completed'
            && $this->warranty_expires_at !== null
            && now()->lt($this->warranty_expires_at);
    }

    /**
     * Teks sisa garansi untuk publik: "sisa 7 hari" / "sisa 5 jam".
     * Null jika garansi tidak aktif. Hari dibulatkan ke atas, jadi tepat setelah
     * serah terima tampil "sisa 7 hari" sesuai janji garansi 7 hari.
     */
    public function warrantyRemainingText(): ?string
    {
        if (! $this->warrantyIsActive()) {
            return null;
        }

        $seconds = $this->warranty_expires_at->getTimestamp() - now()->getTimestamp();

        if ($seconds >= 86400) {
            return 'sisa ' . (int) ceil($seconds / 86400) . ' hari';
        }

        return 'sisa ' . max(1, (int) ceil($seconds / 3600)) . ' jam';
    }

    /**
     * Atribut HTML untuk hitung mundur garansi yang berjalan realtime (public/js/live-time.js).
     * Mengembalikan string kosong kalau garansi tidak aktif, jadi cukup dipasang di elemen:
     *   <span {!! $sale->countdownAttrs('sisa ') !!}>sisa 4 hari</span>
     */
    public function countdownAttrs(string $prefix = 'sisa ', string $expired = 'Garansi berakhir', bool $badge = false): string
    {
        if (! $this->warrantyIsActive()) {
            return '';
        }

        return 'data-countdown="' . e($this->warranty_expires_at->toIso8601String()) . '"'
            . ' data-prefix="' . e($prefix) . '"'
            . ' data-expired="' . e($expired) . '"'
            . ($badge ? ' data-badge' : '');
    }

    /** Waktu transaksi (terjual / dibuat) dalam WIB, mis. "20 Oct 2026, 19:37". */
    public function transactionTimeLabel(): string
    {
        return ($this->sold_at ?? $this->created_at)
            ->clone()->timezone(self::DISPLAY_TZ)->format('d M Y, H:i');
    }

    /** Batas akhir garansi dalam WIB, mis. "23 Oct 2026, 14:30 WIB". */
    public function warrantyUntilLabel(): ?string
    {
        return $this->warranty_expires_at
            ? $this->warranty_expires_at->clone()->timezone(self::DISPLAY_TZ)->format('d M Y, H:i') . ' WIB'
            : null;
    }

    /** Waktu serah terima dalam WIB. */
    public function handoverLabel(): ?string
    {
        return $this->handover_at
            ? $this->handover_at->clone()->timezone(self::DISPLAY_TZ)->format('d M Y, H:i') . ' WIB'
            : null;
    }

    /** Nilai untuk <input type="datetime-local"> dalam WIB. */
    public function handoverInputValue(): string
    {
        $time = $this->handover_at ?? $this->sold_at;

        return $time
            ? $time->clone()->timezone(self::DISPLAY_TZ)->format('Y-m-d\TH:i')
            : '';
    }

    /**
     * Lencana status garansi: [teks, kelas warna Tailwind].
     */
    public function warrantyBadge(): array
    {
        $slate = 'bg-slate-500/15 text-slate-300 border-slate-500/30';

        if ($this->transaction_status !== 'completed') {
            return ['Garansi tidak berlaku', $slate];
        }

        if (! $this->warranty_expires_at) {
            return ['Garansi belum diisi', $slate];
        }

        if (now()->gte($this->warranty_expires_at)) {
            return ['Garansi berakhir', 'bg-red-500/15 text-red-300 border-red-500/30'];
        }

        $seconds = $this->warranty_expires_at->getTimestamp() - now()->getTimestamp();
        $days    = $seconds >= 86400 ? (int) ceil($seconds / 86400) : 0;

        if ($days >= 1) {
            return ["Garansi aktif · sisa {$days} hari", 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'];
        }

        $hours = max(1, (int) ceil($seconds / 3600));

        return ["Garansi aktif · sisa {$hours} jam", 'bg-amber-500/15 text-amber-300 border-amber-500/30'];
    }

    // ------------------------------------------------------------------
    // Validasi form data pembeli (dipakai admin & super admin)
    // ------------------------------------------------------------------

    public static function buyerRules(): array
    {
        return [
            'buyer_name' => ['required', 'string', 'min:2', 'max:100'],
            'buyer_phone' => [
                'required', 'string', 'max:25',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $phone = static::normalizePhone($value);

                    if (! $phone || ! preg_match('/^628[0-9]{8,12}$/', $phone)) {
                        $fail('Nomor WhatsApp pembeli tidak valid. Contoh: 0812-3456-7890.');
                    }
                },
            ],
            'payment_method' => ['nullable', Rule::in(self::PAYMENT_METHODS)],
            'handover_at' => [
                'required', 'date',
                function (string $attribute, mixed $value, \Closure $fail) {
                    try {
                        $time = Carbon::parse($value, self::DISPLAY_TZ);
                    } catch (Throwable) {
                        $fail('Format waktu serah terima tidak valid.');

                        return;
                    }

                    // toleransi 5 menit untuk selisih jam perangkat
                    if ($time->gt(now()->addMinutes(5))) {
                        $fail('Waktu serah terima tidak boleh di masa depan.');
                    }
                },
            ],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public static function buyerMessages(): array
    {
        return [
            'buyer_name.required'  => 'Nama pembeli wajib diisi.',
            'buyer_name.min'       => 'Nama pembeli minimal 2 karakter.',
            'buyer_name.max'       => 'Nama pembeli maksimal 100 karakter.',
            'buyer_phone.required' => 'Nomor WhatsApp pembeli wajib diisi.',
            'payment_method.in'    => 'Metode pembayaran tidak dikenal.',
            'handover_at.required' => 'Waktu serah terima wajib diisi (awal masa garansi).',
            'handover_at.date'     => 'Format waktu serah terima tidak valid.',
            'price.required'       => 'Harga jual wajib diisi.',
            'price.numeric'        => 'Harga jual harus berupa angka.',
            'price.max'            => 'Harga terlalu besar (maksimal Rp 999.999.999).',
            'notes.max'            => 'Catatan maksimal 1000 karakter.',
        ];
    }

    /**
     * Ubah input form yang sudah tervalidasi menjadi kolom tabel sales.
     * handover_at dibaca sebagai WIB lalu disimpan sebagai UTC.
     */
    public static function buyerPayload(array $validated): array
    {
        $handover = Carbon::parse($validated['handover_at'], self::DISPLAY_TZ)
            ->setTimezone(config('app.timezone'));

        return [
            'buyer_name'          => trim($validated['buyer_name']),
            'buyer_phone'         => static::normalizePhone($validated['buyer_phone']),
            'payment_method'      => $validated['payment_method'] ?? null,
            'sale_price'          => $validated['price'],
            'handover_at'         => $handover,
            'warranty_expires_at' => $handover->clone()->addDays(self::WARRANTY_DAYS),
            'notes'               => $validated['notes'] ?? null,
        ];
    }

    // ------------------------------------------------------------------
    // Proses penjualan
    // ------------------------------------------------------------------

    /**
     * Tandai akun terjual + catat transaksi lengkap dengan data pembeli & garansi.
     * Atomik dan dikunci supaya akun yang sama tidak bisa terjual dua kali.
     *
     * Transaksi dibuat SEBELUM status akun diubah, sehingga observer di CocAccount
     * (recordForAccount) melihat transaksi selesai sudah ada dan tidak membuat duplikat.
     *
     * @return self|null null jika akun sudah berstatus terjual
     */
    public static function completeSale(int $accountId, array $validated): ?self
    {
        return DB::transaction(function () use ($accountId, $validated) {
            $account = CocAccount::lockForUpdate()->findOrFail($accountId);

            if ($account->sale_status === 'sold') {
                return null;
            }

            $sale = static::create(array_merge(static::buyerPayload($validated), [
                'transaction_code'      => static::generateCode(),
                'coc_account_id'        => $account->id,
                'account_code_snapshot' => $account->account_code,
                'transaction_status'    => 'completed',
                'payment_status'        => 'paid',
                'processed_by'          => Auth::id(),
                'sold_at'               => now(),
            ]));

            // Status terbit tidak diubah: akun tetap tampil (berlabel Terjual)
            // sampai garansinya berakhir, lalu disembunyikan oleh CocAccount::scopeListed().
            $account->update(['sale_status' => 'sold']);

            return $sale;
        });
    }

    /**
     * Ubah / lengkapi data pembeli pada transaksi yang sudah ada.
     */
    public static function updateBuyer(self $sale, array $validated): self
    {
        $sale->update(static::buyerPayload($validated));

        return $sale;
    }

    /**
     * Catat transaksi selesai untuk sebuah akun yang berstatus terjual.
     * Dipanggil otomatis dari CocAccount saat sale_status berubah menjadi 'sold',
     * sehingga data transaksi selalu ikut tercatat dari jalur mana pun
     * (form edit, tombol status, atau konfirmasi penjualan).
     * Tidak membuat duplikat jika akun sudah punya transaksi selesai.
     *
     * Catatan: jalur ini tidak memiliki data pembeli. Lengkapi lewat form "Data Pembeli".
     */
    public static function recordForAccount(CocAccount $account, ?float $price = null, ?string $notes = null): ?self
    {
        if ($account->sales()->where('transaction_status', 'completed')->exists()) {
            return null;
        }

        $processedBy = Auth::id()
            ?? $account->created_by
            ?? User::where('role', 'super_admin')->value('id');

        return static::create([
            'transaction_code'      => static::generateCode(),
            'coc_account_id'        => $account->id,
            'account_code_snapshot' => $account->account_code,
            'sale_price'            => $price ?? $account->price,
            'transaction_status'    => 'completed',
            'payment_status'        => 'paid',
            'processed_by'          => $processedBy,
            'sold_at'               => now(),
            'notes'                 => $notes,
        ]);
    }
}