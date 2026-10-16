<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Sale extends Model
{
    protected $fillable = [
        'transaction_code', 'coc_account_id', 'account_code_snapshot',
        'sale_price', 'transaction_status', 'payment_status',
        'processed_by', 'sold_at', 'notes'
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'sold_at' => 'datetime',
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

    /**
     * Catat transaksi selesai untuk sebuah akun yang berstatus terjual.
     * Dipanggil otomatis dari CocAccount saat sale_status berubah menjadi 'sold',
     * sehingga data transaksi selalu ikut tercatat dari jalur mana pun
     * (form edit, tombol status, atau konfirmasi penjualan).
     * Tidak membuat duplikat jika akun sudah punya transaksi selesai.
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